<?php
declare(strict_types=1);
$publicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
$orderNumber = trim((string)($_GET['order'] ?? $_GET['success'] ?? ''));

// Fetch basic order details if we have an order number
$order = null;
$isAuthorized = false;
if ($orderNumber !== '') {
    try {
        $db = \Noir\Core\Container::get('db');
        $stmt = $db->prepare("SELECT order_number, status, total, currency, email, name, user_id FROM orders WHERE order_number=? LIMIT 1");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch() ?: null;
        if ($order) {
            $user = \Noir\Core\Container::get('auth')->user();
            $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
            $isAuthorized = ($userId > 0 && isset($order['user_id']) && (int)$order['user_id'] === $userId)
                || (!empty($_SESSION['recent_order_number']) && $_SESSION['recent_order_number'] === $orderNumber);
        }
    } catch (\Throwable $e) {
        // Silently fail — page still renders fine without it
    }
}

$displayEmail = 'your email';
if ($order && !empty($order['email'])) {
    if ($isAuthorized) {
        $displayEmail = (string)$order['email'];
    } else {
        $parts = explode('@', (string)$order['email']);
        $name = $parts[0] ?? '';
        $domain = $parts[1] ?? '';
        $maskedName = strlen($name) > 2 ? substr($name, 0, 1) . '***' . substr($name, -1) : substr($name, 0, 1) . '***';
        $displayEmail = $domain ? $maskedName . '@' . $domain : 'your email';
    }
}

$currencySymbols = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', 'CAD' => 'CA$', 'AUD' => 'AU$', 'GHS' => 'GH₵', 'KES' => 'KSh ', 'ZAR' => 'R '];
$symbol = $currencySymbols[$order['currency'] ?? 'NGN'] ?? (($order['currency'] ?? '') ? $order['currency'] . ' ' : '₦');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order Confirmed — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta('Order Confirmed — ' . $storeName, 'Your ' . $storeName . ' order is confirmed. Payment has been verified and your items are moving into preparation.') ?>
    <?= store_brand_style() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>
<main class="thank-page">
    <?= store_breadcrumbs([['name' => 'Home', 'url' => $publicPath . '/'], ['name' => 'Order confirmed', 'url' => '']]) ?>

    <div class="thank-layout">
        <div class="thank-main">
            <div class="thank-check">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <p class="eyebrow">Order Confirmed</p>
            <h1>Thank you<?= ($order && $isAuthorized) ? ', ' . e(explode(' ', (string)$order['name'])[0]) : '' ?>.</h1>
            <p class="thank-desc">
                <?php if ($orderNumber !== ''): ?>
                    Order <strong><?= e($orderNumber) ?></strong> is confirmed and moving into preparation.
                    A confirmation has been sent to <strong><?= e($displayEmail) ?></strong>.
                <?php else: ?>
                    Your request has been received. We will handle it with care.
                <?php endif; ?>
            </p>

            <?php if ($order): ?>
                <div class="thank-summary-card">
                    <div class="thank-summary-row">
                        <span>Order number</span>
                        <strong><?= e($order['order_number']) ?></strong>
                    </div>
                    <div class="thank-summary-row">
                        <span>Status</span>
                        <span class="thank-badge"><?= ucfirst(e($order['status'])) ?></span>
                    </div>
                    <div class="thank-summary-row">
                        <span>Total</span>
                        <strong><?= ($order['currency'] ?? 'NGN') === 'NGN' ? $symbol . number_format((float)$order['total']) : $symbol . number_format((float)$order['total'], 2) ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <div class="thank-next">
                <article>
                    <div class="thank-next-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </div>
                    <strong>Payment</strong>
                    <span>Verified before fulfilment begins.</span>
                </article>
                <article>
                    <div class="thank-next-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <strong>Packaging</strong>
                    <span>Carefully packed within 1–2 business days.</span>
                </article>
                <article>
                    <div class="thank-next-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <strong>Delivery</strong>
                    <span>Estimated 2–5 business days after dispatch.</span>
                </article>
            </div>

            <div class="thank-actions">
                <a class="button" href="<?= e($publicPath . '/store') ?>">Continue shopping</a>
                <?php if ($orderNumber !== ''): ?>
                    <a class="button button-secondary" href="<?= e($publicPath . '/track-order') ?>">Track order</a>
                <?php endif; ?>
            </div>
        </div>

        <aside class="thank-aside">
            <h3>Need help?</h3>
            <ul>
                <li><a href="<?= e($publicPath . '/track-order') ?>">Track your order</a></li>
                <li><a href="<?= e($publicPath . '/returns') ?>">Returns & exchanges</a></li>
                <li><a href="<?= e($publicPath . '/contact') ?>">Contact support</a></li>
                <li><a href="<?= e($publicPath . '/faq') ?>">FAQs</a></li>
            </ul>
            <div class="thank-support-note">
                <strong>Support promise</strong>
                <p>We respond within 1 business day. Your reference is <strong><?= $orderNumber !== '' ? e($orderNumber) : 'in your confirmation email' ?></strong>.</p>
            </div>
        </aside>
    </div>
</main>
<?php require __DIR__ . '/components/footer.php'; ?>
<style>
.thank-page { padding: clamp(40px,6vw,80px) clamp(16px,5vw,80px); background: var(--brand-paper); color: var(--brand-dark); }
.thank-layout { display: grid; grid-template-columns: 1fr 300px; gap: 60px; align-items: start; margin-top: 24px; }
.thank-check { width: 56px; height: 56px; margin-bottom: 20px; }
.thank-main h1 { margin: 12px 0; font-size: clamp(48px,7vw,100px); font-weight: 500; letter-spacing: -0.05em; line-height: 1; }
.thank-desc { max-width: 560px; color: var(--brand-muted); font-size: 15px; line-height: 1.7; margin-top: 12px; }
.thank-summary-card { margin: 28px 0; border: 1px solid var(--brand-border); border-radius: 16px; overflow: hidden; max-width: 480px; }
.thank-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--brand-border); font-size: 14px; }
.thank-summary-row:last-child { border-bottom: 0; }
.thank-summary-row span:first-child { color: var(--brand-muted); }
.thank-badge { padding: 4px 12px; background: #27ae6018; color: #27ae60; border: 1px solid #27ae6030; border-radius: 20px; font-size: 12px; font-weight: 700; }
.thank-next { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin: 28px 0; max-width: 560px; }
.thank-next article { border: 1px solid var(--brand-border); border-radius: 14px; background: var(--brand-surface); padding: 18px 16px; }
.thank-next-icon { font-size: 22px; margin-bottom: 8px; }
.thank-next strong, .thank-next span { display: block; }
.thank-next strong { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
.thank-next span { color: var(--brand-muted); font-size: 11px; line-height: 1.5; }
.thank-actions { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 8px; }
.thank-aside { background: var(--brand-surface); border: 1px solid var(--brand-border); border-radius: 16px; padding: 28px; position: sticky; top: 100px; }
.thank-aside h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
.thank-aside ul { list-style: none; padding: 0; margin: 0 0 24px; display: grid; gap: 10px; }
.thank-aside ul a { font-size: 14px; color: var(--brand-dark); text-decoration: none; }
.thank-aside ul a::before { content: '→ '; opacity: 0.4; }
.thank-support-note { background: var(--brand-paper); border-radius: 10px; padding: 16px; }
.thank-support-note strong { display: block; font-size: 13px; margin-bottom: 6px; }
.thank-support-note p { font-size: 12px; color: var(--brand-muted); line-height: 1.6; margin: 0; }
@media(max-width:960px){ .thank-layout { grid-template-columns: 1fr; } .thank-aside { position: static; } }
@media(max-width:600px){ .thank-next { grid-template-columns: 1fr; } }
</style>
</body>
</html>
