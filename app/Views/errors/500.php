<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
$publicPath = $base ?: '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Server Concierge | <?= e($storeName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta('500 — Server Interruption - ' . $storeName, 'We encountered a momentary delay. Our concierge is attending to it.') ?>
    <?= store_brand_style() ?>
    <style>
        :root {
            --bg-charcoal: #121412;
            --surface-charcoal: #1C1F1B;
            --card-charcoal: #212520;
            --border-charcoal: #2D322B;
            --soft-olive: #98A682;
            --soft-olive-light: #C3CFB2;
            --text-white: #FFFFFF;
            --text-muted: #D2D6CC;
        }

        body.noir-500-body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-charcoal);
            color: var(--text-white);
            font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .noir-500-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 6vw;
            border-bottom: 1px solid var(--border-charcoal);
            background: rgba(18, 20, 18, 0.85);
            backdrop-filter: blur(16px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .noir-500-brand {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-white);
            text-decoration: none;
        }

        .noir-500-hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 6vw;
            text-align: center;
            position: relative;
        }

        .noir-500-card {
            max-width: 620px;
            background: var(--surface-charcoal);
            border: 1px solid var(--border-charcoal);
            border-radius: 28px;
            padding: 60px 40px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            position: relative;
            z-index: 2;
        }

        .noir-500-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--soft-olive);
            background: rgba(152, 166, 130, 0.12);
            padding: 6px 16px;
            border-radius: 999px;
            margin-bottom: 24px;
        }

        .noir-500-code {
            font-size: clamp(64px, 12vw, 110px);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.05em;
            margin: 0;
            background: linear-gradient(135deg, #ffffff 0%, #98a682 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .noir-500-title {
            font-size: 22px;
            font-weight: 600;
            margin: 16px 0 12px;
            color: var(--text-white);
        }

        .noir-500-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 36px;
        }

        .noir-500-actions {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary-noir {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--soft-olive);
            color: #121412;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.04em;
            padding: 14px 28px;
            border-radius: 14px;
            text-decoration: none;
            cursor: pointer;
            border: 0;
            transition: all 0.25s ease;
        }
        .btn-primary-noir:hover {
            background: var(--soft-olive-light);
            transform: translateY(-2px);
        }

        .btn-secondary-noir {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            color: var(--text-white);
            font-weight: 600;
            font-size: 13px;
            padding: 14px 24px;
            border-radius: 14px;
            border: 1px solid var(--border-charcoal);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .btn-secondary-noir:hover {
            border-color: var(--soft-olive);
            background: rgba(255,255,255,0.04);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="noir-500-body">
    <header class="noir-500-header">
        <a href="<?= e($publicPath . '/') ?>" class="noir-500-brand"><?= e($storeName) ?></a>
        <a href="<?= e($publicPath . '/store') ?>" style="color: var(--soft-olive); text-decoration: none; font-size: 13px; font-weight: 600;"><?= __('Explore Store') ?> &rarr;</a>
    </header>

    <main class="noir-500-hero">
        <div class="noir-500-card">
            <div class="noir-500-tag">
                <?= __('Momentary Interruption') ?>
            </div>
            <h1 class="noir-500-code">500</h1>
            <h2 class="noir-500-title"><?= __('Our Concierge is Attending to This') ?></h2>
            <p class="noir-500-desc">
                <?= __('We encountered a brief delay preparing your luxury session. Your bag and account state remain completely secure.') ?>
            </p>

            <div class="noir-500-actions">
                <button onclick="window.location.reload()" class="btn-primary-noir">
                    <?= __('Try Again') ?>
                </button>
                <a href="<?= e($publicPath . '/') ?>" class="btn-secondary-noir">
                    <?= __('Return Home') ?>
                </a>
                <a href="<?= e($publicPath . '/customer/support') ?>" class="btn-secondary-noir">
                    <?= __('Contact Concierge') ?>
                </a>
            </div>
            <div id="recovery-status" style="margin-top: 20px; font-size: 12px; color: var(--soft-olive); opacity: 0.8;"></div>
        </div>
    </main>
    <script nonce="<?= csp_nonce() ?>">
        (function() {
            var healthUrl = '<?= e($publicPath . "/health") ?>';
            var statusEl = document.getElementById('recovery-status');
            var checkCount = 0;
            var maxChecks = 30;

            function checkRecovery() {
                checkCount++;
                if (checkCount > maxChecks) return;

                fetch(healthUrl, { cache: 'no-store' })
                    .then(function(res) {
                        if (res.ok) {
                            if (statusEl) {
                                statusEl.textContent = 'Connection restored. Returning you to your session...';
                            }
                            setTimeout(function() {
                                window.location.reload();
                            }, 1200);
                        } else {
                            setTimeout(checkRecovery, 3000);
                        }
                    })
                    .catch(function() {
                        setTimeout(checkRecovery, 3000);
                    });
            }

            // Start checking after 2 seconds
            setTimeout(checkRecovery, 2000);
        })();
    </script>
</body>
</html>
