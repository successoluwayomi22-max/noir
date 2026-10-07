<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use PDO;

final class VoiceConciergeApiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Container::get('db');
    }

    /**
     * Parses voice commands and returns spoken speech responses + navigation actions
     */
    public function handleVoiceCommand(): never
    {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? $_POST;
        $command = trim((string) ($body['command'] ?? $body['transcript'] ?? ''));

        if (empty($command)) {
            json([
                'success' => false,
                'message' => 'Please speak or provide a voice command.',
            ], 400);
        }

        $lower = strtolower($command);
        $action = 'search';
        $spokenReply = '';
        $navigationRoute = '/store';
        $dataPayload = [];

        // 1. Order Tracking intent
        if (str_contains($lower, 'track') || str_contains($lower, 'where is my order') || str_contains($lower, 'delivery status')) {
            $action = 'navigate_tracking';
            preg_match('/(?:order|dispatch|#)\s*([A-Za-z0-9-]+)/i', $command, $m);
            $orderRef = $m[1] ?? '';
            $spokenReply = "Checking live white-glove GPS escort for your luxury dispatch.";
            $navigationRoute = '/track-order' . ($orderRef ? "?ref=$orderRef" : '');
            $dataPayload = ['order_number' => $orderRef];
        }
        // 2. Size & Fit Advice
        elseif (str_contains($lower, 'size') || str_contains($lower, 'fit') || str_contains($lower, 'measurement')) {
            $action = 'open_size_predictor';
            $spokenReply = "Opening the Smart Size Predictor to calculate your bespoke atelier fit.";
            $navigationRoute = '/size-guide';
        }
        // 3. Gift Cards
        elseif (str_contains($lower, 'gift card') || str_contains($lower, 'voucher')) {
            $action = 'navigate_gift_cards';
            $spokenReply = "Directing you to the Digital VIP Luxury Gift Card showroom.";
            $navigationRoute = '/gift-cards';
        }
        // 4. Live Runway Stream
        elseif (str_contains($lower, 'runway') || str_contains($lower, 'fashion show') || str_contains($lower, 'live stream')) {
            $action = 'navigate_runway';
            $spokenReply = "Tuning in to the Milan Fashion Week runway live stream.";
            $navigationRoute = '/runway';
        }
        // 5. Stylist Advice
        elseif (str_contains($lower, 'recommend') || str_contains($lower, 'stylist') || str_contains($lower, 'outfit') || str_contains($lower, 'wear')) {
            $action = 'open_ai_stylist';
            $spokenReply = "Allow me to curate an immaculate bespoke ensemble for your occasion.";
            $navigationRoute = '/ai/stylist';
        }
        // 6. Product / Catalogue Search (Default)
        else {
            $action = 'search_catalog';
            $cleanedQuery = preg_replace('/^(show me|find|search for|look for)\s+/i', '', $command);
            $spokenReply = "Searching the NOIR collection for \"$cleanedQuery\".";
            $navigationRoute = '/search?q=' . urlencode($cleanedQuery);

            // Fetch top 3 matches
            $stmt = $this->db->prepare("
                SELECT id, name, slug, price, sale_price 
                FROM products 
                WHERE name LIKE ? OR description LIKE ? OR brand LIKE ? 
                LIMIT 4
            ");
            $term = "%$cleanedQuery%";
            $stmt->execute([$term, $term, $term]);
            $dataPayload['matches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        json([
            'success' => true,
            'command' => $command,
            'intent_action' => $action,
            'spoken_reply' => $spokenReply,
            'navigation_route' => $navigationRoute,
            'payload' => $dataPayload,
        ]);
    }
}
