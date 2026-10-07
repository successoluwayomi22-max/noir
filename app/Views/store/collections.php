<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Collections — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta('Collections — ' . $storeName, 'Explore curated collections from ' . $storeName . '.') ?>
    <?= store_brand_style() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>

<main class="collections-page">
    <?= store_breadcrumbs([['name' => 'Home', 'url' => $publicPath . '/'], ['name' => 'Collections', 'url' => '']]) ?>

    <header class="page-header">
        <h1>Collections</h1>
        <p>Curated edits designed for the modern wardrobe.</p>
    </header>

    <div class="collections-grid">
        <a href="<?= e($publicPath . '/store?search=summer') ?>" class="collection-card">
            <div class="collection-img">
                <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800" alt="Summer Edit">
            </div>
            <div class="collection-info">
                <h3>The Summer Edit</h3>
                <p>Lightweight linens and effortless silhouettes.</p>
                <span class="btn-link">Explore →</span>
            </div>
        </a>

        <a href="<?= e($publicPath . '/store?search=essential') ?>" class="collection-card">
            <div class="collection-img">
                <img src="https://images.unsplash.com/photo-1550614000-4b95dd244093?auto=format&fit=crop&q=80&w=800" alt="Essentials">
            </div>
            <div class="collection-info">
                <h3>Wardrobe Essentials</h3>
                <p>Foundational pieces you'll wear on repeat.</p>
                <span class="btn-link">Explore →</span>
            </div>
        </a>

        <a href="<?= e($publicPath . '/store?search=evening') ?>" class="collection-card">
            <div class="collection-img">
                <img src="https://images.unsplash.com/photo-1566207274740-0f8cf6b7d5a5?auto=format&fit=crop&q=80&w=800" alt="Eveningwear">
            </div>
            <div class="collection-info">
                <h3>Evening Attire</h3>
                <p>Refined elegance for your most special occasions.</p>
                <span class="btn-link">Explore →</span>
            </div>
        </a>
    </div>
</main>

<?php require __DIR__ . '/components/footer.php'; ?>

<style>
.collections-page { padding: clamp(40px,6vw,80px) clamp(16px,5vw,80px); background: var(--brand-paper); color: var(--brand-dark); }
.page-header { max-width: 600px; margin: 40px 0; }
.page-header h1 { font-size: clamp(36px, 5vw, 64px); font-weight: 500; letter-spacing: -0.04em; margin-bottom: 12px; }
.page-header p { font-size: 16px; color: var(--brand-muted); line-height: 1.6; }
.collections-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px; margin-top: 20px; }
.collection-card { text-decoration: none; color: inherit; display: block; group; }
.collection-img { width: 100%; aspect-ratio: 4/5; overflow: hidden; background: #e8e9e0; margin-bottom: 24px; border-radius: 4px; }
.collection-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.collection-card:hover .collection-img img { transform: scale(1.05); }
.collection-info h3 { font-size: 24px; font-weight: 500; margin-bottom: 8px; }
.collection-info p { color: var(--brand-muted); font-size: 14px; margin-bottom: 16px; line-height: 1.5; }
.btn-link { font-size: 13px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; border-bottom: 1px solid currentColor; padding-bottom: 2px; }
</style>
</body>
</html>
