<?php
declare(strict_types=1);

/**
 * Reusable Luxury Administrator Topbar Component
 * 
 * Variables:
 * @var string $eyebrow Page category / eyebrow indicator
 * @var string $title Page H1 heading
 * @var string $subtitle Page description
 * @var array|null $user Authenticated admin user
 * @var string $base Application base URL
 */

$user = $user ?? \Noir\Core\Auth::user();
$base = $base ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$adminAvatar = (string)($user['profile_image'] ?? $user['avatar'] ?? store_setting('admin_profile_image_' . (int)($user['id'] ?? $user['user_id'] ?? 0)) ?: store_setting('admin_profile_image_0'));
?>
<header class="admin-topbar flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-[#e5e5dd] bg-white/70 backdrop-blur-md px-6 py-5 sticky top-0 z-30 shadow-[0_4px_20px_rgba(0,0,0,0.02)]">
    <div>
        <?php if (!empty($eyebrow)): ?>
            <p class="mb-1.5 text-[10px] font-bold uppercase tracking-[.2em] dynamic-brand-primary-text" style="color: var(--brand-primary, #687158);"><?= e($eyebrow) ?></p>
        <?php endif; ?>
        <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight text-[#252820]"><?= e($title ?? 'Dashboard') ?></h1>
        <?php if (!empty($subtitle)): ?>
            <p class="mt-1 text-xs text-[#77796f] max-w-2xl"><?= e($subtitle) ?></p>
        <?php endif; ?>
    </div>

    <div class="flex items-center gap-3 shrink-0">
        <!-- Live Storefront Link -->
        <a href="<?= e(($base ?: '') . '/') ?>" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-[#deded6] bg-[#fafaf7] px-3 py-2 text-xs font-bold text-[#252820] shadow-sm hover:bg-white hover:border-[var(--brand-primary)] transition-all">
            <i data-lucide="external-link" class="h-3.5 w-3.5 text-[#8e9187]"></i>
            <span>View Storefront</span>
        </a>

        <!-- Admin Profile -->
        <div class="flex items-center gap-2.5 pl-2 border-l border-[#e5e5dd]">
            <?php if ($adminAvatar !== ''): ?>
                <img src="<?= e(($base ?: '') . $adminAvatar) ?>" alt="<?= e($user['name'] ?? 'Admin') ?>" class="h-9 w-9 rounded-full object-cover border border-[#deded6] shadow-sm">
            <?php else: ?>
                <span class="grid h-9 w-9 place-items-center rounded-full dynamic-brand-primary-bg text-xs font-bold text-white shadow-sm" style="background-color: var(--brand-primary, #687158);">
                    <?= e(strtoupper(substr((string)($user['name'] ?? 'A'), 0, 1))) ?>
                </span>
            <?php endif; ?>
            
            <div class="hidden sm:block text-left">
                <strong class="block text-xs font-bold text-[#252820] leading-tight"><?= e($user['name'] ?? 'Administrator') ?></strong>
                <span class="text-[11px] text-[#8e9187]"><?= e($user['email'] ?? 'admin@noir.test') ?></span>
            </div>

            <form method="post" action="<?= e(($base ?: '') . '/logout') ?>" class="ml-1">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="p-2 rounded-xl text-[#8e9187] hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Sign out of administrative portal">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                </button>
            </form>
        </div>
    </div>
</header>
