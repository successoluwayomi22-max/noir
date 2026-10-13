<?php
declare(strict_types=1);

$publicPath = $publicPath ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName  = $storeName ?? store_setting('store_name', 'Noir & Co.');
$isIos      = $isIos ?? (bool)preg_match('/iPhone|iPad|iPod/i', $_SERVER['HTTP_USER_AGENT'] ?? '');

$downloadHost = !empty($_SERVER['HTTP_HOST']) 
    ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']) 
    : rtrim((string)config('url', 'http://localhost:8000'), '/');
$appDownloadUrl = $downloadHost . ($publicPath ?: '') . '/download/app';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Download Noir App') ?> — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css?v=1.2.0') ?>">
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/store.min.css?v=1.2.0') ?>">
    <script src="<?= e($publicPath . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= store_seo_meta(__('Download Mobile App — ') . $storeName, __('Get the official Noir mobile app for iOS and Android. Seamless one-touch shopping, private runway releases, and AI styling concierge.')) ?>
    <?= store_brand_style() ?>
    <style>
        .app-hub-page {
            background: #191b15;
            color: #f5f5ef;
            min-height: 80vh;
            padding: 60px 6vw 90px;
        }
        .app-hub-container {
            max-width: 1140px;
            margin: 0 auto;
        }
        .app-hub-hero {
            text-align: center;
            max-width: 720px;
            margin: 0 auto 50px;
        }
        .app-hub-hero .eyebrow {
            color: #b8af84;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .app-hub-hero h1 {
            font-size: clamp(32px, 4.5vw, 54px);
            font-weight: 400;
            letter-spacing: -0.04em;
            line-height: 1.1;
            margin: 0 0 18px;
            color: #ffffff;
        }
        .app-hub-hero p {
            font-size: 16px;
            line-height: 1.6;
            color: #a3a998;
            margin: 0;
        }
        .app-hub-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 50px;
        }
        .app-card {
            background: linear-gradient(160deg, #23271e 0%, #171914 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
        }
        .app-card.featured {
            border-color: rgba(184, 175, 132, 0.4);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(184, 175, 132, 0.2);
        }
        .app-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #b8af84;
            background: rgba(184, 175, 132, 0.12);
            border: 1px solid rgba(184, 175, 132, 0.25);
            padding: 5px 12px;
            border-radius: 999px;
            margin-bottom: 20px;
            align-self: flex-start;
        }
        .app-card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .app-card-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: grid;
            place-items: center;
            color: #ffffff;
            flex: none;
        }
        .app-card-icon svg {
            width: 26px;
            height: 26px;
        }
        .app-card-title-wrap h2 {
            font-size: 22px;
            font-weight: 600;
            margin: 0 0 4px;
            color: #ffffff;
        }
        .app-card-title-wrap span {
            font-size: 13px;
            color: #8c9381;
        }
        .app-card-body {
            flex: 1;
            margin-bottom: 28px;
        }
        .app-step-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .app-step-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 14px;
            color: #d1d5c7;
            line-height: 1.5;
        }
        .app-step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #687158;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            display: grid;
            place-items: center;
            flex: none;
            margin-top: 1px;
        }
        .app-card-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .app-btn-download {
            background: #687158;
            color: #ffffff;
            border: none;
            padding: 15px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.25s ease;
        }
        .app-btn-download:hover {
            background: #778166;
            transform: translateY(-2px);
            color: #ffffff;
        }
        .app-btn-outline {
            background: rgba(255, 255, 255, 0.05);
            color: #e2e6d8;
            border: 1px solid rgba(255, 255, 255, 0.16);
            padding: 13px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .app-btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        .app-hub-qr-section {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 34px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }
        .app-hub-qr-info h3 {
            font-size: 20px;
            font-weight: 500;
            margin: 0 0 8px;
            color: #ffffff;
        }
        .app-hub-qr-info p {
            margin: 0;
            font-size: 14px;
            color: #8c9381;
            max-width: 520px;
            line-height: 1.5;
        }
        .app-hub-qr-box {
            background: #ffffff;
            padding: 8px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            flex: none;
        }
        .app-hub-qr-box img {
            display: block;
            border-radius: 6px;
        }
        @media (max-width: 860px) {
            .app-hub-grid {
                grid-template-columns: 1fr;
            }
            .app-hub-qr-section {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body class="store-page">
    <?php require __DIR__ . '/components/header.php'; ?>

    <main class="app-hub-page">
        <div class="app-hub-container">
            <div class="app-hub-hero">
                <p class="eyebrow"><?= __('Maison Mobile Ecosystem') ?></p>
                <h1><?= __('Carry Noir Wherever You Are.') ?></h1>
                <p><?= __('Experience tailored luxury shopping, one-touch express biometric checkout, live order dispatch tracing, and our private concierge on both Apple iOS & Android.') ?></p>
                <?php if (!empty($errorMessage)): ?>
                    <div style="margin-top: 20px; background: rgba(184, 175, 132, 0.15); border: 1px solid #b8af84; color: #f5f5ef; padding: 14px 20px; border-radius: 12px; font-size: 14px;">
                        <i data-lucide="info" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 6px;"></i>
                        <?= e($errorMessage) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="app-hub-grid">
                <!-- Android Card -->
                <div class="app-card <?= !$isIos ? 'featured' : '' ?>">
                    <div>
                        <span class="app-card-badge" style="background:rgba(61,220,132,0.12);border-color:rgba(61,220,132,0.3);color:#3ddc84;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="display:inline-block;vertical-align:-1px;"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C18.0741 13.3414 18.5227 13.79 18.5227 14.3414c0 .5514-.4486 1-.9997 1zm-11.046 0c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C6.0278 13.3414 6.4764 13.79 6.4764 14.3414c0 .5514-.4486 1-.9994 1zm11.4045-6.02l1.996-3.456c.145-.2513.06-.5727-.1913-.7177-.251-.145-.5724-.06-.7174.1913l-2.0232 3.5042C15.4858 8.3503 13.792 8.005 12 8.005c-1.792 0-3.4858.3453-4.9456.8432L5.0312 5.344c-.145-.2513-.4664-.3363-.7174-.1913-.2513.145-.3363.4664-.1913.7177l1.996 3.456C2.6877 11.2384.3435 15.1118.005 19.645h23.99c-.3385-4.5332-2.6827-8.4066-6.1135-10.3236z"/></svg>
                            <?= __('Android Package') ?>
                        </span>
                        <div class="app-card-header">
                            <div class="app-card-icon" style="background:rgba(61,220,132,0.12);border-color:rgba(61,220,132,0.3);color:#3ddc84;box-shadow:0 8px 24px rgba(61,220,132,0.12);">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C18.0741 13.3414 18.5227 13.79 18.5227 14.3414c0 .5514-.4486 1-.9997 1zm-11.046 0c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C6.0278 13.3414 6.4764 13.79 6.4764 14.3414c0 .5514-.4486 1-.9994 1zm11.4045-6.02l1.996-3.456c.145-.2513.06-.5727-.1913-.7177-.251-.145-.5724-.06-.7174.1913l-2.0232 3.5042C15.4858 8.3503 13.792 8.005 12 8.005c-1.792 0-3.4858.3453-4.9456.8432L5.0312 5.344c-.145-.2513-.4664-.3363-.7174-.1913-.2513.145-.3363.4664-.1913.7177l1.996 3.456C2.6877 11.2384.3435 15.1118.005 19.645h23.99c-.3385-4.5332-2.6827-8.4066-6.1135-10.3236z"/></svg>
                            </div>
                            <div class="app-card-title-wrap">
                                <h2>Noir for Android</h2>
                                <span>APK Direct Installation &bull; Verified</span>
                            </div>
                        </div>
                        <div class="app-card-body">
                            <ul class="app-step-list">
                                <li class="app-step-item">
                                    <span class="app-step-num" style="background:#3ddc84;color:#0e1f14;">1</span>
                                    <span>Download the official <strong>noir-app.apk</strong> binary directly to your Android device.</span>
                                </li>
                                <li class="app-step-item">
                                    <span class="app-step-num" style="background:#3ddc84;color:#0e1f14;">2</span>
                                    <span>Tap the downloaded file to install (allow "Install Unknown Apps" if prompted).</span>
                                </li>
                                <li class="app-step-item">
                                    <span class="app-step-num" style="background:#3ddc84;color:#0e1f14;">3</span>
                                    <span>Launch Noir for the full native Flutter experience with haptic navigation and biometric authentication.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="app-card-actions">
                        <a href="<?= e($publicPath . '/download/app?direct=1') ?>" class="app-btn-download" style="background:#324430;border:1px solid rgba(61,220,132,0.3);color:#ffffff;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="margin-right:4px;"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C18.0741 13.3414 18.5227 13.79 18.5227 14.3414c0 .5514-.4486 1-.9997 1zm-11.046 0c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C6.0278 13.3414 6.4764 13.79 6.4764 14.3414c0 .5514-.4486 1-.9994 1zm11.4045-6.02l1.996-3.456c.145-.2513.06-.5727-.1913-.7177-.251-.145-.5724-.06-.7174.1913l-2.0232 3.5042C15.4858 8.3503 13.792 8.005 12 8.005c-1.792 0-3.4858.3453-4.9456.8432L5.0312 5.344c-.145-.2513-.4664-.3363-.7174-.1913-.2513.145-.3363.4664-.1913.7177l1.996 3.456C2.6877 11.2384.3435 15.1118.005 19.645h23.99c-.3385-4.5332-2.6827-8.4066-6.1135-10.3236z"/></svg> <?= __('Download Android APK') ?>
                        </a>
                    </div>
                </div>

                <!-- iOS Card -->
                <div class="app-card <?= $isIos ? 'featured' : '' ?>" id="ios-card">
                    <div>
                        <span class="app-card-badge" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.22);color:#ffffff;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="display:inline-block;vertical-align:-1px;"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                            <?= __('Apple iOS') ?>
                        </span>
                        <div class="app-card-header">
                            <div class="app-card-icon" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.22);color:#ffffff;box-shadow:0 8px 24px rgba(255,255,255,0.06);">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                            </div>
                            <div class="app-card-title-wrap">
                                <h2>Noir for iPhone &amp; iPad</h2>
                                <span style="color:#b8af84;">Coming Soon to App Store</span>
                            </div>
                        </div>

                        <!-- Coming Soon Banner -->
                        <div style="background:linear-gradient(135deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02));border:1px solid rgba(255,255,255,0.12);border-radius:14px;padding:20px;margin-bottom:20px;text-align:center;">
                            <div style="margin-bottom:12px;display:flex;justify-content:center;">
                                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.16);display:grid;place-items:center;color:#ffffff;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                                </div>
                            </div>
                            <p style="font-size:15px;font-weight:600;color:#ffffff;margin:0 0 6px;"><?= __('Native iOS App In Development') ?></p>
                            <p style="font-size:13px;color:#8c9381;margin:0;line-height:1.5;"><?= __('Our iOS app is currently in preparation for the Apple App Store. In the meantime, enjoy the full Noir experience using our Web App below.') ?></p>
                        </div>

                        <div class="app-card-body">
                            <p style="font-size:12px;color:#b8af84;text-transform:uppercase;letter-spacing:0.12em;font-weight:700;margin:0 0 12px;"><?= __('Use Noir as a Web App Now') ?></p>
                            <ul class="app-step-list">
                                <li class="app-step-item">
                                    <span class="app-step-num" style="background:#ffffff;color:#171914;">1</span>
                                    <span>Open this website in <strong>Safari</strong> on your iPhone or iPad.</span>
                                </li>
                                <li class="app-step-item">
                                    <span class="app-step-num" style="background:#ffffff;color:#171914;">2</span>
                                    <span>Tap the <strong>Share</strong> button <i data-lucide="share" style="width:14px;height:14px;vertical-align:middle;"></i> at the bottom of Safari.</span>
                                </li>
                                <li class="app-step-item">
                                    <span class="app-step-num" style="background:#ffffff;color:#171914;">3</span>
                                    <span>Select <strong>"Add to Home Screen"</strong> <i data-lucide="plus-square" style="width:14px;height:14px;vertical-align:middle;"></i> — it works just like a native app!</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="app-card-actions">
                        <a href="<?= e($publicPath . '/') ?>" class="app-btn-download" style="background:#262a22;border:1px solid rgba(255,255,255,0.18);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="margin-right:4px;"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> <?= __('Add to Home Screen (Safari)') ?>
                        </a>
                        <a href="<?= e($publicPath . '/download/app?direct=1') ?>" class="app-btn-outline">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="margin-right:4px;"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C18.0741 13.3414 18.5227 13.79 18.5227 14.3414c0 .5514-.4486 1-.9997 1zm-11.046 0c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C6.0278 13.3414 6.4764 13.79 6.4764 14.3414c0 .5514-.4486 1-.9994 1zm11.4045-6.02l1.996-3.456c.145-.2513.06-.5727-.1913-.7177-.251-.145-.5724-.06-.7174.1913l-2.0232 3.5042C15.4858 8.3503 13.792 8.005 12 8.005c-1.792 0-3.4858.3453-4.9456.8432L5.0312 5.344c-.145-.2513-.4664-.3363-.7174-.1913-.2513.145-.3363.4664-.1913.7177l1.996 3.456C2.6877 11.2384.3435 15.1118.005 19.645h23.99c-.3385-4.5332-2.6827-8.4066-6.1135-10.3236z"/></svg> <?= __('Download Android APK Instead') ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Desktop QR Scanner Section -->
            <div class="app-hub-qr-section">
                <div class="app-hub-qr-info">
                    <h3><?= __('Scanning from a Desktop or Tablet?') ?></h3>
                    <p><?= __('Point your mobile phone camera at the QR code to open the download hub instantly on your mobile device. Compatible with all modern iOS and Android cameras.') ?></p>
                </div>
                <div class="app-hub-qr-box">
                    <img src="<?= e($publicPath . '/assets/icons/app-qr.svg') ?>"
                         alt="Scan QR code for Noir Mobile App"
                         width="130" height="130">
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/components/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
