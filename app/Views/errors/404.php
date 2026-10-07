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
    <title>404 — Piece Not Found | <?= e($storeName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta('404 — Page Not Found - ' . $storeName, 'The piece or collection you are looking for has moved. Explore Noir luxury collections, new drops, or contact our concierge.') ?>
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

        body.noir-404-body {
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

        .noir-404-header {
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

        .noir-404-brand {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .noir-404-brand-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--soft-olive);
            box-shadow: 0 0 12px var(--soft-olive);
        }

        .noir-404-nav {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .noir-404-nav a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .noir-404-nav a:hover {
            color: var(--soft-olive);
        }

        .noir-404-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 80px 24px;
            position: relative;
        }

        /* Ambient Glow Backdrop */
        .ambient-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(152, 166, 130, 0.12) 0%, rgba(18, 20, 18, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
        }

        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 30px;
            background: rgba(152, 166, 130, 0.12);
            border: 1px solid rgba(152, 166, 130, 0.3);
            color: var(--soft-olive-light);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .error-code {
            font-size: clamp(80px, 14vw, 160px);
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -0.05em;
            color: transparent;
            background: linear-gradient(180deg, #FFFFFF 0%, #7E8578 100%);
            -webkit-background-clip: text;
            background-clip: text;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .error-title {
            font-size: clamp(22px, 3.5vw, 32px);
            font-weight: 700;
            margin: 20px 0 12px;
            max-width: 650px;
            color: var(--text-white);
            position: relative;
            z-index: 1;
        }

        .error-desc {
            font-size: 15px;
            color: var(--text-muted);
            max-width: 520px;
            line-height: 1.6;
            margin: 0 0 36px;
            position: relative;
            z-index: 1;
        }

        /* Search Box */
        .search-container {
            width: 100%;
            max-width: 480px;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .search-form {
            display: flex;
            align-items: center;
            background: var(--card-charcoal);
            border: 1px solid var(--border-charcoal);
            border-radius: 14px;
            padding: 6px 6px 6px 18px;
            transition: all 0.2s ease;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .search-form:focus-within {
            border-color: var(--soft-olive);
            box-shadow: 0 0 0 3px rgba(152, 166, 130, 0.15);
        }

        .search-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-white);
            font-size: 14px;
            outline: none;
            font-family: inherit;
        }

        .search-input::placeholder {
            color: #6C7268;
        }

        .search-btn {
            background: var(--soft-olive);
            color: #121412;
            border: none;
            padding: 12px 22px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.2s ease;
        }

        .search-btn:hover {
            background: var(--soft-olive-light);
            transform: scale(1.02);
        }

        /* Action Buttons */
        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
            margin-bottom: 48px;
            position: relative;
            z-index: 1;
        }

        .btn-primary {
            background: var(--soft-olive);
            color: #121412;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: 0.04em;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: var(--soft-olive-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(152, 166, 130, 0.25);
        }

        .btn-secondary {
            background: var(--card-charcoal);
            color: var(--text-white);
            border: 1px solid var(--border-charcoal);
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            border-color: var(--soft-olive);
            color: var(--soft-olive-light);
            transform: translateY(-2px);
        }

        /* Category Quick Links */
        .curated-section {
            width: 100%;
            max-width: 780px;
            border-top: 1px solid var(--border-charcoal);
            padding-top: 36px;
            position: relative;
            z-index: 1;
        }

        .curated-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 18px;
        }

        .curated-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }

        .curated-card {
            background: var(--card-charcoal);
            border: 1px solid var(--border-charcoal);
            border-radius: 12px;
            padding: 14px;
            text-decoration: none;
            color: var(--text-white);
            text-align: left;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .curated-card:hover {
            border-color: var(--soft-olive);
            transform: translateY(-3px);
            background: #252A24;
        }

        .curated-card-name {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-white);
        }

        .curated-card-hint {
            font-size: 11px;
            color: var(--soft-olive);
            font-weight: 600;
        }

        .noir-404-footer {
            padding: 24px 6vw;
            border-top: 1px solid var(--border-charcoal);
            text-align: center;
            font-size: 12px;
            color: #6C7268;
        }
    </style>
</head>
<body class="noir-404-body">

    <!-- Header Navigation -->
    <header class="noir-404-header">
        <a class="noir-404-brand" href="<?= e($publicPath . '/') ?>">
            <span class="noir-404-brand-dot"></span>
            <?= e($storeName) ?>
        </a>
        <nav class="noir-404-nav">
            <a href="<?= e($publicPath . '/store') ?>">Boutique</a>
            <a href="<?= e($publicPath . '/journal') ?>">Journal</a>
            <a href="<?= e($publicPath . '/contact') ?>">Concierge</a>
        </nav>
    </header>

    <!-- Main 404 Canvas -->
    <main class="noir-404-main">
        <div class="ambient-glow"></div>

        <div class="error-badge">
            Error Status 404
        </div>

        <h1 class="error-code">404</h1>
        <h2 class="error-title">This Piece Has Moved or Is No Longer in Circulation</h2>
        <p class="error-desc">
            The collection, runway archive, or link you followed cannot be retrieved. Search our active boutique pieces below or return to the curated boutique.
        </p>

        <!-- Search Bar -->
        <div class="search-container">
            <form class="search-form" method="get" action="<?= e($publicPath . '/store') ?>">
                <input class="search-input" type="text" name="search" placeholder="Search bespoke garments, blazers, leather..." aria-label="Search Collection" autofocus>
                <button class="search-btn" type="submit">Search Collection</button>
            </form>
        </div>

        <!-- Action Links -->
        <div class="error-actions">
            <a class="btn-primary" href="<?= e($publicPath . '/store') ?>">
                Explore Boutique
            </a>
            <a class="btn-secondary" href="<?= e($publicPath . '/') ?>">
                Return Home
            </a>
            <a class="btn-secondary" href="<?= e($publicPath . '/contact') ?>">
                Client Support
            </a>
        </div>

        <!-- Curated Discovery Hub -->
        <div class="curated-section">
            <div class="curated-title">Curated Collections & Highlights</div>
            <div class="curated-grid">
                <a class="curated-card" href="<?= e($publicPath . '/store?category=outerwear') ?>">
                    <span class="curated-card-name">Tailored Outerwear</span>
                    <span class="curated-card-hint">Blazers & Wool Trench →</span>
                </a>
                <a class="curated-card" href="<?= e($publicPath . '/store?category=couture') ?>">
                    <span class="curated-card-name">Haute Evening</span>
                    <span class="curated-card-hint">Silk & Velvet Robes →</span>
                </a>
                <a class="curated-card" href="<?= e($publicPath . '/store?category=leather') ?>">
                    <span class="curated-card-name">Leather Craft</span>
                    <span class="curated-card-hint">Calfskin Totes & Loafers →</span>
                </a>
                <a class="curated-card" href="<?= e($publicPath . '/store?sort=newest') ?>">
                    <span class="curated-card-name">New Runway Drops</span>
                    <span class="curated-card-hint">2026 Collection →</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="noir-404-footer">
        &copy; <?= date('Y') ?> <?= e($storeName) ?>. All rights reserved. Precision bespoke craftsmanship.
    </footer>

</body>
</html>
