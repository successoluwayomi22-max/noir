<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use Noir\Core\Request;

final class ReviewApiController
{
    public function index(Request $request, array $vars = []): never
    {
        $productId = (int)($vars['id'] ?? $request->input('product_id', 0));
        $db = Container::get('db');
        $stmt = $db->prepare('SELECT r.id, r.rating, r.title, r.body, r.created_at, u.name as user_name FROM customer_reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.status = \'approved\' ORDER BY r.created_at DESC');
        $stmt->execute([$productId]);
        
        $reviews = $stmt->fetchAll();
        
        json(['success' => true, 'reviews' => $reviews]);
    }

    public function store(Request $request, array $vars = []): never
    {
        $user = \Noir\Core\Auth::user() ?? [];
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
        
        if ($userId === 0) {
            json(['error' => 'Unauthorized'], 401);
        }
        
        $productId = (int)($vars['id'] ?? $request->input('product_id', 0));
        $db = Container::get('db');
        $rating = (int)$request->input('rating');
        $title = trim((string)$request->input('title', ''));
        $body = trim((string)$request->input('body', ''));
        
        if ($rating < 1 || $rating > 5) {
            json(['success' => false, 'error' => 'Rating must be between 1 and 5'], 400);
        }

        $stmt = $db->prepare('INSERT INTO customer_reviews (product_id, user_id, rating, title, body, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, \'approved\', NOW(), NOW())');
        $stmt->execute([$productId, $userId, $rating, $title, $body]);

        json(['success' => true, 'message' => 'Review submitted successfully']);
    }
}
