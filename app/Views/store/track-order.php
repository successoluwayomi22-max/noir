<?php
declare(strict_types=1);
$publicPath = $publicPath ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName  = $storeName ?? store_setting('store_name', 'Noir & Co.');
$result     = $result ?? null;
$error      = $error ?? null;
$orderNumber = $orderNumber ?? '';
$email      = $email ?? '';
$csrf       = '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Track Your Order — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta('Track Your Order — ' . $storeName, 'Enter your order number and email to track your ' . $storeName . ' delivery status.') ?>
    <?= store_brand_style() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>
<main class="track-page">
    <?= store_breadcrumbs([['name' => 'Home', 'url' => $publicPath . '/'], ['name' => 'Track Order', 'url' => '']]) ?>
    <div class="track-hero">
        <p class="eyebrow">Order Tracking</p>
        <h1>Where's your order?</h1>
        <p class="track-sub">Enter the order number from your confirmation email along with the email address you used at checkout.</p>
    </div>

    <div class="track-layout">
        <div class="track-form-wrap">
            <form class="track-form" method="post" action="<?= e($publicPath . '/track-order') ?>">
                <?= $csrf ?>
                <label>Order number
                    <input type="text" name="order_number" required placeholder="e.g. ORD-20240001"
                           value="<?= e($orderNumber) ?>" autocomplete="off">
                </label>
                <label>Email address
                    <input type="email" name="email" required placeholder="you@example.com"
                           value="<?= e($email) ?>" autocomplete="email">
                </label>
                <?php if ($error): ?>
                    <p class="track-error"><?= e($error) ?></p>
                <?php endif; ?>
                <button class="button" type="submit">Track order <span>→</span></button>
            </form>

            <?php if ($result): ?>
                <?php
                $statusColors = [
                    'pending'    => '#e67e22',
                    'confirmed'  => '#2980b9',
                    'processing' => '#8e44ad',
                    'shipped'    => '#16a085',
                    'delivered'  => '#27ae60',
                    'cancelled'  => '#c0392b',
                    'refunded'   => '#7f8c8d',
                ];
                $statusColor = $statusColors[strtolower((string)$result['status'])] ?? '#1a1c18';
                $currencySymbols = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', 'CAD' => 'CA$', 'AUD' => 'AU$', 'GHS' => 'GH₵', 'KES' => 'KSh ', 'ZAR' => 'R '];
                $symbol = $currencySymbols[$result['currency'] ?? 'NGN'] ?? (($result['currency'] ?? '') ? $result['currency'] . ' ' : '₦');
                ?>
                <div class="track-result">
                    <div class="track-result-header">
                        <div>
                            <span class="track-label">Order</span>
                            <strong><?= e($result['order_number']) ?></strong>
                        </div>
                        <span class="track-status-badge" style="background:<?= $statusColor ?>20;color:<?= $statusColor ?>;border-color:<?= $statusColor ?>40">
                            <?= ucfirst(e($result['status'])) ?>
                        </span>
                    </div>
                    <div class="track-result-grid">
                        <div><span>Name</span><strong><?= e($result['name']) ?></strong></div>
                        <div><span>City</span><strong><?= e($result['city']) ?></strong></div>
                        <div><span>Order total</span><strong><?= ($result['currency'] ?? 'NGN') === 'NGN' ? $symbol . number_format((float)$result['total']) : $symbol . number_format((float)$result['total'], 2) ?></strong></div>
                        <div><span>Placed</span><strong><?= e(date('d M Y', strtotime((string)$result['created_at']))) ?></strong></div>
                    </div>
                    <?php if (strtolower((string)$result['status']) === 'shipped'): ?>
                        <p class="track-shipping-note">Your order is on its way. Estimated delivery: 2–5 business days.</p>
                    <?php elseif (strtolower((string)$result['status']) === 'delivered'): ?>
                        <p class="track-shipping-note">Delivered. If you haven't received it, <a href="<?= e($publicPath . '/contact') ?>">contact support</a>.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="track-help">
            <h3>Need help?</h3>
            <ul>
                <li><a href="<?= e($publicPath . '/faq') ?>">FAQ & common questions</a></li>
                <li><a href="<?= e($publicPath . '/returns') ?>">Returns & exchanges</a></li>
                <li><a href="<?= e($publicPath . '/contact') ?>">Contact our team</a></li>
            </ul>
            <div class="track-help-note">
                <strong>Can't find your order?</strong>
                <p>Check the confirmation email from noreply@noir.co. Orders placed as guest are tracked by email only.</p>
            </div>
        </aside>
    </div>
</main>
<?php require __DIR__ . '/components/footer.php'; ?>
<style>
.track-page { padding: clamp(40px,6vw,80px) clamp(16px,5vw,80px); background: var(--brand-paper); color: var(--brand-dark); }
.track-hero { max-width: 700px; margin-bottom: 48px; }
.track-hero h1 { margin: 12px 0; font-size: clamp(40px,6vw,80px); font-weight: 500; letter-spacing: -0.04em; line-height: 1; }
.track-sub { font-size: 15px; color: var(--brand-muted); line-height: 1.7; margin-top: 12px; max-width: 520px; }
.track-layout { display: grid; grid-template-columns: 1fr 320px; gap: 48px; align-items: start; }
.track-form label { display: grid; gap: 7px; margin-bottom: 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brand-muted); }
.track-form input { padding: 13px 16px; border: 1px solid var(--brand-border); border-radius: 10px; background: rgba(255,255,255,0.8); font-size: 14px; font-family: inherit; width: 100%; color: var(--brand-dark); transition: border-color 0.2s, box-shadow 0.2s; }
.track-form input:focus { outline: 0; border-color: var(--brand-dark); box-shadow: 0 0 0 3px rgba(26,28,24,0.1); }
.track-form .button { width: 100%; margin-top: 6px; justify-content: center; font-size: 13px; letter-spacing: 0.06em; }
.track-error { background: #fff0f0; border: 1px solid #fcc; color: #c0392b; padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.track-result { margin-top: 28px; border: 1px solid var(--brand-border); border-radius: 16px; background: #fff; overflow: hidden; }
.track-result-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid var(--brand-border); }
.track-result-header strong { display: block; font-size: 16px; font-weight: 700; }
.track-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--brand-muted); }
.track-status-badge { padding: 5px 12px; border-radius: 20px; border: 1px solid; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.track-result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; padding: 8px 0; }
.track-result-grid > div { padding: 14px 24px; border-bottom: 1px solid var(--brand-border); }
.track-result-grid span { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brand-muted); margin-bottom: 4px; }
.track-result-grid strong { font-size: 14px; font-weight: 600; }
.track-shipping-note { padding: 16px 24px; font-size: 13px; color: var(--brand-muted); line-height: 1.6; }
.track-help { background: var(--brand-surface); border: 1px solid var(--brand-border); border-radius: 16px; padding: 28px; }
.track-help h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
.track-help ul { list-style: none; padding: 0; margin: 0 0 24px; display: grid; gap: 10px; }
.track-help ul a { font-size: 14px; color: var(--brand-dark); text-decoration: none; display: flex; align-items: center; gap: 8px; }
.track-help ul a::before { content: '→'; opacity: 0.4; }
.track-help-note { background: var(--brand-paper); border-radius: 10px; padding: 16px; }
.track-help-note strong { display: block; font-size: 13px; margin-bottom: 6px; }
.track-help-note p { font-size: 12px; color: var(--brand-muted); line-height: 1.6; margin: 0; }
@media(max-width:900px){ .track-layout { grid-template-columns: 1fr; } }
</style>
</body>
</html>
