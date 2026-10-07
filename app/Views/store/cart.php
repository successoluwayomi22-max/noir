<?php $total=0;$cartNotice=(string)($_SESSION['cart_notice']??'');unset($_SESSION['cart_notice']); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?= store_seo_meta(title: 'Your cart — ' . $storeName, description: 'Review your selection at ' . $storeName, image: store_setting('store_logo')) ?><link rel="stylesheet" href="<?= e($publicPath.'/assets/css/app.min.css') ?>"><script defer src="<?= e($publicPath.'/assets/js/lucide.min.js?v=1.0.0') ?>"></script><?= store_brand_style() ?></head><body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>
<main class="cart-page"><nav class="breadcrumbs" aria-label="Breadcrumb" style="font-size: 12px; color: var(--brand-muted); display: flex; gap: 8px; align-items: center; margin-bottom: 40px;"><a href="<?= e($publicPath.'/') ?>" style="color: var(--brand-dark); text-decoration: none; font-weight: 500;"><?= __('Home') ?></a> <span>/</span> <span aria-current="page"><?= __('Cart') ?></span></nav><section class="cart-heading"><p class="eyebrow"><?= __('Your selection') ?></p><h1><?= __('Cart') ?>.</h1><p><?= __('Review your pieces before you continue to checkout.') ?></p><?php if($cartNotice): ?><div class="cart-notice" role="status"><i data-lucide="check-circle-2"></i><?= e($cartNotice) ?></div><?php endif; ?></section><section class="cart-content"><?php if(!$cart): ?><div class="cart-empty"><i data-lucide="shopping-bag"></i><h2><?= __('Your cart is empty.') ?></h2><p><?= __('Add something considered from the collection.') ?></p><a class="button" href="<?= e($publicPath.'/store') ?>"><?= __('Explore collection') ?> ↗</a></div><?php else: ?><div class="cart-list"><?php foreach($cart as $item): $line=(float)$item['price']*(int)$item['quantity'];$total+=$line; ?><article class="cart-item"><div class="cart-image"><?php if(!empty($item['image'])): ?><img src="<?= e(media_url($publicPath,$item['image'])) ?>" alt="<?= e($item['name']) ?>"><?php else: ?><span><?= e(substr($item['name'],0,1)) ?></span><?php endif; ?></div><div class="cart-item-copy"><h2><?= e($item['name']) ?></h2><p><?= __('Quantity') ?>: <?= (int)$item['quantity'] ?></p><strong><?= format_currency($line) ?></strong><form method="post" action="<?= e($publicPath.'/cart/remove') ?>"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e((string)$item['id']) ?>"><button class="cart-remove" type="submit"><?= __('Remove') ?></button></form></div></article><?php endforeach; ?></div><aside class="cart-summary"><p class="eyebrow"><?= __('Order Summary') ?></p><div><span><?= __('Subtotal') ?></span><strong><?= format_currency($total) ?></strong></div><p><?= __('Delivery and taxes are calculated at checkout.') ?></p><a class="button" href="<?= e($publicPath.'/checkout') ?>"><?= __('Proceed to Checkout') ?> <span>↗</span></a></aside><?php endif; ?></section></main>
<?php require __DIR__ . '/components/footer.php'; ?>
<style>
/* ── Cart Page ─────────────────────────────────── */
.cart-page{background:var(--brand-paper,#f7f7f2);color:var(--brand-dark,#252820);min-height:70vh;padding:clamp(50px,10vw,100px) clamp(20px,8vw,80px)}
.cart-heading h1{margin:14px 0;font-size:clamp(36px,7vw,90px);font-weight:500;letter-spacing:-.07em;line-height:1}
.cart-heading>p:last-of-type{color:#77796f;font-size:14px;margin-top:8px}
.cart-notice{display:flex;align-items:center;gap:10px;margin-top:24px;border:1px solid #c9d2bb;border-radius:14px;background:#eef2e8;padding:14px 18px;color:#536048;font-size:12px;font-weight:500}
.cart-notice svg{width:16px;flex:none}

/* ── Cart Layout ───────────────────────────────── */
.cart-content{display:grid;grid-template-columns:1.3fr 0.7fr;gap:clamp(24px,6vw,80px);align-items:start;margin-top:clamp(40px,6vw,70px)}
.cart-list{display:grid;gap:0}

/* ── Cart Item ─────────────────────────────────── */
.cart-item{display:flex;gap:clamp(14px,3vw,24px);border-top:1px solid #deded6;padding:clamp(14px,3vw,22px) 0;align-items:flex-start}
.cart-item:first-child{border-top:none;padding-top:0}
.cart-image{width:clamp(80px,14vw,120px);aspect-ratio:1;flex:none;display:grid;place-items:center;overflow:hidden;border-radius:12px;background:#e8e8df}
.cart-image img{width:100%;height:100%;object-fit:cover}
.cart-image span{font-size:36px;color:#687158}
.cart-item-copy{display:flex;flex:1;flex-direction:column;align-items:flex-start;gap:6px;min-width:0}
.cart-item-copy h2{font-size:clamp(14px,2.5vw,18px);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%}
.cart-item-copy p{color:#85877d;font-size:12px;margin:0}
.cart-item-copy strong{font-size:14px;font-weight:700;color:var(--brand-dark,#252820)}
.cart-remove{border:0;background:transparent;color:#a75b52;font-size:11px;font-weight:600;text-decoration:none;cursor:pointer;padding:6px 0;border-radius:6px;transition:color .2s ease}
.cart-remove:hover{color:#7a3d35}

/* ── Cart Summary ──────────────────────────────── */
.cart-summary{position:sticky;top:100px;border-radius:20px;background:var(--brand-dark,#252820);color:var(--brand-on-dark,#f7f7f2);padding:clamp(22px,3vw,32px)}
.cart-summary .eyebrow{color:#9ca49a;letter-spacing:.18em;font-size:10px}
.cart-summary>div{display:flex;justify-content:space-between;align-items:center;border-top:1px solid rgba(255,255,255,.12);border-bottom:1px solid rgba(255,255,255,.12);margin:22px 0;padding:18px 0;font-size:14px}
.cart-summary>div strong{font-size:18px;font-weight:700}
.cart-summary p:not(.eyebrow){color:#9ca49a;font-size:12px;line-height:1.7;margin-bottom:18px}
.cart-summary .button{display:flex;width:100%;align-items:center;justify-content:space-between;border-radius:12px;background:var(--brand-primary,#687158);color:#fff;padding:15px 20px;font-size:13px;font-weight:600;letter-spacing:.02em;transition:background .25s ease,transform .2s ease}
.cart-summary .button:hover{background:#555d48;transform:translateY(-1px)}

/* ── Empty Cart ────────────────────────────────── */
.cart-empty{text-align:center;padding:clamp(40px,8vw,80px);border:1px dashed #d3d5ca;border-radius:20px}
.cart-empty svg{margin:auto;color:#687158;width:40px;height:40px}
.cart-empty h2{margin-top:18px;font-size:22px;font-weight:500}
.cart-empty p{margin:10px 0 24px;color:#77796f;font-size:14px}

/* ── Admin User (Sign out / Back to dashboard) ── */
.admin-user{display:flex;align-items:center;gap:12px;font-size:12px}
.admin-user span{color:#85877d;font-size:11px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.admin-user a,.admin-user button{display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(37,40,32,.15);border-radius:10px;background:transparent;padding:8px 14px;color:var(--brand-primary,#687158);font-size:11px;font-weight:600;letter-spacing:.02em;cursor:pointer;text-decoration:none;transition:background .22s ease,color .22s ease,border-color .22s ease,transform .22s ease;white-space:nowrap}
.admin-user a:hover,.admin-user button:hover{background:var(--brand-primary,#687158);border-color:var(--brand-primary,#687158);color:#fff;transform:translateY(-1px)}
.admin-user form{margin:0;padding:0}
.admin-user button[type="submit"]{border-color:rgba(167,91,82,.3);color:#a75b52}
.admin-user button[type="submit"]:hover{background:#a75b52;border-color:#a75b52;color:#fff}

/* ── Footer ─────────────────────────────────────  */
.site-footer{display:flex;justify-content:space-between;align-items:center;padding:clamp(24px,4vw,40px) clamp(20px,8vw,80px);color:#85877d;font-size:12px;border-top:1px solid #e5e5dd}

/* ── Responsive ──────────────────────────────────  */
@media(max-width:768px){
  .cart-content{grid-template-columns:1fr;gap:32px}
  .cart-summary{position:static;order:1}
  .cart-list{order:2}
}
@media(max-width:480px){
  .cart-item{gap:12px}
  .cart-image{width:70px}
  .cart-item-copy h2{font-size:13px}
  .site-footer{flex-direction:column;gap:12px;align-items:flex-start}
}
</style>
<script>lucide.createIcons()</script></body></html>
