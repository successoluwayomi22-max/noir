<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use PDO;

final class RunwayStreamApiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Container::get('db');
    }

    /**
     * Get list of live, upcoming and replay runway stream sessions
     */
    public function getStreams(): never
    {
        $stmt = $this->db->query("
            SELECT id, title, slug, host_name, description, stream_url, thumbnail_url, status, scheduled_at, live_viewers, total_views, created_at
            FROM runway_streams
            ORDER BY status = 'live' DESC, scheduled_at ASC
        ");
        $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json([
            'success' => true,
            'streams' => $streams,
        ]);
    }

    /**
     * Get runway stream details with pinned luxury products and live reactions
     */
    public function getStreamDetails(array $params = []): never
    {
        $idOrSlug = $params['id'] ?? ($_GET['id'] ?? '1');

        $stmt = $this->db->prepare("
            SELECT * FROM runway_streams 
            WHERE id = ? OR slug = ? 
            LIMIT 1
        ");
        $stmt->execute([$idOrSlug, $idOrSlug]);
        $stream = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stream) {
            json(['success' => false, 'message' => 'Runway stream session not found.'], 404);
        }

        $streamId = (int) $stream['id'];

        // Pinned Products with live discounts
        $pStmt = $this->db->prepare("
            SELECT rsp.timestamp_seconds, rsp.discount_percentage, rsp.is_featured,
                   p.id AS product_id, p.name, p.slug, p.price, p.sale_price,
                   (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC LIMIT 1) AS image
            FROM runway_stream_products rsp
            JOIN products p ON p.id = rsp.product_id
            WHERE rsp.stream_id = ?
            ORDER BY rsp.timestamp_seconds ASC
        ");
        $pStmt->execute([$streamId]);
        $products = $pStmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent live reactions
        $rStmt = $this->db->prepare("
            SELECT id, user_name, message, reaction_type, created_at
            FROM runway_stream_reactions
            WHERE stream_id = ?
            ORDER BY created_at DESC
            LIMIT 25
        ");
        $rStmt->execute([$streamId]);
        $reactions = array_reverse($rStmt->fetchAll(PDO::FETCH_ASSOC));

        // Increment viewer count if live
        if ($stream['status'] === 'live') {
            $this->db->prepare("UPDATE runway_streams SET live_viewers = live_viewers + 1, total_views = total_views + 1 WHERE id = ?")->execute([$streamId]);
            $stream['live_viewers'] = (int) $stream['live_viewers'] + 1;
        }

        json([
            'success' => true,
            'stream' => $stream,
            'pinned_products' => $products,
            'live_reactions' => $reactions,
        ]);
    }

    /**
     * Post a live in-stream comment / reaction
     */
    public function postReaction(array $params = []): never
    {
        $id = (int) ($params['id'] ?? ($_POST['stream_id'] ?? 1));
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? $_POST;

        $auth = Container::get('auth');
        $user = $auth->user();
        $userId = $user ? (int) ($user['id'] ?? $user['user_id'] ?? 0) : null;
        $userName = $user['name'] ?? trim((string) ($body['user_name'] ?? 'VIP Viewer'));
        $message = trim((string) ($body['message'] ?? '🔥'));
        $reactionType = in_array($body['reaction_type'] ?? '', ['comment', 'heart', 'fire', 'gem', 'clap'], true) ? $body['reaction_type'] : 'comment';

        if (empty($message)) {
            $message = '✨';
        }

        $stmt = $this->db->prepare("
            INSERT INTO runway_stream_reactions (stream_id, user_id, user_name, message, reaction_type, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$id, $userId, $userName, $message, $reactionType]);
        $reactionId = (int) $this->db->lastInsertId();

        json([
            'success' => true,
            'reaction' => [
                'id' => $reactionId,
                'user_name' => $userName,
                'message' => $message,
                'reaction_type' => $reactionType,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
