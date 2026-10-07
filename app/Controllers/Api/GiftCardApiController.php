<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use PDO;

final class GiftCardApiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Container::get('db');
    }

    /**
     * Check gift card balance and status
     */
    public function checkBalance(): never
    {
        $code = trim((string) ($_GET['code'] ?? $_POST['code'] ?? ''));
        if (empty($code)) {
            json(['success' => false, 'message' => 'Please provide a luxury gift card code.'], 400);
        }

        $stmt = $this->db->prepare("SELECT id, code, initial_amount, balance, currency, theme, sender_name, recipient_name, recipient_email, message, status, expires_at FROM gift_cards WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            json(['success' => false, 'message' => 'Invalid gift card code. Please verify the embossed characters.'], 404);
        }

        if ($card['status'] !== 'active') {
            json(['success' => false, 'message' => 'This gift card is currently ' . $card['status'] . '.'], 400);
        }

        $bal = (float) $card['balance'];
        if ($bal <= 0.0) {
            json(['success' => false, 'message' => 'This gift card has already been fully redeemed (Balance: ₦0.00).'], 400);
        }

        json([
            'success' => true,
            'gift_card' => [
                'code' => $card['code'],
                'balance' => $bal,
                'initial_amount' => (float) $card['initial_amount'],
                'currency' => $card['currency'] ?? 'NGN',
                'theme' => $card['theme'] ?? 'gold_foil',
                'sender_name' => $card['sender_name'] ?? 'NOIR Patron',
                'recipient_name' => $card['recipient_name'] ?? '',
                'message' => $card['message'] ?? '',
                'expires_at' => $card['expires_at'],
                'status' => $card['status'],
            ],
        ]);
    }

    /**
     * Purchase a new VIP digital luxury gift card
     */
    public function purchase(): never
    {
        $auth = Container::get('auth');
        $user = $auth->user();
        $userId = $user ? (int) ($user['id'] ?? $user['user_id'] ?? 0) : null;

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? $_POST;

        $amount = (float) ($body['amount'] ?? 0);
        if ($amount < 5000) {
            json(['success' => false, 'message' => 'Minimum luxury voucher denomination is ₦5,000.'], 422);
        }

        $theme = in_array($body['theme'] ?? '', ['gold_foil', 'velvet_obsidian', 'emerald_silk', 'rose_gold'], true) ? $body['theme'] : 'gold_foil';
        $senderName = trim((string) ($body['sender_name'] ?? ($user['name'] ?? 'NOIR Patron')));
        $recipientName = trim((string) ($body['recipient_name'] ?? 'Distinguished Guest'));
        $recipientEmail = trim((string) ($body['recipient_email'] ?? ''));
        $message = trim((string) ($body['message'] ?? 'A curated gift of timeless distinction from NOIR & Co.'));

        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            json(['success' => false, 'message' => 'A valid recipient email address is required.'], 422);
        }

        // Generate distinctive code: NOIR-VIP-XXXX-XXXX
        $code = 'NOIR-VIP-' . rand(1000, 9999) . '-' . rand(1000, 9999);

        $stmt = $this->db->prepare("
            INSERT INTO gift_cards (code, initial_amount, balance, currency, theme, purchaser_id, sender_name, recipient_name, recipient_email, message, status, expires_at, created_at, updated_at)
            VALUES (?, ?, ?, 'NGN', ?, ?, ?, ?, ?, ?, 'active', DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW(), NOW())
        ");
        $stmt->execute([$code, $amount, $amount, $theme, $userId, $senderName, $recipientName, $recipientEmail, $message]);
        $cardId = (int) $this->db->lastInsertId();

        json([
            'success' => true,
            'message' => 'Digital Luxury Gift Card created and dispatched to recipient inbox.',
            'gift_card' => [
                'id' => $cardId,
                'code' => $code,
                'balance' => $amount,
                'initial_amount' => $amount,
                'currency' => 'NGN',
                'theme' => $theme,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'sender_name' => $senderName,
                'message' => $message,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            ],
        ]);
    }

    /**
     * Apply gift card to reduce order / checkout total
     */
    public function applyToOrder(): never
    {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? $_POST;

        $code = trim((string) ($body['code'] ?? ''));
        $orderTotal = (float) ($body['order_total'] ?? 0);

        if (empty($code)) {
            json(['success' => false, 'message' => 'Gift card code is required.'], 400);
        }

        $stmt = $this->db->prepare("SELECT * FROM gift_cards WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card || $card['status'] !== 'active' || (float) $card['balance'] <= 0) {
            json(['success' => false, 'message' => 'Invalid or fully redeemed gift card.'], 400);
        }

        $balance = (float) $card['balance'];
        $discountApplied = min($orderTotal, $balance);
        $remainingPayable = max(0.0, $orderTotal - $discountApplied);
        $remainingCardBalance = $balance - $discountApplied;

        json([
            'success' => true,
            'message' => 'VIP Gift Card applied! ₦' . number_format($discountApplied, 2) . ' deducted from total.',
            'code' => $code,
            'card_balance' => $balance,
            'discount_applied' => $discountApplied,
            'remaining_order_total' => $remainingPayable,
            'remaining_card_balance' => $remainingCardBalance,
        ]);
    }
}
