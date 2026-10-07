<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Container::get('auth')->user() ?? [];
$avatar = (string)($user['profile_image'] ?? $user['avatar'] ?? '');
$storeName = store_setting('store_name', 'Noir & Co.');
$activePage = $activePage ?? 'dashboard';
?>
<!-- Sidebar: Static / Sticky docked to viewport without gap -->
<aside class="finance-sidebar w-full md:w-64 shrink-0 p-6 flex flex-col justify-between text-white no-print md:sticky md:top-0 md:h-screen md:overflow-y-auto">
    <div>
        <!-- Brand -->
        <div class="flex items-center justify-between pb-6 border-b border-[#2d332a]">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-[0.25em] text-[#8e9187]">Executive Suite</span>
                <h2 class="text-xl font-extrabold tracking-wider text-white mt-0.5"><?= e($storeName) ?></h2>
            </div>
            <div class="h-9 w-9 rounded-xl bg-[#282d24] flex items-center justify-center text-[#98a682] border border-[#383f32]">
                <i data-lucide="wallet-cards" class="h-5 w-5"></i>
            </div>
        </div>

        <div class="mt-6 mb-2 px-3">
            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#6b7363]">Finance Workspace</span>
        </div>

        <!-- Nav Links: Each leads directly to its own page -->
        <nav class="space-y-1.5">
            <a href="<?= e(($base ?: '') . '/finance/dashboard') ?>" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                <span>Overview &amp; Treasury</span>
            </a>
            <a href="<?= e(($base ?: '') . '/finance/analytics') ?>" class="nav-item <?= $activePage === 'analytics' ? 'active' : '' ?>">
                <i data-lucide="line-chart" class="h-4 w-4"></i>
                <span>Cash-Flow Analytics</span>
            </a>
            <a href="<?= e(($base ?: '') . '/finance/ledger') ?>" class="nav-item <?= $activePage === 'ledger' ? 'active' : '' ?>">
                <i data-lucide="receipt-text" class="h-4 w-4"></i>
                <span>Transaction Ledger</span>
            </a>
            <a href="<?= e(($base ?: '') . '/finance/operations') ?>" class="nav-item <?= $activePage === 'operations' ? 'active' : '' ?>">
                <i data-lucide="package-check" class="h-4 w-4"></i>
                <span>Fulfilment &amp; Reserves</span>
            </a>
            <a href="<?= e(($base ?: '') . '/finance/settings') ?>" class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
                <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                <span>Manager Settings</span>
            </a>
            <a href="<?= e(($base ?: '') . '/') ?>" target="_blank" class="nav-item text-[#7e8574] hover:text-white">
                <i data-lucide="external-link" class="h-4 w-4"></i>
                <span>View Storefront</span>
            </a>
        </nav>
    </div>

    <!-- User Profile & Prominent Sign Out Button in Sidebar (Matching Customer & Admin) -->
    <div class="pt-6 border-t border-[#2d332a] mt-8 space-y-3">
        <a href="<?= e(($base ?: '') . '/finance/settings') ?>" class="flex items-center gap-3 group">
            <?php if ($avatar !== ''): ?>
                <img src="<?= e(($base ?: '') . $avatar) ?>" alt="<?= e($user['name'] ?? 'Manager') ?>" class="h-10 w-10 rounded-full object-cover border border-[#444c3c] group-hover:border-[#687158] transition">
            <?php else: ?>
                <div class="h-10 w-10 rounded-full bg-[#687158] text-white font-bold flex items-center justify-center text-sm shadow-sm">
                    <?= e(strtoupper(substr((string)($user['name'] ?? 'F'), 0, 1))) ?>
                </div>
            <?php endif; ?>
            <div class="overflow-hidden">
                <strong class="block text-xs font-bold text-white group-hover:text-[#c3cfb2] transition truncate max-w-[130px]"><?= e($user['name'] ?? 'Finance Manager') ?></strong>
                <span class="block text-[10px] text-[#8e9187] font-semibold">Finance Director</span>
            </div>
        </a>

        <!-- Sign out form button matching customer and admin style -->
        <form method="post" action="<?= e(($base ?: '') . '/logout') ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 text-xs font-semibold text-[#c0c5b8] hover:bg-rose-500/15 hover:border-rose-500/30 hover:text-rose-300 transition-all">
                <i data-lucide="log-out" class="h-4 w-4"></i>
                <span>Sign out</span>
            </button>
        </form>
    </div>
</aside>
