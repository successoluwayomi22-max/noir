<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Container::get('auth')->user() ?? [];
$avatar = (string)($user['profile_image'] ?? $user['avatar'] ?? '');
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Finance Settings &mdash; <?= e($storeName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <style>
        body { font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, sans-serif; background-color: #f7f7f2; color: #252820; margin: 0; min-height: 100vh; }
        .finance-sidebar { background-color: #1e221c; border-right: 1px solid #2d332a; }
        @media (min-width: 768px) {
            body { display: flex !important; flex-direction: row !important; }
            .finance-sidebar { width: 16rem !important; flex-shrink: 0 !important; position: sticky !important; top: 0 !important; height: 100vh !important; overflow-y: auto !important; }
            main { flex: 1 1 0% !important; min-width: 0 !important; }
        }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; color: #a3a89b; transition: all 0.2s ease; text-decoration: none; }
        .nav-item:hover { color: #ffffff; background-color: rgba(255, 255, 255, 0.05); }
        .nav-item.active { color: #ffffff; background-color: #687158; box-shadow: 0 4px 12px rgba(104, 113, 88, 0.25); }
        @media print { .no-print { display: none !important; } .finance-sidebar { display: none !important; } body { background: #ffffff !important; } }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row antialiased">

    <!-- Shared Navigation Sidebar -->
    <?php require BASE_PATH . '/app/Views/finance/sidebar.php'; ?>

    <!-- Main Workspace -->
    <main class="flex-1 min-w-0 overflow-y-auto">
        
        <!-- Topbar with Signout (Matching Admin & Customer) -->
        <?php 
        $eyebrow = 'Account Controls';
        $title = 'Manager Settings & Security';
        $badgeText = 'Manager Credentials';
        require BASE_PATH . '/app/Views/finance/topbar.php'; 
        ?>

        <div class="p-6 lg:p-8 space-y-8 max-w-4xl">

            <!-- Flash Banners -->
            <?php if ($saved): ?>
                <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-semibold text-emerald-900 shadow-sm">
                    <i data-lucide="check-circle" class="h-5 w-5 text-emerald-600 shrink-0"></i>
                    <div>
                        <strong class="block text-sm font-bold text-emerald-950">Settings Saved</strong>
                        <span>Your avatar and workspace credentials have been updated successfully.</span>
                    </div>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs font-semibold text-rose-900 shadow-sm">
                    <i data-lucide="alert-circle" class="h-5 w-5 text-rose-600 shrink-0"></i>
                    <div>
                        <strong class="block text-sm font-bold text-rose-950">Update Failed</strong>
                        <span>Image must be a valid JPG, PNG, or WebP file up to 5MB.</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile Avatar Card -->
            <section class="rounded-2xl border border-[#e5e5dd] bg-white p-6 sm:p-8 shadow-sm">
                <div class="border-b border-[#f0f0ea] pb-4 mb-6">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Official Portrait</span>
                    <h3 class="text-lg font-extrabold text-[#252820]">Finance Manager Identity</h3>
                </div>

                <form method="post" enctype="multipart/form-data" action="<?= e(($base ?: '') . '/finance/settings/profile') ?>" class="space-y-6">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                    <div class="flex flex-wrap items-center gap-6">
                        <div class="h-24 w-24 rounded-full border-2 border-[#687158] overflow-hidden bg-[#fafaf7] flex items-center justify-center shrink-0 shadow-md">
                            <?php if ($avatar !== ''): ?>
                                <img src="<?= e(($base ?: '') . $avatar) ?>" alt="Manager Avatar" class="h-full w-full object-cover">
                            <?php else: ?>
                                <span class="text-3xl font-extrabold text-[#687158]"><?= e(strtoupper(substr((string)($user['name'] ?? 'F'), 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="flex-1 min-w-[240px] space-y-2">
                            <label class="block text-xs font-bold text-[#252820]">Upload New Profile Photo</label>
                            <input type="file" name="profile" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-[#77796f] file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#f0f1eb] file:text-[#687158] hover:file:bg-[#e4e6dc]">
                            <p class="text-[11px] text-[#8e9187]">Maximum resolution supported: 2000&times;2000px.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-[#f0f0ea]">
                        <button type="submit" class="rounded-xl bg-[#687158] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#596249] transition-all shadow-sm">
                            Save Portrait Photo
                        </button>
                        <?php if ($avatar !== ''): ?>
                            <button type="submit" name="remove_profile" value="1" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition-all">
                                Remove Photo
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <!-- Account Details Card -->
            <section class="rounded-2xl border border-[#e5e5dd] bg-white p-6 sm:p-8 shadow-sm space-y-4">
                <div class="border-b border-[#f0f0ea] pb-4 mb-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">System Account</span>
                    <h3 class="text-lg font-extrabold text-[#252820]">Registered Credentials</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="rounded-xl border border-[#deded6] bg-[#fafaf7] p-4">
                        <span class="text-[10px] font-bold uppercase text-[#8e9187] block">Full Name</span>
                        <strong class="text-sm font-bold text-[#252820] mt-1 block"><?= e($user['name'] ?? 'Finance Manager') ?></strong>
                    </div>
                    <div class="rounded-xl border border-[#deded6] bg-[#fafaf7] p-4">
                        <span class="text-[10px] font-bold uppercase text-[#8e9187] block">Corporate Email</span>
                        <strong class="text-sm font-bold text-[#252820] mt-1 block"><?= e($user['email'] ?? '—') ?></strong>
                    </div>
                    <div class="rounded-xl border border-[#deded6] bg-[#fafaf7] p-4">
                        <span class="text-[10px] font-bold uppercase text-[#8e9187] block">Assigned Role</span>
                        <strong class="text-sm font-bold text-[#687158] mt-1 block">Finance Manager / Director</strong>
                    </div>
                    <div class="rounded-xl border border-[#deded6] bg-[#fafaf7] p-4">
                        <span class="text-[10px] font-bold uppercase text-[#8e9187] block">Active Session IP</span>
                        <strong class="text-sm font-mono font-bold text-[#252820] mt-1 block"><?= e($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></strong>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
    </script>
</body>
</html>
