<?php
declare(strict_types=1);

/**
 * Writes the web-app manifest file with the current store branding.
 */
function sync_store_manifest(string $name, string $paper, string $dark): void
{
    $icons = [];
    if (file_exists(BASE_PATH . '/public/favicon-192x192.png')) {
        $icons[] = [
            'src'     => '/favicon-192x192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any',
        ];
        $icons[] = [
            'src'     => '/favicon-192x192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'maskable',
        ];
    }
    if (file_exists(BASE_PATH . '/public/favicon-512x512.png')) {
        $icons[] = [
            'src'     => '/favicon-512x512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any',
        ];
        $icons[] = [
            'src'     => '/favicon-512x512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'maskable',
        ];
    }
    if (empty($icons)) {
        $logo = (string) store_setting('store_logo');
        $iconSrc = '/assets/icons/noir.svg';
        $iconType = 'image/svg+xml';
        if ($logo !== '' && file_exists(BASE_PATH . '/public' . $logo)) {
            $iconSrc = $logo;
            $ext = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
            $iconType = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                default => 'image/png',
            };
        }
        $icons[] = [
            'src'     => $iconSrc,
            'sizes'   => '192x192',
            'type'    => $iconType,
            'purpose' => 'any',
        ];
        $icons[] = [
            'src'     => $iconSrc,
            'sizes'   => '512x512',
            'type'    => $iconType,
            'purpose' => 'maskable',
        ];
    }

    $manifest = [
        'id'          => 'noir-luxury-atelier',
        'name'        => $name,
        'short_name'  => mb_substr($name, 0, 12),
        'description' => 'Considered luxury design for the everyday atelier.',
        'start_url'   => '/?source=pwa',
        'scope'       => '/',
        'display'     => 'standalone',
        'orientation' => 'portrait-primary',
        'background_color' => $paper,
        'theme_color'      => $dark,
        'categories'  => ['shopping', 'lifestyle'],
        'icons'       => $icons,
        'shortcuts'   => [
            [
                'name' => 'Store Catalogue',
                'short_name' => 'Shop',
                'description' => 'Browse the latest releases',
                'url' => '/store#catalogue',
                'icons' => [['src' => '/favicon-192x192.png', 'sizes' => '192x192']]
            ],
            [
                'name' => 'Shopping Bag',
                'short_name' => 'Bag',
                'description' => 'View your bag',
                'url' => '/cart',
                'icons' => [['src' => '/favicon-192x192.png', 'sizes' => '192x192']]
            ]
        ],
        'prefer_related_applications' => false,
    ];

    $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents(BASE_PATH . '/public/manifest.webmanifest', $json);
    @file_put_contents(BASE_PATH . '/public/manifest.json', $json);
}

/**
 * Returns an <img> or empty string for the store logo, using the configured upload path.
 */
function store_logo_tag(string $base, string $alt = ''): string
{
    $logo = store_setting('store_logo');

    if (!$logo) {
        return '';
    }

    $src   = rtrim($base, '/') . '/' . ltrim($logo, '/');
    $label = $alt !== '' ? $alt : store_setting('store_name', 'Noir & Co.');

    return '<span class="store-brand-with-logo">'
        . '<img class="store-logo-image" src="' . e($src) . '" alt="' . e($label) . '">'
        . '</span>';
}

/**
 * Returns an avatar element (image or initials) for the currently signed-in account.
 */
function store_account_avatar(string $base, array $user): string
{
    $userId = (int) ($user['user_id'] ?? $user['id'] ?? 0);
    $name   = trim((string) ($user['name'] ?? $user['display_name'] ?? 'Account'));
    $image  = (string) ($user['profile_image'] ?? $user['avatar'] ?? '');

    if ($image === '' && $userId) {
        try {
            $stmt = \Noir\Core\Container::get('db')->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $image = (string) ($stmt->fetchColumn() ?: '');
        } catch (\Throwable) {
            $image = '';
        }
        if ($image === '') {
            $image = (string) (store_setting('admin_profile_image_' . $userId) ?: store_setting('admin_profile_image_0'));
        }
    }

    if ($image !== '') {
        return '<img class="account-avatar-image" src="' . e(($base ?: '') . $image) . '" alt="' . e($name) . ' profile picture" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;display:block;">';
    }

    $parts    = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: ['A'];
    $initials = strtoupper(
        substr($parts[0], 0, 1) . (count($parts) > 1 ? substr($parts[count($parts) - 1], 0, 1) : ''),
    );

    return '<span class="account-avatar-initials" aria-hidden="true" style="font-weight:600;font-size:12px;">' . e($initials) . '</span>';
}

/**
 * Returns a WCAG-safe foreground colour (light or dark) for the given background hex.
 */
function brand_contrast(string $hex, string $light = '#fff', string $dark = '#252820'): string
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return $light;
    }

    $channels  = array_map(static fn(string $part): float => hexdec($part) / 255, str_split($hex, 2));
    $linear    = static fn(float $c): float => $c <= .03928 ? $c / 12.92 : (($c + .055) / 1.055) ** 2.4;
    $luminance = .2126 * $linear($channels[0]) + .7152 * $linear($channels[1]) + .0722 * $linear($channels[2]);

    return $luminance > .48 ? $dark : $light;
}

/**
 * Returns the HTML/CSS/JS block that injects brand colours, the preloader,
 * avatar, dark-mode toggle, PWA install prompt, and guest banner into every
 * customer-facing page.
 */
function store_brand_style(): string
{
    $primary    = e(store_setting('brand_primary_color', '#687158'));
    $dark       = e(store_setting('brand_dark_color', '#252820'));
    $accent     = e(store_setting('brand_accent_color', '#b9c1a4'));
    $paper      = e(store_setting('brand_paper_color', '#f7f7f2'));
    $onPrimary  = e(brand_contrast($primary));
    $onDark     = e(brand_contrast($dark));
    $onAccent   = e(brand_contrast($accent, '#252820', '#fff'));

    $authUser = [];
    try {
        $authUser = \Noir\Core\Container::get('auth')->user() ?? [];
    } catch (\Throwable) {
        $authUser = [];
    }

    $publicBase    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $isGuest       = !empty($authUser['is_guest']);
    $accountMarkup = ($authUser && is_array($authUser))
        ? store_account_avatar($publicBase, $authUser)
        : 'Account';
    $accountLabel  = $isGuest
        ? 'Guest account'
        : (($authUser && is_array($authUser))
            ? (string) ($authUser['name'] ?? $authUser['display_name'] ?? 'Account')
            : 'Account');
    $storeName     = e(store_setting('store_name', 'Noir & Co.'));
    $preloaderName = store_setting('store_name', 'Noir & Co.');
    $established   = e(store_setting('hero_established', '2026'));

    $storeLogo   = (string) store_setting('store_logo');
    $favVer      = file_exists(BASE_PATH . '/public/favicon-32x32.png')
        ? (string) filemtime(BASE_PATH . '/public/favicon-32x32.png')
        : '1';
    $faviconUrl  = ($storeLogo !== '' && file_exists(BASE_PATH . '/public' . $storeLogo))
        ? $publicBase . $storeLogo
        : $publicBase . '/assets/icons/noir.svg';
    $faviconExt  = strtolower(pathinfo((string) parse_url($faviconUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
    $faviconMime = match ($faviconExt) {
        'svg'     => 'image/svg+xml',
        'png'     => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp'    => 'image/webp',
        'ico'     => 'image/x-icon',
        default   => 'image/png',
    };

    $requestPath        = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $isAdminPage        = (bool) preg_match('#/admin(?:/|$)#', $requestPath);
    $isAdminDashboard   = (bool) preg_match('#/admin(?:/dashboard)?$#', $requestPath);
    $adminAssistant     = ($isAdminPage && !$isAdminDashboard) ? ai_assistant_assets($publicBase) : '';

    $guestBannerScript = $isGuest
        ? '<script nonce="' . csp_nonce() . '">document.addEventListener("DOMContentLoaded",function(){if(!document.body.matches(".store-page,.shop-page,.product-page"))return;var banner=document.createElement("div");banner.className="guest-mode-store-banner";banner.innerHTML="<strong>Guest mode</strong><span>Your data is private to this session.</span><a href=\\"' . e($publicBase . '/register') . '\\">Register to keep it</a>";document.body.appendChild(banner)});</script>'
        : '';

    // Keep one palette contract for every customer-facing view. The semantic
    // aliases let existing page-specific styles inherit the admin palette,
    // while the contrast-safe foregrounds remain readable on dark surfaces.
    return '<meta name="store-name" content="' . $storeName . '">'
        . '<meta name="store-established" content="' . $established . '">'
        . '<meta name="store-csrf-token" content="' . e(csrf_token()) . '">'
        . '<script nonce="' . csp_nonce() . '">window.lucide=window.lucide||{createIcons:function(){}};</script>'
        . '<script defer src="' . e(asset_url($publicBase, '/assets/js/install.js')) . '"></script>'
        . '<script defer src="' . e(asset_url($publicBase, '/assets/js/storefront-motion.js')) . '" data-analytics-endpoint="' . e($publicBase . '/analytics/storefront') . '"></script>'
        . '<style>'
        . ':root{--brand-primary:' . $primary . ';--brand-dark:' . $dark . ';--brand-accent:' . $accent . ';--brand-paper:' . $paper . ';--brand-name:"' . $preloaderName . '";--brand-surface:color-mix(in srgb,var(--brand-paper),var(--brand-dark) 6%);--brand-elevated:color-mix(in srgb,var(--brand-paper),var(--brand-dark) 12%);--brand-border:color-mix(in srgb,var(--brand-paper),var(--brand-dark) 14%);--brand-muted:#383b32;--brand-on-dark:' . $onDark . ';--brand-on-primary:' . $onPrimary . ';--brand-on-accent:' . $onAccent . ';--brand-olive:' . $primary . ';--brand-soft-olive:' . $accent . '}'
        . '.sr-only{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}'
        . '@keyframes pageLoadProgress{0%{transform:scaleX(0)}70%{transform:scaleX(.75)}100%{transform:scaleX(1);opacity:0}}'
        . 'body::before{content:"";position:fixed;top:0;left:0;right:0;height:2px;background:var(--brand-primary);transform-origin:0 0;z-index:10000;pointer-events:none;animation:pageLoadProgress .35s ease-out forwards}'
        . '.store-page,.shop-page,.product-page,.customer-shell,.auth-page{background:var(--brand-paper)!important;color:var(--brand-dark)}'
        . '.store-page a,.shop-page a,.product-page a,.customer-shell a,.auth-page a{color:inherit}'
        . '.store-page .site-header,.shop-page .shop-header,.product-page .shop-header,.product-page .site-header,.customer-shell header,.auth-page main{background:var(--brand-paper)!important;border-color:var(--brand-border)!important}'
        . '.store-page .brand,.shop-page .brand,.product-page .brand,.shop-page .shop-header nav a.active,.shop-page .shop-header nav a:hover,.customer-shell .brand,.auth-page .brand{color:var(--brand-dark)!important}'
        . '.store-page .category-strip,.shop-page .shop-category-section,.cart-summary,.shop-page .shop-hero-empty,.customer-shell .customer-sidebar{background:var(--brand-dark)!important;color:var(--brand-on-dark)!important}'
        . '.store-page .catalogue,.shop-page .shop-catalogue,.customer-shell main{background:var(--brand-surface)!important}'
        . '.store-page .filter-button,.shop-page .shop-filter-button,.store-page .button,.shop-page .shop-button,.customer-shell button,.auth-page .button{background:var(--brand-primary)!important;color:var(--brand-on-primary)!important}'
        . '.cart-summary .button{background:var(--brand-accent)!important;color:var(--brand-on-accent)!important}'
        . '.store-page .eyebrow,.shop-page .shop-eyebrow,.customer-shell .eyebrow,.auth-page .eyebrow{color:var(--brand-primary)!important}'
        . '.store-page .ticker{background:var(--brand-primary)!important;color:var(--brand-on-primary)!important;border-color:var(--brand-primary)!important;animation:none!important;transform:none!important;width:100%;box-sizing:border-box}'
        . '.store-page .ticker{gap:1vw!important}'
        . '.store-page .ticker span{animation:tickerTextMove 28s linear infinite!important;transform:translateX(110vw);will-change:transform;contain:paint}'
        . '.store-page .ticker span:nth-child(2){animation-delay:1.5s!important}.store-page .ticker span:nth-child(3){animation-delay:3s!important}.store-page .ticker span:nth-child(4){animation-delay:4.5s!important}'
        . '@keyframes tickerTextMove{from{transform:translateX(110vw)}to{transform:translateX(-110vw)}}'
        . '.store-page .ticker span{color:inherit!important}'
        . '.store-page .category-strip .eyebrow,.shop-page .shop-category-section .shop-eyebrow,.shop-page .shop-hero .shop-eyebrow{color:#e4eccd!important}'
        . '.store-page input,.store-page select,.shop-page input,.shop-page select,.customer-shell input,.customer-shell textarea,.customer-shell select,.auth-page input,.auth-page select{border-color:var(--brand-border)!important;background:var(--brand-paper)!important;color:var(--brand-dark)!important}'
        . '.store-page .pagination-store,.shop-page .shop-pagination div,.product-page .text-[#687158],.customer-shell .text-[#687158],.auth-page .text-[#687158]{color:var(--brand-primary)!important}'
        . '.store-page .store-empty,.store-page .catalogue-heading p:not(.eyebrow),.shop-page .shop-catalogue-heading p:not(.shop-eyebrow),.shop-page .shop-collection-card small,.shop-page .shop-footer,.product-page footer,.customer-shell footer,.auth-page footer,.store-card-brand,.store-card-meta,.collection-tile small,.review-author{color:var(--brand-muted,#383b32)!important}'
        . '.store-card-price del{color:#4a4f43!important;text-decoration:line-through!important}'
        . '.store-page .cart-image span,.store-page .cart-empty svg,.shop-page .shop-collection-card>div>span,.shop-page .shop-category-image>span{color:var(--brand-primary)!important}'
        . '.account-avatar{display:inline-grid;width:30px;height:30px;place-items:center;overflow:hidden;border:1px solid var(--brand-border);border-radius:999px;background:var(--brand-accent);color:var(--brand-dark);font-size:10px;font-weight:700;letter-spacing:.04em;vertical-align:middle}'
        . '.account-avatar-image{width:100%;height:100%;object-fit:cover}'
        . '.account-avatar-initials{line-height:1}'
        . '.store-page .site-footer,.shop-page .shop-footer,.customer-shell .site-footer,.auth-page .site-footer{border-color:var(--brand-border)!important}'
        . '.store-page .hero-art{background:color-mix(in srgb,var(--brand-paper),var(--brand-dark) 14%)!important}.store-page .art-circle{background:var(--brand-primary)!important}.store-page .art-card{background:var(--brand-paper)!important;color:var(--brand-dark)!important}'
        . '.store-page .hero-art .art-card{overflow:hidden}.store-page .hero-art .art-card:has(.art-logo){background:transparent!important;box-shadow:none}'
        . '.store-page .hero-art .art-logo{display:block;width:100%;height:100%;max-width:none;max-height:none;object-fit:contain;padding:18px;box-sizing:border-box;margin:auto;filter:none}'
        . '.store-page .product-placeholder,.shop-page .product-placeholder{background:var(--brand-surface)!important;color:var(--brand-primary)!important}'
        . '.product-main-image{background:var(--brand-surface)!important}.product-main-image img{object-fit:contain!important;object-position:center;padding:1.25rem}'
        . '.product-thumb{background:var(--brand-surface)!important}.product-thumb img{object-fit:contain!important;object-position:center;padding:.2rem}'
        . '.store-page .newsletter{background:var(--brand-paper,#f7f7f2)!important;color:var(--brand-dark,#252820)!important;border-top:1px solid var(--brand-border,#deded6)!important;transition:background 0.3s ease}'
        . '.store-page .newsletter .eyebrow{color:var(--brand-primary,#687158)!important;letter-spacing:0.18em!important;font-weight:700!important}'
        . '.store-page .newsletter>p:not(.eyebrow){color:#45483f!important;opacity:1}'
        . '.store-page .newsletter form{background:#ffffff!important;border:1.5px solid var(--brand-border,#deded6)!important;border-radius:999px!important;box-shadow:0 8px 30px rgba(0,0,0,0.04)!important;transition:all 0.35s cubic-bezier(0.16, 1, 0.3, 1)!important}'
        . '.store-page .newsletter form:focus-within{border-color:var(--brand-primary,#687158)!important;transform:translateY(-2px)!important;box-shadow:0 16px 40px rgba(104,113,88,0.18)!important}'
        . '.store-page .newsletter form input{background:transparent!important;color:var(--brand-dark,#252820)!important;border:none!important;padding:14px 22px!important;font-size:14px!important}'
        . '.store-page .newsletter form button{background:var(--brand-dark,#252820)!important;color:#ffffff!important;border-radius:999px!important;padding:12px 28px!important;font-weight:700!important;letter-spacing:0.02em!important;transition:all 0.3s cubic-bezier(0.16, 1, 0.3, 1)!important}'
        . '.store-page .newsletter form button:hover{background:var(--brand-primary,#687158)!important;transform:scale(1.03)!important;box-shadow:0 8px 20px rgba(104,113,88,0.3)!important}'
        . '.shop-page .shop-hero-overlay{z-index:2}.shop-page .shop-hero-overlay h1,.shop-page .shop-hero-empty h1{display:block!important;visibility:visible!important;opacity:1!important;color:var(--brand-on-dark)!important}'
        . '.filtered-landing main{display:flex;flex-direction:column}.filtered-landing .shop-hero{order:1}.filtered-landing .shop-catalogue{order:2}.filtered-landing .shop-category-section{order:3}.filtered-landing .shop-section{order:4}'
        . '.store-page .checkout-page{background:var(--brand-paper)!important;color:var(--brand-dark)}.store-page .checkout-summary{background:var(--brand-dark)!important;color:var(--brand-on-dark)!important}.store-page .checkout-summary .button{background:var(--brand-accent)!important;color:var(--brand-on-accent)!important}'
        . '.store-page .checkout-form input,.store-page .checkout-form textarea,.store-page .checkout-form select{border-color:var(--brand-border)!important;background:var(--brand-paper)!important;color:var(--brand-dark)!important}'
        . '.store-page .info-page{background:var(--brand-paper)!important;color:var(--brand-dark)!important;min-height:100vh}'
        . '.store-page .info-page h1,.store-page .info-page h2,.store-page .info-page h3{color:var(--brand-dark)!important;font-weight:600;letter-spacing:-0.02em}'
        . '.store-page .info-page p,.store-page .info-page span,.store-page .info-page div{color:var(--brand-dark)!important;line-height:1.7}'
        . '.store-page .info-page strong{color:var(--brand-primary)!important;font-weight:600}'
        . '.store-page .info-hero{background:var(--brand-primary)!important;color:var(--brand-on-primary)!important;padding:80px 40px;position:relative;overflow:hidden}'
        . '.store-page .info-hero::before{content:"";position:absolute;top:-50%;right:-10%;width:400px;height:400px;background:var(--brand-accent);opacity:0.1;border-radius:50%;filter:blur(80px)}'
        . '.store-page .info-hero::after{content:"";position:absolute;bottom:-30%;left:-10%;width:300px;height:300px;background:var(--brand-primary);opacity:0.08;border-radius:50%;filter:blur(60px)}'
        . '.store-page .info-hero .eyebrow{color:var(--brand-on-primary)!important;font-size:13px;font-weight:600;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:16px;position:relative;z-index:1}'
        . '.store-page .info-hero h1{color:var(--brand-on-primary)!important;font-size:clamp(2rem,5vw,3.5rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:20px;position:relative;z-index:1}'
        . '.store-page .info-hero p{color:var(--brand-on-primary)!important;font-size:1.125rem;max-width:600px;line-height:1.6;opacity:0.9;position:relative;z-index:1}'
        . '.store-page .info-content{padding:60px 40px;max-width:1200px;margin:0 auto}'
        . '.store-page .info-grid{background:var(--brand-paper)!important;display:grid;grid-template-columns:1fr;gap:40px}'
        . '@media(min-width:768px){.store-page .info-grid{grid-template-columns:1fr 2fr}}'
        . '.store-page .info-grid h2{color:var(--brand-dark)!important;font-size:2rem;font-weight:700;letter-spacing:-0.02em;margin-bottom:24px}'
        . '.store-page .info-grid h3{color:var(--brand-primary)!important;font-size:1.25rem;font-weight:600;margin:32px 0 16px}'
        . '.store-page .info-grid p{color:var(--brand-dark)!important;margin-bottom:16px}'
        . '.store-page .contact-form label{color:var(--brand-dark)!important;display:block;margin-bottom:8px;font-size:14px;font-weight:500}'
        . '.store-page .contact-form input,.store-page .contact-form textarea,.store-page .contact-form select{background:var(--brand-paper)!important;border:2px solid var(--brand-border)!important;color:var(--brand-dark)!important;padding:14px 16px;width:100%;border-radius:12px;margin-bottom:20px;font-size:15px;transition:all 0.3s ease;box-shadow:0 1px 3px rgba(0,0,0,0.05)}'
        . '.store-page .contact-form input:focus,.store-page .contact-form textarea:focus,.store-page .contact-form select:focus{outline:none;border-color:var(--brand-primary)!important;box-shadow:0 0 0 4px color-mix(in srgb,var(--brand-primary) 20%,transparent);transform:translateY(-1px)}'
        . '.store-page .contact-form button{background:linear-gradient(135deg,var(--brand-primary) 0%,color-mix(in srgb,var(--brand-primary),var(--brand-dark) 10%) 100%)!important;color:var(--brand-on-primary)!important;padding:16px 32px;border:none;border-radius:12px;cursor:pointer;font-weight:600;font-size:15px;letter-spacing:0.02em;transition:all 0.3s ease;box-shadow:0 4px 12px color-mix(in srgb,var(--brand-primary) 30%,transparent)}'
        . '.store-page .contact-form button:hover{transform:translateY(-2px);box-shadow:0 8px 20px color-mix(in srgb,var(--brand-primary) 40%,transparent)}'
        . '.store-page .faq-list{margin-top:24px}.store-page .faq-list details{background:var(--brand-surface)!important;border:1px solid var(--brand-border)!important;border-radius:16px;margin-bottom:16px;padding:24px;transition:all 0.3s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04)}'
        . '.store-page .faq-list details:hover{border-color:var(--brand-primary)!important;transform:translateX(4px)}'
        . '.store-page .faq-list details[open]{background:var(--brand-paper)!important;border-color:var(--brand-primary)!important}'
        . '.store-page .faq-list summary{color:var(--brand-dark)!important;font-weight:600;font-size:16px;cursor:pointer;list-style:none;position:relative;padding-right:30px;transition:color 0.3s ease}'
        . '.store-page .faq-list summary::after{content:"";position:absolute;right:0;top:50%;transform:translateY(-50%);width:8px;height:8px;border-right:2px solid var(--brand-primary);border-bottom:2px solid var(--brand-primary);transition:transform 0.3s ease}'
        . '.store-page .faq-list details[open] summary::after{transform:translateY(-50%) rotate(45deg)}'
        . '.store-page .faq-list p{color:var(--brand-dark)!important;margin-top:16px;line-height:1.7;padding-left:8px}'
        . '.store-page .site-footer,.shop-page .shop-footer{background:var(--brand-primary,#687158)!important;color:#f7f7f2!important;border-top:1px solid color-mix(in srgb,var(--brand-primary,#687158),#fff 15%)!important}'
        . '.store-page .info-grid>div:first-child{position:sticky;top:40px;height:fit-content}'
        . '.store-page .info-grid>div:nth-child(2){background:var(--brand-surface)!important;border-radius:20px;padding:32px;box-shadow:0 4px 20px rgba(0,0,0,0.06)}'
        . '.store-page .info-grid ol{padding-left:24px;line-height:2.2;margin:20px 0}.store-page .info-grid ol li{margin-bottom:12px;position:relative}.store-page .info-grid ol li::marker{color:var(--brand-primary);font-weight:600}'
        . '.store-page .button{background:linear-gradient(135deg,var(--brand-primary) 0%,color-mix(in srgb,var(--brand-primary),var(--brand-dark) 10%) 100%)!important;color:var(--brand-on-primary)!important;padding:16px 32px;border:none;border-radius:12px;cursor:pointer;font-weight:600;font-size:15px;letter-spacing:0.02em;transition:all 0.3s ease;box-shadow:0 4px 12px color-mix(in srgb,var(--brand-primary) 30%,transparent);display:inline-block;text-decoration:none}'
        . '.store-page .button:hover{transform:translateY(-2px);box-shadow:0 8px 20px color-mix(in srgb,var(--brand-primary) 40%,transparent)}'
        . '.product-page button.bg-[#252820]{background:var(--brand-dark)!important}.product-page button:hover{border-color:var(--brand-primary)!important}'
        . '.customer-shell .customer-nav-active,.customer-shell button.bg-[#687158]{background:var(--brand-primary)!important}'
        . '.customer-shell a.text-[#687158],.customer-shell button.text-[#687158]{color:var(--brand-primary)!important}'
        . '.auth-card,.auth-page .auth-shell main{background:var(--brand-paper)!important}'
        . '.google-auth-button{background:var(--brand-paper)!important;border-color:var(--brand-border)!important}.google-auth-button:hover{border-color:var(--brand-primary)!important}'
        . '.guest-mode-store-banner{position:fixed;left:50%;bottom:22px;z-index:1100;display:flex;max-width:calc(100vw - 32px);transform:translateX(-50%);align-items:center;gap:10px;border:1px solid var(--brand-border);border-radius:999px;background:var(--brand-paper);box-shadow:0 12px 32px rgba(37,40,32,.16);padding:10px 14px;color:var(--brand-dark);font-size:12px}.guest-mode-store-banner strong{font-weight:800}.guest-mode-store-banner span{color:var(--brand-muted)}.guest-mode-store-banner a{font-weight:800;color:var(--brand-primary)!important;text-decoration:underline}@media(max-width:560px){.guest-mode-store-banner{left:12px;right:12px;bottom:12px;transform:none;justify-content:center;flex-wrap:wrap;border-radius:14px}}'
        . '.theme-toggle{position:fixed;bottom:24px;right:24px;z-index:1000;width:48px;height:48px;min-width:48px;min-height:48px;border-radius:50%;background:var(--brand-surface);border:1px solid var(--brand-border);color:var(--brand-dark);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .3s ease;box-shadow:0 2px 8px rgba(0,0,0,.15)}.theme-toggle:hover{background:var(--brand-elevated);transform:scale(1.05)}.theme-toggle svg{width:20px;height:20px}@media(max-width:768px){.theme-toggle{bottom:16px;right:16px;width:48px;height:48px;min-width:48px;min-height:48px}.theme-toggle svg{width:20px;height:20px}}.store-page .header-actions .theme-toggle,.shop-page .shop-actions .theme-toggle{position:static;inset:auto;width:34px;height:34px;flex:0 0 34px;border-radius:999px;box-shadow:none;background:transparent;border-color:var(--brand-border);color:var(--brand-dark)}.store-page .header-actions .theme-toggle:hover,.shop-page .shop-actions .theme-toggle:hover{background:var(--brand-surface);transform:none}'
        . '.noir-install-banner{position:fixed;bottom:24px;left:24px;z-index:10000;max-width:380px;background:var(--brand-paper,#f7f7f2);border:1px solid var(--brand-border,#deded6);border-radius:20px;padding:18px 20px;box-shadow:0 12px 36px rgba(37,40,32,.16);display:grid;grid-template-columns:1fr auto;grid-template-areas:"title close" "desc desc" "btn .";gap:8px 12px;align-items:center;font-family:inherit;color:var(--brand-dark,#252820);opacity:1;transform:translateY(0);transition:opacity .35s ease,transform .35s ease}.noir-install-banner.hide{opacity:0;transform:translateY(18px);pointer-events:none}.noir-install-banner strong{grid-area:title;font-size:15px;font-weight:700;letter-spacing:-.01em}.noir-install-banner button[aria-label="Dismiss"]{grid-area:close;background:transparent!important;border:none!important;color:var(--brand-dark,#252820)!important;font-size:20px;line-height:1;cursor:pointer;padding:4px;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;box-shadow:none!important}.noir-install-banner button[aria-label="Dismiss"]:hover{background:var(--brand-surface,#eae8e1)!important}.noir-install-banner span{grid-area:desc;font-size:13px;color:var(--brand-muted,#77796f);line-height:1.4}.noir-install-banner button:not([aria-label="Dismiss"]){grid-area:btn;background:var(--brand-primary,#687158)!important;color:var(--brand-on-primary,#fff)!important;border:none!important;border-radius:999px;padding:9px 22px;font-size:13px;font-weight:600;cursor:pointer;margin-top:6px;width:fit-content;box-shadow:0 4px 12px rgba(104,113,88,.25);transition:transform .2s ease}.noir-install-banner button:not([aria-label="Dismiss"]):hover{transform:translateY(-1px)}@media(max-width:560px){.noir-install-banner{left:14px;right:14px;bottom:14px;max-width:none}}@media(display-mode:standalone){.noir-install-banner,.pwa-install-trigger{display:none!important}}'
        . '</style>'
        . '<link rel="icon" type="image/png" sizes="32x32" href="' . e($publicBase . '/favicon-32x32.png?v=' . $favVer) . '">'
        . '<link rel="icon" type="image/png" sizes="16x16" href="' . e($publicBase . '/favicon-16x16.png?v=' . $favVer) . '">'
        . '<link rel="icon" type="image/png" sizes="48x48" href="' . e($publicBase . '/favicon-48x48.png?v=' . $favVer) . '">'
        . '<link rel="shortcut icon" type="image/x-icon" href="' . e($publicBase . '/favicon.ico?v=' . $favVer) . '">'
        . '<link rel="apple-touch-icon" sizes="180x180" href="' . e($publicBase . '/favicon-180x180.png?v=' . $favVer) . '">'
        . '<link rel="manifest" href="' . e($publicBase . '/manifest.webmanifest') . '">'
        . '<meta name="theme-color" content="' . $dark . '">'
        . '<meta name="application-name" content="' . $storeName . '">'
        . '<meta name="mobile-web-app-capable" content="yes">'
        . '<meta name="apple-mobile-web-app-capable" content="yes">'
        . '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
        . '<meta name="apple-mobile-web-app-title" content="' . $storeName . '">'
        . '<meta name="msapplication-TileColor" content="' . $dark . '">'
        . '<script nonce="' . csp_nonce() . '">document.addEventListener("DOMContentLoaded",function(){'
        . 'if("serviceWorker" in navigator)navigator.serviceWorker.register("' . e($publicBase . '/sw.js') . '",{scope:"' . e($publicBase . '/') . '"});'
        . 'if(/\/(categories|collections)\//.test(location.pathname))document.body.classList.add("filtered-landing");'
        . 'document.querySelectorAll(".main-nav a,.shop-header nav a").forEach(function(link){if(link.textContent.trim().toLowerCase()==="shop"){var target=link.getAttribute("href")||"";link.setAttribute("href",target.split("#")[0]+"#catalogue")}});'
        . 'document.querySelectorAll(".store-page .header-actions a:nth-child(2),.shop-page .shop-actions a:nth-child(2)").forEach(function(link){link.classList.add("account-link");link.setAttribute("aria-label",' . json_encode($accountLabel, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');link.innerHTML=' . json_encode($accountMarkup, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '});'
        . 'if(window.matchMedia("(display-mode: standalone)").matches||window.navigator.standalone===true){document.querySelectorAll(".pwa-install-trigger").forEach(function(el){el.style.display="none";});}'
        . '});'
        . 'window.addEventListener("beforeinstallprompt",function(event){event.preventDefault();window.noirInstallEvent=event;document.querySelectorAll(".pwa-install-trigger").forEach(function(el){el.style.removeProperty("display");el.hidden=false;});if(sessionStorage.getItem("noir_pwa_dismissed")==="1")return;if(window.matchMedia("(display-mode: standalone)").matches||window.navigator.standalone===true)return;if(document.querySelector(".noir-install-banner"))return;var banner=document.createElement("div");banner.className="noir-install-banner";banner.innerHTML="<strong>Install ' . $storeName . '</strong><button type=\\"button\\" aria-label=\\"Dismiss\\">&times;</button><span>Open the store faster like an app.</span><button type=\\"button\\">Install</button>";document.body.appendChild(banner);var timer;function startDismissTimer(){timer=setTimeout(function(){banner.classList.add("hide");setTimeout(function(){banner.remove();},380);},3000);}startDismissTimer();banner.addEventListener("mouseenter",function(){clearTimeout(timer);});banner.addEventListener("mouseleave",function(){startDismissTimer();});banner.addEventListener("touchstart",function(){clearTimeout(timer);},{passive:true});banner.querySelector("button[aria-label=\\"Dismiss\\"]").onclick=function(){clearTimeout(timer);sessionStorage.setItem("noir_pwa_dismissed","1");banner.classList.add("hide");setTimeout(function(){banner.remove();},380);};banner.querySelector("button:not([aria-label=\\"Dismiss\\"])").onclick=function(){clearTimeout(timer);sessionStorage.setItem("noir_pwa_dismissed","1");banner.classList.add("hide");setTimeout(function(){banner.remove();},380);event.prompt();event.userChoice.finally(function(){window.noirInstallEvent=null;});};});'
        . 'window.addEventListener("appinstalled",function(){sessionStorage.setItem("noir_pwa_dismissed","1");var b=document.querySelector(".noir-install-banner");if(b)b.remove();document.querySelectorAll(".pwa-install-trigger").forEach(function(el){el.hidden=true;});});'
        . 'window.noirPromptInstall=function(){if(window.noirInstallEvent){window.noirInstallEvent.prompt();window.noirInstallEvent.userChoice.finally(function(res){if(res&&res.outcome==="accepted"){window.noirInstallEvent=null;document.querySelectorAll(".pwa-install-trigger").forEach(function(el){el.hidden=true;});}});}else if(window.matchMedia("(display-mode: standalone)").matches||window.navigator.standalone===true){alert("Noir & Co. is already installed and running as an app.");}else{var isIOS=/iPad|iPhone|iPod/.test(navigator.userAgent)&&!window.MSStream;if(isIOS){alert("To install Noir & Co. on your device:\n1. Tap the Share button at the bottom of Safari.\n2. Scroll down and tap \'Add to Home Screen\'.");}else{alert("To install Noir & Co.:\nClick the install icon in your browser address bar (\u{2295}), or open your browser menu and choose \'Install Noir & Co.\' or \'Add to Home screen\'.");}}};'
        . '</script>'
        . $guestBannerScript
        . $adminAssistant;
}

/**
 * Returns the HTML link + script tags needed to mount the AI shopping assistant,
 * or an empty string when the feature is disabled in config.
 */
function ai_assistant_assets(string $publicBase): string
{
    static $loaded = false;
    if ($loaded || !config('ai.enabled', false)) {
        return '';
    }
    $loaded = true;

    $requestTimeoutMs = min(70000, max(15000, ((int) config('ai.timeout', 20) + 10) * 1000));
    $active = active_currency();
    $rate = get_exchange_rate($active);

    $aiCssUrl = e(asset_url($publicBase, '/assets/css/ai-assistant.css'));
    return '<link rel="stylesheet" href="' . $aiCssUrl . '">'
        . '<script defer src="' . e(asset_url($publicBase, '/assets/js/ai-assistant.js')) . '"'
        . ' data-noir-ai-assistant'
        . ' data-endpoint="' . e($publicBase . '/api/v1/assistant/chat') . '"'
        . ' data-csrf-token="' . e(csrf_token()) . '"'
        . ' data-store-name="' . e(store_setting('store_name', 'Noir & Co.')) . '"'
        . ' data-catalogue-url="' . e($publicBase . '/store#catalogue') . '"'
        . ' data-currency-symbol="' . e(currency_symbol()) . '"'
        . ' data-currency-rate="' . e((string) $rate) . '"'
        . ' data-locale="' . e(current_locale()) . '"'
        . ' data-timeout-ms="' . e((string) $requestTimeoutMs) . '">'
        . '</script>';
}
