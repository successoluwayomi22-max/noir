<?php
declare(strict_types=1);

$user = $user ?? \Noir\Core\Container::get('auth')->user() ?? [];
$base = $base ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$avatar = (string)($user['profile_image'] ?? $user['avatar'] ?? '');
$eyebrow = $eyebrow ?? 'Treasury Management';
$title = $title ?? 'Finance Workspace';
$badgeText = $badgeText ?? 'Live Settlement Engine';
?>
<!-- Reusable Luxury Finance Topbar Component (Matching Customer & Admin) -->
<header class="sticky top-0 z-30 bg-[#f7f7f2]/90 backdrop-blur-md border-b border-[#e5e5dd] px-6 lg:px-10 py-4 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-[#8e9187]">
            <span><?= e($eyebrow) ?></span>
            <span>&bull;</span>
            <span class="inline-flex items-center gap-1.5 text-emerald-700 bg-emerald-100/70 px-2.5 py-0.5 rounded-full">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                <?= e($badgeText) ?>
            </span>
        </div>
        <h1 class="text-2xl font-extrabold text-[#252820] mt-0.5"><?= e($title) ?></h1>
    </div>

    <!-- Actions & User Profile with Sign Out Button -->
    <div class="flex flex-wrap items-center gap-3 no-print">
        <?php if (!empty($customActions)): ?>
            <?= $customActions ?>
        <?php endif; ?>

        <!-- Manager Profile & Sign Out Button (Identical to Customer & Admin) -->
        <div class="flex items-center gap-3 pl-3 border-l border-[#deded6]">
            <a href="<?= e(($base ?: '') . '/finance/settings') ?>" class="group flex items-center gap-2.5" title="Manage account settings">
                <?php if ($avatar !== ''): ?>
                    <img src="<?= e(($base ?: '') . $avatar) ?>" alt="<?= e($user['name'] ?? 'Manager') ?>" class="h-9 w-9 rounded-full object-cover border border-[#deded6] shadow-sm group-hover:ring-2 group-hover:ring-[#687158]/40 transition">
                <?php else: ?>
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-[#687158] text-xs font-bold text-white shadow-sm ring-2 ring-white">
                        <?= e(strtoupper(substr((string)($user['name'] ?? 'F'), 0, 1))) ?>
                    </span>
                <?php endif; ?>
                
                <div class="hidden sm:block text-left">
                    <strong class="block text-xs font-bold text-[#252820] leading-tight group-hover:text-[#687158] transition"><?= e($user['name'] ?? 'Finance Manager') ?></strong>
                    <span class="text-[10px] text-[#8e9187] uppercase tracking-wider font-semibold">Finance Director</span>
                </div>
            </a>

            <form method="post" action="<?= e(($base ?: '') . '/logout') ?>" class="ml-1">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="flex items-center gap-1.5 rounded-xl border border-transparent px-3 py-2 text-xs font-semibold text-[#77796f] transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600" title="Sign out of financial dashboard">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    <span class="hidden md:inline">Sign out</span>
                </button>
            </form>
        </div>
    </div>
</header>
