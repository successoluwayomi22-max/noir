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
    <title>Transaction Audit Ledger &mdash; <?= e($storeName) ?></title>
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
        $eyebrow = 'General Audit Ledger';
        $title = 'Transaction Audit Ledger';
        $badgeText = count($orders) . ' Transactions Loaded';
        ob_start();
        ?>
        <a href="<?= e(($base ?: '') . '/finance/export?preset=' . urlencode($preset) . '&from=' . urlencode($from) . '&to=' . urlencode($to) . '&payment_status=' . urlencode($paymentStatus) . '&search=' . urlencode($search)) ?>" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] text-white px-4 py-2 text-xs font-bold hover:bg-[#596249] transition-all shadow-sm">
            <i data-lucide="download" class="h-3.5 w-3.5"></i>
            Export Filtered CSV
        </a>
        <?php 
        $customActions = ob_get_clean();
        require BASE_PATH . '/app/Views/finance/topbar.php'; 
        ?>

        <div class="p-6 lg:p-8 space-y-8 w-full">

            <!-- Filter Controls Card -->
            <div class="bg-white rounded-2xl border border-[#e5e5dd] p-5 shadow-sm space-y-4 no-print">
                <form method="get" action="<?= e(($base ?: '') . '/finance/ledger') ?>" class="flex flex-wrap items-center justify-between gap-4">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[280px]">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-[#92948a]"></i>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= e($search) ?>" 
                            placeholder="Search Order # (e.g. ORD-...), Customer, or Gateway Ref..."
                            class="w-full rounded-xl border border-[#deded6] bg-[#fafaf7] py-2.5 pl-10 pr-4 text-xs outline-none focus:border-[#687158]"
                        >
                    </div>

                    <!-- Payment Status Filter -->
                    <div class="flex items-center gap-2">
                        <select name="payment_status" onchange="this.form.submit()" class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3.5 py-2.5 text-xs outline-none focus:border-[#687158]">
                            <option value="">All Payment Statuses</option>
                            <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : '' ?>>Paid Only</option>
                            <option value="pending" <?= $paymentStatus === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                            <option value="failed" <?= $paymentStatus === 'failed' ? 'selected' : '' ?>>Failed Only</option>
                        </select>
                        <button type="submit" class="rounded-xl bg-[#687158] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#596249]">Search</button>
                    </div>
                </form>

                <!-- Presets Row -->
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-[#f0f0ea]">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8e9187] mr-2">Range:</span>
                    <a href="<?= e(($base ?: '') . '/finance/ledger?preset=today') ?>" class="preset-btn <?= $preset === 'today' ? 'active' : '' ?>">Today</a>
                    <a href="<?= e(($base ?: '') . '/finance/ledger?preset=7d') ?>" class="preset-btn <?= $preset === '7d' ? 'active' : '' ?>">Last 7 Days</a>
                    <a href="<?= e(($base ?: '') . '/finance/ledger?preset=30d') ?>" class="preset-btn <?= $preset === '30d' ? 'active' : '' ?>">Last 30 Days</a>
                    <a href="<?= e(($base ?: '') . '/finance/ledger?preset=month') ?>" class="preset-btn <?= $preset === 'month' ? 'active' : '' ?>">This Month</a>
                    <a href="<?= e(($base ?: '') . '/finance/ledger?preset=year') ?>" class="preset-btn <?= $preset === 'year' ? 'active' : '' ?>">Year to Date</a>
                    <a href="<?= e(($base ?: '') . '/finance/ledger?preset=all') ?>" class="preset-btn <?= $preset === 'all' ? 'active' : '' ?>">All Time</a>
                </div>
            </div>

            <!-- Ledger Table -->
            <section class="rounded-2xl border border-[#e5e5dd] bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th>Order &amp; Gateway Ref</th>
                                <th>Client / Customer</th>
                                <th>Payment Channel</th>
                                <th>Gross Total</th>
                                <th>Est. Fee</th>
                                <th>Net Settlement</th>
                                <th>Status</th>
                                <th>Date &amp; Time</th>
                                <th class="text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-16 text-[#8e9187]">
                                        <i data-lucide="inbox" class="h-8 w-8 mx-auto text-[#d4d6cd] mb-2"></i>
                                        No financial records found matching your filters.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): 
                                    $st = (string)($ord['payment_status'] ?? 'pending');
                                ?>
                                    <tr>
                                        <td>
                                            <strong class="block font-mono text-xs font-bold text-[#252820]"><?= e($ord['order_number']) ?></strong>
                                            <?php if (!empty($ord['provider_reference'])): ?>
                                                <span class="block font-mono text-[10px] text-[#8e9187] truncate max-w-[130px]" title="<?= e($ord['provider_reference']) ?>">
                                                    <?= e($ord['provider_reference']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="block text-[10px] text-[#aaa]">Direct / Awaiting</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="block text-xs font-bold text-[#252820]"><?= e($ord['customer_name']) ?></strong>
                                            <span class="block text-[10px] text-[#8e9187] truncate max-w-[140px]"><?= e($ord['customer_email']) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-medium text-[#55584f] inline-flex items-center gap-1.5">
                                                <i data-lucide="credit-card" class="h-3.5 w-3.5 text-[#8e9187]"></i>
                                                <?= e($ord['channel']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-xs font-bold text-[#252820] whitespace-nowrap">
                                                <?= e($ord['currency']) ?> <?= number_format((float)$ord['total'], 2) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="text-xs text-[#8e9187] whitespace-nowrap">
                                                -₦<?= number_format((float)$ord['estimated_fee'], 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-xs font-bold text-emerald-800 whitespace-nowrap">
                                                ₦<?= number_format((float)$ord['net_payout'], 2) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if ($st === 'paid'): ?>
                                                <span class="badge-paid"><i data-lucide="check" class="h-3 w-3"></i> Paid</span>
                                            <?php elseif ($st === 'failed'): ?>
                                                <span class="badge-failed"><i data-lucide="x" class="h-3 w-3"></i> Failed</span>
                                            <?php else: ?>
                                                <span class="badge-pending"><i data-lucide="clock" class="h-3 w-3"></i> Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-xs text-[#77796f] whitespace-nowrap">
                                            <?= e(date('d M Y, H:i', strtotime($ord['created_at']))) ?>
                                        </td>
                                        <td class="text-right">
                                            <button 
                                                type="button" 
                                                onclick="viewReceipt(<?= (int)$ord['order_id'] ?>)" 
                                                class="rounded-xl border border-[#deded6] bg-white px-3 py-1.5 text-[11px] font-bold text-[#687158] hover:bg-[#687158] hover:text-white transition-all shadow-sm"
                                            >
                                                Receipt
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-[#fafaf7] border-t border-[#e8e8e0] flex items-center justify-between text-xs text-[#8e9187]">
                    <span>Displaying <?= count($orders) ?> transactions for the active parameters.</span>
                    <a href="<?= e(($base ?: '') . '/finance/export?preset=' . urlencode($preset) . '&from=' . urlencode($from) . '&to=' . urlencode($to) . '&payment_status=' . urlencode($paymentStatus) . '&search=' . urlencode($search)) ?>" class="font-bold text-[#687158] hover:underline">
                        Download Full CSV &rarr;
                    </a>
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
