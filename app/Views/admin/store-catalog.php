<?php declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$activeNav = 'store-catalog';

$catActive = 0; $catHidden = 0;
foreach ($categories as $c) { if (!empty($c['is_active'])) $catActive++; else $catHidden++; }

$brandActive = 0; $brandHidden = 0;
foreach ($brands as $b) { if (!empty($b['is_active'])) $brandActive++; else $brandHidden++; }

$colActive = 0; $colHidden = 0;
foreach ($collections as $col) { if (!empty($col['is_featured'])) $colActive++; else $colHidden++; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Store catalogue — <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Urbanist', 'ui-sans-serif', 'system-ui'] },
                    colors: { noir: '#252820', olive: '#687158', paper: '#f5f5f0' }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/admin-mobile.min.js') ?>" defer></script>
    <style>
        body { font-family: Urbanist, ui-sans-serif, system-ui; }
        .admin-main { margin-left: 260px; min-height: 100vh; background: #f6f6f1; padding-bottom: 40px; }
        .admin-topbar { display: flex; justify-content: space-between; align-items: center; gap: 24px; padding: 32px 40px; border-bottom: 1px solid #e5e5dd; background: #fff; }
        @media (max-width: 900px) {
            .admin-main { margin-left: 0; }
            .admin-topbar { padding: 24px 20px; flex-direction: column; align-items: flex-start; }
        }
        .toggle-track { width: 36px; height: 20px; border-radius: 9999px; transition: background-color .2s; position: relative; cursor: pointer; }
        .toggle-thumb { width: 14px; height: 14px; border-radius: 9999px; background: #fff; position: absolute; top: 3px; left: 3px; transition: transform .2s; }
        .toggle-track.active { background-color: #687158; }
        .toggle-track.active .toggle-thumb { transform: translateX(16px); }
        .toggle-track.inactive { background-color: #d1d5db; }
        .catalog-item-card { transition: all .2s ease; }
        .catalog-item-card.is-hidden { opacity: 0.65; background-color: #fbfbf9; }
        .field { display: grid; gap: .4rem; font-size: .8rem; color: #5f6358; font-weight: 500; }
        .field input, .field textarea, .field select { border: 1px solid #deded6; border-radius: .75rem; background: #fff; padding: .75rem .9rem; color: #252820; outline: 0; font-size: .85rem; }
        .field input:focus, .field textarea:focus { border-color: #687158; box-shadow: 0 0 0 2px rgba(104,113,88,0.15); }
    </style>
</head>
<body class="text-[#252820]">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <header class="admin-topbar">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Store Architecture</p>
            <h1 class="text-3xl font-medium tracking-tight">Catalogue management</h1>
            <p class="mt-1 text-sm text-[#77796f]">Control the categories, brands, and collections that shape your online store and mobile app.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= e(($base ?: '') . '/store') ?>" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-[#deded6] bg-white px-4 py-2.5 text-xs font-semibold text-[#252820] shadow-sm hover:bg-[#f5f5f0]">
                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>View Storefront
            </a>
        </div>
    </header>

    <div class="p-6 lg:p-10 space-y-8 max-w-7xl">
        <!-- Summary Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#8e9187]">Categories</span>
                    <div class="grid h-8 w-8 place-items-center rounded-lg bg-[#f0f1ec] text-[#687158]">
                        <i data-lucide="folder-tree" class="h-4 w-4"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <strong class="text-2xl font-bold"><?= count($categories) ?></strong>
                    <span class="text-xs text-[#687158] font-medium"><?= $catActive ?> visible</span>
                    <?php if ($catHidden > 0): ?>
                        <span class="text-xs text-[#877546] font-medium">• <?= $catHidden ?> hidden</span>
                    <?php endif; ?>
                </div>
                <p class="mt-1 text-[11px] text-[#92948a]">Product department grouping</p>
            </div>

            <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#8e9187]">Brands</span>
                    <div class="grid h-8 w-8 place-items-center rounded-lg bg-[#f0f1ec] text-[#687158]">
                        <i data-lucide="badge-check" class="h-4 w-4"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <strong class="text-2xl font-bold"><?= count($brands) ?></strong>
                    <span class="text-xs text-[#687158] font-medium"><?= $brandActive ?> visible</span>
                    <?php if ($brandHidden > 0): ?>
                        <span class="text-xs text-[#877546] font-medium">• <?= $brandHidden ?> hidden</span>
                    <?php endif; ?>
                </div>
                <p class="mt-1 text-[11px] text-[#92948a]">Designers &amp; label taxonomy</p>
            </div>

            <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#8e9187]">Collections</span>
                    <div class="grid h-8 w-8 place-items-center rounded-lg bg-[#f0f1ec] text-[#687158]">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <strong class="text-2xl font-bold"><?= count($collections) ?></strong>
                    <span class="text-xs text-[#687158] font-medium"><?= $colActive ?> visible</span>
                    <?php if ($colHidden > 0): ?>
                        <span class="text-xs text-[#877546] font-medium">• <?= $colHidden ?> hidden</span>
                    <?php endif; ?>
                </div>
                <p class="mt-1 text-[11px] text-[#92948a]">Curated seasonal showcases</p>
            </div>
        </div>

        <!-- Filter and Search Bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#92948a]"></i>
                <input id="catalog-search" type="search" placeholder="Filter categories, brands, or collections…" class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] py-2.5 pl-10 pr-4 text-xs outline-none focus:border-[#687158]">
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="filter-status-btn active rounded-xl px-3 py-2 text-xs font-medium bg-[#252820] text-white" data-filter="all">All</button>
                <button type="button" class="filter-status-btn rounded-xl px-3 py-2 text-xs font-medium text-[#77796f] hover:bg-[#f0f1ec]" data-filter="visible">Visible only</button>
                <button type="button" class="filter-status-btn rounded-xl px-3 py-2 text-xs font-medium text-[#77796f] hover:bg-[#f0f1ec]" data-filter="hidden">Hidden only</button>
            </div>
        </div>

        <!-- 3 Columns Taxonomy Grid -->
        <div class="grid gap-6 lg:grid-cols-3">
            <?php 
            $taxonomySections = [
                ['category', 'Categories', $categories, 'Category', 'category_id'],
                ['brand', 'Brands', $brands, 'Brand', 'brand_id'],
                ['collection', 'Collections', $collections, 'Collection', 'collection_id']
            ];
            foreach ($taxonomySections as [$type, $title, $items, $label, $fkField]): 
            ?>
            <section class="flex flex-col rounded-2xl border border-[#e3e3da] bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-[#e8e8e0] bg-[#fafaf7] px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-[#252820]"><?= $title ?></h2>
                        <span class="text-[11px] text-[#8e9187]"><?= count($items) ?> configured</span>
                    </div>
                    <button class="inline-flex items-center gap-1.5 rounded-xl bg-[#687158] px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#596249] transition-colors" onclick="openForm('<?= $type ?>','<?= $label ?>')">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>Add <?= $label ?>
                    </button>
                </div>

                <div class="p-4 space-y-3 flex-1 overflow-y-auto max-h-[750px]" id="section-<?= $type ?>">
                    <?php if (!$items): ?>
                        <div class="rounded-xl border border-dashed border-[#dcdcd4] bg-[#f9f9f5] p-8 text-center">
                            <i data-lucide="inbox" class="mx-auto h-6 w-6 text-[#92948a]"></i>
                            <p class="mt-2 text-xs text-[#77796f]">No <?= strtolower($title) ?> added yet.</p>
                            <button class="mt-3 text-xs font-semibold text-[#687158] hover:underline" onclick="openForm('<?= $type ?>','<?= $label ?>')">+ Create first <?= strtolower($label) ?></button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($items as $item): 
                        $isActive = $type === 'collection' ? (bool)($item['is_featured'] ?? true) : (bool)($item['is_active'] ?? true);
                        $itemJson = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="catalog-item-card <?= !$isActive ? 'is-hidden' : '' ?> flex flex-col gap-3 rounded-xl border border-[#ecece5] bg-white p-3.5 shadow-sm hover:border-[#deded6]" id="item-<?= $type ?>-<?= (int)$item['id'] ?>" data-type="<?= $type ?>" data-name="<?= strtolower(e($item['name'])) ?>" data-status="<?= $isActive ? 'visible' : 'hidden' ?>">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= e(media_url($b ?? '', $item['image'])) ?>" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover border border-[#e5e5dd]" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800'">
                                <?php else: ?>
                                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[#f0f1ec] text-[#687158] font-bold text-sm">
                                        <?= strtoupper(substr($item['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm text-[#252820] font-semibold"><?= e($item['name']) ?></strong>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] text-[#92948a]">/<?= e($item['slug']) ?></span>
                                        <span class="inline-block h-1 w-1 rounded-full bg-[#c5c8bd]"></span>
                                        <span class="text-[11px] font-medium text-[#687158]"><?= number_format((int)$item['product_count']) ?> products</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($item['description'])): ?>
                            <p class="text-[11px] text-[#77796f] line-clamp-1 italic px-0.5"><?= e($item['description']) ?></p>
                        <?php endif; ?>

                        <div class="flex items-center justify-between pt-2 border-t border-[#f0f0eb]">
                            <!-- Status Badge & Toggle Button -->
                            <div class="flex items-center gap-2">
                                <span class="status-badge inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-semibold <?= $isActive ? 'bg-[#e7eadf] text-[#5d6a51]' : 'bg-[#f0f0eb] text-[#8e9187]' ?>" id="badge-<?= $type ?>-<?= (int)$item['id'] ?>">
                                    <span class="h-1.5 w-1.5 rounded-full <?= $isActive ? 'bg-[#5d6a51]' : 'bg-[#92948a]' ?>"></span>
                                    <span class="badge-text"><?= $isActive ? 'Visible' : 'Hidden' ?></span>
                                </span>

                                <!-- Toggle Switch Button -->
                                <button type="button" 
                                        class="toggle-btn inline-flex items-center gap-1.5 rounded-lg border border-[#deded6] bg-[#fbfbf8] px-2 py-1 text-[11px] font-medium text-[#4b4e45] hover:bg-[#f0f1ec] transition-colors"
                                        onclick="toggleItem('<?= $type ?>', <?= (int)$item['id'] ?>, this)" 
                                        title="<?= $isActive ? 'Click to Hide from Store' : 'Click to Make Visible' ?>"
                                        id="toggle-btn-<?= $type ?>-<?= (int)$item['id'] ?>">
                                    <i data-lucide="<?= $isActive ? 'eye' : 'eye-off' ?>" class="h-3.5 w-3.5 <?= $isActive ? 'text-[#687158]' : 'text-[#8e9187]' ?>"></i>
                                    <span class="toggle-text"><?= $isActive ? 'Hide' : 'Show' ?></span>
                                </button>
                            </div>

                            <!-- Actions: Edit & Delete -->
                            <div class="flex items-center gap-1">
                                <button class="rounded-lg p-1.5 text-[#687158] hover:bg-[#f0f1ec] transition-colors" onclick="openEditForm('<?= $type ?>', '<?= $label ?>', <?= $itemJson ?>)" title="Edit item">
                                    <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                                </button>
                                <button class="rounded-lg p-1.5 text-[#a75b52] hover:bg-[#faebeb] transition-colors" onclick="deleteItem('<?= $type ?>', <?= (int)$item['id'] ?>)" title="Remove item">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Modal for Add & Edit -->
<div id="catalog-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#252820]/50 p-4 backdrop-blur-sm overflow-y-auto">
    <form id="catalog-form" class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-[#e5e5dd] my-auto flex flex-col max-h-[90vh] overflow-hidden">
        <div class="shrink-0 p-6 pb-4 border-b border-[#e8e8e0] flex items-start justify-between bg-white">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]" id="catalog-eyebrow">Store taxonomy</p>
                <h2 id="catalog-title" class="text-xl font-bold text-[#252820]">Add item</h2>
            </div>
            <button type="button" onclick="closeForm()" class="rounded-lg p-1 text-[#92948a] hover:bg-[#f0f1ec]">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        
        <input type="hidden" name="type" id="catalog-type">
        <input type="hidden" name="id" id="catalog-id" value="">
        <input type="hidden" name="action" id="catalog-action" value="create">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="flex-1 overflow-y-auto p-6 space-y-4" style="overscroll-behavior: contain;">
            <label class="field">
                <span>Name <span class="text-red-500">*</span></span>
                <input name="name" id="f-name" required placeholder="e.g. Outerwear, Silk &amp; Cashmere" oninput="autoSlug(this.value)">
            </label>
            <label class="field">
                <span>URL Slug</span>
                <input name="slug" id="f-slug" placeholder="auto-generated from name">
                <small class="text-[11px] text-[#92948a]">Used in storefront routing e.g. /category/outerwear</small>
            </label>
            <label class="field">
                <span>Description</span>
                <textarea name="description" id="f-description" rows="3" placeholder="Brief note explaining this category or brand line…"></textarea>
            </label>
            <label class="field">
                <span>Image path / URL</span>
                <input name="image" id="f-image" placeholder="https://… or /uploads/catalogue/item.jpg" oninput="previewModalImage(this.value)">
                <small class="text-[11px] text-[#92948a]">Banner or icon for boutique navigation</small>
            </label>
            <div id="modal-img-preview-box" class="hidden overflow-hidden rounded-xl border border-[#e8e8e0]">
                <img id="modal-img-preview" src="" alt="Preview" class="h-32 w-full object-cover">
            </div>
        </div>

        <div class="shrink-0 p-6 pt-4 border-t border-[#e8e8e0] flex justify-end gap-3 bg-[#fafaf7] rounded-b-2xl">
            <button type="button" class="rounded-xl border border-[#deded6] px-4 py-2.5 text-xs font-semibold text-[#55584f] hover:bg-[#f5f5f0]" onclick="closeForm()">Cancel</button>
            <button type="submit" id="catalog-submit-btn" class="rounded-xl bg-[#687158] px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-[#596249] transition-colors">Save item</button>
        </div>
    </form>
</div>

<!-- Toast notification -->
<div id="catalog-toast" class="fixed bottom-6 right-6 z-[60] hidden rounded-xl bg-[#252820] px-5 py-3.5 text-xs font-medium text-white shadow-2xl transition-all duration-200 flex items-center gap-2">
    <i data-lucide="check-circle" class="h-4 w-4 text-emerald-400"></i>
    <span id="toast-text">Success</span>
</div>

<script>
const endpoint = '<?= e(($base ?: '') . '/admin/store-catalog/action') ?>';
const csrf = '<?= e(csrf_token()) ?>';

function toast(msg, isError = false) {
    const t = document.getElementById('catalog-toast');
    const txt = document.getElementById('toast-text');
    txt.textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 2800);
}

function autoSlug(val) {
    if (document.getElementById('catalog-action').value === 'create') {
        const slug = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        document.getElementById('f-slug').value = slug;
    }
}

function previewModalImage(url) {
    const box = document.getElementById('modal-img-preview-box');
    const img = document.getElementById('modal-img-preview');
    if (url && url.trim().length > 3) {
        img.src = url;
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}

function openForm(type, label) {
    document.getElementById('catalog-form').reset();
    document.getElementById('catalog-type').value = type;
    document.getElementById('catalog-id').value = '';
    document.getElementById('catalog-action').value = 'create';
    document.getElementById('catalog-eyebrow').textContent = 'New ' + label;
    document.getElementById('catalog-title').textContent = 'Add ' + label;
    document.getElementById('catalog-submit-btn').textContent = 'Create ' + label;
    document.getElementById('modal-img-preview-box').classList.add('hidden');
    
    const m = document.getElementById('catalog-modal');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.getElementById('f-name').focus();
    lucide.createIcons();
}

function openEditForm(type, label, item) {
    document.getElementById('catalog-form').reset();
    document.getElementById('catalog-type').value = type;
    document.getElementById('catalog-id').value = item.id;
    document.getElementById('catalog-action').value = 'update';
    document.getElementById('catalog-eyebrow').textContent = 'Edit ' + label;
    document.getElementById('catalog-title').textContent = 'Edit ' + item.name;
    document.getElementById('catalog-submit-btn').textContent = 'Save Changes';
    
    document.getElementById('f-name').value = item.name || '';
    document.getElementById('f-slug').value = item.slug || '';
    document.getElementById('f-description').value = item.description || '';
    document.getElementById('f-image').value = item.image || '';
    previewModalImage(item.image || '');

    const m = document.getElementById('catalog-modal');
    m.classList.remove('hidden');
    m.classList.add('flex');
    lucide.createIcons();
}

function closeForm() {
    const m = document.getElementById('catalog-modal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

// Live Toggle Visibility with instantaneous UI flip & AJAX persistence
async function toggleItem(type, id, btn) {
    const card = document.getElementById(`item-${type}-${id}`);
    const badge = document.getElementById(`badge-${type}-${id}`);
    const toggleBtn = document.getElementById(`toggle-btn-${type}-${id}`);
    
    const isCurrentlyHidden = card.classList.contains('is-hidden');
    const willBeActive = isCurrentlyHidden; // flipping state
    
    // Optimistic UI update
    updateItemUi(card, badge, toggleBtn, willBeActive);

    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                type: type,
                id: id,
                action: 'toggle',
                _csrf: csrf
            })
        });
        const data = await res.json();
        if (data.success) {
            toast(data.message || (willBeActive ? 'Item is now visible' : 'Item is now hidden'));
            // confirm actual server state
            if (data.is_active !== undefined) {
                updateItemUi(card, badge, toggleBtn, Boolean(data.is_active));
            }
        } else {
            // revert
            updateItemUi(card, badge, toggleBtn, !willBeActive);
            toast(data.message || 'Failed to update visibility', true);
        }
    } catch (err) {
        updateItemUi(card, badge, toggleBtn, !willBeActive);
        toast('Network error while toggling item', true);
    }
}

function updateItemUi(card, badge, toggleBtn, isActive) {
    if (isActive) {
        card.classList.remove('is-hidden');
        card.setAttribute('data-status', 'visible');
        if (badge) {
            badge.className = 'status-badge inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-semibold bg-[#e7eadf] text-[#5d6a51]';
            badge.innerHTML = '<span class="h-1.5 w-1.5 rounded-full bg-[#5d6a51]"></span><span class="badge-text">Visible</span>';
        }
        if (toggleBtn) {
            toggleBtn.title = 'Click to Hide from Store';
            toggleBtn.innerHTML = '<i data-lucide="eye" class="h-3.5 w-3.5 text-[#687158]"></i><span class="toggle-text">Hide</span>';
        }
    } else {
        card.classList.add('is-hidden');
        card.setAttribute('data-status', 'hidden');
        if (badge) {
            badge.className = 'status-badge inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-semibold bg-[#f0f0eb] text-[#8e9187]';
            badge.innerHTML = '<span class="h-1.5 w-1.5 rounded-full bg-[#92948a]"></span><span class="badge-text">Hidden</span>';
        }
        if (toggleBtn) {
            toggleBtn.title = 'Click to Make Visible';
            toggleBtn.innerHTML = '<i data-lucide="eye-off" class="h-3.5 w-3.5 text-[#8e9187]"></i><span class="toggle-text">Show</span>';
        }
    }
    lucide.createIcons();
}

async function deleteItem(type, id) {
    if (!confirm('Are you sure you want to permanently remove this ' + type + '?')) return;
    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                type: type,
                id: id,
                action: 'delete',
                _csrf: csrf
            })
        });
        const data = await res.json();
        if (data.success) {
            toast(data.message || 'Removed');
            const card = document.getElementById(`item-${type}-${id}`);
            if (card) {
                card.style.transform = 'scale(0.9)';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 200);
            }
        } else {
            toast(data.message || 'Delete failed', true);
        }
    } catch (e) {
        toast('Network error during deletion', true);
    }
}

document.getElementById('catalog-form').onsubmit = async function(e) {
    e.preventDefault();
    const btn = document.getElementById('catalog-submit-btn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    
    try {
        const formData = new FormData(this);
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(formData)
        });
        const d = await res.json();
        if (d.success) {
            toast(d.message || 'Saved successfully');
            closeForm();
            setTimeout(() => location.reload(), 300);
        } else {
            toast(d.message || 'Action failed', true);
            btn.disabled = false;
            btn.textContent = 'Save item';
        }
    } catch (err) {
        toast('Network error while saving', true);
        btn.disabled = false;
        btn.textContent = 'Save item';
    }
};

// Real-time search & filter
document.getElementById('catalog-search').addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('.catalog-item-card').forEach(card => {
        const name = card.dataset.name || '';
        const matchesQuery = !q || name.includes(q);
        const currentFilter = document.querySelector('.filter-status-btn.active')?.dataset.filter || 'all';
        const matchesFilter = currentFilter === 'all' || card.dataset.status === currentFilter;
        card.style.display = (matchesQuery && matchesFilter) ? '' : 'none';
    });
});

document.querySelectorAll('.filter-status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-status-btn').forEach(b => {
            b.classList.remove('active', 'bg-[#252820]', 'text-white');
            b.classList.add('text-[#77796f]');
        });
        this.classList.add('active', 'bg-[#252820]', 'text-white');
        this.classList.remove('text-[#77796f]');

        const filter = this.dataset.filter;
        const q = document.getElementById('catalog-search').value.toLowerCase().trim();
        document.querySelectorAll('.catalog-item-card').forEach(card => {
            const matchesFilter = filter === 'all' || card.dataset.status === filter;
            const matchesQuery = !q || (card.dataset.name || '').includes(q);
            card.style.display = (matchesFilter && matchesQuery) ? '' : 'none';
        });
    });
});

lucide.createIcons();
</script>
</body>
</html>
