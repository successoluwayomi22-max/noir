<?php
$auth = \Noir\Core\Container::get('auth');
$isLoggedIn = $auth->check();
$user = $auth->user() ?? [];
$isCustomer = ($user['account_type'] ?? null) === 'customer' || in_array('customer', $user['roles'] ?? [], true);
$userRoles = $user['roles'] ?? [];
if ($isCustomer) {
    $profilePath = $publicPath . '/customer';
} elseif (in_array('super-admin', $userRoles, true) || in_array('admin', $userRoles, true)) {
    $profilePath = $publicPath . '/admin';
} elseif (in_array('vendor', $userRoles, true)) {
    $profilePath = $publicPath . '/vendor/dashboard';
} elseif (in_array('delivery', $userRoles, true)) {
    $profilePath = $publicPath . '/delivery/dashboard';
} elseif (in_array('finance', $userRoles, true)) {
    $profilePath = $publicPath . '/finance/dashboard';
} elseif ($isLoggedIn) {
    $profilePath = $publicPath . '/admin';
} else {
    $profilePath = $publicPath . '/login';
}
if ($isLoggedIn && class_exists('\Noir\App\Services\CartService')) {
    try {
        $db = \Noir\Core\Container::get('db');
        if ($db instanceof \PDO) {
            \Noir\App\Services\CartService::syncSessionCart($db, (int)($user['id'] ?? $user['user_id'] ?? 0));
        }
    } catch (\Throwable) {}
}
$cartCount = array_sum(array_map(static fn(array $item): int => (int)($item['quantity'] ?? 0), $_SESSION['cart'] ?? []));
$storeName = store_setting('store_name', 'Noir & Co.');
$brandDisplayName = $storeName;
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$isHome = rtrim($currentUri, '/') === rtrim($publicPath, '/');
$isShop = str_starts_with($currentUri, $publicPath . '/store');
$isAbout = str_starts_with($currentUri, $publicPath . '/about');
$isJournal = str_starts_with($currentUri, $publicPath . '/journal');
$isContact = str_starts_with($currentUri, $publicPath . '/contact');
?>
<style>
/* Essential header alignment for pages without store.min.css */
.shop-header {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 24px;
    padding: 22px 5vw;
    max-width: 100vw;
    box-sizing: border-box;
    overflow-x: clip;
}
.mobile-menu-panel, .mobile-menu-toggle {
    display: none;
}
.shop-header .nav-brand-lockup {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    max-width: 100%;
    letter-spacing: 0;
    text-transform: none;
    overflow: hidden;
}
.shop-header .nav-brand-mark {
    display: inline-grid;
    flex: 0 0 auto;
    width: 62px;
    height: 62px;
    place-items: center;
    overflow: hidden;
    border: 1px solid color-mix(in srgb,var(--brand-dark,#252820) 10%,transparent);
    border-radius: 4px;
    background: var(--brand-primary,#687158);
}
.shop-header .nav-brand-mark img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.shop-header .nav-brand-name {
    display: block;
    min-width: 0;
    color: var(--brand-dark,#252820);
    font-size: 18px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.shop-header nav.main-nav {
    display: flex;
    gap: 25px;
    font-size: 12px;
    color: #686b62;
}
.shop-header .shop-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 17px;
    font-size: 12px;
}
@media(max-width:950px){
    .shop-header { grid-template-columns: minmax(0,1fr) auto auto; gap: 10px; }
    .shop-header nav.main-nav { display: none !important; }
}
@media(max-width:820px){
    .shop-header .nav-brand-lockup { gap: 10px; }
    .shop-header .nav-brand-mark { width: 50px; height: 50px; }
    .shop-header .nav-brand-name { font-size: 15px; }
}
@media(max-width:560px){
    .shop-header {
        padding: 10px 14px;
        gap: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 56px;
    }
    .shop-header .nav-brand-lockup {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1 1 auto;
        text-decoration: none;
        overflow: hidden;
    }
    .shop-header .nav-brand-mark {
        display: inline-grid !important;
        width: 34px !important;
        height: 34px !important;
        flex: 0 0 34px;
        border-radius: 4px;
    }
    .shop-header .nav-brand-name {
        display: inline-block !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        color: var(--brand-dark, #252820) !important;
        line-height: 1.2;
        letter-spacing: -0.01em;
        max-width: 180px;
    }
    .shop-header .shop-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .shop-header .shop-actions .nav-icon {
        min-width: 44px;
        min-height: 44px;
        display: inline-grid;
        place-items: center;
        padding: 0;
    }
    .shop-header .mobile-menu-toggle {
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        width: 44px;
        height: 44px;
        min-width: 44px;
        min-height: 44px;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 10px 6px;
        margin-left: 6px;
    }
    .shop-header .mobile-menu-toggle span {
        width: 100%;
        height: 3px;
        background: var(--brand-dark, #252820);
        border-radius: 3px;
        transition: all 0.3s linear;
        transform-origin: center;
    }
    .shop-header .mobile-menu-toggle[aria-expanded="true"] span:nth-child(1) {
        transform: translateY(9px) rotate(45deg);
    }
    .shop-header .mobile-menu-toggle[aria-expanded="true"] span:nth-child(2) {
        opacity: 0;
    }
    .shop-header .mobile-menu-toggle[aria-expanded="true"] span:nth-child(3) {
        transform: translateY(-9px) rotate(-45deg);
    }
    .mobile-menu-panel {
        display: none;
        background: var(--brand-paper, #F7F7F2);
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        border: 1px solid var(--brand-border, #E8E6E1);
    }
    .mobile-menu-panel.is-open {
        display: grid;
        gap: 16px;
    }
    .mobile-menu-panel a {
        text-decoration: none;
        color: var(--brand-dark, #252820);
        font-size: 16px;
        font-weight: 500;
        padding: 12px 8px;
        min-height: 44px;
        display: flex;
        align-items: center;
    }
}
</style>
<header class="site-header shop-header">
    <a class="brand nav-brand-lockup" href="<?= e($publicPath.'/') ?>" aria-label="<?= e($brandDisplayName) ?> home">
        <?php if(store_setting('store_logo')): ?>
            <span class="nav-brand-mark"><?= store_logo_tag($publicPath, $storeName) ?></span>
        <?php else: ?>
            <span class="nav-brand-mark nav-brand-fallback" aria-hidden="true">NC</span>
        <?php endif; ?>
        <span class="nav-brand-name"><?= e($brandDisplayName) ?></span>
    </a>
    <nav class="main-nav" aria-label="Main navigation">
        <a href="<?= e($publicPath.'/') ?>" class="<?= $isHome ? 'active' : '' ?>"><?= __('Home') ?></a>
        <a href="<?= e($publicPath.'/store') ?>" class="<?= $isShop ? 'active' : '' ?>"><?= __('Shop') ?></a>
        <a href="<?= e($publicPath.'/store#collections') ?>"><?= __('Collections') ?></a>
        <a href="<?= e($publicPath.'/about') ?>" class="<?= $isAbout ? 'active' : '' ?>"><?= __('Our story') ?></a>
        <a href="<?= e($publicPath.'/journal') ?>" class="<?= $isJournal ? 'active' : '' ?>"><?= __('Journal') ?></a>
        <a href="<?= e($publicPath.'/contact') ?>" class="<?= $isContact ? 'active' : '' ?>"><?= __('Contact') ?></a>
        <a href="<?= e($publicPath.'/download/app') ?>" class="nav-app-link" title="Download App" style="display:inline-flex;align-items:center;gap:5px;font-weight:600;color:var(--brand-primary,#687158);"><i data-lucide="smartphone" style="width:14px;height:14px;"></i> <?= __('App') ?></a>
    </nav>
    <div class="header-actions shop-actions">
        <a href="<?= e($publicPath.'/store') ?>#catalogue" class="nav-icon nav-search" aria-label="Search">
            <i data-lucide="search"></i>
        </a>

        <?php if($isLoggedIn): ?>
            <?php 
                $avatarImg = (string)($user['profile_image'] ?? $user['avatar'] ?? '');
                if ($avatarImg === '' && !empty($user['user_id'] ?? $user['id'])) {
                    $uId = (int)($user['user_id'] ?? $user['id']);
                    try {
                        $stmt = \Noir\Core\Container::get('db')->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
                        $stmt->execute([$uId]);
                        $avatarImg = (string)($stmt->fetchColumn() ?: '');
                    } catch (\Throwable) { $avatarImg = ''; }
                    if ($avatarImg === '') {
                        $avatarImg = (string)(store_setting('admin_profile_image_' . $uId) ?: store_setting('admin_profile_image_0'));
                    }
                }
            ?>
            <?php if($avatarImg !== ''): ?>
                <a href="<?= e($profilePath) ?>" class="nav-icon" aria-label="Account (<?= e($user['name'] ?? 'User') ?>)" title="<?= e($user['name'] ?? 'Account') ?>" style="display:inline-grid; place-items:center; width:34px; height:34px; border-radius:9999px; overflow:hidden; padding:0; border: 1.5px solid color-mix(in srgb,var(--brand-primary,#687158) 35%,transparent); box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <img src="<?= e(($publicPath?:'').$avatarImg) ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 9999px; display: block;">
                </a>
            <?php elseif($isCustomer): ?>
                <a href="<?= e($profilePath) ?>" class="nav-icon" aria-label="Account" title="<?= e($user['name'] ?? 'Account') ?>">
                    <i data-lucide="user"></i>
                </a>
            <?php else: ?>
                <a href="<?= e($profilePath) ?>" class="nav-icon" aria-label="Dashboard" title="<?= e($user['name'] ?? 'Dashboard') ?>">
                    <i data-lucide="layout-dashboard"></i>
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?= e($publicPath.'/login') ?>" class="nav-signin hide-on-mobile"><?= __('Sign in') ?></a>
        <?php endif; ?>
        <?php if($isCustomer): ?>
            <?php
                $storeUnreadNotifs = 0;
                try {
                    $uId = (int)($user['user_id'] ?? $user['id'] ?? 0);
                    if ($uId > 0) {
                        $storeUnreadNotifs = (int)\Noir\Core\Container::get('db')->query("SELECT COUNT(*) FROM customer_notifications WHERE user_id={$uId} AND read_at IS NULL")->fetchColumn();
                    }
                } catch (\Throwable) {}
            ?>
            <a href="<?= e($publicPath.'/customer/notifications') ?>" class="nav-icon hide-on-mobile" aria-label="Notifications" title="<?= e(__('Concierge Notifications')) ?>" style="position: relative; display: inline-flex; align-items: center; justify-content: center;">
                <i data-lucide="bell"></i>
                <?php if($storeUnreadNotifs > 0): ?>
                    <span style="position: absolute; top: -3px; right: -5px; min-width: 15px; height: 15px; border-radius: 9999px; background: #9f4f46; color: #ffffff; font-size: 9px; font-weight: 700; display: inline-grid; place-items: center; padding: 0 3px; line-height: 1;">
                        <?= $storeUnreadNotifs > 9 ? '9+' : $storeUnreadNotifs ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="<?= e($publicPath.'/customer/wishlist') ?>" class="nav-icon hide-on-mobile" aria-label="Wishlist">
                <i data-lucide="heart"></i>
            </a>
        <?php endif; ?>
        <button type="button" class="nav-bag shop-bag" aria-label="Open shopping bag" aria-haspopup="dialog" aria-expanded="false" aria-controls="offcanvas-cart" onclick="openOffcanvasCart()" style="background: none; border: none; cursor: pointer; padding: 0; display: inline-flex; align-items: center; justify-content: center; min-width: 44px; min-height: 44px; color: inherit;">
            <i data-lucide="shopping-bag"></i>
            <sup data-cart-count><?= $cartCount ?></sup>
        </button>
    </div>
    <button type="button" class="mobile-menu-toggle" aria-label="Open main navigation" aria-expanded="false" aria-controls="store-mobile-menu">
        <span></span><span></span><span></span>
    </button>
    <nav id="store-mobile-menu" class="mobile-menu-panel" aria-label="Mobile navigation">
        <a href="<?= e($publicPath.'/') ?>" class="<?= $isHome ? 'active' : '' ?>"><?= __('Home') ?></a>
        <a href="<?= e($publicPath.'/store') ?>" class="<?= $isShop ? 'active' : '' ?>"><?= __('Shop') ?></a>
        <a href="<?= e($publicPath.'/search') ?>"><?= __('Search') ?></a>
        <a href="<?= e($publicPath.'/about') ?>" class="<?= $isAbout ? 'active' : '' ?>"><?= __('About') ?></a>
        <a href="<?= e($publicPath.'/journal') ?>" class="<?= $isJournal ? 'active' : '' ?>"><?= __('Journal') ?></a>
        <a href="<?= e($publicPath.'/contact') ?>" class="<?= $isContact ? 'active' : '' ?>"><?= __('Contact') ?></a>
        
        <hr style="border: 0; border-top: 1px solid var(--brand-border, #e8e9e0); margin: 8px 0;">
        
        <a href="<?= e($publicPath.'/download/app') ?>" style="display:flex;align-items:center;gap:8px;font-weight:600;color:var(--brand-primary,#687158);background:rgba(104,113,88,0.06);padding:10px 14px;border-radius:8px;margin-bottom:6px;">
            <i data-lucide="smartphone" style="width:16px;height:16px;"></i> <?= __('Download App') ?>
        </a>

        <?php if(!$isLoggedIn): ?>
            <a href="<?= e($publicPath.'/login') ?>"><?= __('Sign in') ?></a>
        <?php endif; ?>
        <a href="<?= e($publicPath.'/track-order') ?>"><?= __('Track Order') ?></a>
    </nav>
</header>
<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.mobile-menu-toggle[aria-controls]').forEach(function (toggle) {
        if (toggle.dataset.noirMenuReady) return;
        var panel = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!panel) return;
        toggle.dataset.noirMenuReady = 'true';
        function placePanel() {
            var header = toggle.closest('.site-header, .shop-header');
            var bottom = header ? header.getBoundingClientRect().bottom : toggle.getBoundingClientRect().bottom;
            var top = Math.max(8, Math.round(bottom + 8));
            document.documentElement.style.setProperty('--store-mobile-menu-top', top + 'px');
            panel.style.position = 'fixed';
            panel.style.top = top + 'px';
            panel.style.left = 'max(14px, env(safe-area-inset-left))';
            panel.style.right = 'max(14px, env(safe-area-inset-right))';
            panel.style.zIndex = '70';
            panel.style.maxHeight = 'calc(100dvh - ' + top + 'px - 12px)';
            panel.style.overflowY = 'auto';
        }
        function setOpen(open) {
            panel.classList.toggle('is-open', open);
            panel.style.display = open ? 'grid' : '';
            if (open) {
                placePanel();
                window.requestAnimationFrame(placePanel);
            }
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close main navigation' : 'Open main navigation');
        }
        toggle.addEventListener('click', function () {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });
        panel.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () { setOpen(false); });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setOpen(false);
        });
        window.addEventListener('resize', function () {
            if (panel.classList.contains('is-open')) placePanel();
        });
        window.addEventListener('scroll', function () {
            if (panel.classList.contains('is-open')) placePanel();
        }, { passive: true });
    });

    // Real-Time Cross-Device Cart Sync Hook
    function refreshLiveCartBadge() {
        fetch('<?= e($publicPath) ?>/ajax/cart/count', { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d && typeof d.count === 'number') {
                    document.querySelectorAll('[data-cart-count]').forEach(function(el) {
                        el.textContent = d.count;
                    });
                }
            }).catch(function() {});
    }
    window.addEventListener('focus', refreshLiveCartBadge);
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') refreshLiveCartBadge();
    });
    setInterval(refreshLiveCartBadge, 30000);
});
</script>
