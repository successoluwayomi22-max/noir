<?php
$image = $product['featured_image'] ?? '';
if (!$image) {
    $image = catalogue_image_for($product);
}
$fallbackImage = catalogue_image_for($product) ?: '/uploads/catalogue/tshirt.jpg';
$discount = (!empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['price'])
    ? round((1 - ((float) $product['price'] / (float) $product['compare_at_price'])) * 100)
    : 0;
$storeUser = \Noir\Core\Container::get('auth')->user();
$storeUserId = (int) ($storeUser['user_id'] ?? $storeUser['id'] ?? 0);
$wishlistUrl = $publicPath . '/customer/ajax/wishlist-add';
$cartUrl = $publicPath . '/store/ajax/cart-add';
$productUrl = $publicPath . '/store/product/' . rawurlencode((string) ($product['slug'] ?: $product['id']));
$loginUrl = $publicPath . '/login';
$csrf = csrf_token();
$wishlistAction = $storeUserId
    ? "storeWishlist(this," . (int) $product['id'] . ",'" . e($wishlistUrl) . "','" . e($csrf) . "')"
    : "window.location.href='" . e($loginUrl) . "'";
$cartAction = "storeCart(this," . (int) $product['id'] . ",'" . e($cartUrl) . "','" . e($csrf) . "')";
$meta = implode(' &middot; ', array_map('e', array_filter([$product['colors'] ?? '', $product['sizes'] ?? ''])));
?>
<article class="store-card">
    <div class="store-card-media">
        <a href="<?= e($productUrl) ?>" aria-label="View <?= e($product['name']) ?>">
            <img src="<?= e(media_url($publicPath, $image ?: $fallbackImage, 420)) ?>" alt="<?= e($product['name']) ?>" loading="lazy" decoding="async" width="300" height="360" onerror="this.onerror=null;this.src='<?= e(media_url($publicPath, $fallbackImage, 420)) ?>'">
        </a>
        <div class="store-card-badges">
            <?php if ($discount): ?><span class="card-badge sale">-<?= $discount ?>%</span><?php endif; ?>
            <?php if (($product['best_seller'] ?? false)): ?><span class="card-badge">Best seller</span><?php endif; ?>
            <?php if (($product['featured'] ?? false)): ?><span class="card-badge">Featured</span><?php endif; ?>
        </div>
        <button class="wishlist-button" type="button" aria-label="Save <?= e($product['name']) ?>" onclick="<?= e($wishlistAction) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path></svg>
        </button>
        <a class="quick-view" href="<?= e($productUrl) ?>">Quick view</a>
    </div>
    <div class="store-card-body">
        <div>
            <a class="store-card-title" href="<?= e($productUrl) ?>"><?= e($product['name']) ?></a>
            <p class="store-card-brand"><?= e($product['brand'] ?: ($product['category'] ?: 'Noir collection')) ?></p>
        </div>
        <div class="store-card-price">
            <strong><?= format_currency($product['price']) ?></strong>
            <?php if ($discount): ?><del><?= format_currency($product['compare_at_price']) ?></del><?php endif; ?>
        </div>
    </div>
    <?php if ($meta !== ''): ?><p class="store-card-meta"><?= $meta ?></p><?php endif; ?>
    <button type="button" class="store-card-cart" onclick="<?= e($cartAction) ?>">Add to cart</button>
</article>
