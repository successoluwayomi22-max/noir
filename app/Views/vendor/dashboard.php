<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Container::get('auth')->user() ?? [];
$avatar = (string)($user['profile_image'] ?? $user['avatar'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Vendor Workspace — <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
</head>
<body class="admin-page">
<aside class="admin-sidebar">
    <a class="brand" href="<?= e(($base ?: '') . '/') ?>"><?= e(store_setting('store_name', 'Noir & Co.')) ?></a>
    <p class="sidebar-label">Vendor workspace</p>
    <a class="side-link active" href="<?= e(($base ?: '') . '/vendor/dashboard') ?>">Overview</a>
    <a class="side-link" href="<?= e(($base ?: '') . '/') ?>">View storefront ↗</a>
</aside>
<main class="admin-main">
    <header class="admin-topbar">
        <div>
            <p class="eyebrow">Vendor portal</p>
            <h1>Welcome, <?= e(explode(' ', $user['name'] ?? 'Vendor')[0]) ?>.</h1>
            <p class="admin-subtitle">Manage your vendor profile and catalogue products.</p>
        </div>
        <div class="admin-user" style="display:flex; align-items:center; gap:12px;">
            <?php if ($avatar !== ''): ?>
                <img src="<?= e(($base ?: '') . $avatar) ?>" alt="<?= e($user['name'] ?? 'Vendor') ?>" style="width:36px; height:36px; border-radius:9999px; object-fit:cover; border:1px solid rgba(0,0,0,0.1);">
            <?php else: ?>
                <span style="display:inline-grid; place-items:center; width:36px; height:36px; border-radius:9999px; background:#687158; color:#fff; font-size:12px; font-weight:600;"><?= e(strtoupper(substr((string)($user['name'] ?? 'V'), 0, 1))) ?></span>
            <?php endif; ?>
            <div>
                <strong style="display:block; font-size:13px; line-height:1.2;"><?= e($user['name'] ?? 'Vendor') ?></strong>
                <span style="font-size:11px; color:#85877d;"><?= e($user['email'] ?? '') ?></span>
            </div>
            <form method="post" action="<?= e(($base ?: '') . '/logout') ?>" style="margin-left:8px;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" style="background:transparent; border:none; color:#687158; cursor:pointer; font-size:12px;">Sign out</button>
            </form>
        </div>
    </header>

    <?php if (isset($_GET['saved'])): ?>
        <div class="form-message success settings-message" style="margin:20px 42px 0;">Settings saved successfully.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="form-message error settings-message" style="margin:20px 42px 0;">Upload failed. Image must be JPG, PNG, or WebP up to 5MB.</div>
    <?php endif; ?>

    <section class="admin-panel" style="margin:32px 42px;">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Account</p>
                <h2>Profile settings</h2>
            </div>
        </div>
        <form class="settings-form" method="post" enctype="multipart/form-data" action="<?= e(($base ?: '') . '/vendor/settings/profile') ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="settings-fields settings-upload-fields" style="display:flex; flex-wrap:wrap; align-items:center; gap:24px; padding:20px 0;">
                <div style="width:80px; height:80px; border-radius:9999px; overflow:hidden; border:2px solid #687158; box-shadow:0 4px 14px rgba(0,0,0,0.1); background:#f4f4ed; display:grid; place-items:center;">
                    <?php if ($avatar !== ''): ?>
                        <img src="<?= e(($base ?: '') . $avatar) ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; display:block;">
                    <?php else: ?>
                        <span style="font-size:24px; font-weight:700; color:#687158;"><?= e(strtoupper(substr((string)($user['name'] ?? 'V'), 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <label style="font-size:13px; font-weight:600;">Profile picture
                        <input type="file" name="profile" accept="image/jpeg,image/png,image/webp" style="margin-top:6px; display:block;">
                    </label>
                    <div style="display:flex; gap:10px; margin-top:4px;">
                        <button class="button" type="submit" style="padding:8px 16px; font-size:12px;">Save photo</button>
                        <?php if ($avatar !== ''): ?>
                            <button class="upload-remove-button" type="submit" name="remove_profile" value="1" style="padding:8px 16px; font-size:12px; border-radius:10px; background:#fff; border:1px solid #dcdcd4; color:#c0392b; cursor:pointer;">Delete photo</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <section class="admin-panel" style="margin:0 42px 32px;">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Workspace</p>
                <h2>Catalogue & orders</h2>
            </div>
        </div>
        <p style="padding:20px; color:#77796f; font-size:13px;">Your vendor dashboard is ready. Product, inventory, order, and sales tools will appear here as those modules are enabled.</p>
    </section>
</main>
</body>
</html>
