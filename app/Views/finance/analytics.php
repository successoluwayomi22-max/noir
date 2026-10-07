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
    <title>Cash-Flow Analytics &mdash; <?= e($storeName) ?></title>
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
        $eyebrow = 'Treasury Intelligence';
        $title = 'Cash-Flow Analytics';
        $badgeText = 'Cash-Flow Visualizer';
        ob_start();
        ?>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-[#deded6] bg-white px-3.5 py-2 text-xs font-bold text-[#252820] hover:bg-[#f2f2eb] transition-all shadow-sm">
            <i data-lucide="printer" class="h-3.5 w-3.5 text-[#687158]"></i>
            Print Chart Report
        </button>
        <?php 
        $customActions = ob_get_clean();
        require BASE_PATH . '/app/Views/finance/topbar.php'; 
        ?>

        <div class="p-6 lg:p-8 space-y-8 w-full">

            <!-- Date Presets Filter Bar -->
            <div class="bg-white rounded-2xl border border-[#e5e5dd] p-4 lg:p-5 shadow-sm no-print">
                <form method="get" action="<?= e(($base ?: '') . '/finance/analytics') ?>" class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#8e9187] mr-2">Timeline:</span>
                        <a href="<?= e(($base ?: '') . '/finance/analytics?preset=today') ?>" class="preset-btn <?= $preset === 'today' ? 'active' : '' ?>">Today</a>
                        <a href="<?= e(($base ?: '') . '/finance/analytics?preset=7d') ?>" class="preset-btn <?= $preset === '7d' ? 'active' : '' ?>">Last 7 Days</a>
                        <a href="<?= e(($base ?: '') . '/finance/analytics?preset=30d') ?>" class="preset-btn <?= $preset === '30d' ? 'active' : '' ?>">Last 30 Days</a>
                        <a href="<?= e(($base ?: '') . '/finance/analytics?preset=month') ?>" class="preset-btn <?= $preset === 'month' ? 'active' : '' ?>">This Month</a>
                        <a href="<?= e(($base ?: '') . '/finance/analytics?preset=year') ?>" class="preset-btn <?= $preset === 'year' ? 'active' : '' ?>">Year to Date</a>
                        <a href="<?= e(($base ?: '') . '/finance/analytics?preset=all') ?>" class="preset-btn <?= $preset === 'all' ? 'active' : '' ?>">All Time</a>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-[#8e9187] font-semibold">Custom:</span>
                            <input type="date" name="from" value="<?= e($from) ?>" class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3 py-1.5 text-xs outline-none focus:border-[#687158]">
                            <span class="text-[#8e9187]">&rarr;</span>
                            <input type="date" name="to" value="<?= e($to) ?>" class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3 py-1.5 text-xs outline-none focus:border-[#687158]">
                            <button type="submit" class="rounded-xl bg-[#687158] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#596249] transition-colors">Apply</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Key Ratios Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187] block">Period Inflows</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#252820] tracking-tight whitespace-nowrap">₦<?= number_format($stats['revenue'], 2) ?></strong>
                    <small class="text-xs text-[#8e9187]"><?= number_format($stats['paid']) ?> paid acquisitions</small>
                </div>
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187] block">Estimated Net Payout</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#687158] tracking-tight whitespace-nowrap">₦<?= number_format($stats['net_payout'], 2) ?></strong>
                    <small class="text-xs text-emerald-700">Cleared funds</small>
                </div>
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187] block">Average Ticket (AOV)</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#252820] tracking-tight whitespace-nowrap">₦<?= number_format($stats['aov'], 2) ?></strong>
                    <small class="text-xs text-[#8e9187]">Per transaction</small>
                </div>
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187] block">Gateway Retainage</span>
                    <strong class="mt-2 block text-2xl font-extrabold text-[#b45309] tracking-tight whitespace-nowrap">₦<?= number_format($stats['estimated_fees'], 2) ?></strong>
                    <small class="text-xs text-[#8e9187]">1.5% processor fee</small>
                </div>
            </div>

            <!-- Full-Width Timeline Revenue Chart -->
            <section class="rounded-2xl border border-[#e5e5dd] bg-white p-6 lg:p-8 shadow-sm">
                <div class="flex items-center justify-between border-b border-[#f0f0ea] pb-4 mb-6">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Daily Breakdown</span>
                        <h3 class="text-lg font-extrabold text-[#252820]">Revenue &amp; Settlement Timeline</h3>
                    </div>
                    <span class="text-xs text-[#8e9187]"><?= e($from) ?> &rarr; <?= e($to) ?></span>
                </div>

                <?php if (empty($revenueTimeline)): ?>
                    <div class="py-24 text-center text-sm text-[#8e9187]">
                        <i data-lucide="bar-chart-3" class="h-10 w-10 mx-auto text-[#d4d6cd] mb-2"></i>
                        No transaction activity recorded for this period.
                    </div>
                <?php else: ?>
                    <?php 
                    $maxRev = 1.0;
                    foreach ($revenueTimeline as $tl) {
                        if ((float)$tl['paid_revenue'] > $maxRev) $maxRev = (float)$tl['paid_revenue'];
                    }
                    ?>
                    <div class="space-y-6">
                        <div class="flex items-end gap-3 h-64 border-b border-[#deded6] pb-3 px-2">
                            <?php foreach ($revenueTimeline as $tl): 
                                $pct = max(6, round(((float)$tl['paid_revenue'] / $maxRev) * 100));
                                $hasRev = (float)$tl['paid_revenue'] > 0;
                            ?>
                                <div class="flex-1 flex flex-col items-center h-full justify-end group relative">
                                    <div class="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none bg-[#252820] text-white text-[11px] font-bold py-1.5 px-3 rounded-lg whitespace-nowrap z-20 shadow-lg">
                                        <?= e($tl['date_label']) ?>: ₦<?= number_format((float)$tl['paid_revenue'], 2) ?> (<?= (int)$tl['paid_count'] ?> orders)
                                    </div>
                                    <div class="w-full rounded-t-xl transition-all <?= $hasRev ? 'bg-[#687158] group-hover:bg-[#596249]' : 'bg-[#e8e8e0]' ?>" style="height: <?= $pct ?>%;"></div>
                                    <span class="text-[10px] font-semibold text-[#8e9187] mt-3 truncate w-full text-center"><?= e($tl['date_label']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 text-xs text-[#8e9187]">
                            <div class="rounded-xl bg-[#fafaf7] p-3 border border-[#e5e5dd]">
                                <span class="block text-[10px] uppercase font-bold text-[#8e9187]">Total Period Inflows</span>
                                <strong class="text-sm font-bold text-[#252820]">₦<?= number_format($stats['revenue'], 2) ?></strong>
                            </div>
                            <div class="rounded-xl bg-[#fafaf7] p-3 border border-[#e5e5dd]">
                                <span class="block text-[10px] uppercase font-bold text-[#8e9187]">Daily Average Run Rate</span>
                                <strong class="text-sm font-bold text-[#252820]">₦<?= count($revenueTimeline) > 0 ? number_format($stats['revenue'] / count($revenueTimeline), 2) : '0.00' ?></strong>
                            </div>
                            <div class="rounded-xl bg-[#fafaf7] p-3 border border-[#e5e5dd]">
                                <span class="block text-[10px] uppercase font-bold text-[#8e9187]">Payment Conversion Rate</span>
                                <strong class="text-sm font-bold text-emerald-700"><?= $stats['conversion_rate'] ?>% Completed</strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Channel Breakdown & Settlement Rules -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Gateway Distribution -->
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm">
                    <div class="border-b border-[#f0f0ea] pb-4 mb-5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Settlement Rails</span>
                        <h3 class="text-base font-extrabold text-[#252820]">Payment Channels</h3>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[#252820]">Paystack Direct (Bank Transfer &amp; Card)</span>
                                <strong class="text-xs font-mono font-bold text-[#687158]">100%</strong>
                            </div>
                            <div class="w-full bg-[#e8e8e0] rounded-full h-2">
                                <div class="bg-[#687158] h-2 rounded-full" style="width: 100%;"></div>
                            </div>
                            <div class="flex justify-between text-[11px] text-[#8e9187] mt-2">
                                <span><?= number_format($stats['paid']) ?> Successful Payments</span>
                                <strong class="text-[#252820]">₦<?= number_format($stats['revenue'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Treasury Settlement Policy -->
                <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm">
                    <div class="border-b border-[#f0f0ea] pb-4 mb-5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Clearing &amp; Compliance</span>
                        <h3 class="text-base font-extrabold text-[#252820]">Settlement Terms</h3>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1.5 border-b border-[#f0f0ea]">
                            <span class="text-[#77796f]">Clearing Window:</span>
                            <strong class="font-bold text-[#252820]">Next Business Day (T+1)</strong>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-[#f0f0ea]">
                            <span class="text-[#77796f]">Currency Supported:</span>
                            <strong class="font-bold text-[#252820]">NGN (Nigerian Naira)</strong>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-[#f0f0ea]">
                            <span class="text-[#77796f]">Reconciliation Mode:</span>
                            <strong class="font-bold text-emerald-800">Automated Webhook Ledger</strong>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-[#77796f]">Dispute Reserves:</span>
                            <strong class="font-bold text-[#252820]">0% (Zero Active Chargebacks)</strong>
                        </div>
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
