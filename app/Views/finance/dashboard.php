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
    <title>Finance Overview &mdash; <?= e($storeName) ?></title>
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
        .dark-kpi-card { background: #252820; border: 1px solid #33382c; border-radius: 20px; padding: 22px 20px; color: #ffffff; }
        .ledger-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
        .ledger-table th { background: #fafaf7; padding: 14px 18px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #8e9187; border-bottom: 1px solid #e8e8e0; text-align: left; }
        .ledger-table td { padding: 16px 18px; border-bottom: 1px solid #f0f0ea; color: #252820; vertical-align: middle; }
        .ledger-table tr:hover td { background: #fafaf6; }
        .badge-paid { display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-pending { display: inline-flex; align-items: center; gap: 5px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-failed { display: inline-flex; align-items: center; gap: 5px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .preset-btn { padding: 7px 14px; font-size: 12px; font-weight: 600; border-radius: 10px; border: 1px solid #deded6; background: #ffffff; color: #686b62; text-decoration: none; display: inline-flex; align-items: center; }
        .preset-btn:hover { border-color: #687158; color: #252820; }
        .preset-btn.active { background: #687158; border-color: #687158; color: #ffffff; }
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
        $eyebrow = 'Treasury Overview';
        $title = 'Overview & Treasury';
        $badgeText = 'Live Settlement Engine';
        ob_start();
        ?>
        <a href="<?= e(($base ?: '') . '/finance/export?preset=' . urlencode($preset) . '&from=' . urlencode($from) . '&to=' . urlencode($to)) ?>" class="inline-flex items-center gap-2 rounded-xl border border-[#deded6] bg-white px-3.5 py-2 text-xs font-bold text-[#252820] hover:bg-[#f2f2eb] transition-all shadow-sm">
            <i data-lucide="download" class="h-3.5 w-3.5 text-[#687158]"></i>
            Export CSV
        </a>
        <a href="<?= e(($base ?: '') . '/finance/ledger') ?>" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] text-white px-4 py-2 text-xs font-bold hover:bg-[#596249] transition-all shadow-sm">
            <i data-lucide="receipt-text" class="h-3.5 w-3.5"></i>
            Go to Ledger
        </a>
        <?php 
        $customActions = ob_get_clean();
        require BASE_PATH . '/app/Views/finance/topbar.php'; 
        ?>

        <div class="p-6 lg:p-8 space-y-8 w-full">

            <!-- Date Presets Filter Bar -->
            <div class="bg-white rounded-2xl border border-[#e5e5dd] p-4 lg:p-5 shadow-sm no-print">
                <form method="get" action="<?= e(($base ?: '') . '/finance/dashboard') ?>" class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#8e9187] mr-2">Timeline:</span>
                        <a href="<?= e(($base ?: '') . '/finance/dashboard?preset=today') ?>" class="preset-btn <?= $preset === 'today' ? 'active' : '' ?>">Today</a>
                        <a href="<?= e(($base ?: '') . '/finance/dashboard?preset=7d') ?>" class="preset-btn <?= $preset === '7d' ? 'active' : '' ?>">Last 7 Days</a>
                        <a href="<?= e(($base ?: '') . '/finance/dashboard?preset=30d') ?>" class="preset-btn <?= $preset === '30d' ? 'active' : '' ?>">Last 30 Days</a>
                        <a href="<?= e(($base ?: '') . '/finance/dashboard?preset=month') ?>" class="preset-btn <?= $preset === 'month' ? 'active' : '' ?>">This Month</a>
                        <a href="<?= e(($base ?: '') . '/finance/dashboard?preset=year') ?>" class="preset-btn <?= $preset === 'year' ? 'active' : '' ?>">Year to Date</a>
                        <a href="<?= e(($base ?: '') . '/finance/dashboard?preset=all') ?>" class="preset-btn <?= $preset === 'all' ? 'active' : '' ?>">All Time</a>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-[#8e9187] font-semibold">Range:</span>
                            <input type="date" name="from" value="<?= e($from) ?>" class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3 py-1.5 text-xs outline-none focus:border-[#687158]">
                            <span class="text-[#8e9187]">&rarr;</span>
                            <input type="date" name="to" value="<?= e($to) ?>" class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3 py-1.5 text-xs outline-none focus:border-[#687158]">
                            <button type="submit" class="rounded-xl bg-[#687158] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#596249] transition-colors">Apply</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Primary Financial Stat Ribbon (Full Numbers) -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
                
                <!-- Net Captured Revenue -->
                <div class="col-span-2 sm:col-span-1 xl:col-span-2 dark-kpi-card flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#b9bcb2]">Net Collected Revenue</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10 text-[#98a682]">
                                <i data-lucide="banknote" class="h-4 w-4"></i>
                            </div>
                        </div>
                        <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-white tracking-tight leading-tight whitespace-nowrap" title="₦<?= number_format($stats['revenue'], 2) ?>">
                            ₦<?= number_format($stats['revenue'], 2) ?>
                        </strong>
                        <p class="mt-1 text-xs text-[#a3a89b]">Realized cash from <?= number_format($stats['paid']) ?> paid acquisitions</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px]">
                        <span class="text-[#8e9187]">Est. Net Settlement:</span>
                        <strong class="font-bold text-[#c3cfb2]">₦<?= number_format($stats['net_payout'], 2) ?></strong>
                    </div>
                </div>

                <!-- Gross Merchandise Value (GMV) -->
                <div class="kpi-card flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187]">Total GMV Volume</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#f2f3ec] text-[#687158]">
                                <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                            </div>
                        </div>
                        <strong class="mt-3 block text-xl xl:text-2xl font-bold text-[#252820] tracking-tight leading-tight whitespace-nowrap" title="₦<?= number_format($stats['gmv'], 2) ?>">
                            ₦<?= number_format($stats['gmv'], 2) ?>
                        </strong>
                        <p class="mt-1 text-xs text-[#8e9187]"><?= number_format($stats['total_orders']) ?> total order dispatches</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#f0f0ea] flex items-center justify-between text-[11px]">
                        <span class="text-[#8e9187]">Conversion Rate:</span>
                        <strong class="font-bold text-emerald-700"><?= $stats['conversion_rate'] ?>%</strong>
                    </div>
                </div>

                <!-- Average Order Value (AOV) -->
                <div class="kpi-card flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187]">Average Basket (AOV)</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#f2f3ec] text-[#687158]">
                                <i data-lucide="trending-up" class="h-4 w-4"></i>
                            </div>
                        </div>
                        <strong class="mt-3 block text-xl xl:text-2xl font-bold text-[#687158] tracking-tight leading-tight whitespace-nowrap" title="₦<?= number_format($stats['aov'], 2) ?>">
                            ₦<?= number_format($stats['aov'], 2) ?>
                        </strong>
                        <p class="mt-1 text-xs text-[#8e9187]">Average ticket per acquisition</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#f0f0ea] flex items-center justify-between text-[11px]">
                        <span class="text-[#8e9187]">Portfolio:</span>
                        <strong class="font-bold text-[#252820]">High Horology &amp; Luxury</strong>
                    </div>
                </div>

                <!-- Pending Receivables -->
                <div class="kpi-card flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#b45309]">Pending Checkout</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                                <i data-lucide="clock" class="h-4 w-4"></i>
                            </div>
                        </div>
                        <strong class="mt-3 block text-xl xl:text-2xl font-bold text-[#b45309] tracking-tight leading-tight whitespace-nowrap" title="₦<?= number_format($stats['pending_amount'], 2) ?>">
                            ₦<?= number_format($stats['pending_amount'], 2) ?>
                        </strong>
                        <p class="mt-1 text-xs text-[#8e9187]"><?= number_format($stats['pending']) ?> orders awaiting capture</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#f0f0ea] flex items-center justify-between text-[11px]">
                        <span class="text-[#8e9187]">Pipeline Risk:</span>
                        <strong class="font-bold text-amber-700">Protected</strong>
                    </div>
                </div>

                <!-- Gateway Processing Fees -->
                <div class="kpi-card flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187]">Gateway Processing</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#f2f3ec] text-[#687158]">
                                <i data-lucide="shield-check" class="h-4 w-4"></i>
                            </div>
                        </div>
                        <strong class="mt-3 block text-xl xl:text-2xl font-bold text-[#252820] tracking-tight leading-tight whitespace-nowrap" title="₦<?= number_format($stats['estimated_fees'], 2) ?>">
                            ₦<?= number_format($stats['estimated_fees'], 2) ?>
                        </strong>
                        <p class="mt-1 text-xs text-[#8e9187]">Paystack 1.5% fee model</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#f0f0ea] flex items-center justify-between text-[11px]">
                        <span class="text-[#8e9187]">Clearing:</span>
                        <strong class="font-bold text-[#252820]">T+1 Settlement</strong>
                    </div>
                </div>

            </section>

            <!-- Quick Navigation Tiles to Dedicated Pages -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Analytics Tile -->
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 rounded-xl bg-[#f2f3ec] text-[#687158] flex items-center justify-center">
                                <i data-lucide="line-chart" class="h-5 w-5"></i>
                            </div>
                            <span class="text-xs font-bold text-[#687158] bg-[#f2f3ec] px-2.5 py-1 rounded-full">Chart View</span>
                        </div>
                        <h3 class="text-base font-extrabold text-[#252820]">Cash-Flow Analytics</h3>
                        <p class="text-xs text-[#8e9187] mt-1.5 leading-relaxed">View daily inflow bar charts, payment channel distributions, and liquidity run-rate trends.</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#f0f0ea]">
                        <a href="<?= e(($base ?: '') . '/finance/analytics') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#687158] hover:text-[#252820] transition-colors">
                            Open Analytics Page &rarr;
                        </a>
                    </div>
                </div>

                <!-- Ledger Tile -->
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 rounded-xl bg-[#f2f3ec] text-[#687158] flex items-center justify-center">
                                <i data-lucide="receipt-text" class="h-5 w-5"></i>
                            </div>
                            <span class="text-xs font-bold text-[#687158] bg-[#f2f3ec] px-2.5 py-1 rounded-full"><?= count($orders) ?> Recorded</span>
                        </div>
                        <h3 class="text-base font-extrabold text-[#252820]">Transaction Ledger</h3>
                        <p class="text-xs text-[#8e9187] mt-1.5 leading-relaxed">Inspect every customer order, verify gateway authorization codes, and print commercial receipts.</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#f0f0ea]">
                        <a href="<?= e(($base ?: '') . '/finance/ledger') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#687158] hover:text-[#252820] transition-colors">
                            Open Audit Ledger &rarr;
                        </a>
                    </div>
                </div>

                <!-- Operations & Reserves Tile -->
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 rounded-xl bg-[#f2f3ec] text-[#687158] flex items-center justify-center">
                                <i data-lucide="package-check" class="h-5 w-5"></i>
                            </div>
                            <span class="text-xs font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-full">Active</span>
                        </div>
                        <h3 class="text-base font-extrabold text-[#252820]">Fulfilment &amp; Reserves</h3>
                        <p class="text-xs text-[#8e9187] mt-1.5 leading-relaxed">Monitor open returns, shipments in transit, pending courier pickups, and inventory holds.</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#f0f0ea]">
                        <a href="<?= e(($base ?: '') . '/finance/operations') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#687158] hover:text-[#252820] transition-colors">
                            Open Operations Controls &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Ledger Preview -->
            <section class="rounded-2xl border border-[#e5e5dd] bg-white shadow-sm overflow-hidden">
                <div class="p-6 border-b border-[#f0f0ea] flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Recent Transactions</span>
                        <h3 class="text-lg font-extrabold text-[#252820]">Latest Ledger Highlights</h3>
                    </div>
                    <a href="<?= e(($base ?: '') . '/finance/ledger') ?>" class="rounded-xl border border-[#deded6] px-4 py-2 text-xs font-bold text-[#687158] hover:bg-[#687158] hover:text-white transition-all shadow-sm">
                        View Complete Ledger &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th>Order &amp; Gateway Ref</th>
                                <th>Customer</th>
                                <th>Gross Total</th>
                                <th>Net Settlement</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($orders, 0, 5) as $ord): 
                                $st = (string)($ord['payment_status'] ?? 'pending');
                            ?>
                                <tr>
                                    <td>
                                        <strong class="block font-mono text-xs font-bold text-[#252820]"><?= e($ord['order_number']) ?></strong>
                                        <span class="block font-mono text-[10px] text-[#8e9187]"><?= e($ord['provider_reference'] ?: 'Direct / Offline') ?></span>
                                    </td>
                                    <td>
                                        <strong class="block text-xs font-bold text-[#252820]"><?= e($ord['customer_name']) ?></strong>
                                        <span class="block text-[10px] text-[#8e9187]"><?= e($ord['customer_email']) ?></span>
                                    </td>
                                    <td><strong class="text-xs font-bold text-[#252820]">₦<?= number_format((float)$ord['total'], 2) ?></strong></td>
                                    <td><strong class="text-xs font-bold text-emerald-800">₦<?= number_format((float)$ord['net_payout'], 2) ?></strong></td>
                                    <td>
                                        <?php if ($st === 'paid'): ?>
                                            <span class="badge-paid"><i data-lucide="check" class="h-3 w-3"></i> Paid</span>
                                        <?php elseif ($st === 'failed'): ?>
                                            <span class="badge-failed"><i data-lucide="x" class="h-3 w-3"></i> Failed</span>
                                        <?php else: ?>
                                            <span class="badge-pending"><i data-lucide="clock" class="h-3 w-3"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-xs text-[#77796f]"><?= e(date('d M Y, H:i', strtotime($ord['created_at']))) ?></td>
                                    <td class="text-right">
                                        <button type="button" onclick="viewReceipt(<?= (int)$ord['order_id'] ?>)" class="rounded-xl border border-[#deded6] bg-white px-3 py-1.5 text-[11px] font-bold text-[#687158] hover:bg-[#687158] hover:text-white transition-all shadow-sm">
                                            Receipt
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

    <!-- Shared Receipt Modal -->
    <?php require BASE_PATH . '/app/Views/finance/receipt-modal.php'; ?>

    <script>
    const base = '<?= e($base ?: "") ?>';
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
    </script>
</body>
</html>
