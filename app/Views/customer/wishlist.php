<div class="mb-8">
    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]"><?= __('My Account') ?></p>
    <h1 class="mt-2 text-3xl font-medium text-[#252820]"><?= __('Wishlist') ?></h1>
    <p class="mt-2 text-sm text-[#77796f]"><?= __('Pieces you have saved to your private collection. Add to bag or review availability at any time.') ?></p>
</div>

<?php if (empty($products)): ?>
    <div class="rounded-2xl border border-dashed border-[#d6d6cd] bg-[#fbfbf7] p-12 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#f0f2eb] text-[#687158] mb-4">
            <i data-lucide="bookmark" class="h-8 w-8"></i>
        </div>
        <h3 class="text-lg font-medium text-[#252820]"><?= __('Your bag is empty') ?></h3>
        <p class="mt-2 max-w-md mx-auto text-sm text-[#77796f]"><?= __('Add something considered from the collection.') ?></p>
        <div class="mt-6">
            <a href="<?= e(($base ?: '') . '/store') ?>" class="inline-flex items-center gap-2 rounded-xl bg-[#252820] px-6 py-3 text-xs font-semibold text-white transition hover:bg-[#687158]">
                <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i> <?= __('Shop the collection') ?>
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($products as $p): ?>
            <?php 
                $price = (float)($p['price'] ?? 0);
                $comparePrice = !empty($p['compare_at_price']) ? (float)$p['compare_at_price'] : 0;
                $imageUrl = catalogue_image_for($p);
                $inStock = (int)($p['quantity'] ?? 0) > 0;
                $productUrl = ($base ?: '') . '/store/product/' . urlencode((string)($p['slug'] ?? $p['id']));
            ?>
            <article class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-[#e5e5dd] bg-white transition hover:shadow-lg" id="saved-item-<?= (int)$p['id'] ?>">
                <div>
                    <!-- Image Box -->
                    <div class="relative aspect-[3/4] w-full overflow-hidden bg-[#f4f3ed]">
                        <a href="<?= e($productUrl) ?>">
                            <img src="<?= e(media_url($base, $imageUrl)) ?>" alt="<?= e($p['name']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&q=80';">
                        </a>
                        
                        <!-- Remove button -->
                        <button type="button" 
                                onclick="removeSavedItem(<?= (int)$p['id'] ?>)" 
                                title="<?= e(__('Remove')) ?>"
                                class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-[#252820] shadow-sm backdrop-blur-md transition hover:bg-[#e74c3c] hover:text-white">
                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                        </button>
                        
                        <?php if ($comparePrice > $price): ?>
                            <span class="absolute left-3 top-3 rounded-full bg-[#252820] px-2.5 py-1 text-[10px] font-bold tracking-wider text-white">
                                <?= __('Sale') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Details -->
                    <div class="p-5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#687158]"><?= e($p['category'] ?? __('Noir Piece')) ?></p>
                        <h3 class="mt-1 text-base font-semibold text-[#252820] line-clamp-1">
                            <a href="<?= e($productUrl) ?>" class="hover:underline">
                                <?= e($p['name']) ?>
                            </a>
                        </h3>
                        
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-base font-bold text-[#252820]"><?= format_currency($price) ?></span>
                            <?php if ($comparePrice > $price): ?>
                                <span class="text-xs text-[#92948a] line-through"><?= format_currency($comparePrice) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="border-t border-[#f0f0ea] p-5 pt-3">
                    <?php if ($inStock): ?>
                        <button type="button" 
                                onclick="moveToCart(<?= (int)$p['id'] ?>)" 
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#252820] py-3 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-[#687158]">
                            <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                            <span><?= __('Add to cart') ?></span>
                        </button>
                    <?php else: ?>
                        <button disabled class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-[#e5e5dd] py-3 text-xs font-semibold uppercase tracking-wider text-[#92948a]">
                            <span><?= __('Out of stock') ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function removeSavedItem(productId) {
    if (!confirm('Remove this piece from your saved items?')) return;
    
    $.post('<?= e(($base ?: '') . '/customer/ajax/wishlist-remove') ?>', {
        product_id: productId
    }).done(function(res) {
        if (typeof customerToast === 'function') {
            customerToast(res.message || 'Item removed from saved list.');
        }
        $('#saved-item-' + productId).fadeOut(300, function() { 
            $(this).remove(); 
            if ($('article[id^="saved-item-"]').length === 0) {
                location.reload();
            }
        });
    }).fail(function() {
        if (typeof customerToast === 'function') {
            customerToast('Failed to remove item. Please try again.');
        }
    });
}

function moveToCart(productId) {
    $.post('<?= e(($base ?: '') . '/customer/ajax/cart-add') ?>', {
        product_id: productId,
        quantity: 1
    }).done(function(res) {
        if (typeof customerToast === 'function') {
            customerToast(res.message || 'Piece added to shopping bag.');
        }
        // Update header cart count badge if available
        const badge = $('#header-cart-count, .cart-count-badge');
        if (badge.length) {
            const count = parseInt(badge.text() || '0') + 1;
            badge.text(count).show();
        }
    }).fail(function(xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Failed to add item to bag.';
        if (typeof customerToast === 'function') {
            customerToast(msg);
        }
    });
}
</script>
