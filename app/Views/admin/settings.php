<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$value = static fn(string $key, mixed $default = '') => $settings[$key] ?? $default;
$storeName = (string)$value('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>System Settings — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Urbanist', 'ui-sans-serif', 'system-ui'] },
                    colors: {
                        noir: 'var(--brand-dark, #252820)',
                        olive: 'var(--brand-primary, #687158)',
                        paper: 'var(--brand-paper, #f7f7f2)',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <style>
        body { font-family: Urbanist, ui-sans-serif, system-ui; }
        .admin-main { margin-left: 250px; min-height: 100vh; background: var(--brand-paper, #f7f7f2); }
        .admin-topbar { display: flex; justify-content: space-between; gap: 24px; padding: 34px 42px; border-bottom: 1px solid #e5e5dd; }
        .panel-luxury {
            display: block !important;
            background: #ffffff !important;
            border: 1px solid #e5e5dd !important;
            border-radius: 24px !important;
            padding: 28px !important;
            box-shadow: 0 4px 20px rgba(37,40,32,0.03) !important;
            transition: box-shadow .2s ease, transform .2s ease !important;
        }
        .palette-card {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            text-align: left !important;
            border: 1.5px solid #deded6 !important;
            border-radius: 16px !important;
            background: #fafaf7 !important;
            padding: 14px !important;
            cursor: pointer !important;
            transition: all 0.2s cubic-bezier(0.2, 0.8, 0.2, 1) !important;
            outline: none !important;
            width: 100% !important;
        }
        .palette-card:hover {
            transform: translateY(-3px) !important;
            border-color: var(--brand-primary, #687158) !important;
            background: #ffffff !important;
            box-shadow: 0 8px 24px rgba(37,40,32,0.08) !important;
        }
        .palette-card.active {
            border-color: var(--brand-primary, #687158) !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 2px var(--brand-primary, #687158) !important;
        }
        .field-input {
            width: 100% !important;
            display: block !important;
            border: 1px solid #deded6 !important;
            border-radius: 12px !important;
            background: #fafaf7 !important;
            padding: 11px 14px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #252820 !important;
            outline: none !important;
            transition: border-color 0.2s, background-color 0.2s !important;
        }
        .field-input:focus {
            border-color: var(--brand-primary, #687158) !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(104,113,88,0.12) !important;
        }
        /* Disable native Windows/Edge password reveal icons to prevent double-eye overlay */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        .input-password-group {
            display: flex !important;
            align-items: center !important;
            border: 1px solid #deded6 !important;
            border-radius: 12px !important;
            background: #fafaf7 !important;
            overflow: hidden !important;
            transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s !important;
        }
        .input-password-group:focus-within {
            border-color: var(--brand-primary, #687158) !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(104,113,88,0.12) !important;
        }
        .input-password-group .field-input-clean {
            flex: 1 !important;
            border: none !important;
            background: transparent !important;
            padding: 11px 14px !important;
            outline: none !important;
            box-shadow: none !important;
            color: #252820 !important;
            min-width: 0 !important;
            font-size: 13px !important;
            font-weight: 600 !important;
        }
        .input-password-group .btn-toggle-eye {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 14px !important;
            background: transparent !important;
            border: none !important;
            color: #8e9187 !important;
            cursor: pointer !important;
            transition: color 0.15s ease !important;
            outline: none !important;
        }
        .input-password-group .btn-toggle-eye:hover {
            color: #252820 !important;
        }
    </style>
</head>
<body class="text-[#252820]">
<?php $activeNav = 'settings'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <!-- Topbar Component -->
    <?php 
    $eyebrow = 'Global Configuration';
    $title = 'System Settings';
    $subtitle = 'Configure storefront identity, dynamic branding palettes, media assets, policies, and integrations.';
    include __DIR__ . '/partials/topbar.php'; 
    ?>

    <div class="p-6 lg:p-10 max-w-6xl mx-auto space-y-8">
        
        <?php if (!empty($saved)): ?>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-bold text-emerald-800 shadow-sm flex items-center gap-2">
                <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-600"></i>
                Settings saved and brand parameters updated successfully.
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs font-bold text-rose-800 shadow-sm flex items-center gap-2">
                <i data-lucide="alert-circle" class="h-5 w-5 text-rose-600"></i>
                <?= $_GET['error'] === 'upload' ? 'Upload failed: Image must be JPG, PNG, or WebP up to 5MB.' : 'Please enter a valid configuration value.' ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="<?= e(($base ?: '') . '/admin/settings') ?>" class="space-y-8">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <!-- 1. Store Identity -->
            <section class="panel-luxury">
                <div class="pb-5 border-b border-[#f0f1ec]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="store" class="h-5 w-5 text-[#687158]"></i>
                        <h2 class="text-xl font-extrabold text-[#252820]">Storefront Identity</h2>
                    </div>
                    <p class="text-xs text-[#8e9187] mt-1">Core business information displayed across customer receipts, emails, and headers.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 mt-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Store Name</label>
                        <input type="text" name="store_name" value="<?= e((string)$value('store_name', 'Noir & Co.')) ?>" required class="field-input">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Established Year</label>
                        <input type="text" name="hero_established" value="<?= e((string)$value('hero_established', '2026')) ?>" class="field-input">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Store Email Address</label>
                        <input type="email" name="store_email" value="<?= e((string)$value('store_email')) ?>" required class="field-input">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Customer Support Phone</label>
                        <input type="text" name="store_phone" value="<?= e((string)$value('store_phone')) ?>" class="field-input">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Store Currency</label>
                        <select name="currency" class="field-input">
                            <option value="NGN" <?= $value('currency') === 'NGN' ? 'selected' : '' ?>>NGN — Nigerian Naira (₦)</option>
                            <option value="USD" <?= $value('currency') === 'USD' ? 'selected' : '' ?>>USD — US Dollar ($)</option>
                            <option value="GBP" <?= $value('currency') === 'GBP' ? 'selected' : '' ?>>GBP — British Pound (£)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Operational Timezone</label>
                        <select name="timezone" class="field-input">
                            <option value="Africa/Lagos" <?= $value('timezone') === 'Africa/Lagos' ? 'selected' : '' ?>>Africa/Lagos (UTC+1)</option>
                            <option value="UTC" <?= $value('timezone') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                            <option value="Europe/London" <?= $value('timezone') === 'Europe/London' ? 'selected' : '' ?>>Europe/London (GMT/BST)</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Physical Atelier Address</label>
                        <textarea name="store_address" rows="2" class="field-input"><?= e((string)$value('store_address')) ?></textarea>
                    </div>
                </div>
            </section>

            <!-- 2. Homepage Hero Content -->
            <section class="panel-luxury">
                <div class="pb-5 border-b border-[#f0f1ec]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="layout-template" class="h-5 w-5 text-[#687158]"></i>
                        <h2 class="text-xl font-extrabold text-[#252820]">Homepage Hero Banner</h2>
                    </div>
                    <p class="text-xs text-[#8e9187] mt-1">Manage the hero headline, emphasis typography, description text, and button displayed at the top of the storefront.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 mt-6">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Eyebrow Tagline</label>
                        <input type="text" name="hero_eyebrow" value="<?= e((string)$value('hero_eyebrow', 'The new standard')) ?>" placeholder="e.g. The new standard" class="field-input">
                        <small class="text-[11px] text-[#8e9187] mt-1 block">Small uppercase label displayed above the main headline.</small>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Headline Line 1</label>
                        <input type="text" name="hero_title_line1" value="<?= e((string)$value('hero_title_line1', 'Objects with')) ?>" placeholder="e.g. Objects with" class="field-input">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Highlighted / Italic Word</label>
                        <input type="text" name="hero_title_highlight" value="<?= e((string)$value('hero_title_highlight', 'quiet')) ?>" placeholder="e.g. quiet" class="field-input font-serif italic">
                        <small class="text-[11px] text-[#8e9187] mt-1 block">Rendered in the distinctive italic serif style.</small>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Headline Line 2 (Suffix)</label>
                        <input type="text" name="hero_title_line2" value="<?= e((string)$value('hero_title_line2', 'character.')) ?>" placeholder="e.g. character." class="field-input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Hero Body / Subtitle</label>
                        <textarea name="hero_subtitle" rows="3" placeholder="e.g. Thoughtfully sourced pieces for a life well lived. Discover the latest collection from..." class="field-input"><?= e((string)$value('hero_subtitle', 'Thoughtfully sourced pieces for a life well lived. Discover the latest collection from Noir & Co.')) ?></textarea>
                        <small class="text-[11px] text-[#8e9187] mt-1 block">Supporting paragraph under the headline on the storefront hero section.</small>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Call to Action Button Label</label>
                        <input type="text" name="hero_cta_text" value="<?= e((string)$value('hero_cta_text', 'Explore collection')) ?>" placeholder="e.g. Explore collection" class="field-input">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Button Link Target</label>
                        <input type="text" name="hero_cta_link" value="<?= e((string)$value('hero_cta_link', '#catalog')) ?>" placeholder="e.g. #catalog or /store" class="field-input">
                    </div>
                </div>
            </section>

            <!-- 3. Profile & Store Logo -->
            <section class="panel-luxury">
                <div class="pb-5 border-b border-[#f0f1ec]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="image" class="h-5 w-5 text-[#687158]"></i>
                        <h2 class="text-xl font-extrabold text-[#252820]">Media Assets &amp; Brand Logo</h2>
                    </div>
                    <p class="text-xs text-[#8e9187] mt-1">Upload visual assets representing your brand across storefront navigation headers and admin avatars.</p>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 mt-6">
                    <!-- Store Logo -->
                    <div class="rounded-3xl border border-[#e5e5dd] bg-[#fafaf7] p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <strong class="text-xs font-bold uppercase tracking-wider text-[#8e9187]">Storefront Brand Logo</strong>
                                <?php if (store_setting('store_logo')): ?>
                                    <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-700">Active Logo</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-4 mb-5">
                                <div id="container-preview-logo" class="relative h-16 w-16 shrink-0 rounded-2xl border border-[#deded6] bg-white overflow-hidden shadow-sm flex items-center justify-center">
                                    <?php if (store_setting('store_logo')): ?>
                                        <img id="img-preview-logo" src="<?= e($base . store_setting('store_logo')) ?>" alt="Logo" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <img id="img-preview-logo" src="" alt="Logo" class="h-full w-full object-cover hidden">
                                        <span id="fallback-logo" class="grid h-full w-full place-items-center dynamic-brand-primary-bg text-xl font-bold text-white shadow-sm" style="background-color: var(--brand-primary, #687158);">NC</span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1">
                                    <strong class="block text-sm font-bold text-[#252820]">Header &amp; Checkout Mark</strong>
                                    <p class="text-xs text-[#8e9187]">Square or circular mark (recommended 250×250px).</p>
                                    <?php if (store_setting('store_logo')): ?>
                                        <button type="submit" name="remove_logo" value="1" formnovalidate class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 hover:bg-rose-100 transition-colors">
                                            <i data-lucide="trash-2" class="h-3 w-3"></i>
                                            Remove current logo
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Luxury Custom File Input -->
                        <label class="group relative flex items-center justify-between rounded-2xl border-2 border-dashed border-[#deded6] bg-white p-3.5 cursor-pointer hover:border-[var(--brand-primary)] hover:bg-[#fafaf7] transition-all shadow-sm">
                            <input type="file" name="logo" id="input-file-logo" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="hidden" onchange="handleFileSelected(this, 'img-preview-logo', 'label-selected-logo', 'fallback-logo')">
                            <div class="flex items-center gap-3">
                                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#f0f1ec] text-[var(--brand-primary)] group-hover:scale-105 transition-transform" style="color: var(--brand-primary, #687158);">
                                    <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <span id="label-selected-logo" class="block text-xs font-bold text-[#252820] group-hover:text-[var(--brand-primary)] transition-colors">Choose New Logo</span>
                                    <span class="block text-[10px] text-[#8e9187] font-medium">JPG, PNG, WebP or SVG up to 5MB</span>
                                </div>
                            </div>
                            <span class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3.5 py-1.5 text-xs font-bold text-[#252820] shadow-sm group-hover:bg-white group-hover:border-[var(--brand-primary)] transition-all">
                                Browse
                            </span>
                        </label>
                    </div>

                    <!-- Profile Photo -->
                    <div class="rounded-3xl border border-[#e5e5dd] bg-[#fafaf7] p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <strong class="text-xs font-bold uppercase tracking-wider text-[#8e9187]">Administrator Portrait</strong>
                                <?php $profileImage = (string)($user['profile_image'] ?? $user['avatar'] ?? store_setting('admin_profile_image_' . (int)($user['id'] ?? $user['user_id'] ?? 0)) ?: store_setting('admin_profile_image_0')); ?>
                                <?php if ($profileImage !== ''): ?>
                                    <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-700">Active Avatar</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-4 mb-5">
                                <div id="container-preview-profile" class="relative h-16 w-16 shrink-0 rounded-full border border-[#deded6] bg-white overflow-hidden shadow-sm flex items-center justify-center">
                                    <?php if ($profileImage !== ''): ?>
                                        <img id="img-preview-profile" src="<?= e($base . $profileImage) ?>" alt="Profile" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <img id="img-preview-profile" src="" alt="Profile" class="h-full w-full object-cover hidden">
                                        <span id="fallback-profile" class="grid h-full w-full place-items-center dynamic-brand-primary-bg text-xl font-bold text-white shadow-sm" style="background-color: var(--brand-primary, #687158);">
                                            <?= e(strtoupper(substr((string)($user['name'] ?? 'A'), 0, 1))) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1">
                                    <strong class="block text-sm font-bold text-[#252820]"><?= e($user['name'] ?? 'Admin') ?></strong>
                                    <p class="text-xs text-[#8e9187]">Appears on topbar badges, audit logs, and notes.</p>
                                    <?php if ($profileImage !== ''): ?>
                                        <button type="submit" name="remove_profile" value="1" formnovalidate class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 hover:bg-rose-100 transition-colors">
                                            <i data-lucide="trash-2" class="h-3 w-3"></i>
                                            Remove portrait
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Luxury Custom File Input -->
                        <label class="group relative flex items-center justify-between rounded-2xl border-2 border-dashed border-[#deded6] bg-white p-3.5 cursor-pointer hover:border-[var(--brand-primary)] hover:bg-[#fafaf7] transition-all shadow-sm">
                            <input type="file" name="profile" id="input-file-profile" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="handleFileSelected(this, 'img-preview-profile', 'label-selected-profile', 'fallback-profile')">
                            <div class="flex items-center gap-3">
                                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#f0f1ec] text-[var(--brand-primary)] group-hover:scale-105 transition-transform" style="color: var(--brand-primary, #687158);">
                                    <i data-lucide="user" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <span id="label-selected-profile" class="block text-xs font-bold text-[#252820] group-hover:text-[var(--brand-primary)] transition-colors">Choose New Portrait</span>
                                    <span class="block text-[10px] text-[#8e9187] font-medium">JPG, PNG, WebP up to 5MB</span>
                                </div>
                            </div>
                            <span class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3.5 py-1.5 text-xs font-bold text-[#252820] shadow-sm group-hover:bg-white group-hover:border-[var(--brand-primary)] transition-all">
                                Browse
                            </span>
                        </label>
                    </div>
                </div>
            </section>

            <!-- 3. Dynamic Brand Colours & Palettes -->
            <section class="panel-luxury">
                <div class="pb-5 border-b border-[#f0f1ec]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="palette" class="h-5 w-5 text-[#687158]"></i>
                        <h2 class="text-xl font-extrabold text-[#252820]">Dynamic Brand Palettes</h2>
                    </div>
                    <p class="text-xs text-[#8e9187] mt-1">Fine-tune the central color tokens that dynamically paint both the customer storefront and this administrative console.</p>
                </div>

                <!-- Custom Pickers -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mt-6">
                    <div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-[#8e9187] mb-2">Primary Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="brand_primary_color" id="picker-primary" value="<?= e((string)$value('brand_primary_color', '#687158')) ?>" class="h-10 w-10 cursor-pointer rounded-xl border border-[#deded6] bg-transparent p-0">
                            <span class="text-xs font-mono font-bold text-[#252820]" id="lbl-primary"><?= e((string)$value('brand_primary_color', '#687158')) ?></span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-[#8e9187] mb-2">Dark Surface</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="brand_dark_color" id="picker-dark" value="<?= e((string)$value('brand_dark_color', '#252820')) ?>" class="h-10 w-10 cursor-pointer rounded-xl border border-[#deded6] bg-transparent p-0">
                            <span class="text-xs font-mono font-bold text-[#252820]" id="lbl-dark"><?= e((string)$value('brand_dark_color', '#252820')) ?></span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-[#8e9187] mb-2">Accent Highlight</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="brand_accent_color" id="picker-accent" value="<?= e((string)$value('brand_accent_color', '#b9c1a4')) ?>" class="h-10 w-10 cursor-pointer rounded-xl border border-[#deded6] bg-transparent p-0">
                            <span class="text-xs font-mono font-bold text-[#252820]" id="lbl-accent"><?= e((string)$value('brand_accent_color', '#b9c1a4')) ?></span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-[#8e9187] mb-2">Paper Background</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="brand_paper_color" id="picker-paper" value="<?= e((string)$value('brand_paper_color', '#f7f7f2')) ?>" class="h-10 w-10 cursor-pointer rounded-xl border border-[#deded6] bg-transparent p-0">
                            <span class="text-xs font-mono font-bold text-[#252820]" id="lbl-paper"><?= e((string)$value('brand_paper_color', '#f7f7f2')) ?></span>
                        </div>
                    </div>
                </div>

                <!-- 16 Luxury Presets with Rich 4-Color Swatch Bars -->
                <div class="mt-8 pt-6 border-t border-[#f0f1ec]">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <strong class="block text-sm font-extrabold text-[#252820]">Curated Luxury Color Harmonies</strong>
                            <p class="text-xs text-[#8e9187]">Click any preset to instantly apply and preview across the interface.</p>
                        </div>
                        <span class="rounded-full bg-[#f0f1ec] px-3 py-1 text-[11px] font-bold text-[#687158]">16 Presets</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                        <?php 
                        $palettes = [
                            ['Default Noir', '#687158', '#252820', '#b9c1a4', '#f7f7f2', 'Sage Green & Charcoal'],
                            ['Slate & Sand', '#52616b', '#1f2933', '#d7b98e', '#f4f1ea', 'Slate Blue & Sand'],
                            ['Rosewood', '#7b5e57', '#302522', '#d8b4a0', '#f6efeb', 'Dusty Rose & Espresso'],
                            ['Forest', '#496b5b', '#1f3028', '#b9d0b7', '#f1f5ef', 'Forest Pine & Sage'],
                            ['Ocean', '#3d6680', '#1d2b36', '#a9c8d8', '#eef4f7', 'Deep Navy & Mist Blue'],
                            ['Terracotta', '#a65f45', '#38251f', '#e4b29b', '#fbf0e9', 'Burnt Terracotta & Clay'],
                            ['Plum', '#6a5b86', '#2c263b', '#c6b7df', '#f5f1f8', 'Muted Plum & Aubergine'],
                            ['Monochrome', '#3f474b', '#171a1c', '#c5c9c7', '#f1f3f4', 'Graphite & Ink Black'],
                            ['Cognac', '#8B4513', '#2F1810', '#D4A574', '#FDF5E6', 'Warm Cognac & Amber'],
                            ['Emerald', '#2E5A4C', '#1A3329', '#7FB5A3', '#E8F5F0', 'Deep Jade & Mint'],
                            ['Mist', '#4A4E69', '#22223B', '#9A8C98', '#F2E9E4', 'Dusk Violet & Rose'],
                            ['Blush', '#D4A373', '#5C4033', '#FAE1DD', '#FEFAE0', 'Blush Peach & Cocoa'],
                            ['Teal & Gold', '#264653', '#1A3540', '#2A9D8F', '#E9C46A', 'Nordic Teal & Gold'],
                            ['Sage', '#6B705C', '#3D4033', '#CB997E', '#DDDBE6', 'Earthy Sage & Ochre'],
                            ['Spice', '#7B2D26', '#4A1A16', '#E76F51', '#F4A261', 'Rustic Crimson & Amber'],
                            ['Midnight', '#355070', '#1A2F45', '#6D597A', '#B56576', 'Midnight Blue & Berry'],
                        ];
                        foreach ($palettes as [$pname, $pcol, $dcol, $acol, $bgcol, $subtitle]):
                        ?>
                            <button type="button" data-primary="<?= strtolower($pcol) ?>" onclick="setBrandPalette('<?= $pcol ?>','<?= $dcol ?>','<?= $acol ?>','<?= $bgcol ?>')" class="palette-card group">
                                <div class="flex items-center justify-between mb-2">
                                    <strong class="text-xs font-bold text-[#252820] group-hover:text-[var(--brand-primary)] transition-colors"><?= $pname ?></strong>
                                    <i data-lucide="check" class="check-icon h-3.5 w-3.5 opacity-0 group-hover:opacity-100 text-[var(--brand-primary)] transition-opacity"></i>
                                </div>
                                <div class="flex h-5 w-full overflow-hidden rounded-lg border border-black/10 shadow-inner mb-2">
                                    <div style="background: <?= $pcol ?>;" class="flex-1" title="Primary: <?= $pcol ?>"></div>
                                    <div style="background: <?= $dcol ?>;" class="flex-1" title="Dark: <?= $dcol ?>"></div>
                                    <div style="background: <?= $acol ?>;" class="flex-1" title="Accent: <?= $acol ?>"></div>
                                    <div style="background: <?= $bgcol ?>;" class="flex-1" title="Paper: <?= $bgcol ?>"></div>
                                </div>
                                <small class="text-[10px] text-[#8e9187] font-semibold truncate"><?= $subtitle ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- 4. Storefront Behaviour -->
            <section class="panel-luxury">
                <div class="pb-5 border-b border-[#f0f1ec]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sliders" class="h-5 w-5 text-[#687158]"></i>
                        <h2 class="text-xl font-extrabold text-[#252820]">Storefront Policy &amp; Gating</h2>
                    </div>
                    <p class="text-xs text-[#8e9187] mt-1">Control access rules, maintenance gating, and registration policies.</p>
                </div>
                <div class="divide-y divide-[#f0f1ec] mt-3">
                    <label class="flex items-center justify-between py-4 cursor-pointer">
                        <div>
                            <strong class="block text-xs font-bold text-[#252820]">Maintenance Mode</strong>
                            <span class="text-[11px] text-[#8e9187]">Temporarily lock storefront browsing from visitors.</span>
                        </div>
                        <input type="checkbox" name="maintenance_mode" value="1" <?= $value('maintenance_mode') ? 'checked' : '' ?> class="h-5 w-5 rounded-md accent-[#687158]">
                    </label>
                    <label class="flex items-center justify-between py-4 cursor-pointer">
                        <div>
                            <strong class="block text-xs font-bold text-[#252820]">Customer Registration</strong>
                            <span class="text-[11px] text-[#8e9187]">Allow visitors to create accounts on web and mobile app.</span>
                        </div>
                        <input type="checkbox" name="customer_registration" value="1" <?= $value('customer_registration', true) ? 'checked' : '' ?> class="h-5 w-5 rounded-md accent-[#687158]">
                    </label>
                    <label class="flex items-center justify-between py-4 cursor-pointer">
                        <div>
                            <strong class="block text-xs font-bold text-[#252820]">Guest Checkout</strong>
                            <span class="text-[11px] text-[#8e9187]">Allow express purchasing without mandatory account registration.</span>
                        </div>
                        <input type="checkbox" name="guest_checkout" value="1" <?= $value('guest_checkout', true) ? 'checked' : '' ?> class="h-5 w-5 rounded-md accent-[#687158]">
                    </label>
                </div>
            </section>

            <!-- 5. Operational Notifications -->
            <section class="panel-luxury">
                <div class="pb-5 border-b border-[#f0f1ec]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="bell" class="h-5 w-5 text-[#687158]"></i>
                        <h2 class="text-xl font-extrabold text-[#252820]">Alerts &amp; Notifications</h2>
                    </div>
                    <p class="text-xs text-[#8e9187] mt-1">Configure automated dispatch alerts sent to staff and customers.</p>
                </div>
                <div class="divide-y divide-[#f0f1ec] mt-3">
                    <label class="flex items-center justify-between py-4 cursor-pointer">
                        <div>
                            <strong class="block text-xs font-bold text-[#252820]">Email Notifications</strong>
                            <span class="text-[11px] text-[#8e9187]">Send transactional order receipts and dispatch emails.</span>
                        </div>
                        <input type="checkbox" name="email_notifications" value="1" <?= $value('email_notifications', true) ? 'checked' : '' ?> class="h-5 w-5 rounded-md accent-[#687158]">
                    </label>
                    <label class="flex items-center justify-between py-4 cursor-pointer">
                        <div>
                            <strong class="block text-xs font-bold text-[#252820]">Staff Order Alerts</strong>
                            <span class="text-[11px] text-[#8e9187]">Alert the operations team instantly upon new paid orders.</span>
                        </div>
                        <input type="checkbox" name="order_notifications" value="1" <?= $value('order_notifications', true) ? 'checked' : '' ?> class="h-5 w-5 rounded-md accent-[#687158]">
                    </label>
                    <label class="flex items-center justify-between py-4 cursor-pointer">
                        <div>
                            <strong class="block text-xs font-bold text-[#252820]">Low Stock Alerts</strong>
                            <span class="text-[11px] text-[#8e9187]">Notify atelier team when inventory falls below threshold.</span>
                        </div>
                        <input type="checkbox" name="low_stock_notifications" value="1" <?= $value('low_stock_notifications', true) ? 'checked' : '' ?> class="h-5 w-5 rounded-md accent-[#687158]">
                    </label>
                    <label class="flex items-center justify-between py-4 cursor-pointer">
                        <div>
                            <strong class="block text-xs font-bold text-[#252820]">SMS Alerts</strong>
                            <span class="text-[11px] text-[#8e9187]">Enable automated SMS tracking dispatches when SMS gateway is active.</span>
                        </div>
                        <input type="checkbox" name="sms_notifications" value="1" <?= $value('sms_notifications') ? 'checked' : '' ?> class="h-5 w-5 rounded-md accent-[#687158]">
                    </label>
                </div>
            </section>

            <!-- 6. Integrations & Security (Redesigned Clean Vertical Cards) -->
            <section class="grid gap-6 md:grid-cols-2">
                <!-- Klaviyo -->
                <div class="panel-luxury">
                    <div class="pb-5 border-b border-[#f0f1ec]">
                        <div class="flex items-center gap-2">
                            <i data-lucide="plug" class="h-5 w-5 text-[#687158]"></i>
                            <h2 class="text-lg font-extrabold text-[#252820]">Klaviyo Email Automation</h2>
                        </div>
                        <p class="text-xs text-[#8e9187] mt-1">Connect Klaviyo for automated abandoned cart recovery and marketing sync.</p>
                    </div>
                    <div class="space-y-4 mt-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Public Site ID</label>
                            <input type="text" name="klaviyo_public_key" value="<?= e((string)$value('klaviyo_public_key')) ?>" placeholder="e.g. XyZ123" class="field-input font-mono text-xs">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187]">Private API Key</label>
                                <?php if (!empty($value('klaviyo_private_key'))): ?>
                                    <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md font-bold inline-flex items-center gap-1">
                                        <i data-lucide="shield-check" class="h-3 w-3"></i> Configured
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="input-password-group">
                                <input type="password" id="input-klaviyo-key" name="klaviyo_private_key" value="" placeholder="<?= !empty($value('klaviyo_private_key')) ? '•••••••••••••••••••• (Encrypted — Leave blank to keep)' : 'pk_...' ?>" class="field-input-clean font-mono text-xs">
                                <button type="button" class="btn-toggle-eye" onclick="togglePasswordEye('input-klaviyo-key', this)" title="Toggle visibility">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                            </div>
                            <small class="text-[11px] text-[#8e9187] mt-1.5 block">Leave empty to keep existing key. Enter a new key only if rotating.</small>
                        </div>
                    </div>
                </div>

                <!-- Admin Session Security -->
                <div class="panel-luxury">
                    <div class="pb-5 border-b border-[#f0f1ec]">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield" class="h-5 w-5 text-[#687158]"></i>
                            <h2 class="text-lg font-extrabold text-[#252820]">Admin Session Security</h2>
                        </div>
                        <p class="text-xs text-[#8e9187] mt-1">Control administrative login timeouts and access expiration.</p>
                    </div>
                    <div class="space-y-4 mt-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Session Inactivity Timeout</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="session_timeout" min="5" max="1440" value="<?= e((string)$value('session_timeout', 120)) ?>" class="field-input max-w-[140px]">
                                <span class="text-xs font-bold text-[#8e9187]">minutes</span>
                            </div>
                            <small class="text-[11px] text-[#8e9187] mt-2 block font-medium">Default: 120 minutes (2 hours). Inactive sessions will automatically sign out.</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 7. Payment Gateway & AIOps Maintenance -->
            <section class="grid gap-6 md:grid-cols-2">
                <!-- Paystack Live Gateway -->
                <div class="panel-luxury">
                    <div class="pb-5 border-b border-[#f0f1ec]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="credit-card" class="h-5 w-5 text-[#687158]"></i>
                                <h2 class="text-lg font-extrabold text-[#252820]">Paystack Live Gateway</h2>
                            </div>
                            <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-700">
                                Live Mode Active
                            </span>
                        </div>
                        <p class="text-xs text-[#8e9187] mt-1">Configure and monitor live transaction keys and webhook listeners.</p>
                    </div>
                    <div class="space-y-4 mt-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Live Public Key</label>
                            <input type="text" name="paystack_public_key" value="<?= e((string)$value('paystack_public_key', $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '')) ?>" class="field-input font-mono text-xs">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187]">Live Secret Key</label>
                                <?php $hasPaystack = !empty($value('paystack_secret_key', $_ENV['PAYSTACK_SECRET_KEY'] ?? '')); ?>
                                <?php if ($hasPaystack): ?>
                                    <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md font-bold inline-flex items-center gap-1">
                                        <i data-lucide="shield-check" class="h-3 w-3"></i> Encrypted at Rest
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="input-password-group">
                                <input type="password" id="input-paystack-key" name="paystack_secret_key" value="" placeholder="<?= $hasPaystack ? '•••••••••••••••••••••••••••••••• (Encrypted — Leave blank to keep)' : 'sk_live_...' ?>" class="field-input-clean font-mono text-xs">
                                <button type="button" class="btn-toggle-eye" onclick="togglePasswordEye('input-paystack-key', this)" title="Toggle visibility">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                            </div>
                            <small class="text-[11px] text-[#8e9187] mt-1.5 block">Leave empty to retain your current live secret key. Enter a new key only to rotate.</small>
                        </div>
                        
                        <!-- Webhook Action & Status Box -->
                        <div class="pt-3 border-t border-[#f0f1ec] space-y-2.5">
                            <div class="flex items-center justify-between gap-3">
                                <button type="button" id="btn-test-webhook" class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-4 py-2.5 text-xs font-bold text-[#252820] hover:bg-[#f0f1ec] inline-flex items-center gap-2 transition shadow-sm whitespace-nowrap">
                                    <i data-lucide="radio" class="h-3.5 w-3.5 text-[#687158]"></i>
                                    <span>Ping Webhook Listener</span>
                                </button>
                                <span class="text-[11px] text-[#8e9187] font-medium hidden sm:inline">Verifies listener response</span>
                            </div>
                            <div id="webhook-test-status" class="hidden"></div>
                        </div>
                    </div>
                </div>

                <!-- Automated Self-Healing Scheduler -->
                <div class="panel-luxury">
                    <div class="pb-5 border-b border-[#f0f1ec]">
                        <div class="flex items-center gap-2">
                            <i data-lucide="cpu" class="h-5 w-5 text-[#687158]"></i>
                            <h2 class="text-lg font-extrabold text-[#252820]">Automated AIOps Maintenance</h2>
                        </div>
                        <p class="text-xs text-[#8e9187] mt-1">Schedule automatic B-tree table defragmentation, cache flushing, and telemetry pruning.</p>
                    </div>
                    <div class="space-y-4 mt-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Scheduled Maintenance Time</label>
                            <select name="self_heal_schedule" class="field-input">
                                <option value="03:00" <?= $value('self_heal_schedule', '03:00') === '03:00' ? 'selected' : '' ?>>03:00 AM (Recommended Off-Peak)</option>
                                <option value="01:00" <?= $value('self_heal_schedule') === '01:00' ? 'selected' : '' ?>>01:00 AM</option>
                                <option value="05:00" <?= $value('self_heal_schedule') === '05:00' ? 'selected' : '' ?>>05:00 AM</option>
                                <option value="manual" <?= $value('self_heal_schedule') === 'manual' ? 'selected' : '' ?>>Manual Only (Via Telemetry Panel)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-1.5">Guest Telemetry Retention</label>
                            <select name="telemetry_retention_days" class="field-input">
                                <option value="30" <?= $value('telemetry_retention_days', '30') === '30' ? 'selected' : '' ?>>30 Days (Standard Audit Window)</option>
                                <option value="60" <?= $value('telemetry_retention_days') === '60' ? 'selected' : '' ?>>60 Days</option>
                                <option value="90" <?= $value('telemetry_retention_days') === '90' ? 'selected' : '' ?>>90 Days (Maximum)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Submit Button Bar -->
            <div class="sticky bottom-6 z-20 flex justify-end">
                <button type="submit" id="btn-save-settings" style="background-color: var(--brand-primary, #687158); color: var(--brand-on-primary, #ffffff);" class="inline-flex items-center gap-2 rounded-2xl px-8 py-4 text-xs font-bold shadow-xl transition-all hover:brightness-95 hover:scale-[1.02]">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span>Save All Settings</span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
function updateBrandVariables(primary, dark, accent, paper) { 
    document.documentElement.style.setProperty('--brand-primary', primary); 
    document.documentElement.style.setProperty('--brand-dark', dark); 
    document.documentElement.style.setProperty('--brand-accent', accent); 
    document.documentElement.style.setProperty('--brand-paper', paper);
    
    // Explicitly paint the Save Button and all dynamic brand accents in real-time
    const saveBtn = document.getElementById('btn-save-settings');
    if (saveBtn) {
        saveBtn.style.backgroundColor = primary;
    }
    
    document.querySelectorAll('.dynamic-brand-primary-bg').forEach(el => el.style.backgroundColor = primary);
    document.querySelectorAll('.dynamic-brand-primary-text').forEach(el => el.style.color = primary);
    document.querySelectorAll('.dynamic-brand-primary-border').forEach(el => el.style.borderColor = primary);

    const lblP = document.getElementById('lbl-primary');
    const lblD = document.getElementById('lbl-dark');
    const lblA = document.getElementById('lbl-accent');
    const lblBg = document.getElementById('lbl-paper');
    if (lblP) lblP.textContent = primary;
    if (lblD) lblD.textContent = dark;
    if (lblA) lblA.textContent = accent;
    if (lblBg) lblBg.textContent = paper;
}

function setBrandPalette(primary, dark, accent, paper) {
    document.getElementById('picker-primary').value = primary;
    document.getElementById('picker-dark').value = dark;
    document.getElementById('picker-accent').value = accent;
    document.getElementById('picker-paper').value = paper;
    updateBrandVariables(primary, dark, accent, paper);

    // Highlight active card
    document.querySelectorAll('.palette-card').forEach(c => {
        const isCurrent = c.getAttribute('data-primary') === primary.toLowerCase();
        c.classList.toggle('active', isCurrent);
        const icon = c.querySelector('.check-icon');
        if (icon) icon.style.opacity = isCurrent ? '1' : '0';
    });
}

function handleFileSelected(input, imgId, labelId, fallbackId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const labelEl = document.getElementById(labelId);
        if (labelEl) {
            labelEl.textContent = file.name;
            labelEl.style.color = 'var(--brand-primary, #687158)';
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgEl = document.getElementById(imgId);
            const fallbackEl = fallbackId ? document.getElementById(fallbackId) : null;
            if (imgEl) {
                imgEl.src = e.target.result;
                imgEl.classList.remove('hidden');
            }
            if (fallbackEl) {
                fallbackEl.classList.add('hidden');
            }
        };
        reader.readAsDataURL(file);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    const p = document.getElementById('picker-primary');
    const d = document.getElementById('picker-dark');
    const a = document.getElementById('picker-accent');
    const bg = document.getElementById('picker-paper');
    if (p && d && a && bg) {
        const update = () => updateBrandVariables(p.value, d.value, a.value, bg.value);
        p.addEventListener('input', update);
        d.addEventListener('input', update);
        a.addEventListener('input', update);
        bg.addEventListener('input', update);
        // Initialize dynamic colors immediately on load
        updateBrandVariables(p.value, d.value, a.value, bg.value);
    }

    const testWebhookBtn = document.getElementById('btn-test-webhook');
    const webhookStatus = document.getElementById('webhook-test-status');
    if (testWebhookBtn && webhookStatus) {
        testWebhookBtn.addEventListener('click', async () => {
            testWebhookBtn.disabled = true;
            testWebhookBtn.innerHTML = '<i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin"></i> <span>Pinging...</span>';
            lucide.createIcons();
            try {
                const res = await fetch('<?= e(($base ?: '') . '/admin/settings/test-webhook') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_csrf=<?= e(csrf_token()) ?>'
                });
                const data = await res.json();
                webhookStatus.classList.remove('hidden');
                if (data.success) {
                    webhookStatus.className = 'rounded-xl border border-emerald-200 bg-emerald-50/90 p-3 text-xs text-emerald-800 shadow-sm flex items-start gap-2.5';
                    webhookStatus.innerHTML = '<i data-lucide="check-circle-2" class="h-4 w-4 text-emerald-600 shrink-0 mt-0.5"></i> <div><strong class="block font-bold">Webhook Listener Verified (HTTP 200 OK)</strong><span class="text-[11px] opacity-90">' + data.message + '</span></div>';
                } else {
                    webhookStatus.className = 'rounded-xl border border-rose-200 bg-rose-50/90 p-3 text-xs text-rose-800 shadow-sm flex items-start gap-2.5';
                    webhookStatus.innerHTML = '<i data-lucide="alert-circle" class="h-4 w-4 text-rose-600 shrink-0 mt-0.5"></i> <div><strong class="block font-bold">Webhook Verification Failed</strong><span class="text-[11px] opacity-90">' + data.message + '</span></div>';
                }
                lucide.createIcons();
            } catch (err) {
                alert('Webhook ping failed: ' + err.message);
            } finally {
                testWebhookBtn.disabled = false;
                testWebhookBtn.innerHTML = '<i data-lucide="radio" class="h-3.5 w-3.5 text-[#687158]"></i> <span>Ping Webhook Listener</span>';
                lucide.createIcons();
            }
        });
    }
});

function togglePasswordEye(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass ? '<i data-lucide="eye-off" class="h-4 w-4 text-[#252820]"></i>' : '<i data-lucide="eye" class="h-4 w-4 text-[#8e9187]"></i>';
    if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }
}
</script>
</body>
</html>
