<?php
declare(strict_types=1);

$items = $items ?? [];
$isLoggedIn = $isLoggedIn ?? false;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Wishlist — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta('Wishlist — ' . $storeName, 'Your saved items synced across mobile app and web.') ?>
    <?= store_brand_style() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>

<main class="wishlist-page">
    <?= store_breadcrumbs([['name' => 'Home', 'url' => $publicPath . '/'], ['name' => 'Wishlist', 'url' => '']]) ?>

    <header class="page-header">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <h1>Saved Items</h1>
                <p style="color:var(--brand-muted);font-size:14px;margin-top:4px;">
                    <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> saved • Synced with your Noir Mobile App
                </p>
            </div>
            <?php if (!$isLoggedIn): ?>
                <a href="<?= e($publicPath . '/login') ?>" class="button secondary" style="font-size:13px;padding:8px 16px;">Sign In to Sync Across Devices</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (empty($items)): ?>
        <div class="wishlist-empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.5; margin-bottom: 16px;">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
            <h2>Your wishlist is empty</h2>
            <p>You haven't saved any items yet. Tap the heart icon on any product in the web store or in the mobile app to save it for later.</p>
            <a href="<?= e($publicPath . '/store') ?>" class="button">Explore Catalogue</a>
        </div>
    <?php else: ?>
        <div class="wishlist-grid">
            <?php foreach ($items as $item): ?>
                <?php
                    $prodUrl = $publicPath . '/store/product/' . rawurlencode((string)($item['slug'] ?: $item['public_id'] ?: $item['id']));
                    $imgUrl = !empty($item['image']) ? $item['image'] : $publicPath . '/assets/img/placeholder.jpg';
                    if (!str_starts_with($imgUrl, 'http') && !str_starts_with($imgUrl, '/')) {
                        $imgUrl = $publicPath . '/' . ltrim($imgUrl, '/');
                    }
                ?>
                <article class="wishlist-card" id="wishlist-card-<?= (int)$item['id'] ?>">
                    <a href="<?= e($prodUrl) ?>" class="wishlist-image-wrap">
                        <img src="<?= e($imgUrl) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                    </a>
                    <div class="wishlist-card-body">
                        <div class="wishlist-meta">
                            <span class="wishlist-category"><?= e($item['category'] ?? 'Luxury') ?></span>
                            <?php if ((int)($item['quantity'] ?? 0) > 0): ?>
                                <span class="stock-badge in-stock">In Stock</span>
                            <?php else: ?>
                                <span class="stock-badge out-of-stock">Sold Out</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="wishlist-title">
                            <a href="<?= e($prodUrl) ?>"><?= e($item['name']) ?></a>
                        </h3>
                        <div class="wishlist-price">
                            <strong><?= e(store_currency_format((float)$item['price'])) ?></strong>
                            <?php if (!empty($item['compare_at_price']) && (float)$item['compare_at_price'] > (float)$item['price']): ?>
                                <del><?= e(store_currency_format((float)$item['compare_at_price'])) ?></del>
                            <?php endif; ?>
                        </div>
                        <div class="wishlist-actions">
                            <button type="button" class="button add-to-bag-btn" onclick="addToBag(<?= (int)$item['id'] ?>, this)">
                                Add to Bag
                            </button>
                            <button type="button" class="remove-btn" title="Remove from wishlist" onclick="removeFromWishlist(<?= (int)$item['id'] ?>)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/components/footer.php'; ?>

<style>
.wishlist-page { padding: clamp(30px,5vw,60px) clamp(16px,5vw,80px); background: var(--brand-paper); color: var(--brand-dark); min-height: 75vh; }
.page-header { margin: 20px 0 40px 0; border-bottom: 1px solid var(--brand-border); padding-bottom: 24px; }
.page-header h1 { font-size: clamp(30px, 4vw, 48px); font-weight: 500; letter-spacing: -0.03em; margin: 0; }

.wishlist-empty-state { text-align: center; padding: 80px 20px; background: var(--brand-surface); border: 1px dashed var(--brand-border); border-radius: 12px; max-width: 560px; margin: 40px auto; }
.wishlist-empty-state h2 { font-size: 22px; font-weight: 500; margin-bottom: 10px; }
.wishlist-empty-state p { color: var(--brand-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.6; }

.wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 24px; }
.wishlist-card { background: var(--brand-surface); border: 1px solid var(--brand-border); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; }
.wishlist-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(0,0,0,0.06); }
.wishlist-image-wrap { display: block; aspect-ratio: 4/5; overflow: hidden; background: #f0f0ec; }
.wishlist-image-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.wishlist-card:hover .wishlist-image-wrap img { transform: scale(1.04); }

.wishlist-card-body { padding: 16px; display: flex; flex-direction: column; flex: 1; }
.wishlist-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; }
.wishlist-category { color: var(--brand-muted); font-weight: 600; }
.stock-badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; }
.stock-badge.in-stock { background: #dcfce7; color: #15803d; }
.stock-badge.out-of-stock { background: #fee2e2; color: #b91c1c; }

.wishlist-title { font-size: 15px; font-weight: 500; margin: 0 0 10px 0; line-height: 1.35; }
.wishlist-title a { color: inherit; text-decoration: none; }
.wishlist-title a:hover { text-decoration: underline; }

.wishlist-price { font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.wishlist-price del { font-size: 13px; color: var(--brand-muted); }

.wishlist-actions { display: flex; gap: 8px; margin-top: auto; }
.wishlist-actions .add-to-bag-btn { flex: 1; font-size: 13px; padding: 10px 12px; border-radius: 8px; }
.wishlist-actions .remove-btn { background: none; border: 1px solid var(--brand-border); border-radius: 8px; width: 40px; display: flex; align-items: center; justify-content: center; color: #ef4444; cursor: pointer; transition: background 0.15s ease; }
.wishlist-actions .remove-btn:hover { background: #fee2e2; border-color: #fca5a5; }
</style>

<script>
async function addToBag(productId, btn) {
    const origText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Adding...';
    try {
        const res = await fetch('<?= e($publicPath) ?>/store/ajax/cart-add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id: productId, quantity: 1 })
        });
        const data = await res.json();
        if (data && data.success) {
            btn.innerText = 'Added to Bag';
            setTimeout(() => { btn.innerText = origText; btn.disabled = false; }, 1800);
            if (typeof window.NoirCartDrawer !== 'undefined' && typeof window.NoirCartDrawer.open === 'function') {
                window.NoirCartDrawer.open();
            }
        } else {
            alert(data.message || 'Unable to add to cart.');
            btn.innerText = origText;
            btn.disabled = false;
        }
    } catch (e) {
        btn.innerText = origText;
        btn.disabled = false;
    }
}

async function removeFromWishlist(productId) {
    if (!confirm('Remove this item from your wishlist?')) return;
    try {
        const res = await fetch('<?= e($publicPath) ?>/ajax/wishlist/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ product_id: productId })
        });
        const data = await res.json();
        if (data && data.success) {
            const card = document.getElementById('wishlist-card-' + productId);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => { card.remove(); location.reload(); }, 250);
            }
        }
    } catch (e) {}
}
</script>
</body>
</html>
