<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Container::get('auth')->user() ?? [];
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fulfilment &amp; Operations Reserves &mdash; <?= e($storeName) ?></title>
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
        .kpi-card { background: #ffffff; border: 1px solid #e5e5dd; border-radius: 20px; padding: 22px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
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
        $eyebrow = 'Fulfillment Controls';
        $title = 'Fulfilment, Returns & Reserves';
        $badgeText = 'Warehouse Inventory Synced';
        ob_start();
        ?>
        <a href="<?= e(($base ?: '') . '/admin/orders') ?>" class="inline-flex items-center gap-2 rounded-xl border border-[#deded6] bg-white px-3.5 py-2 text-xs font-bold text-[#252820] hover:bg-[#f2f2eb] transition-all shadow-sm">
            <i data-lucide="package" class="h-3.5 w-3.5 text-[#687158]"></i>
            Orders Center
        </a>
        <?php 
        $customActions = ob_get_clean();
        require BASE_PATH . '/app/Views/finance/topbar.php'; 
        ?>

        <div class="p-6 lg:p-8 space-y-8 w-full">

            <!-- Primary Fulfillment Status Ribbon -->
            <section class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="kpi-card">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#b45309] block">Pending Packing</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#b45309]"><?= number_format((int)$operations['shipments_pending']) ?></strong>
                    <small class="text-xs text-[#8e9187]">Awaiting warehouse pack</small>
                </div>

                <div class="kpi-card">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#0284c7] block">In Transit</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#0284c7]"><?= number_format((int)$operations['shipments_in_transit']) ?></strong>
                    <small class="text-xs text-[#8e9187]">White-glove dispatch</small>
                </div>

                <div class="kpi-card">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#15803d] block">Delivered &amp; Closed</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#15803d]"><?= number_format((int)$operations['delivered_closed']) ?></strong>
                    <small class="text-xs text-[#8e9187]">Completed deliveries</small>
                </div>

                <div class="kpi-card">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187] block">Open Returns</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#252820]"><?= number_format((int)$operations['returns_open']) ?></strong>
                    <small class="text-xs text-[#8e9187]">Under evaluation</small>
                </div>

                <div class="kpi-card">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187] block">Refunds Closed</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#252820]"><?= number_format((int)$operations['refunds_completed']) ?></strong>
                    <small class="text-xs text-[#8e9187]">Returned capital</small>
                </div>

                <div class="kpi-card">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#687158] block">Active Reserves</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#687158]"><?= number_format((int)$operations['reservations_active']) ?></strong>
                    <small class="text-xs text-[#8e9187]">Customer checkout holds</small>
                </div>
            </section>

            <!-- Operational Details Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm">
                    <div class="border-b border-[#f0f0ea] pb-4 mb-4">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Inventory Security</span>
                        <h3 class="text-base font-extrabold text-[#252820]">Stock Locking &amp; Reservations</h3>
                    </div>
                    <p class="text-xs text-[#77796f] leading-relaxed mb-4">
                        When clients initiate checkout, luxury units are placed in an isolated reservation hold for 30 minutes to prevent over-allocation during peak drops.
                    </p>
                    <div class="rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-4 flex items-center justify-between text-xs">
                        <span class="font-semibold text-[#55584f]">Current Active Lock Rate:</span>
                        <strong class="text-emerald-800 font-bold"><?= number_format((int)$operations['reservations_active']) ?> Pieces Reserved</strong>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm">
                    <div class="border-b border-[#f0f0ea] pb-4 mb-4">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Return Merchandise Authorization</span>
                        <h3 class="text-base font-extrabold text-[#252820]">Claims &amp; Reverse Logistics</h3>
                    </div>
                    <p class="text-xs text-[#77796f] leading-relaxed mb-4">
                        Client returns undergo high-magnification verification at our fulfillment hub before refund authorizations are issued to the payment gateway.
                    </p>
                    <div class="rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-4 flex items-center justify-between text-xs">
                        <span class="font-semibold text-[#55584f]">Open Disputed Claims:</span>
                        <strong class="text-[#252820] font-bold"><?= number_format((int)$operations['returns_open']) ?> Active</strong>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
    </script>
</body>
</html>
