<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>403 Access Denied — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <?= store_brand_style() ?>
    <style>
        body {
            display: grid;
            min-height: 100vh;
            place-items: center;
            background: #121310;
            color: #eceee6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 24px;
        }
        .shield-card {
            max-width: 520px;
            width: 100%;
            background: #1c1e18;
            border: 1px solid #2f3328;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .shield-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            display: grid;
            place-items: center;
            background: rgba(220, 38, 38, 0.12);
            color: #ef4444;
            border-radius: 9999px;
            font-size: 28px;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 12px;
            letter-spacing: -0.02em;
            color: #fff;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: #9da391;
            margin: 0 0 24px;
        }
        .meta-box {
            background: #141512;
            border: 1px solid #282c22;
            border-radius: 8px;
            padding: 12px 16px;
            font-family: monospace;
            font-size: 12px;
            color: #d1d5db;
            text-align: left;
            margin-bottom: 24px;
            word-break: break-all;
        }
        .btn-home {
            display: inline-block;
            padding: 12px 24px;
            background: #687158;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="shield-card">
        <div class="shield-icon" style="display: flex; justify-content: center; align-items: center; margin-bottom: 20px;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#687158" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <h1>Access Restricted by Security Shield</h1>
        <p>Your connection has been temporarily restricted due to excessive requests or suspicious traffic patterns protecting the store.</p>
        <div class="meta-box">
            <div>Incident: IP Rate Limit Lockout</div>
            <div>Reason: <?= e($blocked['reason'] ?? 'Automated request volume threshold exceeded') ?></div>
            <div>Expiration: <?= e($blocked['blocked_until'] ?? 'Temporary Lockout') ?></div>
        </div>
        <a class="btn-home" href="<?= e(($base ?: '') . '/') ?>">Return to Storefront</a>
    </div>
</body>
</html>
