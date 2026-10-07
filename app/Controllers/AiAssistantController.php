<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\App\Services\AiAssistantService;
use Noir\App\Services\FashionDecisionEngine;
use Noir\Core\Container;
use Noir\Core\Request;

final class AiAssistantController
{
    public function chat(Request $request): never
    {
        $payload = $request->input();
        $message = $this->message($payload['message'] ?? null);
        if ($message === '') {
            json(['error' => 'Please enter a message for your personal stylist.'], 422);
        }
        if (mb_strlen($message) > 700) {
            json(['error' => 'Please keep your message to 700 characters or fewer.'], 422);
        }

        $this->enforceRateLimit();

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $sessionId = (string)($payload['session_id'] ?? (session_id() ?: ('web_' . time())));

        try {
            $service = new AiAssistantService(Container::get('db'));
            $decision = $service->stylistDecision($message, $userId, $sessionId, $this->history($payload['history'] ?? []));
            
            json([
                'success' => true,
                'reply' => $decision['message'],
                'message' => $decision['message'],
                'recommendation_type' => $decision['recommendation_type'] ?? 'outfit',
                'confidence' => $decision['confidence'] ?? 0.90,
                'confidence_level' => $decision['confidence_level'] ?? 'high',
                'occasion' => $decision['occasion'] ?? 'General',
                'style_profile' => $decision['style_profile'] ?? 'Minimalist',
                'total_price' => $decision['total_price'] ?? 0,
                'budget_status' => $decision['budget_status'] ?? 'within_budget',
                'products' => $decision['products'] ?? [],
                'alternatives' => $decision['alternatives'] ?? [],
                'winner_product_id' => $decision['winner_product_id'] ?? null,
                'actions' => $decision['actions'] ?? [],
                'follow_up_suggestions' => $decision['follow_up_suggestions'] ?? [],
            ]);
        } catch (\RuntimeException $exception) {
            $status = $exception->getCode() === 429 ? 429 : 503;
            error_log('Noir AI assistant error [' . $status . ']: ' . $exception->getMessage());
            json(['error' => $status === 429 ? 'The stylist is busy. Please wait a moment and try again.' : 'The stylist is unavailable right now. Please try again shortly.'], $status);
        } catch (\Throwable $exception) {
            error_log('Noir AI assistant error: ' . $exception->getMessage());
            json(['error' => 'The stylist is unavailable right now. Please try again shortly.'], 503);
        }
    }

    public function feedback(Request $request): never
    {
        $payload = $request->input();
        $action = trim((string)($payload['action'] ?? ''));
        $productId = isset($payload['product_id']) ? (int)$payload['product_id'] : null;
        $sessionId = (string)($payload['session_id'] ?? (session_id() ?: ('web_' . time())));
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        if ($action === '') {
            json(['error' => 'Action is required.'], 422);
        }

        try {
            $engine = new FashionDecisionEngine(Container::get('db'));
            $res = $engine->recordFeedback($userId, $sessionId, $productId, $action, (array)($payload['metadata'] ?? []));
            json(['success' => $res, 'message' => 'Thank you for your feedback!']);
        } catch (\Throwable $e) {
            json(['error' => 'Failed to record feedback.'], 500);
        }
    }

    private function enforceRateLimit(): void
    {
        $now = time();
        $window = (int)config('ai.rate_limit_window', 600);
        $limit = (int)config('ai.max_requests_per_window', 30);
        $bucket = $_SESSION['ai_assistant_rate_limit'] ?? [];
        $startedAt = (int)($bucket['started_at'] ?? 0);

        if ($startedAt <= 0 || $startedAt + $window <= $now) {
            $bucket = ['started_at' => $now, 'count' => 0];
        }

        if ((int)($bucket['count'] ?? 0) >= $limit) {
            json(['error' => 'The assistant is busy. Please wait a moment and try again.'], 429);
        }

        $bucket['count'] = (int)($bucket['count'] ?? 0) + 1;
        $_SESSION['ai_assistant_rate_limit'] = $bucket;
    }

    private function history(mixed $history): array
    {
        if (!is_array($history)) {
            return [];
        }

        $clean = [];
        foreach (array_slice($history, -6) as $turn) {
            if (!is_array($turn)) {
                continue;
            }
            $role = (string)($turn['role'] ?? '');
            $content = $this->message($turn['content'] ?? null);
            if (!in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }
            $clean[] = ['role' => $role, 'content' => mb_substr($content, 0, 700)];
        }

        return $clean;
    }

    private function message(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }

        return preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;
    }
}
