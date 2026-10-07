<!doctype html>
<html lang="en" class="h-full bg-[#f8f8f5]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e(ucwords(str_replace('-', ' ', $page))) ?> &mdash; <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script>
        if (typeof tailwind === 'undefined') {
            document.write('<script src="https://cdn.tailwindcss.com"><\/script>');
        }
        if (window.tailwind) {
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            brand: {
                                DEFAULT: '#687158',
                                dark: '#1a1c16',
                                light: '#a89d70',
                                paper: '#f8f8f5'
                            }
                        }
                    }
                }
            };
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;1,400&display=swap" rel="stylesheet">
    <script src="<?= e(($base ?: '') . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/responsive.css') ?>">
    <?= store_brand_style() ?>
    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-serif: 'Playfair Display', Georgia, serif;
        }
        body {
            font-family: var(--font-sans);
            background-color: #f7f7f3;
            color: #171a14;
            -webkit-font-smoothing: antialiased;
            background-image: 
                radial-gradient(at 15% 15%, rgba(104, 113, 88, 0.04) 0px, transparent 50%),
                radial-gradient(at 85% 85%, rgba(168, 157, 112, 0.03) 0px, transparent 50%);
            background-attachment: fixed;
            margin: 0;
            padding: 0;
        }
        .font-serif-luxury { font-family: var(--font-serif); }

        /* Robust Core Layout Fallbacks */
        .customer-shell {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        .customer-sidebar {
            width: 270px;
            min-width: 270px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
            background: linear-gradient(180deg, #171913 0%, #1c1f17 100%);
            padding: 24px 20px;
            color: #e2e4dc;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @media (max-width: 1023px) {
            .customer-sidebar {
                width: min(280px, 86vw);
                transform: translateX(-100%);
            }
            .customer-sidebar.open {
                transform: translateX(0) !important;
            }
        }
        @media (min-width: 1024px) {
            .customer-sidebar {
                transform: none !important;
            }
            .customer-content-shell {
                margin-left: 270px !important;
                width: calc(100% - 270px) !important;
            }
        }
        .customer-content-shell {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            min-width: 0;
        }
        .customer-header {
            position: sticky;
            top: 0;
            z-index: 30;
            display: flex;
            height: 72px;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e6e7df;
            background: rgba(248, 248, 245, 0.95);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            padding: 0 20px;
        }
        @media (min-width: 1024px) {
            .customer-header {
                padding: 0 40px;
            }
        }

        .customer-sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .customer-sidebar nav a {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            padding: 10px 14px !important;
            border-radius: 12px;
            font-size: 12px;
            line-height: 1.25rem;
            color: #a4a99c;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .customer-sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }
        .customer-nav-active {
            background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0.05) 100%) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-left: 3px solid var(--brand-primary, #687158);
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
        }
        .customer-nav-active i {
            color: var(--brand-primary-light, #a89d70) !important;
        }
        .customer-table th {
            font-size: 10.5px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #8c8f85;
            font-weight: 700;
            text-align: left;
            padding: 14px 18px;
        }
        .customer-table td {
            padding: 16px 18px;
            border-top: 1px solid #f0f1ea;
            font-size: 13px;
        }
        .customer-card-hover {
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .customer-card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 32px -8px rgba(24, 26, 20, 0.08), 0 0 0 1px rgba(104, 113, 88, 0.12);
        }
        .luxury-glass-card {
            background: rgba(255, 255, 255, 0.88);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(229, 231, 222, 0.85);
        }
        .field-input {
            width: 100%;
            border: 1px solid #e1e3da;
            background: #ffffff;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 13px;
            color: #1c1e18;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .field-input:focus {
            border-color: var(--brand-primary, #687158);
            box-shadow: 0 0 0 3px rgba(104, 113, 88, 0.16);
            background: #ffffff;
        }
        .field-input:disabled {
            background: #f4f4ef;
            color: #888b80;
            cursor: not-allowed;
        }

        /* Custom luxury scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d7d8d0; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #b8b9b0; }
    </style>
</head>
<body class="h-full flex flex-col">
<?php 
$isGuest = !empty(\Noir\Core\Auth::user()['is_guest']); 
$currentUri = (string)($_SERVER['REQUEST_URI'] ?? '');
$currentPath = parse_url($currentUri, PHP_URL_PATH) ?? '';
$basePrefix = rtrim($base ?: '', '/');
if ($basePrefix && str_starts_with($currentPath, $basePrefix)) {
    $currentPath = substr($currentPath, strlen($basePrefix));
}
$currentPath = rtrim($currentPath, '/') ?: '/';
?>
<div class="customer-shell flex min-h-screen w-full">
    <!-- Luxury Client Sidebar -->
    <aside id="customer-sidebar" class="customer-sidebar fixed inset-y-0 left-0 z-40 flex w-[270px] -translate-x-full flex-col justify-between overflow-y-auto bg-[#1a1c16] px-5 py-6 text-[#e2e4dc] lg:translate-x-0 border-r border-white/5">
        <div>
            <!-- Brand Mark -->
            <div class="mb-8 flex items-center justify-between px-2">
                <a href="<?= e(($base ?: '') . '/customer') ?>" class="group flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#687158] text-xs font-bold text-white shadow-md shadow-[#687158]/30">N</span>
                    <div>
                        <span class="text-sm font-bold tracking-[0.2em] text-white block uppercase"><?= e(store_setting('store_name', 'Noir & Co.')) ?></span>
                        <span class="text-[9px] uppercase tracking-[0.22em] text-[#8e9383] font-semibold">Client Portal</span>
                    </div>
                </a>
                <button id="close-customer-sidebar" class="rounded-lg p-1.5 text-[#8e9383] hover:bg-white/10 hover:text-white lg:hidden" type="button" aria-label="Close navigation">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <!-- Client Tier Card -->
            <div class="mb-6 rounded-2xl border border-white/10 bg-gradient-to-b from-white/[0.08] to-white/[0.03] p-4 backdrop-blur-sm">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-[#687158] text-xs font-bold text-white shadow-md overflow-hidden">
                            <?php if(!empty($customer['profile_image'])): ?>
                                <img src="<?= e(($base?:'').$customer['profile_image']) ?>" alt="" class="h-full w-full object-cover">
                            <?php else: ?>
                                <?= e($customer['initials'] ?? 'CL') ?>
                            <?php endif; ?>
                        </span>
                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[#1a1c16] bg-[#a89d70]"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <strong class="block truncate text-xs font-semibold text-white"><?= e($customer['name']) ?></strong>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span class="text-[10px] text-[#a89d70] font-medium uppercase tracking-wider">Private Client</span>
                            <?php if(!empty($customerStats['points'])): ?>
                                <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[9px] font-semibold text-[#c8cdbe]">&bull; <?= number_format((int)$customerStats['points']) ?> pts</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mb-2 px-3 text-[9px] font-bold uppercase tracking-[0.22em] text-[#787d6e]"><?= __('Dossier Navigation') ?></p>
            <nav class="space-y-1">
                <?php 
                $links = [
                    ['layout-dashboard', 'Dashboard', '/customer'],
                    ['shopping-bag', 'My Orders', '/customer/orders'],
                    ['bookmark', 'Saved Pieces', '/customer/saved-items'],
                    ['map-pin', 'Delivery Addresses', '/customer/addresses'],
                    ['credit-card', 'Payment Methods', '/customer/payments'],
                    ['ticket', 'Privilege Coupons', '/customer/coupons'],
                    ['star', 'Customer Reviews', '/customer/reviews'],
                    ['bell', 'Concierge Alerts', '/customer/notifications'],
                    ['life-buoy', 'Client Support', '/customer/support'],
                    ['settings', 'Account Settings', '/customer/settings']
                ];
                foreach($links as [$icon, $label, $url]): 
                    $isActive = ($url === '/customer' && ($currentPath === '/customer' || $currentPath === '/dashboard')) || 
                                ($url !== '/customer' && str_starts_with($currentPath, $url));
                ?>
                    <a class="<?= $isActive ? 'customer-nav-active ' : 'text-[#a4a99c] hover:bg-white/5 hover:text-white ' ?>flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs transition duration-150" href="<?= e(($base ?: '') . $url) ?>">
                        <i data-lucide="<?= e($icon) ?>" class="h-4 w-4 shrink-0 text-[#84897a]"></i>
                        <span class="flex-1 truncate"><?= e(__($label)) ?></span>
                        <?php if($url === '/customer/notifications' && !empty($unreadNotifications) && (int)$unreadNotifications > 0): ?>
                            <span class="rounded-full bg-[#a89d70] px-1.5 py-0.5 text-[9px] font-bold text-[#1a1c16]"><?= (int)$unreadNotifications ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="mt-8 border-t border-white/10 pt-4">
            <a href="<?= e(($base ?: '') . '/store') ?>" class="flex items-center justify-between rounded-xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 text-xs text-[#c0c5b8] transition hover:bg-white/10 hover:text-white">
                <span class="flex items-center gap-2.5">
                    <i data-lucide="store" class="h-4 w-4 text-[#a89d70]"></i>
                    <?= __('Explore Boutique') ?>
                </span>
                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 text-[#7f8474]"></i>
            </a>
        </div>
    </aside>

    <!-- Main Content Shell -->
    <div class="customer-content-shell flex min-h-screen w-full flex-col lg:ml-[270px]">
        <!-- Sticky Luxury Header -->
        <header class="customer-header sticky top-0 z-30 flex h-[72px] items-center justify-between border-b border-[#e6e7df] bg-[#f8f8f5]/90 px-5 backdrop-blur-md lg:px-10">
            <div class="flex items-center gap-4">
                <button id="open-customer-sidebar" class="rounded-xl border border-[#dcded4] bg-white p-2.5 text-[#3e4235] shadow-sm hover:bg-[#f2f2eb] lg:hidden" type="button" aria-label="Open navigation">
                    <i data-lucide="menu" class="h-4 w-4"></i>
                </button>
                <div class="hidden items-center gap-2.5 rounded-xl border border-[#dfe2d8] bg-white px-3.5 py-2 shadow-sm sm:flex">
                    <i data-lucide="search" class="h-3.5 w-3.5 text-[#8f9286]"></i>
                    <input class="w-52 bg-transparent text-xs text-[#1c1e18] placeholder-[#9ea196] outline-none" placeholder="<?= e(__('Search orders, pieces, tickets...')) ?>">
                </div>
                <div class="sm:hidden">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#687158]"><?= e(ucwords(str_replace('-', ' ', $page))) ?></span>
                </div>
            </div>

            <div class="flex items-center gap-4 lg:gap-6">
                <!-- Points Chip -->
                <?php if(!empty($customerStats['points'])): ?>
                    <a href="<?= e(($base ?: '') . '/customer/coupons') ?>" class="hidden md:flex items-center gap-1.5 rounded-full border border-[#a89d70]/30 bg-[#f4f1e6] px-3 py-1 text-[11px] font-semibold text-[#74693a] transition hover:border-[#a89d70]">
                        <i data-lucide="sparkles" class="h-3 w-3 text-[#a89d70]"></i>
                        <span><?= number_format((int)$customerStats['points']) ?> <?= __('Privilege Pts') ?></span>
                    </a>
                <?php endif; ?>

                <!-- Notifications Bell -->
                <a href="<?= e(($base ?: '') . '/customer/notifications') ?>" class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-[#dfe2d8] bg-white text-[#585c4e] shadow-sm transition hover:bg-[#f2f2eb]" title="<?= e(__('Concierge Notifications')) ?>">
                    <i data-lucide="bell" class="h-4 w-4"></i>
                    <?php if(!empty($unreadNotifications) && (int)$unreadNotifications > 0): ?>
                        <span class="notification-unread-badge absolute -right-1.5 -top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-[#9f4f46] px-1 text-[9px] font-bold text-white shadow-sm">
                            <?= (int)$unreadNotifications > 9 ? '9+' : (int)$unreadNotifications ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- User Profile & Sign Out -->
                <div class="flex items-center gap-3 border-l border-[#dcded4] pl-4 lg:pl-6">
                    <a href="<?= e(($base ?: '') . '/customer/settings') ?>" class="group flex items-center gap-2.5">
                        <span class="customer-avatar grid h-9 w-9 place-items-center rounded-full bg-[#687158] text-xs font-bold text-white shadow-sm ring-2 ring-white overflow-hidden transition group-hover:ring-[#687158]/40">
                            <?php if(!empty($customer['profile_image'])): ?>
                                <img src="<?= e(($base?:'').$customer['profile_image']) ?>" alt="" class="h-full w-full object-cover">
                            <?php else: ?>
                                <?= e($customer['initials'] ?? 'CL') ?>
                            <?php endif; ?>
                        </span>
                        <div class="hidden sm:block text-left">
                            <strong class="block text-xs font-semibold text-[#1c1e18] group-hover:text-[#687158] transition customer-display-name"><?= e($customer['name']) ?></strong>
                            <small class="text-[10px] text-[#8e9383] uppercase tracking-wider font-medium customer-display-username"><?= e($customer['username'] ?? 'Member') ?></small>
                        </div>
                    </a>

                    <form method="post" action="<?= e(($base ?: '') . '/logout') ?>" class="ml-1">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="flex items-center gap-1.5 rounded-lg border border-transparent px-2.5 py-1.5 text-xs font-medium text-[#7d8273] transition hover:border-[#dedfd6] hover:bg-white hover:text-[#1c1e18]" title="<?= __('Sign out of your dossier') ?>">
                            <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                            <span class="hidden xl:inline"><?= __('Sign out') ?></span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Body Stage -->
        <main class="flex-1 px-5 py-8 lg:px-10 lg:py-10 max-w-7xl">
            <?php if($isGuest): ?>
                <div class="guest-mode-banner mb-6 flex items-center justify-between rounded-2xl border border-[#d3d8c4] bg-[#edf0e4] px-5 py-3.5 text-xs text-[#445037] shadow-sm">
                    <div class="flex items-center gap-3">
                        <i data-lucide="shield-alert" class="h-4 w-4 text-[#687158]"></i>
                        <div>
                            <strong class="font-semibold"><?= __('Guest Mode Active') ?>:</strong>
                            <span class="ml-1 text-[#5b684e]"><?= __('Your temporary selections and bag are localized to this browser session.') ?></span>
                        </div>
                    </div>
                    <a class="rounded-lg bg-[#687158] px-3 py-1.5 font-semibold text-white transition hover:bg-[#525a44]" href="<?= e(($base ?: '') . '/register') ?>"><?= __('Create Full Account') ?> &rarr;</a>
                </div>
            <?php endif; ?>

            <?php require $viewFile; ?>
        </main>

        <!-- Refined Footer -->
        <footer class="mt-auto border-t border-[#e6e7df] bg-white/60 px-5 py-6 text-xs text-[#8f9286] lg:px-10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="font-semibold text-[#1c1e18]">&copy; <?= date('Y') ?> <?= e(store_setting('store_name', 'Noir & Co.')) ?></span>
                    <span class="h-1 w-1 rounded-full bg-[#8f9286]"></span>
                    <span>Bespoke Ready-to-Wear</span>
                </div>
                <div class="flex items-center gap-5 text-[11px]">
                    <a href="<?= e(($base ?: '') . '/privacy-policy') ?>" class="hover:text-[#1c1e18] transition">Privacy Charter</a>
                    <a href="<?= e(($base ?: '') . '/returns') ?>" class="hover:text-[#1c1e18] transition">Complimentary Returns</a>
                    <a href="<?= e(($base ?: '') . '/customer/support') ?>" class="hover:text-[#1c1e18] transition">Concierge Support</a>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Floating Luxury Toast Container -->
<div id="customer-toast" class="pointer-events-none fixed bottom-8 right-8 z-50 flex items-center gap-3 rounded-2xl border border-white/10 bg-[#1c1e17] px-5 py-3.5 text-xs font-semibold text-[#f5f5f0] shadow-2xl opacity-0 translate-y-4 transition-all duration-300"></div>

<!-- Customer Global Javascript Setup -->
<script>
window.noirCustomer = {
    passwordUrl: '<?= e(($base ?: '') . '/customer/ajax/password-change') ?>',
    passwordOtpRequestUrl: '<?= e(($base ?: '') . '/customer/ajax/password-otp-request') ?>',
    passwordOtpChangeUrl: '<?= e(($base ?: '') . '/customer/ajax/password-otp-change') ?>',
    preferencesUrl: '<?= e(($base ?: '') . '/customer/ajax/notification-preferences') ?>',
    profileUpdateUrl: '<?= e(($base ?: '') . '/customer/ajax/profile-update') ?>',
    profileUploadUrl: '<?= e(($base ?: '') . '/customer/ajax/profile-upload') ?>',
    profileDeleteUrl: '<?= e(($base ?: '') . '/customer/ajax/profile-delete') ?>'
};
</script>
<script src="<?= e(($base ?: '') . '/assets/js/customer.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
    const sb = document.getElementById('customer-sidebar');
    document.getElementById('open-customer-sidebar')?.addEventListener('click', () => sb?.classList.add('open'));
    document.getElementById('close-customer-sidebar')?.addEventListener('click', () => sb?.classList.remove('open'));
});
</script>
</body>
</html>
