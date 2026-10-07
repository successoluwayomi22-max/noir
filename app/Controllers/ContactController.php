<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\Core\Container;
use Noir\Core\Mailer;
use Noir\Core\Request;

/**
 * Handles contact form submissions, newsletter subscriptions,
 * and storefront analytics events.
 * Extracted from StoreController to enforce single-responsibility.
 */
final class ContactController
{
    public function contactSubmit(Request $request): never
    {
        $name      = trim((string) $request->input('name'));
        $email     = trim((string) $request->input('email'));
        $subject   = trim((string) $request->input('subject'));
        $order     = trim((string) $request->input('order_number'));
        $message   = trim((string) $request->input('message'));
        $publicPath = $this->publicPath();

        $recaptchaToken = (string) $request->input('g-recaptcha-response', '');
        if (!recaptcha_verify($recaptchaToken)) {
            header('Location: ' . $publicPath . '/contact?error=recaptcha#contact-form', true, 303);
            exit;
        }

        if (
            $name === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || $message === ''
            || mb_strlen($message) > 5000
        ) {
            header('Location: ' . $publicPath . '/contact?error=invalid#contact-form', true, 303);
            exit;
        }

        // Resolve the recipient address, falling back through multiple config sources.
        $to = (string) config('mail.contact_to', '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $to = (string) store_setting('store_email', '');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $to = (string) config('mail.from_address', 'no-reply@noir.test');
        }

        $subjectLine = 'Contact form' . ($subject !== '' ? ' - ' . $subject : '');
        $body = '<p><strong>From:</strong> ' . e($name) . ' &lt;' . e($email) . '&gt;</p>'
            . ($order !== '' ? '<p><strong>Order number:</strong> ' . e($order) . '</p>' : '')
            . '<p>' . nl2br(e($message)) . '</p>';

        $db = Container::get('db');

        try {
            $db->prepare('INSERT INTO contact_messages (name,email,subject,order_number,message) VALUES (?,?,?,?,?)')
                ->execute([
                    $name,
                    $email,
                    $subject !== '' ? $subject : null,
                    $order   !== '' ? $order : null,
                    $message,
                ]);
        } catch (\Throwable $e) {
            error_log('Contact message storage failed: ' . $e->getMessage());
        }

        $sent = (new Mailer())->sendContactMessage($to, $name, $email, $subjectLine, $body);

        header(
            'Location: ' . $publicPath . '/contact?' . ($sent ? 'sent=1' : 'error=send') . '#contact-form',
            true,
            303,
        );
        exit;
    }

    public function newsletterSubscribe(Request $request): never
    {
        $email      = strtolower(trim((string) $request->input('email')));
        $publicPath = $this->publicPath();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . $publicPath . '/?newsletter=invalid#journal', true, 303);
            exit;
        }

        try {
            Container::get('db')
                ->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE status='subscribed',updated_at=NOW()")
                ->execute([$email]);

            header('Location: ' . $publicPath . '/?newsletter=subscribed#journal', true, 303);
        } catch (\Throwable $e) {
            error_log('Newsletter subscription failed: ' . $e->getMessage());
            header('Location: ' . $publicPath . '/?newsletter=error#journal', true, 303);
        }

        exit;
    }

    public function storefrontAnalytics(Request $request): never
    {
        $event   = trim((string) $request->input('event'));
        $allowed = [
            'search_submitted', 'product_viewed', 'product_clicked',
            'cart_intent', 'checkout_started', 'checkout_success',
            'checkout_failure', 'payment_retry_clicked', 'performance_metric',
        ];

        if (!in_array($event, $allowed, true)) {
            http_response_code(204);
            exit;
        }

        $payload = json_decode((string) $request->input('payload', '{}'), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $entry = [
            'at'      => date(DATE_ATOM),
            'event'   => $event,
            'path'    => mb_substr((string) $request->input('path', ''), 0, 180),
            'session' => substr(session_id(), 0, 12),
            'payload' => array_map(static fn ($value): string => mb_substr((string) $value, 0, 180), $payload),
        ];

        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir . '/storefront-analytics.log', json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);

        try {
            $db = Container::get('db');
            if ($this->tableExists($db, 'analytics_events')) {
                $user      = Container::get('auth')->user() ?? [];
                $source    = mb_substr((string) ($payload['utm_source']   ?? $request->input('utm_source', '')), 0, 120);
                $campaign  = mb_substr((string) ($payload['utm_campaign'] ?? $request->input('utm_campaign', '')), 0, 120);
                $db->prepare(
                    'INSERT INTO analytics_events(event_name,page_path,session_key,user_id,attribution_source,attribution_campaign,payload) VALUES(?,?,?,?,?,?,?)',
                )->execute([
                    $event,
                    $entry['path'],
                    $entry['session'],
                    (int) ($user['user_id'] ?? $user['id'] ?? 0) ?: null,
                    $source ?: null,
                    $campaign ?: null,
                    json_encode($payload, JSON_UNESCAPED_SLASHES),
                ]);
            }
        } catch (\Throwable) {
        }

        http_response_code(204);
        exit;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function publicPath(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }

    private function tableExists(\PDO $db, string $table): bool
    {
        $q = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
        $q->execute([$table]);
        return (bool) $q->fetchColumn();
    }
}
