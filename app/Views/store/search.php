<?php
declare(strict_types=1);
$publicPath = $publicPath ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName  = $storeName ?? store_setting('store_name', 'Noir & Co.');
$query      = $query ?? '';
$products   = $products ?? [];
$total      = $total ?? 0;
$page       = $page ?? 1;
$pages      = $pages ?? 1;

function searchFormatPrice(mixed $price): string {
    return format_currency($price);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $query !== '' ? 'Search: ' . e($query) . ' — ' : 'Search — ' ?><?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta(
        ($query !== '' ? 'Search results for "' . $query . '" — ' : 'Search — ') . $storeName,
        'Browse ' . $storeName . ' search results for "' . $query . '". Find curated fashion pieces, accessories, and more.'
    ) ?>
    <?= store_brand_style() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>
<main class="search-page">
    <?= store_breadcrumbs([['name' => __('Home'), 'url' => $publicPath . '/'], ['name' => __('Search'), 'url' => '']]) ?>

    <div class="search-header">
        <form class="search-bar-form" method="get" action="<?= e($publicPath . '/search') ?>" role="search">
            <div class="search-input-wrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="q" value="<?= e($query) ?>"
                       placeholder="<?= e(__('Search tees, hoodies, bags...')) ?>"
                       autofocus aria-label="<?= e(__('Search')) ?>">
                <button type="submit"><?= __('Search') ?></button>
            </div>
        </form>
        <?php if ($query !== ''): ?>
            <p class="search-meta">
                <?= $total > 0
                    ? '<strong>' . $total . '</strong> ' . e($total !== 1 ? __('pieces in this edit') : __('Item')) . ' for <em>"' . e($query) . '"</em>'
                    : e(__('No products found')) . ' <em>"' . e($query) . '"</em>' ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if ($query === ''): ?>
        <div class="search-empty-prompt">
            <p><?= __('Search tees, hoodies, bags...') ?></p>
        </div>
    <?php elseif (empty($products)): ?>
        <div class="search-no-results">
            <p class="eyebrow"><?= __('No products found') ?></p>
            <h2><?= __('No pieces match your selection.') ?></h2>
            <p><a href="<?= e($publicPath . '/store') ?>"><?= __('Shop the collection') ?></a></p>
        </div>
    <?php else: ?>
        <div class="search-grid">
            <?php foreach ($products as $p): ?>
                <?php
                $slug  = $p['slug'] ?? $p['id'];
                $price = (float)($p['min_price'] ?? $p['price'] ?? 0);
                $image = catalogue_image_for($p);
                ?>
                <a class="search-card" href="<?= e($publicPath . '/store/product/' . rawurlencode((string)$slug)) ?>">
                    <div class="search-card-image">
                        <img src="<?= e(media_url($publicPath, $image)) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&q=80';">
                    </div>
                    <div class="search-card-info">
                        <?php if (!empty($p['category_name'])): ?>
                            <span class="search-card-cat"><?= e($p['category_name']) ?></span>
                        <?php endif; ?>
                        <h3><?= e($p['name']) ?></h3>
                        <strong><?= searchFormatPrice($price) ?></strong>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="search-pagination" aria-label="Search pages">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?q=<?= urlencode($query) ?>&page=<?= $i ?>"
                       class="<?= $i === $page ? 'active' : '' ?>"
                       aria-current="<?= $i === $page ? 'page' : 'false' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/components/footer.php'; ?>
<style>
.search-page { padding: clamp(30px,5vw,70px) clamp(16px,5vw,80px); background: var(--brand-paper); color: var(--brand-dark); min-height: 60vh; }
.search-header { margin-bottom: 40px; }
.search-bar-form { margin-bottom: 18px; }
.search-input-wrap { display: flex; align-items: center; gap: 10px; border: 1.5px solid var(--brand-dark); border-radius: 14px; padding: 12px 18px; background: #fff; max-width: 660px; transition: box-shadow 0.2s; }
.search-input-wrap:focus-within { box-shadow: 0 0 0 4px rgba(26,28,24,0.1); }
.search-input-wrap svg { flex-shrink: 0; color: var(--brand-muted); }
.search-input-wrap input { flex: 1; border: 0; outline: 0; background: transparent; font-size: 15px; font-family: inherit; color: var(--brand-dark); }
.search-input-wrap button { border: 0; background: var(--brand-dark); color: #fff; padding: 8px 18px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; letter-spacing: 0.04em; white-space: nowrap; transition: opacity 0.2s; }
.search-input-wrap button:hover { opacity: 0.85; }
.search-meta { font-size: 14px; color: var(--brand-muted); }
.search-meta strong { color: var(--brand-dark); }
.search-empty-prompt, .search-no-results { text-align: center; padding: 80px 20px; color: var(--brand-muted); }
.search-no-results h2 { font-size: clamp(28px,5vw,56px); font-weight: 500; letter-spacing: -0.03em; margin: 12px 0 16px; color: var(--brand-dark); }
.search-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 24px; }
.search-card { text-decoration: none; color: var(--brand-dark); display: flex; flex-direction: column; transition: transform 0.25s; }
.search-card:hover { transform: translateY(-4px); }
.search-card-image { aspect-ratio: 3/4; border-radius: 12px; overflow: hidden; background: var(--brand-surface); }
.search-card-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
.search-card:hover .search-card-image img { transform: scale(1.04); }
.search-card-placeholder { width: 100%; height: 100%; }
.search-card-info { padding: 12px 4px 0; }
.search-card-cat { font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--brand-muted); display: block; margin-bottom: 4px; }
.search-card-info h3 { font-size: 14px; font-weight: 600; margin: 0 0 6px; }
.search-card-info strong { font-size: 14px; font-weight: 700; }
.search-pagination { display: flex; gap: 8px; justify-content: center; margin-top: 48px; flex-wrap: wrap; }
.search-pagination a { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--brand-border); font-size: 14px; text-decoration: none; color: var(--brand-dark); transition: all 0.2s; }
.search-pagination a.active, .search-pagination a:hover { background: var(--brand-dark); color: #fff; border-color: var(--brand-dark); }
</style>
</body>
</html>
