<?php
declare(strict_types=1);

$publicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$auth = \Noir\Core\Container::get('auth');
$user = $auth->user() ?? [];
$userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
$csrf = csrf_token();
$cartUrl = $publicPath . '/store/ajax/cart-add';
$images = $images ?: [];
$gallery = $images ?: [['path' => $product['featured_image'] ?? catalogue_image_for($product)]];
foreach ($gallery as &$galleryImage) {
    $imagePath = (string)($galleryImage['path'] ?? '');
    if ($imagePath === '') {
        $galleryImage['path'] = catalogue_image_for($product);
    }
}
unset($galleryImage);
$variantColors = [];
$variantSizes = [];
foreach ($variants ?? [] as $variant) {
    $attributes = json_decode((string)($variant['attributes'] ?? '{}'), true) ?: [];
    if (!empty($attributes['color'])) $variantColors[] = $attributes['color'];
    if (!empty($attributes['size'])) $variantSizes[] = $attributes['size'];
}
$variantColors = array_values(array_unique($variantColors));
$variantSizes = array_values(array_unique($variantSizes));
$quantityAvailable = (int)($product['quantity'] ?? 0);
$stockCopy = $quantityAvailable <= 0 ? 'Out of stock' : ($quantityAvailable <= 5 ? 'Only ' . $quantityAvailable . ' left' : 'In stock');
$material = trim((string)($product['material'] ?? 'Premium cotton blend'));
$careCopy = str_contains(strtolower($material), 'leather') ? 'Wipe clean with a soft dry cloth. Store away from direct heat.' : 'Cold wash, dry flat, and avoid harsh bleach to preserve the finish.';
$card = static function (array $product) use ($publicPath): void { require BASE_PATH . '/app/Views/store/product-card.php'; };
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?= store_seo_meta(title: $product['name'] . ' - ' . store_setting('store_name', 'Noir & Co.'), description: (string)($product['short_description'] ?? $product['name']), image: $gallery[0]['path'] ?? null) ?>
    <link rel="preload" as="style" href="<?= e(($publicPath ?: '') . '/assets/css/store.min.css?v=1.2.5') ?>">
    <link rel="stylesheet" href="<?= e(($publicPath ?: '') . '/assets/css/store.min.css?v=1.2.5') ?>">
    <link rel="stylesheet" href="<?= e(($publicPath ?: '') . '/assets/css/responsive.min.css?v=1.2.5') ?>">
    <link rel="stylesheet" href="<?= e(($publicPath ?: '') . '/assets/css/app.min.css?v=1.2.5') ?>">
    <script defer src="<?= e($publicPath.'/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= store_brand_style() ?>
    <style>
    /* ==========================================================================
       NOIR ATELIER — PRODUCT DETAIL PAGE
       ========================================================================== */
    html, body {
        overflow-x: hidden !important;
        max-width: 100vw !important;
        box-sizing: border-box !important;
    }
    .site-header.shop-header,
    .shop-header {
        position: sticky;
        top: 0;
        z-index: 50;
        background: var(--brand-paper, #f7f7f2) !important;
        border-bottom: 1px solid var(--brand-border, #e3e3da);
        max-width: 100vw !important;
        overflow-x: clip !important;
    }

    /* Main Container */
    .product-page-main {
        max-width: 1240px !important;
        margin: 0 auto !important;
        padding: 24px 28px 80px !important;
        box-sizing: border-box !important;
        width: 100% !important;
    }
    @media (max-width: 768px) {
        .product-page-main {
            padding: 16px 16px 90px !important;
        }
    }

    /* Breadcrumbs */
    .product-breadcrumbs {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin-bottom: 24px !important;
        font-size: 12px !important;
        color: var(--brand-muted, #85877d) !important;
        letter-spacing: 0.02em !important;
    }
    .product-breadcrumbs a {
        color: var(--brand-muted, #85877d) !important;
        text-decoration: none !important;
        transition: color 0.2s ease !important;
    }
    .product-breadcrumbs a:hover {
        color: var(--brand-dark, #252820) !important;
    }
    .product-breadcrumbs .crumb-sep {
        color: #c7c9bf !important;
    }
    .product-breadcrumbs .crumb-current {
        color: var(--brand-dark, #252820) !important;
        font-weight: 500 !important;
    }

    /* Two-Column Product Hero Layout */
    .product-hero-layout {
        display: grid !important;
        grid-template-columns: 1.15fr 0.85fr !important;
        gap: 48px !important;
        align-items: start !important;
        width: 100% !important;
    }
    @media (max-width: 960px) {
        .product-hero-layout {
            grid-template-columns: 1fr !important;
            gap: 32px !important;
        }
    }

    /* Gallery */
    .product-gallery-section {
        display: grid !important;
        grid-template-columns: 84px 1fr !important;
        gap: 16px !important;
        align-items: start !important;
    }
    @media (max-width: 768px) {
        .product-gallery-section {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
        }
    }

    .product-thumb-list {
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
        order: 1 !important;
    }
    @media (max-width: 768px) {
        .product-thumb-list {
            flex-direction: row !important;
            order: 2 !important;
            overflow-x: auto !important;
            padding-bottom: 6px !important;
            gap: 8px !important;
            -webkit-overflow-scrolling: touch !important;
        }
    }

    .product-thumb {
        width: 80px !important;
        height: 80px !important;
        flex-shrink: 0 !important;
        border-radius: 12px !important;
        border: 1.5px solid #e3e3da !important;
        background: #f7f7f2 !important;
        padding: 4px !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        overflow: hidden !important;
        transition: all 0.22s ease !important;
        box-sizing: border-box !important;
    }
    .product-thumb:hover {
        border-color: var(--brand-primary, #687158) !important;
        transform: translateY(-2px) !important;
    }
    .product-thumb.active {
        border-color: var(--brand-primary, #687158) !important;
        background: #eef0e7 !important;
        box-shadow: 0 4px 14px color-mix(in srgb, var(--brand-primary, #687158) 25%, transparent) !important;
    }
    .product-thumb img {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
        border-radius: 8px !important;
    }

    /* Main Product Image Container */
    .product-main-image {
        order: 2 !important;
        position: relative !important;
        width: 100% !important;
        height: 540px !important;
        max-height: 540px !important;
        aspect-ratio: 1 / 1 !important;
        border-radius: 20px !important;
        background: #f7f7f2 !important;
        border: 1px solid var(--brand-border, #e3e3da) !important;
        box-shadow: 0 10px 32px rgba(37, 40, 32, 0.04) !important;
        padding: 24px !important;
        box-sizing: border-box !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        overflow: hidden !important;
        cursor: zoom-in !important;
    }
    @media (max-width: 768px) {
        .product-main-image {
            order: 1 !important;
            height: auto !important;
            min-height: 280px !important;
            max-height: 380px !important;
            aspect-ratio: 1 / 1 !important;
            padding: 14px !important;
            border-radius: 16px !important;
        }
    }
    #product-image {
        max-width: 100% !important;
        max-height: 100% !important;
        width: auto !important;
        height: auto !important;
        object-fit: contain !important;
        border-radius: 12px !important;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .product-main-image.zoomed #product-image {
        transform: scale(1.4) !important;
        cursor: zoom-out !important;
    }

    /* Right Column: Product Summary */
    .product-summary-section {
        padding: 4px 0 0 !important;
        display: flex !important;
        flex-direction: column !important;
    }
    .product-brand-badge {
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.18em !important;
        color: var(--brand-primary, #687158) !important;
        margin: 0 0 10px !important;
    }
    .product-title {
        margin: 0 0 14px !important;
        font-size: clamp(26px, 3.2vw, 38px) !important;
        font-weight: 500 !important;
        line-height: 1.15 !important;
        letter-spacing: -0.04em !important;
        color: var(--brand-dark, #252820) !important;
    }
    .product-price-container {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin-bottom: 18px !important;
    }
    .product-price-container strong {
        font-size: 24px !important;
        font-weight: 700 !important;
        color: var(--brand-dark, #252820) !important;
        letter-spacing: -0.01em !important;
    }
    .product-price-container del {
        font-size: 15px !important;
        color: #92948a !important;
    }
    .product-sale-pill {
        display: inline-flex !important;
        align-items: center !important;
        background: var(--brand-dark, #252820) !important;
        color: #ffffff !important;
        border-radius: 999px !important;
        padding: 4px 12px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        letter-spacing: 0.04em !important;
        text-transform: uppercase !important;
    }
    .product-description-snippet {
        font-size: 14px !important;
        line-height: 1.7 !important;
        color: var(--brand-muted, #77796f) !important;
        margin: 0 0 20px !important;
        max-width: 520px !important;
    }

    /* Trust Grid */
    .product-trust-grid {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 10px !important;
        margin: 16px 0 24px !important;
    }
    @media (max-width: 640px) {
        .product-trust-grid {
            grid-template-columns: 1fr !important;
        }
    }
    .product-trust-grid span {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        border: 1px solid var(--brand-border, #e2e2da) !important;
        border-radius: 12px !important;
        padding: 10px 12px !important;
        font-size: 11px !important;
        font-weight: 500 !important;
        color: #66695f !important;
        background: #fafaf6 !important;
    }
    .product-trust-grid svg {
        width: 16px !important;
        height: 16px !important;
        color: var(--brand-primary, #687158) !important;
        flex-shrink: 0 !important;
    }

    /* Variant Box */
    .product-options-box {
        margin: 16px 0 24px !important;
        padding: 20px 0 !important;
        border-top: 1px solid var(--brand-border, #e2e2da) !important;
        border-bottom: 1px solid var(--brand-border, #e2e2da) !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 18px !important;
    }
    .product-option-group-title {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 8px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: var(--brand-dark, #252820) !important;
    }
    .variant-buttons-row {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    .variant-option {
        border: 1.5px solid #d8d8cf !important;
        border-radius: 999px !important;
        padding: 7px 16px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        background: #ffffff !important;
        color: var(--brand-dark, #252820) !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-family: inherit !important;
    }
    .variant-option:hover {
        border-color: var(--brand-primary, #687158) !important;
        transform: translateY(-1px) !important;
    }
    .variant-option[aria-pressed="true"],
    .variant-option.selected {
        border-color: var(--brand-primary, #687158) !important;
        background: #eef0e7 !important;
        color: var(--brand-primary, #687158) !important;
        font-weight: 600 !important;
    }
    .variant-size-option {
        border-radius: 10px !important;
        padding: 8px 16px !important;
    }

    .ai-size-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        border-radius: 999px !important;
        background: linear-gradient(135deg, #252820, #4a4f41) !important;
        padding: 5px 12px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(37, 40, 32, 0.18) !important;
        border: 0 !important;
        cursor: pointer !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }
    .ai-size-btn:hover {
        transform: translateY(-1px) scale(1.02) !important;
        box-shadow: 0 6px 16px rgba(37, 40, 32, 0.25) !important;
    }
    .ai-size-btn svg {
        width: 14px !important;
        height: 14px !important;
    }

    .product-stock-line {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        font-size: 13px !important;
    }
    .product-stock-line span:first-child {
        color: var(--brand-muted, #85877d) !important;
    }
    .product-stock-badge.in-stock {
        color: var(--brand-primary, #687158) !important;
        font-weight: 600 !important;
    }
    .product-stock-badge.out-of-stock {
        color: #a75b52 !important;
        font-weight: 600 !important;
    }

    /* Actions Toolbar */
    .product-actions-toolbar {
        display: flex !important;
        align-items: stretch !important;
        gap: 12px !important;
        margin: 20px 0 !important;
    }
    .qty-control {
        display: inline-flex !important;
        align-items: center !important;
        border: 1.5px solid #d8d8cf !important;
        border-radius: 12px !important;
        background: #ffffff !important;
        overflow: hidden !important;
    }
    .qty-control button {
        border: 0 !important;
        background: transparent !important;
        padding: 0 16px !important;
        height: 48px !important;
        font-size: 18px !important;
        color: var(--brand-dark, #252820) !important;
        cursor: pointer !important;
        transition: background 0.15s ease !important;
        font-family: inherit !important;
    }
    .qty-control button:hover {
        background: #f0f1eb !important;
    }
    .qty-control output {
        width: 36px !important;
        text-align: center !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        color: var(--brand-dark, #252820) !important;
    }
    .btn-add-to-cart {
        flex: 1 !important;
        height: 48px !important;
        border-radius: 12px !important;
        background: var(--brand-dark, #252820) !important;
        color: #ffffff !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        letter-spacing: 0.02em !important;
        border: 0 !important;
        cursor: pointer !important;
        transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-family: inherit !important;
    }
    .btn-add-to-cart:hover:not(:disabled) {
        background: var(--brand-primary, #687158) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px rgba(104, 113, 88, 0.25) !important;
    }
    .btn-add-to-cart:disabled {
        background: #9d9f95 !important;
        cursor: not-allowed !important;
    }
    .btn-icon-square {
        width: 48px !important;
        height: 48px !important;
        border-radius: 12px !important;
        border: 1.5px solid #d8d8cf !important;
        background: #ffffff !important;
        color: var(--brand-dark, #252820) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        flex-shrink: 0 !important;
    }
    .btn-icon-square:hover {
        border-color: var(--brand-primary, #687158) !important;
        color: var(--brand-primary, #687158) !important;
        transform: translateY(-2px) !important;
    }
    .btn-icon-square.active {
        background: var(--brand-primary, #687158) !important;
        border-color: var(--brand-primary, #687158) !important;
        color: #ffffff !important;
    }
    .btn-icon-square svg {
        width: 18px !important;
        height: 18px !important;
    }

    /* Metadata Specs */
    .product-meta-specs {
        margin-top: 20px !important;
        border-top: 1px solid var(--brand-border, #e2e2da) !important;
        padding-top: 18px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
        font-size: 12px !important;
    }
    .spec-item {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }
    .spec-item span:first-child {
        color: var(--brand-muted, #85877d) !important;
    }
    .spec-item span:last-child {
        color: var(--brand-dark, #252820) !important;
        font-weight: 500 !important;
    }

    /* Product Info 4-Card Grid */
    .product-info-grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 16px !important;
        margin-top: 60px !important;
    }
    .product-info-grid article,
    .review-grid article {
        border: 1px solid var(--brand-border, #e2e2da) !important;
        border-radius: 14px !important;
        background: #fbfbf7 !important;
        padding: 22px !important;
        box-sizing: border-box !important;
    }
    .product-section-label {
        font-size: 10px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.2em !important;
        color: var(--brand-primary, #687158) !important;
        margin: 0 0 10px !important;
    }
    .product-info-grid h2 {
        margin: 10px 0 8px !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        color: var(--brand-dark, #252820) !important;
    }
    .product-info-grid p:not(:first-child),
    .review-grid p {
        color: var(--brand-muted, #77796f) !important;
        font-size: 13px !important;
        line-height: 1.65 !important;
        margin: 0 !important;
    }

    /* Reviews Section */
    .product-section-header {
        margin-bottom: 24px !important;
    }
    .product-section-header h2 {
        margin: 6px 0 0 !important;
        font-size: 28px !important;
        font-weight: 500 !important;
        letter-spacing: -0.04em !important;
        color: var(--brand-dark, #252820) !important;
    }
    .review-grid {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 16px !important;
    }
    .review-grid strong {
        color: var(--brand-primary, #687158) !important;
        letter-spacing: 0.08em !important;
    }
    .review-grid small {
        display: block !important;
        margin-top: 12px !important;
        color: #92948a !important;
        font-size: 11px !important;
    }

    /* Cross-sells */
    .product-cross-sell-grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 18px !important;
    }
    @media (max-width: 1024px) {
        .product-info-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .product-cross-sell-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
    }
    @media (max-width: 768px) {
        .product-info-grid,
        .review-grid {
            grid-template-columns: 1fr !important;
        }
        .product-cross-sell-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
        }
    }

    /* Sticky Mobile Purchase Bar */
    .sticky-mobile-buy-bar {
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 45 !important;
        background: rgba(247, 247, 242, 0.96) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        backdrop-filter: blur(12px) !important;
        border-top: 1px solid var(--brand-border, #e2e2da) !important;
        padding: 12px 18px !important;
        display: none;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 16px !important;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06) !important;
    }
    @media (max-width: 768px) {
        .sticky-mobile-buy-bar {
            display: flex !important;
        }
        body.product-page {
            padding-bottom: 76px !important;
        }
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border-width: 0;
    }
    </style>
    <?= ai_assistant_assets($publicPath) ?>
</head>
<body class="product-page">
<?php require __DIR__ . '/components/header.php'; ?>
<main class="product-page-main">
    <div class="product-breadcrumbs">
        <a href="<?= e($publicPath . '/store') ?>">Store</a>
        <span class="crumb-sep">/</span>
        <span class="crumb-current"><?= e($product['name']) ?></span>
    </div>

    <div class="product-hero-layout">
        <!-- Left Column: Gallery -->
        <section class="product-gallery-section" aria-label="Product gallery">
            <div class="product-thumb-list">
                <?php foreach ($gallery as $index => $image): ?>
                    <button type="button" class="product-thumb <?= $index === 0 ? 'active' : '' ?>" data-image="<?= e(media_url($publicPath, $image['path'])) ?>" aria-label="Show image <?= $index + 1 ?>">
                        <img src="<?= e(media_url($publicPath, $image['path'])) ?>" alt="<?= e($product['name']) ?> Thumbnail <?= $index + 1 ?>" style="<?= e(product_image_tint($product)) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= e(media_url($publicPath, catalogue_image_for($product))) ?>';">
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="product-main-image">
                <img id="product-image" src="<?= e(media_url($publicPath, $gallery[0]['path'])) ?>" alt="<?= e($product['name']) ?>" style="<?= e(product_image_tint($product)) ?>" onerror="this.onerror=null;this.src='<?= e(media_url($publicPath, catalogue_image_for($product))) ?>';">
            </div>
        </section>

        <!-- Right Column: Product Summary -->
        <section class="product-summary-section">
            <p class="product-brand-badge"><?= e($product['brand'] ?: ($product['category'] ?: '-')) ?></p>
            <h1 class="product-title"><?= e($product['name']) ?></h1>
            
            <div class="product-price-container">
                <strong><?= format_currency($product['price']) ?></strong>
                <?php if (!empty($product['compare_at_price']) && (float)$product['compare_at_price'] > (float)$product['price']): ?>
                    <del><?= format_currency($product['compare_at_price']) ?></del>
                    <span class="product-sale-pill">Sale</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['short_description'])): ?>
                <p class="product-description-snippet"><?= e($product['short_description']) ?></p>
            <?php endif; ?>

            <div class="product-trust-grid">
                <span><i data-lucide="truck"></i> 2-5 business days</span>
                <span><i data-lucide="refresh-cw"></i> 7-day returns</span>
                <span><i data-lucide="shield-check"></i> Secure checkout</span>
            </div>

            <div class="product-options-box">
                <?php if ($variantColors): ?>
                    <div>
                        <p class="product-option-group-title">Colour</p>
                        <div class="variant-buttons-row">
                            <?php foreach ($variantColors as $color): ?>
                                <button type="button" class="variant-option" aria-pressed="false" onclick="selectOption(this)"><?= e($color) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($variantSizes): ?>
                    <div>
                        <div class="product-option-group-title">
                            <span>Size</span>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <a href="#size-guide" style="font-size:12px;color:var(--brand-primary,#687158);text-decoration:underline;">Size guide</a>
                                <button type="button" onclick="openSizePredictorModal()" class="ai-size-btn">
                                    <i data-lucide="sparkles"></i> AI Size Predictor
                                </button>
                            </div>
                        </div>
                        <div class="variant-buttons-row">
                            <?php foreach ($variantSizes as $size): ?>
                                <button type="button" class="variant-option variant-size-option" aria-pressed="false" onclick="selectOption(this)"><?= e($size) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="product-stock-line">
                    <span>Availability</span>
                    <span class="product-stock-badge <?= $quantityAvailable > 0 ? 'in-stock' : 'out-of-stock' ?>"><?= e($stockCopy) ?></span>
                </div>
            </div>

            <div class="product-actions-toolbar">
                <div class="qty-control">
                    <button type="button" aria-label="Decrease quantity" onclick="changeQuantity(-1)">&minus;</button>
                    <output id="quantity">1</output>
                    <button type="button" aria-label="Increase quantity" onclick="changeQuantity(1)">+</button>
                </div>
                <button type="button" class="btn-add-to-cart" <?= $quantityAvailable <= 0 ? 'disabled' : '' ?> onclick="addProductToCart(this)">
                    <?= $quantityAvailable > 0 ? 'Add to cart' : 'Out of stock' ?>
                </button>
                <button type="button" id="wishlist-btn-<?= (int)$product['id'] ?>" class="btn-icon-square" onclick="toggleWishlist(<?= (int)$product['id'] ?>)" aria-label="Toggle wishlist">
                    <i data-lucide="heart"></i>
                </button>
                <button type="button" class="btn-icon-square" onclick="shareProductPage('<?= e(addslashes($product['name'])) ?>', window.location.href)" aria-label="Share product">
                    <i data-lucide="share-2"></i>
                </button>
            </div>
            <p id="product-status" class="sr-only" aria-live="polite"></p>

            <div class="product-meta-specs">
                <div class="spec-item"><span>Category</span><span><?= e($product['category'] ?: '-') ?></span></div>
                <div class="spec-item"><span>Material</span><span><?= e($material) ?></span></div>
                <div class="spec-item"><span>Reviews</span><span><?= (int)($reviews['review_count'] ?? 0) ?> reviews<?= (float)($reviews['review_average'] ?? 0) > 0 ? ' / ' . number_format((float)$reviews['review_average'], 1) . ' stars' : '' ?></span></div>
            </div>
        </section>
    </div>

    <!-- 4-Card Information Grid -->
    <section class="product-info-grid">
        <article>
            <p class="product-section-label">Details</p>
            <p style="white-space:pre-line;"><?= e($product['description'] ?: 'A considered piece designed for everyday wear.') ?></p>
        </article>
        <article>
            <p class="product-section-label">Material and care</p>
            <h2><?= e($material) ?></h2>
            <p><?= e($careCopy) ?></p>
        </article>
        <article id="size-guide">
            <p class="product-section-label">Size guide</p>
            <h2>Choose your regular size.</h2>
            <p>For a relaxed fit, size up. Measurements can vary slightly by fabric and finish.</p>
        </article>
        <article>
            <p class="product-section-label">Delivery and returns</p>
            <h2>2-5 business days.</h2>
            <p>Orders move to fulfilment after payment confirmation. Returns are accepted within 7 days when items are unused and in original condition.</p>
        </article>
    </section>

    <!-- Customer Reviews -->
    <?php if (!empty($reviewRows)): ?>
        <section style="margin-top:60px;">
            <div class="product-section-header">
                <p class="product-section-label">Customer reviews</p>
                <h2>What customers noticed.</h2>
            </div>
            <div class="review-grid">
                <?php foreach ($reviewRows as $review): ?>
                    <article>
                        <strong><?= str_repeat('★', max(1, (int)$review['rating'])) ?></strong>
                        <p><?= e($review['review_text']) ?></p>
                        <small><?= e(date('M j, Y', strtotime((string)$review['created_at']))) ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Recently Viewed -->
    <?php if (!empty($recentlyViewed)): ?>
        <section style="margin-top:60px;">
            <div class="product-section-header">
                <p class="product-section-label">Recently viewed</p>
                <h2>Keep comparing.</h2>
            </div>
            <div class="product-cross-sell-grid">
                <?php foreach ($recentlyViewed as $recentProduct) $card($recentProduct); ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if ($related): ?>
        <section style="margin-top:60px;">
            <div class="product-section-header">
                <p class="product-section-label">You may also like</p>
                <h2>Complete the look.</h2>
            </div>
            <div class="product-cross-sell-grid">
                <?php foreach ($related as $relatedProduct) $card($relatedProduct); ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<!-- Sticky Mobile Purchase Bar -->
<div class="sticky-mobile-buy-bar">
    <div>
        <span style="display:block;font-size:10px;color:#85877d;text-transform:uppercase;letter-spacing:0.05em;">Total</span>
        <strong style="font-size:16px;color:#252820;"><?= format_currency($product['price']) ?></strong>
    </div>
    <button type="button" class="btn-add-to-cart" style="flex:none;padding:0 24px;height:42px;font-size:13px;" <?= $quantityAvailable <= 0 ? 'disabled' : '' ?> onclick="addProductToCart(this)">
        <?= $quantityAvailable > 0 ? 'Add to cart' : 'Out of stock' ?>
    </button>
</div>

<?php require __DIR__ . '/partials/size_predictor_modal.php'; ?>
<?php require __DIR__ . '/components/footer.php'; ?>
<script>
lucide.createIcons();
let quantity=1;
function changeQuantity(amount){
    quantity=Math.max(1,Math.min(99,quantity+amount));
    document.getElementById('quantity').value=quantity;
}
function selectOption(button){
    button.parentElement.querySelectorAll('button').forEach(item=>{
        item.classList.remove('selected');
        item.setAttribute('aria-pressed','false');
    });
    button.classList.add('selected');
    button.setAttribute('aria-pressed','true');
}
document.querySelectorAll('.product-thumb').forEach(thumb=>thumb.addEventListener('click',()=>{
    document.getElementById('product-image').src=thumb.dataset.image;
    document.querySelectorAll('.product-thumb').forEach(item=>item.classList.remove('active'));
    thumb.classList.add('active');
}));
document.querySelector('.product-main-image').addEventListener('click',event=>{
    if(event.target.id==='product-image')event.currentTarget.classList.toggle('zoomed');
});
async function addProductToCart(button){
    const status=document.getElementById('product-status');
    button.disabled=true;
    const original=button.textContent;
    button.textContent='Adding...';
    const response=await fetch('<?= e($cartUrl) ?>',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        body:new URLSearchParams({_csrf:'<?= e($csrf) ?>',id:'<?= (int)$product['id'] ?>',quantity:quantity})
    });
    const data=await response.json();
    button.disabled=false;
    if(response.ok&&data.success){
        button.textContent='Added to cart';
        if(status)status.textContent='Added to cart';
        document.querySelectorAll('[data-cart-count]').forEach(item=>item.textContent=data.count);
        if(window.storeToast)storeToast('Added to cart.');
    }else{
        button.textContent=original;
        if(status)status.textContent=data.message||'Unable to add this product.';
        alert(data.message||'Unable to add this product.');
    }
}
let isProductSaved = <?= !empty($isWishlisted) ? 'true' : 'false' ?>;

function toggleWishlist(productId) {
    isProductSaved = !isProductSaved;
    updateWishlistUI(isProductSaved);

    fetch('<?= e($publicPath . "/ajax/wishlist/toggle") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'product_id=' + encodeURIComponent(productId) + '&_csrf=<?= e(csrf_token()) ?>'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            isProductSaved = !!data.is_saved;
            updateWishlistUI(isProductSaved);
            if (window.storeToast) storeToast(data.message);
        } else if (data.error) {
            if (window.storeToast) storeToast(data.error);
        }
    })
    .catch(() => {});

    let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    const index = wishlist.indexOf(productId);
    if (isProductSaved && index === -1) {
        wishlist.push(productId);
    } else if (!isProductSaved && index > -1) {
        wishlist.splice(index, 1);
    }
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
}

function updateWishlistUI(savedState) {
    if (typeof savedState === 'undefined') {
        let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        savedState = isProductSaved || wishlist.includes(<?= (int)$product['id'] ?>);
    }
    const btns = [document.getElementById('wishlist-btn-<?= (int)$product['id'] ?>'), document.getElementById('wishlist-btn-mobile-<?= (int)$product['id'] ?>')];
    btns.forEach(btn => {
        if (!btn) return;
        const icon = btn.querySelector('svg');
        if (savedState) {
            btn.classList.add('active');
            if(icon) { icon.style.fill = 'currentColor'; icon.style.stroke = 'currentColor'; }
        } else {
            btn.classList.remove('active');
            if(icon) { icon.style.fill = 'none'; }
        }
    });
}
function shareProductPage(name, url) {
    if (navigator.share) {
        navigator.share({
            title: name + ' | <?= e(store_setting("store_name", "Noir & Co.")) ?>',
            text: 'Discover ' + name + ' on <?= e(store_setting("store_name", "Noir & Co.")) ?> Atelier.',
            url: url
        }).catch(function() {});
    } else {
        navigator.clipboard.writeText(url).then(function() {
            if (typeof window.storeToast === 'function') {
                window.storeToast('Product link copied to clipboard!');
            } else {
                alert('Product link copied to clipboard!');
            }
        });
    }
}
document.addEventListener('DOMContentLoaded', updateWishlistUI);
</script>
</body>
</html>
