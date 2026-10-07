<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use PDO;

final class CurrencyApiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Container::get('db');
    }

    /**
     * List all active currencies and their exchange rates
     */
    public function getCurrencies(): never
    {
        $stmt = $this->db->query("
            SELECT code, name, symbol, rate_to_ngn, symbol_position, decimal_places
            FROM currency_rates
            WHERE is_active = 1
            ORDER BY code = 'NGN' DESC, code = 'USD' DESC, code ASC
        ");
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json([
            'success' => true,
            'base_currency' => 'NGN',
            'currencies' => $rates,
        ]);
    }

    /**
     * Converts an amount from one currency to target currency
     */
    public function convert(): never
    {
        $amount = (float) ($_GET['amount'] ?? $_POST['amount'] ?? 0);
        $from = strtoupper(trim((string) ($_GET['from'] ?? $_POST['from'] ?? 'NGN')));
        $to = strtoupper(trim((string) ($_GET['to'] ?? $_POST['to'] ?? 'USD')));

        $stmt = $this->db->prepare("SELECT code, symbol, rate_to_ngn, symbol_position, decimal_places FROM currency_rates WHERE code IN (?, ?) AND is_active = 1");
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rates = [];
        foreach ($rows as $r) {
            $rates[$r['code']] = $r;
        }

        if (!isset($rates[$from]) || !isset($rates[$to])) {
            json(['success' => false, 'message' => 'Unsupported currency code.'], 400);
        }

        // Amount in NGN
        $amountInNgn = $amount * (float) $rates[$from]['rate_to_ngn'];
        // Convert to target
        $targetRate = (float) $rates[$to]['rate_to_ngn'];
        $converted = ($targetRate > 0) ? ($amountInNgn / $targetRate) : $amountInNgn;

        $dec = (int) $rates[$to]['decimal_places'];
        $sym = $rates[$to]['symbol'];
        $pos = $rates[$to]['symbol_position'];

        $formatted = ($pos === 'before') 
            ? $sym . number_format($converted, $dec) 
            : number_format($converted, $dec) . ' ' . $sym;

        json([
            'success' => true,
            'original_amount' => $amount,
            'from_currency' => $from,
            'to_currency' => $to,
            'converted_amount' => round($converted, $dec),
            'formatted' => $formatted,
            'exchange_rate' => round($rates[$from]['rate_to_ngn'] / $targetRate, 6),
        ]);
    }
}
