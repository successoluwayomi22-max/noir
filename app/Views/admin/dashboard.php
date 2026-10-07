<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = $user ?? \Noir\Core\Auth::user();
$adminUrl = ($base ?: '') . '/admin';
$settingsUrl = ($base ?: '') . '/admin/settings';
$reportsUrl = ($base ?: '') . '/admin/reports';
$productsUrl = ($base ?: '') . '/admin/products';
$storeName = store_setting('store_name', 'Noir & Co.');
$stats = $stats ?? [];
$productSummary = $productSummary ?? [];
$orders = $orders ?? [];
$activityLogs = $activityLogs ?? [];
$blockedIps = $blockedIps ?? [];
$securityAlerts = $securityAlerts ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Executive Overview — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= store_brand_style() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/admin-mobile.min.js') ?>" defer></script>
    <style>
        body { font-family: 'Urbanist', ui-sans-serif, system-ui; }
        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body class="admin-page bg-[#f6f6f1] text-[#252820]">
<?php $activeNav = 'overview'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
        <div>
            <p class="eyebrow text-[#687158] font-bold">Workspace Overview</p>
            <h1 class="text-3xl font-extrabold tracking-tight">Good day, <?= e(explode(' ', $user['name'] ?? 'Admin')[0]) ?>.</h1>
            <p class="admin-subtitle text-sm text-[#77796f]">Here is the real-time operational pulse across <?= e($storeName) ?> today.</p>
        </div>
        <div class="admin-user flex items-center gap-3">
            <?php 
                $adminAvatar = (string)($user['profile_image'] ?? $user['avatar'] ?? store_setting('admin_profile_image_' . (int)($user['id'] ?? $user['user_id'] ?? 0)) ?: store_setting('admin_profile_image_0')); 
            ?>
            <?php if ($adminAvatar !== ''): ?>
                <img src="<?= e(($base ?: '') . $adminAvatar) ?>" alt="<?= e($user['name'] ?? 'Admin') ?>" class="h-9 w-9 rounded-full object-cover border border-[#deded6]">
            <?php else: ?>
                <span class="grid h-9 w-9 place-items-center rounded-full bg-[#687158] text-xs font-bold text-white shadow-sm">
                    <?= e(strtoupper(substr((string)($user['name'] ?? 'A'), 0, 1))) ?>
                </span>
            <?php endif; ?>
            <div>
                <strong class="block text-xs font-bold text-[#252820]"><?= e($user['name'] ?? 'Admin') ?></strong>
                <span class="text-[11px] text-[#8e9187]"><?= e($user['email'] ?? '') ?></span>
            </div>
            <form method="post" action="<?= e(($base ?: '') . '/logout') ?>" class="ml-2">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="text-xs font-semibold text-[#687158] hover:underline">Sign out</button>
            </form>
        </div>
    </header>

    <div class="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
        
        <!-- Key Metrics Grid -->
        <section class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <!-- Customers -->
            <a href="<?= e(($base ?: '') . '/admin/customers') ?>" class="stat-card block rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm min-w-0 overflow-hidden">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Customers</span>
                    <i data-lucide="users" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong class="mt-2 block font-extrabold text-[#252820] tracking-tight truncate" style="font-size: clamp(1.15rem, 1.25vw, 1.5rem);"><?= number_format((float)($stats['customers'] ?? 0)) ?></strong>
                <small class="text-[11px] text-[#8e9187] truncate block">Registered accounts</small>
            </a>

            <!-- Staff -->
            <a href="<?= e(($base ?: '') . '/admin/roles') ?>" class="stat-card block rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm min-w-0 overflow-hidden">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Staff</span>
                    <i data-lucide="shield" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong class="mt-2 block font-extrabold text-[#252820] tracking-tight truncate" style="font-size: clamp(1.15rem, 1.25vw, 1.5rem);"><?= number_format((float)($stats['staff'] ?? 0)) ?></strong>
                <small class="text-[11px] text-[#8e9187] truncate block">Active operators</small>
            </a>

            <!-- Published Products -->
            <a href="<?= e($productsUrl) ?>" class="stat-card block rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm min-w-0 overflow-hidden">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Catalogue</span>
                    <i data-lucide="package" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong class="mt-2 block font-extrabold text-[#252820] tracking-tight truncate" style="font-size: clamp(1.15rem, 1.25vw, 1.5rem);"><?= number_format((float)($stats['products'] ?? 0)) ?></strong>
                <small class="text-[11px] text-[#8e9187] truncate block">Published in store</small>
            </a>

            <!-- Total Orders -->
            <a href="<?= e(($base ?: '') . '/admin/orders') ?>" class="stat-card block rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm min-w-0 overflow-hidden">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Orders</span>
                    <i data-lucide="shopping-bag" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong class="mt-2 block font-extrabold text-[#252820] tracking-tight truncate" style="font-size: clamp(1.15rem, 1.25vw, 1.5rem);"><?= number_format((float)($stats['orders'] ?? 0)) ?></strong>
                <small class="text-[11px] text-[#687158] font-semibold truncate block">View all orders ↗</small>
            </a>

            <!-- Net Revenue -->
            <div class="stat-card rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm min-w-0 overflow-hidden">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Net Sales</span>
                    <i data-lucide="banknote" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong class="mt-2 block font-extrabold text-[#252820] tracking-tight truncate" style="font-size: clamp(1rem, 1.1vw, 1.3rem);" title="₦<?= number_format((float)($stats['revenue'] ?? 0), 2) ?>">
                    ₦<?= number_format((float)($stats['revenue'] ?? 0), 0) ?>
                </strong>
                <small class="text-[11px] text-[#8e9187] truncate block">Captured revenue</small>
            </div>

            <!-- Security Status -->
            <a href="<?= e(($base ?: '') . '/admin/security') ?>" class="stat-card block rounded-2xl border <?= ($stats['blocked_ips'] ?? 0) > 0 ? 'border-rose-200 bg-rose-50/40' : 'border-emerald-200 bg-emerald-50/40' ?> p-4 shadow-sm min-w-0 overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider <?= ($stats['blocked_ips'] ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">Security</span>
                    <i data-lucide="shield-check" class="h-4 w-4 <?= ($stats['blocked_ips'] ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"></i>
                </div>
                <strong class="mt-2 block font-extrabold tracking-tight truncate <?= ($stats['blocked_ips'] ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700' ?>" style="font-size: clamp(1.15rem, 1.25vw, 1.5rem);">
                    <?= number_format($stats['blocked_ips'] ?? 0) ?>
                </strong>
                <small class="text-[11px] font-semibold truncate block <?= ($stats['blocked_ips'] ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                    <?= ($stats['blocked_ips'] ?? 0) > 0 ? 'Blocked threats ↗' : 'Shield active ↗' ?>
                </small>
            </a>
        </section>

        <!-- Product Inventory Breakdown Bar -->
        <section class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 mb-4 border-b border-[#f0f1ec]">
                <div>
                    <h3 class="text-sm font-bold text-[#252820]">Catalogue Inventory Breakdown</h3>
                    <p class="text-xs text-[#8e9187]">Real-time visibility into stock and publishing stages.</p>
                </div>
                <a href="<?= e($productsUrl) ?>" class="mt-2 sm:mt-0 inline-flex items-center gap-1 text-xs font-bold text-[#687158] hover:underline">
                    Manage Products <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                </a>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-[#fafaf6] p-3 border border-[#f0f1ec]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Total Products</span>
                    <strong class="block text-xl font-bold text-[#252820] mt-1"><?= number_format((float)($productSummary['total'] ?? 0)) ?></strong>
                </div>
                <div class="rounded-xl bg-[#f2f8f2] p-3 border border-[#e0ece0]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#3d6836]">Active In Store</span>
                    <strong class="block text-xl font-bold text-[#325b2c] mt-1"><?= number_format((float)($productSummary['active'] ?? 0)) ?></strong>
                </div>
                <div class="rounded-xl bg-[#fbf8f0] p-3 border border-[#f2ebd9]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#98741e]">Draft Products</span>
                    <strong class="block text-xl font-bold text-[#866214] mt-1"><?= number_format((float)($productSummary['draft'] ?? 0)) ?></strong>
                </div>
                <div class="rounded-xl bg-[#fdf4f4] p-3 border border-[#fae2e2]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#ad433c]">Out of Stock</span>
                    <strong class="block text-xl font-bold text-[#963029] mt-1"><?= number_format((float)($productSummary['out'] ?? 0)) ?></strong>
                </div>
            </div>
        </section>

        <!-- Main Workspace 2-Column: Recent Orders & Quick Launchpad -->
        <section class="grid gap-6 lg:grid-cols-[1.6fr_.9fr]">
            <!-- Recent Orders -->
            <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-[#f0f1ec]">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Fulfillment</p>
                            <h2 class="text-xl font-extrabold text-[#252820]">Recent Orders</h2>
                        </div>
                        <a href="<?= e(($base ?: '') . '/admin/orders') ?>" class="text-xs font-bold text-[#687158] hover:underline">
                            View all orders ↗
                        </a>
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="py-14 text-center text-[#8e9187]">
                            <i data-lucide="package-open" class="mx-auto h-10 w-10 text-[#b3b6aa]"></i>
                            <p class="mt-2 text-sm font-semibold">No recent orders yet</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto mt-2">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-[#f0f1ec] text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">
                                    <tr>
                                        <th class="py-3 pr-4">Order</th>
                                        <th class="py-3 px-3">Status</th>
                                        <th class="py-3 px-3">Total</th>
                                        <th class="py-3 pl-3 text-right">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f5f6f1]">
                                    <?php foreach (array_slice($orders, 0, 6) as $order): 
                                        $st = strtolower((string)$order['status']);
                                        $badge = match ($st) {
                                            'completed', 'delivered' => 'bg-[#dcf1dc] text-[#325b2c]',
                                            'processing', 'pending' => 'bg-[#fcf2d9] text-[#916b15]',
                                            'cancelled' => 'bg-[#fae2e0] text-[#8e3830]',
                                            default => 'bg-[#f0f1ec] text-[#555d49]',
                                        };
                                    ?>
                                    <tr class="hover:bg-[#fafaf7] transition-colors">
                                        <td class="py-3 pr-4 font-mono font-bold text-[#252820]">
                                            <a href="<?= e(($base ?: '') . '/admin/orders/' . ($order['id'] ?? $order['order_number'])) ?>" class="hover:text-[#687158] hover:underline">
                                                <?= e($order['order_number']) ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold <?= $badge ?>">
                                                <?= e(ucfirst($order['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 font-semibold text-[#252820]">
                                            <?= e($order['currency']) ?> <?= number_format((float)$order['total'], 2) ?>
                                        </td>
                                        <td class="py-3 pl-3 text-right text-[#8e9187] whitespace-nowrap">
                                            <?= e(date('d M Y', strtotime($order['created_at']))) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 pt-3 border-t border-[#f0f1ec] text-right">
                    <a href="<?= e(($base ?: '') . '/admin/orders') ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-[#687158] px-4 py-2 text-xs font-bold text-white hover:bg-[#586048]">
                        <i data-lucide="list-ordered" class="h-3.5 w-3.5"></i> Open Full Orders Console
                    </a>
                </div>
            </div>

            <!-- Quick Launchpad Hub -->
            <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Navigation Hub</p>
                    <h2 class="text-xl font-extrabold text-[#252820] pb-4 border-b border-[#f0f1ec]">Quick Operations</h2>
                    
                    <div class="mt-4 space-y-2">
                        <a href="<?= e($productsUrl) ?>" class="flex items-center justify-between rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 transition hover:bg-[#f5f5f0]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#687158] text-white">
                                    <i data-lucide="package-plus" class="h-4 w-4"></i>
                                </div>
                                <div class="text-left">
                                    <strong class="block text-xs font-bold text-[#252820]">Product Catalogue</strong>
                                    <span class="text-[10px] text-[#8e9187]">Add items, stock &amp; pricing</span>
                                </div>
                            </div>
                            <i data-lucide="arrow-right" class="h-4 w-4 text-[#8e9187]"></i>
                        </a>

                        <a href="<?= e(($base ?: '') . '/admin/store-catalog') ?>" class="flex items-center justify-between rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 transition hover:bg-[#f5f5f0]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#252820] text-white">
                                    <i data-lucide="layers" class="h-4 w-4"></i>
                                </div>
                                <div class="text-left">
                                    <strong class="block text-xs font-bold text-[#252820]">Categories &amp; Collections</strong>
                                    <span class="text-[10px] text-[#8e9187]">Manage brand hierarchy</span>
                                </div>
                            </div>
                            <i data-lucide="arrow-right" class="h-4 w-4 text-[#8e9187]"></i>
                        </a>

                        <a href="<?= e(($base ?: '') . '/admin/staff-applications') ?>" class="flex items-center justify-between rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 transition hover:bg-[#f5f5f0]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#9f7823] text-white">
                                    <i data-lucide="user-check" class="h-4 w-4"></i>
                                </div>
                                <div class="text-left">
                                    <strong class="block text-xs font-bold text-[#252820]">Staff Applications</strong>
                                    <span class="text-[10px] text-[#8e9187]">Review candidates &amp; assign roles</span>
                                </div>
                            </div>
                            <i data-lucide="arrow-right" class="h-4 w-4 text-[#8e9187]"></i>
                        </a>

                        <a href="<?= e(($base ?: '') . '/admin/messages') ?>" class="flex items-center justify-between rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 transition hover:bg-[#f5f5f0]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#5a644c] text-white">
                                    <i data-lucide="inbox" class="h-4 w-4"></i>
                                </div>
                                <div class="text-left">
                                    <strong class="block text-xs font-bold text-[#252820]">Customer Messages</strong>
                                    <span class="text-[10px] text-[#8e9187]">Inquiries &amp; newsletters</span>
                                </div>
                            </div>
                            <i data-lucide="arrow-right" class="h-4 w-4 text-[#8e9187]"></i>
                        </a>

                        <a href="<?= e($reportsUrl) ?>" class="flex items-center justify-between rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 transition hover:bg-[#f5f5f0]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#385c32] text-white">
                                    <i data-lucide="chart-pie" class="h-4 w-4"></i>
                                </div>
                                <div class="text-left">
                                    <strong class="block text-xs font-bold text-[#252820]">Analytics &amp; Revenue Reports</strong>
                                    <span class="text-[10px] text-[#8e9187]">Sales forecasts &amp; data</span>
                                </div>
                            </div>
                            <i data-lucide="arrow-right" class="h-4 w-4 text-[#8e9187]"></i>
                        </a>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-[#f0f1ec] flex items-center justify-between text-xs text-[#8e9187]">
                    <a href="<?= e($settingsUrl) ?>" class="inline-flex items-center gap-1 hover:text-[#252820]">
                        <i data-lucide="settings" class="h-3.5 w-3.5"></i> Settings
                    </a>
                    <a href="<?= e(($base ?: '') . '/admin/system-logs') ?>" class="inline-flex items-center gap-1 hover:text-[#252820]">
                        <i data-lucide="terminal" class="h-3.5 w-3.5"></i> Terminal
                    </a>
                </div>
            </div>
        </section>

        <!-- Active Threat Defense (Security Shield) -->
        <section class="rounded-2xl border border-rose-200/80 bg-white p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-[#f0f1ec]">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-rose-600">Active Threat Defense</p>
                    <h2 class="text-xl font-extrabold text-[#252820]">Security Shield &amp; Firewall</h2>
                </div>
                <div class="mt-2 sm:mt-0 flex items-center gap-2">
                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">
                        <?= count($blockedIps ?? []) ?> Blocked <?= count($blockedIps ?? []) === 1 ? 'Attacker' : 'Attackers' ?>
                    </span>
                    <a href="<?= e(($base ?: '') . '/admin/security') ?>" class="text-xs font-bold text-[#687158] hover:underline">
                        Open Security Center ↗
                    </a>
                </div>
            </div>

            <?php if (!empty($_GET['unblocked'])): ?>
                <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-xs font-semibold text-emerald-800 border border-emerald-200">
                    IP address was successfully unblocked and access restored.
                </div>
            <?php elseif (!empty($_GET['blocked'])): ?>
                <div class="mt-4 rounded-xl bg-rose-50 p-3 text-xs font-semibold text-rose-800 border border-rose-200">
                    IP address was manually blocked from accessing Noir store and APIs.
                </div>
            <?php endif; ?>

            <?php if (!empty($blockedIps)): ?>
                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#f0f1ec] text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">
                            <tr>
                                <th class="py-3">Attacker IP</th>
                                <th class="py-3">Target Endpoint</th>
                                <th class="py-3">Total Requests</th>
                                <th class="py-3">Detection Reason</th>
                                <th class="py-3">Blocked Until</th>
                                <th class="py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#f5f6f1]">
                            <?php foreach ($blockedIps as $b): ?>
                                <tr>
                                    <td class="py-3 font-mono font-bold text-rose-600"><?= e($b['ip_address']) ?></td>
                                    <td class="py-3 font-mono text-[11px] text-[#555]"><?= e($b['route'] ?? '/') ?></td>
                                    <td class="py-3">
                                        <span class="rounded-md bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                                            <?= (int)$b['request_count'] ?> requests
                                        </span>
                                    </td>
                                    <td class="py-3 text-[#666]"><?= e($b['reason']) ?></td>
                                    <td class="py-3 text-[#8e9187]"><?= e(date('H:i:s M d', strtotime($b['blocked_until']))) ?></td>
                                    <td class="py-3 text-right">
                                        <form method="post" action="<?= e(($base ?: '') . '/admin/security/unblock-ip') ?>" class="inline" onsubmit="return confirm('Unblock IP <?= e($b['ip_address']) ?>?');">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                            <button type="submit" class="rounded-lg bg-[#687158] px-2.5 py-1 text-[11px] font-bold text-white hover:bg-[#586048]">Unblock</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="mt-4 rounded-xl bg-[#fafaf7] p-6 text-center text-xs text-[#687158] border border-[#f0f1ec]">
                    <i data-lucide="shield-check" class="mx-auto h-8 w-8 text-[#687158]"></i>
                    <p class="mt-2 font-bold text-[#252820]">All systems clean and operational.</p>
                    <p class="text-[11px] text-[#8e9187]">No malicious bots, scrapers, or abusive floods currently detected.</p>
                </div>
            <?php endif; ?>

            <!-- Manual Block Accordion -->
            <details class="mt-4 rounded-xl border border-dashed border-[#deded6] bg-[#fafaf7] p-3 text-xs">
                <summary class="font-bold text-[#252820] cursor-pointer">+ Manually Blacklist &amp; Block IP Address</summary>
                <form method="post" action="<?= e(($base ?: '') . '/admin/security/block-ip') ?>" class="mt-3 grid gap-3 sm:grid-cols-4 items-end">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#8e9187] mb-1">IP Address</label>
                        <input type="text" name="ip_address" required placeholder="e.g. 192.0.2.1" class="w-full rounded-xl border border-[#deded6] bg-white p-2.5 text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#8e9187] mb-1">Reason</label>
                        <input type="text" name="reason" placeholder="Suspicious automated traffic" class="w-full rounded-xl border border-[#deded6] bg-white p-2.5 text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#8e9187] mb-1">Duration</label>
                        <select name="duration_hours" class="w-full rounded-xl border border-[#deded6] bg-white p-2.5 text-xs outline-none">
                            <option value="24">24 Hours</option>
                            <option value="72">3 Days</option>
                            <option value="168">7 Days</option>
                            <option value="8760">1 Year</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-rose-700">Block Attacker IP</button>
                </form>
            </details>
        </section>

        <!-- Observability & Live System Logs -->
        <section class="rounded-2xl border border-[#e5e5dd] bg-white shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-6 border-b border-[#f0f1ea] bg-[#fafaf7] gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Observability &amp; Audit</p>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                            LIVE STREAM
                        </span>
                    </div>
                    <h2 class="mt-0.5 text-xl font-extrabold text-[#252820]">Live System Logs</h2>
                </div>
                <a href="<?= e(($base ?: '') . '/admin/system-logs') ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-[#687158] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#586048]">
                    <span>View all logs console</span>
                    <span>↗</span>
                </a>
            </div>

            <?php if (!empty($activityLogs)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#f0f1ea] bg-[#fcfcf9] text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">
                            <tr>
                                <th class="py-3.5 pl-6 pr-3">Time</th>
                                <th class="py-3.5 px-3">Actor / User</th>
                                <th class="py-3.5 px-3">Event Classification</th>
                                <th class="py-3.5 pl-3 pr-6">Context Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#f2f3ee]">
                            <?php foreach ($activityLogs as $log): 
                                $ev = strtoupper((string)$log['event']);
                                $badgeStyle = match (true) {
                                    str_starts_with($ev, 'AUTH') => 'bg-blue-50 text-blue-700 border-blue-200',
                                    str_starts_with($ev, 'MAIL') => 'bg-amber-50 text-amber-700 border-amber-200',
                                    str_starts_with($ev, 'ORDER'), str_starts_with($ev, 'CHECKOUT') => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    str_starts_with($ev, 'SECURITY'), str_starts_with($ev, 'IP') => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-[#f0f1eb] text-[#59624b] border-[#e1e3dc]',
                                };
                            ?>
                            <tr class="hover:bg-[#fafaf6] transition-colors">
                                <td class="py-3 pl-6 pr-3 whitespace-nowrap font-mono text-[11px] text-[#77796f]">
                                    <?= e(date('H:i:s • M d', strtotime($log['created_at']))) ?>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <?php if (strtolower((string)($log['user_name'] ?? 'system')) === 'system'): ?>
                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#252820] px-2 py-0.5 text-[10px] font-bold text-white">
                                            System
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 font-semibold text-[#252820]">
                                            <span class="grid h-5 w-5 place-items-center rounded-full bg-[#e2e5dc] text-[10px] font-bold text-[#555e48]">
                                                <?= strtoupper(substr((string)$log['user_name'], 0, 1)) ?>
                                            </span>
                                            <?= e($log['user_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-block rounded-md border px-2 py-0.5 font-mono text-[10px] font-bold <?= $badgeStyle ?>">
                                        <?= e(str_replace('_', '.', $ev)) ?>
                                    </span>
                                </td>
                                <td class="py-3 pl-3 pr-6 text-[#55584f]">
                                    <?php if (!empty($log['subject_type'])): ?>
                                        <span class="inline-block rounded bg-[#f5f6f1] px-2 py-0.5 text-[11px] font-semibold text-[#687158]">
                                            <?= e(ucwords(str_replace('_', ' ', (string)$log['subject_type']))) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[#b3b6aa]">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-8 text-center text-xs text-[#8e9187]">No recent system audit activity.</div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>
</body>
</html>
