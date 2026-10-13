<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$url = ($base ?: '') . '/admin/products';
$money = fn($n): string => '₦' . number_format((float)$n, 2);
$stock = (int)$product['quantity'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($product['name']) ?> — Products</title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/admin-mobile.min.js') ?>" defer></script>
</head>
<body class="admin-page bg-[#f6f6f1]">
    <?php $activeNav = 'products'; include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <a class="text-xs font-bold text-[#687158] hover:underline flex items-center gap-1.5 mb-2" href="<?= e($url) ?>">
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i> Back to Products
                </a>
                <h1 class="text-3xl font-extrabold tracking-tight text-[#252820]"><?= e($product['name']) ?></h1>
                <p class="mt-1 text-sm text-[#77796f]">Review photography, inventory depth, SKU specification, and pricing margins.</p>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <a href="<?= e(($base ?: '') . '/store/product/' . rawurlencode((string)($product['slug'] ?: $product['id']))) ?>" target="_blank" class="rounded-xl border border-[#d9d9d0] bg-white px-4 py-2.5 text-xs font-bold text-[#687158] hover:bg-[#f0f1eb] flex items-center gap-2 shadow-sm">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    View on Storefront
                </a>
                <?php $user = \Noir\Core\Auth::user(); ?>
                <?php $adminAvatar = (string)($user['profile_image'] ?? $user['avatar'] ?? store_setting('admin_profile_image_' . (int)($user['id'] ?? $user['user_id'] ?? 0)) ?: store_setting('admin_profile_image_0')); ?>
                <?php if ($adminAvatar !== ''): ?>
                    <img src="<?= e(($base ?: '') . $adminAvatar) ?>" alt="<?= e($user['name'] ?? 'Admin') ?>" class="h-9 w-9 rounded-full object-cover border border-[#deded6]">
                <?php else: ?>
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-[#687158] text-xs font-bold text-white shadow-sm">
                        <?= e(strtoupper(substr((string)($user['name'] ?? 'A'), 0, 1))) ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <section class="p-5 lg:p-10 space-y-6">
            <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
                <!-- Product Photography Display -->
                <section class="rounded-2xl border border-[#e3e3da] bg-white p-6 shadow-[0_8px_30px_rgba(37,40,32,.04)]">
                    <h3 class="text-xs uppercase tracking-wider font-bold text-[#85877d] mb-4">Product Photography</h3>
                    <div class="flex min-h-[360px] items-center justify-center rounded-2xl bg-[#fbfbf8] border border-[#ecece5] p-4 overflow-hidden">
                        <?php if (!empty($product['featured_image'])): ?>
                            <img src="<?= e(media_url($base, $product['featured_image'])) ?>" class="max-h-[360px] w-auto max-w-full rounded-xl object-contain shadow-sm" alt="<?= e($product['name']) ?>">
                        <?php else: ?>
                            <div class="text-center text-[#85877d] space-y-2">
                                <i data-lucide="image" class="h-12 w-12 mx-auto text-[#9aa18c]"></i>
                                <p class="text-xs">No image uploaded yet</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($images)): ?>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <?php foreach ($images as $image): ?>
                                <img src="<?= e(media_url($base, $image['path'])) ?>" class="h-16 w-16 rounded-xl object-cover border border-[#e3e3da]" alt="">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="space-y-4">
                    <div class="rounded-2xl border border-[#e3e3da] bg-white p-6 shadow-[0_8px_30px_rgba(37,40,32,.04)]">
                        <p class="eyebrow">Overview</p>
                        <div class="mt-5 grid grid-cols-2 gap-5">
                            <div>
                                <span class="text-xs text-[#85877d]">Selling price</span>
                                <strong class="mt-1 block text-xl text-[#252820]"><?= $money($product['price']) ?></strong>
                            </div>
                            <div>
                                <span class="text-xs text-[#85877d]">Cost price</span>
                                <strong class="mt-1 block text-xl text-[#686b62]"><?= $money($product['cost_price']) ?></strong>
                            </div>
                            <div>
                                <span class="text-xs text-[#85877d]">Inventory</span>
                                <strong class="mt-1 block text-xl <?= $stock <= 0 ? 'text-[#ad6763]' : 'text-[#252820]' ?>"><?= number_format($stock) ?></strong>
                            </div>
                            <div>
                                <span class="text-xs text-[#85877d]">Status</span>
                                <strong class="mt-1 block text-xl text-[#252820]"><?= $product['status'] === 'published' ? 'Active' : e(ucfirst($product['status'])) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[#e3e3da] bg-white p-6 shadow-[0_8px_30px_rgba(37,40,32,.04)]">
                        <p class="eyebrow">Product information</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#85877d]">SKU</dt>
                                <dd class="font-medium text-[#252820]"><?= e($product['sku'] ?: '—') ?></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#85877d]">Category</dt>
                                <dd class="font-medium text-[#252820]"><?= e($product['category'] ?: 'Uncategorized') ?></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#85877d]">Brand</dt>
                                <dd class="font-medium text-[#252820]"><?= e($product['brand'] ?: '—') ?></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[#85877d]">Last updated</dt>
                                <dd class="text-[#686b62]"><?= e(date('d M Y H:i', strtotime($product['updated_at']))) ?></dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </div>

            <!-- Configuration & Edit Form -->
            <section class="rounded-2xl border border-[#e3e3da] bg-white p-6 shadow-[0_8px_30px_rgba(37,40,32,.04)]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="eyebrow">Edit product</p>
                        <h2 class="mt-1 text-2xl font-bold text-[#252820]">Product configuration</h2>
                    </div>
                </div>

                <form class="mt-6 grid gap-4 md:grid-cols-2" method="post" action="<?= e($url . '/' . (int)$product['id']) ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                    <label class="field">Name
                        <input name="name" value="<?= e($product['name']) ?>" required>
                    </label>

                    <label class="field">Slug
                        <input name="slug" value="<?= e($product['slug']) ?>">
                    </label>

                    <label class="field">SKU
                        <input name="sku" value="<?= e($product['sku']) ?>">
                    </label>

                    <label class="field">Category
                        <input name="category" value="<?= e($product['category']) ?>">
                    </label>

                    <label class="field">Brand
                        <input name="brand" value="<?= e($product['brand']) ?>">
                    </label>

                    <label class="field">Quantity
                        <input name="quantity" type="number" min="0" value="<?= (int)$product['quantity'] ?>">
                    </label>

                    <label class="field">Cost price
                        <input name="cost_price" type="number" step=".01" value="<?= e((string)$product['cost_price']) ?>">
                    </label>

                    <label class="field">Selling price
                        <input name="price" type="number" step=".01" value="<?= e((string)$product['price']) ?>" required>
                    </label>

                    <label class="field">Status
                        <select name="status">
                            <?php foreach (['draft' => 'Draft', 'published' => 'Active', 'pending' => 'Pending', 'archived' => 'Archived'] as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $product['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field">Change Featured Image
                        <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp,image/avif,image/gif">
                    </label>

                    <label class="field md:col-span-2">Description
                        <textarea name="description" rows="5"><?= e($product['description']) ?></textarea>
                    </label>

                    <div class="md:col-span-2 pt-2 flex justify-end">
                        <button class="rounded-xl bg-[#687158] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#596249] shadow-sm">Save Changes</button>
                    </div>
                </form>
            </section>
        </section>
    </main>

    <style>
        .field { display: grid; gap: .45rem; font-size: .75rem; color: #77796f; font-weight: 600; }
        .field input, .field textarea, .field select { border: 1px solid #deded6; background: #fbfbf8; border-radius: .75rem; padding: .7rem .85rem; color: #252820; outline: 0; font-size: 13px; }
        .field input:focus, .field textarea:focus, .field select:focus { border-color: #687158; background: #fff; }
    </style>
    <script>lucide.createIcons()</script>
</body>
</html>
