<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$ordersUrl = ($base ?: '') . '/admin/orders';
$adminUrl = ($base ?: '') . '/admin';
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Orders Management — <?= e($storeName) ?></title>
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <?= store_brand_style() ?>
    <style>
        body { font-family: Urbanist, ui-sans-serif, system-ui; }
        .admin-main { margin-left: 250px; min-height: 100vh; background: var(--brand-paper, #f5f5f0); }
        .admin-topbar { display: flex; justify-content: space-between; gap: 24px; padding: 34px 42px; border-bottom: 1px solid #e5e5dd; }
        .order-table th { padding: 14px 16px; text-align: left; color: #92948a; font-size: 10px; letter-spacing: .13em; text-transform: uppercase; white-space: nowrap; }
        .order-table td { padding: 15px 16px; border-top: 1px solid #e8e8e0; font-size: 12px; white-space: nowrap; }
        .order-table tbody tr:hover { background: #f4f4ed; }
        .modal-backdrop { background: rgba(37, 40, 32, .55); backdrop-filter: blur(4px); }
        @media print {
            .no-print, .admin-sidebar, .admin-topbar, .filters-bar, .pagination-bar { display: none !important; }
            .admin-main { margin-left: 0 !important; background: #fff !important; }
            #order-modal { position: static !important; background: transparent !important; display: block !important; }
            #order-modal-content { max-width: 100% !important; box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="text-[#252820]">

<?php $activeNav = 'orders'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <header class="admin-topbar no-print">
        <div>
            <p class="mb-2 text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Commerce Engine</p>
            <h1 class="text-3xl font-medium">Orders Management</h1>
            <p class="mt-2 text-sm text-[#77796f]">Track, review, fulfill, and manage customer orders across all channels.</p>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <?php $adminAvatar = (string)($user['profile_image'] ?? $user['avatar'] ?? store_setting('admin_profile_image_' . (int)($user['id'] ?? $user['user_id'] ?? 0)) ?: store_setting('admin_profile_image_0')); ?>
            <?php if ($adminAvatar !== ''): ?>
                <img src="<?= e(($base ?: '') . $adminAvatar) ?>" alt="<?= e($user['name'] ?? 'Admin') ?>" style="width:36px;height:36px;border-radius:9999px;object-fit:cover;border:1px solid rgba(0,0,0,0.1);">
            <?php else: ?>
                <span style="display:inline-grid;place-items:center;width:36px;height:36px;border-radius:9999px;background:#687158;color:#fff;font-size:12px;font-weight:600;"><?= e(strtoupper(substr((string)($user['name'] ?? 'A'), 0, 1))) ?></span>
            <?php endif; ?>
            <div>
                <strong style="display:block;font-size:13px;line-height:1.2;"><?= e($user['name'] ?? 'Admin') ?></strong>
                <span style="font-size:11px;color:#85877d;"><?= e($user['email'] ?? '') ?></span>
            </div>
            <form method="post" action="<?= e(($base ?: '') . '/logout') ?>" style="margin-left:8px;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="text-[#687158] hover:text-[#252820] text-xs font-semibold">Sign out</button>
            </form>
        </div>
    </header>

    <section class="p-6 lg:p-10">
        <!-- Stat Ribbon -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5 mb-8 no-print">
            <div class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#92948a]">Total Orders</span>
                <strong class="mt-2 block text-2xl font-medium text-[#252820]"><?= number_format((int)($orderStats['total'] ?? 0)) ?></strong>
                <small class="text-[11px] text-[#77796f]">All storefront orders</small>
            </div>
            <div class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#b45309]">Pending Fulfillment</span>
                <strong class="mt-2 block text-2xl font-medium text-[#b45309]"><?= number_format((int)($orderStats['pending'] ?? 0)) ?></strong>
                <small class="text-[11px] text-[#77796f]">Awaiting packing</small>
            </div>
            <div class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#0284c7]">In Processing / Transit</span>
                <strong class="mt-2 block text-2xl font-medium text-[#0284c7]"><?= number_format((int)($orderStats['processing'] ?? 0)) ?></strong>
                <small class="text-[11px] text-[#77796f]">Dispatched & on the way</small>
            </div>
            <div class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#15803d]">Delivered & Closed</span>
                <strong class="mt-2 block text-2xl font-medium text-[#15803d]"><?= number_format((int)($orderStats['delivered'] ?? 0)) ?></strong>
                <small class="text-[11px] text-[#77796f]">Completed successfully</small>
            </div>
            <div class="col-span-2 sm:col-span-1 rounded-2xl border border-[#e5e5dd] bg-[#252820] p-4 sm:p-5 text-white">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#b9bcb2]">Net Order Revenue</span>
                <strong class="mt-2 block text-[18px] sm:text-lg lg:text-[20px] xl:text-[22px] font-bold text-white tracking-tight leading-tight whitespace-nowrap" title="₦<?= number_format((float)($orderStats['revenue'] ?? 0), 2) ?>">₦<?= number_format((float)($orderStats['revenue'] ?? 0), 2) ?></strong>
                <small class="text-[11px] text-[#8e9187]">Recorded transactions</small>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 filters-bar no-print">
            <div class="flex w-full max-w-3xl flex-1 flex-wrap gap-3">
                <div class="relative flex-1 min-w-[220px]">
                    <i data-lucide="search" class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#92948a]"></i>
                    <input id="order-search" type="search" placeholder="Search Order # (e.g. ORD-...), Customer name, or Email" class="w-full rounded-2xl border border-[#deded6] bg-[#fbfbf7] py-3.5 pl-12 pr-4 text-sm outline-none focus:border-[#687158]">
                </div>
                <select id="order-status" class="rounded-2xl border border-[#deded6] bg-[#fbfbf7] px-4 py-3.5 text-sm outline-none focus:border-[#687158]">
                    <option value="">All Order Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="packed">Packed</option>
                    <option value="shipped">Shipped</option>
                    <option value="out_for_delivery">Out For Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select id="order-payment" class="rounded-2xl border border-[#deded6] bg-[#fbfbf7] px-4 py-3.5 text-sm outline-none focus:border-[#687158]">
                    <option value="">All Payment Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Payment Pending</option>
                    <option value="refunded">Refunded</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <a id="export-orders" href="<?= e($ordersUrl . '/export') ?>" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] px-4 py-3 text-xs font-semibold text-white hover:bg-[#575f49] transition-colors">
                    <i data-lucide="download" class="h-4 w-4"></i>Export CSV
                </a>
            </div>
        </div>

        <!-- Orders Table Card -->
        <div class="overflow-hidden rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7]">
            <div id="order-status-message" class="hidden border-b border-[#e8e8e0] px-5 py-3 text-xs text-[#77796f]"></div>
            <div class="overflow-x-auto">
                <table class="order-table w-full">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Items Preview</th>
                            <th>Total Amount</th>
                            <th>Payment</th>
                            <th>Fulfillment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="order-rows">
                        <tr>
                            <td colspan="8" class="p-10 text-center text-sm text-[#92948a]">Loading orders…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#e8e8e0] px-5 py-4 pagination-bar no-print">
                <span id="order-summary" class="text-xs text-[#92948a]"></span>
                <div id="order-pagination" class="flex gap-2"></div>
            </div>
        </div>
    </section>
</main>

<!-- Order Details Modal / Slideout -->
<div id="order-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-5">
    <div id="order-modal-content" class="w-full max-w-4xl rounded-3xl bg-[#fbfbf7] p-6 lg:p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between border-b border-[#e8e8e0] pb-5 no-print">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Order Overview</p>
                <h2 id="modal-order-number" class="mt-1 text-2xl font-medium">ORD-000000</h2>
                <p id="modal-order-date" class="text-xs text-[#92948a] mt-1"></p>
            </div>
            <div class="flex items-center gap-3">
                <button id="print-order" class="rounded-xl border border-[#d8d8cf] px-3.5 py-2 text-xs font-semibold text-[#687158] hover:bg-[#eef0e8] flex items-center gap-1.5">
                    <i data-lucide="printer" class="h-4 w-4"></i>Print Invoice
                </button>
                <button id="close-order" class="rounded-lg p-2 hover:bg-[#e8e8e0]">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>

        <div id="order-modal-body" class="mt-6">
            <p class="text-sm text-[#92948a] text-center py-8">Loading order details…</p>
        </div>
    </div>
</div>

<div id="toast" class="fixed bottom-6 right-6 z-[60] hidden rounded-xl bg-[#252820] px-5 py-3 text-xs text-white shadow-xl"></div>

<script>
const base = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const csrf = <?= json_encode(csrf_token()) ?>;
let page = 1, timer;

function esc(v) { return $('<div>').text(v ?? '').html(); }
function initials(n) { return (n || 'U').split(' ').map(x => x[0]).slice(0, 2).join('').toUpperCase(); }
function toast(m) { $('#toast').text(m).stop(true, true).fadeIn(150).delay(2200).fadeOut(250); }

function paymentBadge(s) {
    s = (s || 'pending').toLowerCase();
    if (s === 'paid') return '<span class="rounded-full bg-[#dcfce7] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#15803d]">Paid</span>';
    if (s === 'refunded') return '<span class="rounded-full bg-[#e0e7ff] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#4338ca]">Refunded</span>';
    if (s === 'failed') return '<span class="rounded-full bg-[#fee2e2] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#b91c1c]">Failed</span>';
    return '<span class="rounded-full bg-[#fef3c7] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#b45309]">Pending</span>';
}

function statusBadge(s) {
    s = (s || 'pending').toLowerCase();
    if (s === 'delivered' || s === 'completed') return '<span class="rounded-full bg-[#dcfce7] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#15803d]">' + esc(s) + '</span>';
    if (s === 'shipped' || s === 'out_for_delivery') return '<span class="rounded-full bg-[#e0f2fe] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#0369a1]">' + esc(s.replace('_', ' ')) + '</span>';
    if (s === 'processing' || s === 'packed') return '<span class="rounded-full bg-[#fef9c3] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#a16207]">' + esc(s) + '</span>';
    if (s === 'cancelled') return '<span class="rounded-full bg-[#fee2e2] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#b91c1c]">Cancelled</span>';
    return '<span class="rounded-full bg-[#f1f5f9] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#475569]">Pending</span>';
}

function loadOrders() {
    const search = $('#order-search').val();
    const status = $('#order-status').val();
    const payment_status = $('#order-payment').val();
    $('#order-status-message').text('Loading orders…').removeClass('hidden');

    $.get(base + '/admin/orders/list', { search, status, payment_status, page }).done(function(data) {
        page = data.pagination.page;
        let html = '';
        if (!data.orders || !data.orders.length) {
            html = '<tr><td colspan="8" class="p-10 text-center text-sm text-[#92948a]">No orders matched your search criteria.</td></tr>';
        } else {
            data.orders.forEach(o => {
                const customerName = o.customer_name || 'Guest Checkout';
                const customerEmail = o.customer_email || '—';
                const dateFormatted = new Date(o.created_at.replace(' ', 'T')).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                
                let itemsSummary = '—';
                if (o.items && o.items.length) {
                    const firstItem = o.items[0].product_name;
                    itemsSummary = esc(firstItem);
                    if (o.items.length > 1) {
                        itemsSummary += ' <span class="text-[#92948a] font-normal">(+' + (o.items.length - 1) + ' more)</span>';
                    }
                }

                html += '<tr>' +
                    '<td><strong class="font-mono text-[11px] font-bold text-[#252820]">' + esc(o.order_number) + '</strong></td>' +
                    '<td class="text-[#77796f] text-[11px]">' + esc(dateFormatted) + '</td>' +
                    '<td><div class="flex items-center gap-2.5"><span class="grid h-7 w-7 place-items-center rounded-full bg-[#687158] text-[10px] font-semibold text-white">' + esc(initials(customerName)) + '</span><div><strong class="block text-xs">' + esc(customerName) + '</strong><span class="block text-[10px] text-[#92948a]">' + esc(customerEmail) + '</span></div></div></td>' +
                    '<td class="max-w-[200px] truncate text-xs" title="' + esc(itemsSummary) + '">' + itemsSummary + '</td>' +
                    '<td><strong class="text-xs">' + esc(o.currency) + ' ' + Number(o.total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong></td>' +
                    '<td>' + paymentBadge(o.payment_status) + '</td>' +
                    '<td>' + statusBadge(o.status) + '</td>' +
                    '<td><button class="view-order rounded-xl border border-[#d8d8cf] bg-[#fff] px-3.5 py-1.5 text-[11px] font-semibold text-[#687158] hover:bg-[#687158] hover:text-white transition-colors" data-id="' + o.id + '">Manage &bull; Details</button></td>' +
                '</tr>';
            });
        }
        $('#order-rows').html(html);
        $('#order-summary').text(data.pagination.total + ' total order' + (data.pagination.total === 1 ? '' : 's'));

        let p = '';
        for (let i = 1; i <= data.pagination.pages; i++) {
            p += '<button class="order-page h-8 min-w-8 rounded-lg px-2 text-xs ' + (i === data.pagination.page ? 'bg-[#687158] text-white font-semibold' : 'border border-[#deded6] text-[#687158] hover:bg-[#eef0e8]') + '" data-page="' + i + '">' + i + '</button>';
        }
        $('#order-pagination').html(p);
        $('#order-status-message').addClass('hidden');
    }).fail(() => {
        $('#order-status-message').text('Failed to load orders. Please refresh.').removeClass('hidden');
    });
}

function openOrder(id) {
    $('#order-modal').removeClass('hidden').addClass('flex');
    $('#order-modal-body').html('<p class="text-sm text-[#92948a] text-center py-8">Loading order details…</p>');

    $.get(base + '/admin/orders/' + id).done(function(data) {
        if (!data.success || !data.order) {
            $('#order-modal-body').html('<p class="text-sm text-[#b91c1c] text-center py-8">Could not retrieve order details.</p>');
            return;
        }

        const o = data.order;
        const items = data.items || [];
        const shipment = data.shipment || {};

        $('#modal-order-number').text(o.order_number);
        $('#modal-order-date').text('Placed on ' + new Date(o.created_at.replace(' ', 'T')).toLocaleString());

        let itemsRows = '';
        let itemsSubtotal = 0;
        if (items.length) {
            items.forEach(it => {
                const lineTotal = Number(it.unit_price) * Number(it.quantity);
                itemsSubtotal += lineTotal;
                const img = it.image_url ? '<img src="' + esc(it.image_url) + '" class="h-12 w-12 rounded-lg object-cover border border-[#e5e5dd]">' : '<div class="h-12 w-12 rounded-lg bg-[#e8e8e0] grid place-items-center text-xs font-semibold text-[#8e9187]">Item</div>';
                itemsRows += '<tr class="border-t border-[#e8e8e0]">' +
                    '<td class="py-3 pr-3">' + img + '</td>' +
                    '<td class="py-3"><strong class="block text-xs">' + esc(it.product_name) + '</strong><span class="text-[10px] text-[#92948a]">Unit: ' + esc(o.currency) + ' ' + Number(it.unit_price).toLocaleString() + '</span></td>' +
                    '<td class="py-3 text-center text-xs font-medium">' + esc(it.quantity) + '</td>' +
                    '<td class="py-3 text-right text-xs font-semibold">' + esc(o.currency) + ' ' + Number(lineTotal).toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</td>' +
                '</tr>';
            });
        } else {
            itemsRows = '<tr><td colspan="4" class="py-4 text-center text-xs text-[#92948a]">No item records attached to this order.</td></tr>';
        }

        currentOrderData = data;
        const customerName = o.customer_name || 'Guest Customer';
        const customerEmail = o.customer_email || '—';
        const customerPhone = o.customer_phone || '—';
        const customerIp = o.customer_ip || 'Not recorded';
        const isIpBlocked = !!o.is_ip_blocked;

        let ipBlockControls = '';
        if (customerIp && customerIp !== 'Not recorded') {
            if (isIpBlocked) {
                ipBlockControls = '<div class="mt-2.5 flex items-center justify-between bg-rose-50 border border-rose-200 rounded-xl px-2.5 py-1.5">' +
                    '<span class="text-[11px] font-bold text-rose-700">IP Blocked</span>' +
                    '<button type="button" onclick="quickUnblockIp(\'' + esc(customerIp) + '\')" class="text-[11px] font-semibold text-[#687158] underline hover:text-black">Unblock</button>' +
                '</div>';
            } else {
                ipBlockControls = '<div class="mt-2.5 flex items-center justify-between bg-[#fbfbf7] border border-[#e8e8e0] rounded-xl px-2.5 py-1.5">' +
                    '<span class="text-[10px] text-[#77796f]">Status: Clean</span>' +
                    '<button type="button" onclick="quickBlockIp(\'' + esc(customerIp) + '\', \'Order #' + esc(o.order_number) + '\')" class="text-[10px] font-semibold text-rose-700 bg-rose-100 hover:bg-rose-200 border border-rose-200 px-2 py-0.5 rounded-md">Block IP</button>' +
                '</div>';
            }
        }

        let html = '<div class="grid gap-6 lg:grid-cols-3">' +
            '<!-- Customer & Summary Column -->' +
            '<div class="space-y-4">' +
                '<div class="rounded-2xl bg-[#f0f0e9] p-5">' +
                    '<h4 class="text-[11px] font-bold uppercase tracking-wider text-[#687158] mb-3">Customer Information</h4>' +
                    '<p class="text-sm font-semibold text-[#252820]">' + esc(customerName) + '</p>' +
                    '<p class="text-xs text-[#77796f] mt-1 break-all">' + esc(customerEmail) + '</p>' +
                    '<p class="text-xs text-[#77796f] mt-1">Phone: ' + esc(customerPhone) + '</p>' +
                    '<div class="mt-3 pt-3 border-t border-[#deded6]">' +
                        '<div class="flex items-center justify-between text-xs">' +
                            '<span class="text-[11px] font-semibold text-[#77796f]">Client IP:</span>' +
                            '<code class="font-mono text-[#252820] bg-white px-1.5 py-0.5 rounded border border-[#deded6] text-[11px]">' + esc(customerIp) + '</code>' +
                        '</div>' +
                        ipBlockControls +
                    '</div>' +
                '</div>' +
                '<div class="rounded-2xl bg-[#252820] p-5 text-white">' +
                    '<h4 class="text-[11px] font-bold uppercase tracking-wider text-[#b9bcb2] mb-3">Payment Summary</h4>' +
                    '<div class="flex justify-between text-xs py-1 text-[#b9bcb2]"><span>Payment Status:</span> ' + paymentBadge(o.payment_status) + '</div>' +
                    '<div class="flex justify-between text-xs py-1 text-[#b9bcb2]"><span>Total Amount:</span> <strong class="text-sm text-white font-bold">' + esc(o.currency) + ' ' + Number(o.total).toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</strong></div>' +
                '</div>' +
            '</div>' +
            '<!-- Items Column -->' +
            '<div class="lg:col-span-2 rounded-2xl border border-[#e5e5dd] p-5 bg-white">' +
                '<h4 class="text-[11px] font-bold uppercase tracking-wider text-[#687158] mb-3">Ordered Products (' + items.length + ')</h4>' +
                '<div class="overflow-x-auto max-h-[220px] overflow-y-auto">' +
                    '<table class="w-full text-left text-xs">' +
                        '<thead><tr><th class="pb-2">Photo</th><th class="pb-2">Product</th><th class="pb-2 text-center">Qty</th><th class="pb-2 text-right">Subtotal</th></tr></thead>' +
                        '<tbody>' + itemsRows + '</tbody>' +
                    '</table>' +
                '</div>' +
                '<div class="border-t border-[#e8e8e0] mt-3 pt-3 flex justify-between items-center text-sm font-semibold">' +
                    '<span>Grand Total:</span>' +
                    '<span class="text-base text-[#687158] font-bold">' + esc(o.currency) + ' ' + Number(o.total).toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</span>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<!-- Status & Fulfillment Actions Form -->' +
        '<div class="mt-6 rounded-2xl border border-[#deded6] bg-[#fbfbf7] p-5 no-print">' +
            '<h4 class="text-[11px] font-bold uppercase tracking-wider text-[#687158] mb-4">Update Fulfillment & Order Status</h4>' +
            '<form id="update-order-form" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">' +
                '<input type="hidden" name="order_id" value="' + o.id + '">' +
                '<div>' +
                    '<label class="block text-[11px] font-semibold text-[#77796f] mb-1">Order Status</label>' +
                    '<select name="status" class="w-full rounded-xl border border-[#deded6] bg-white p-2.5 text-xs outline-none focus:border-[#687158]">' +
                        '<option value="pending"' + (o.status === 'pending' ? ' selected' : '') + '>Pending</option>' +
                        '<option value="processing"' + (o.status === 'processing' ? ' selected' : '') + '>Processing</option>' +
                        '<option value="packed"' + (o.status === 'packed' ? ' selected' : '') + '>Packed</option>' +
                        '<option value="shipped"' + (o.status === 'shipped' ? ' selected' : '') + '>Shipped</option>' +
                        '<option value="out_for_delivery"' + (o.status === 'out_for_delivery' ? ' selected' : '') + '>Out For Delivery</option>' +
                        '<option value="delivered"' + (o.status === 'delivered' ? ' selected' : '') + '>Delivered</option>' +
                        '<option value="completed"' + (o.status === 'completed' ? ' selected' : '') + '>Completed</option>' +
                        '<option value="cancelled"' + (o.status === 'cancelled' ? ' selected' : '') + '>Cancelled</option>' +
                    '</select>' +
                '</div>' +
                '<div>' +
                    '<label class="block text-[11px] font-semibold text-[#77796f] mb-1">Payment Status</label>' +
                    '<select name="payment_status" class="w-full rounded-xl border border-[#deded6] bg-white p-2.5 text-xs outline-none focus:border-[#687158]">' +
                        '<option value="pending"' + (o.payment_status === 'pending' ? ' selected' : '') + '>Payment Pending</option>' +
                        '<option value="paid"' + (o.payment_status === 'paid' ? ' selected' : '') + '>Paid</option>' +
                        '<option value="refunded"' + (o.payment_status === 'refunded' ? ' selected' : '') + '>Refunded</option>' +
                        '<option value="failed"' + (o.payment_status === 'failed' ? ' selected' : '') + '>Failed</option>' +
                    '</select>' +
                '</div>' +
                '<div>' +
                    '<label class="block text-[11px] font-semibold text-[#77796f] mb-1">Courier / Carrier</label>' +
                    '<input type="text" name="courier" value="' + esc(shipment.courier || '') + '" placeholder="e.g. DHL, In-House Courier" class="w-full rounded-xl border border-[#deded6] bg-white p-2.5 text-xs outline-none focus:border-[#687158]">' +
                '</div>' +
                '<div>' +
                    '<label class="block text-[11px] font-semibold text-[#77796f] mb-1">Tracking Number</label>' +
                    '<input type="text" name="tracking_number" value="' + esc(shipment.tracking_number || '') + '" placeholder="e.g. TRK-8921820" class="w-full rounded-xl border border-[#deded6] bg-white p-2.5 text-xs outline-none focus:border-[#687158]">' +
                '</div>' +
                '<div class="sm:col-span-2 lg:col-span-4 flex justify-end gap-3 mt-2">' +
                    '<button type="submit" id="save-order-btn" class="rounded-xl bg-[#687158] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#575f49] transition-colors">' +
                        'Save & Update Order Status' +
                    '</button>' +
                '</div>' +
            '</form>' +
        '</div>';

        $('#order-modal-body').html(html);
        lucide.createIcons();
    }).fail(() => {
        $('#order-modal-body').html('<p class="text-sm text-[#b91c1c] text-center py-8">Failed to load order details from server.</p>');
    });
}

function quickBlockIp(ip, context) {
    if (!ip || ip === 'Not recorded') return;
    if (!confirm('Blacklist and block IP ' + ip + '?\n\nThis will immediately terminate their active session and return 403 Forbidden.')) return;
    
    $.post(base + '/admin/security/block-ip', {
        ip_address: ip,
        reason: 'Blocked from ' + (context || 'Order modal'),
        duration_hours: 168,
        is_ajax: 1,
        _csrf: csrf
    }).done(function(res) {
        toast(res.message || 'IP blacklisted successfully.');
        if (currentOrderData && currentOrderData.order) {
            openOrder(currentOrderData.order.id);
        }
    }).fail(function(xhr) {
        toast((xhr.responseJSON || {}).message || 'Failed to block IP.');
    });
}

function quickUnblockIp(ip) {
    if (!ip) return;
    if (!confirm('Lift block on IP ' + ip + '?')) return;
    
    $.post(base + '/admin/security/unblock-ip', {
        ip_address: ip,
        is_ajax: 1,
        _csrf: csrf
    }).done(function(res) {
        toast(res.message || 'IP unblocked successfully.');
        if (currentOrderData && currentOrderData.order) {
            openOrder(currentOrderData.order.id);
        }
    }).fail(function(xhr) {
        toast((xhr.responseJSON || {}).message || 'Failed to unblock IP.');
    });
}

function printOrderInvoice(data) {
    if (!data || !data.order) {
        toast('Please wait for the order details to load before printing.');
        return;
    }
    const o = data.order;
    const items = data.items || [];
    const shipment = data.shipment || {};
    const storeName = <?= json_encode($storeName) ?>;
    const customerName = o.customer_name || 'Guest Customer';
    const customerEmail = o.customer_email || '—';
    const customerPhone = o.customer_phone || '—';
    const customerIp = o.customer_ip || '—';
    const dateFormatted = new Date(o.created_at.replace(' ', 'T')).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });

    let rowsHtml = '';
    let calcSubtotal = 0;
    items.forEach((it, idx) => {
        const lineTotal = Number(it.unit_price) * Number(it.quantity);
        calcSubtotal += lineTotal;
        rowsHtml += '<tr style="border-bottom:1px solid #eee;">' +
            '<td style="padding:10px 8px;font-size:12px;color:#555;">' + (idx + 1) + '</td>' +
            '<td style="padding:10px 8px;font-size:13px;color:#111;font-weight:600;">' + esc(it.product_name) + '</td>' +
            '<td style="padding:10px 8px;font-size:12px;color:#555;text-align:center;">' + esc(it.quantity) + '</td>' +
            '<td style="padding:10px 8px;font-size:12px;color:#555;text-align:right;">' + esc(o.currency) + ' ' + Number(it.unit_price).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</td>' +
            '<td style="padding:10px 8px;font-size:13px;color:#111;font-weight:600;text-align:right;">' + esc(o.currency) + ' ' + Number(lineTotal).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</td>' +
        '</tr>';
    });

    const printHtml = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
        '<title>Invoice #' + esc(o.order_number) + ' - ' + esc(storeName) + '</title>' +
        '<style>' +
            '@page { size: A4 portrait; margin: 15mm; }' +
            'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #252820; margin: 0; padding: 20px; font-size: 13px; line-height: 1.5; background: #fff; }' +
            '.inv-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #252820; padding-bottom: 18px; margin-bottom: 20px; }' +
            '.inv-brand { font-size: 24px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #252820; }' +
            '.inv-tagline { font-size: 10px; color: #777; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 2px; }' +
            '.inv-title-box { text-align: right; }' +
            '.inv-title { font-size: 20px; font-weight: 700; color: #687158; text-transform: uppercase; letter-spacing: 0.08em; }' +
            '.inv-meta-line { font-size: 12px; color: #555; margin-top: 2px; }' +
            '.badge { display: inline-block; padding: 3px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }' +
            '.badge-paid { background: #e0f2fe; color: #0284c7; }' +
            '.badge-pending { background: #fef3c7; color: #b45309; }' +
            '.inv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }' +
            '.inv-card { background: #fafaf7; border: 1px solid #e8e8e0; border-radius: 8px; padding: 14px; }' +
            '.inv-card-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #687158; margin-bottom: 6px; }' +
            '.table-items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }' +
            '.table-items th { background: #252820; color: #fff; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; padding: 9px 8px; text-align: left; }' +
            '.totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 25px; }' +
            '.totals-table { width: 280px; }' +
            '.totals-table td { padding: 5px 0; font-size: 12px; }' +
            '.totals-total { border-top: 2px solid #252820; font-size: 15px; font-weight: 800; color: #252820; padding-top: 8px !important; }' +
            '.inv-footer { border-top: 1px solid #e8e8e0; padding-top: 16px; text-align: center; font-size: 11px; color: #888; margin-top: 20px; }' +
        '</style>' +
    '</head>' +
    '<body>' +
        '<div class="inv-header">' +
            '<div>' +
                '<div class="inv-brand">' + esc(storeName) + '</div>' +
                '<div class="inv-tagline">Maison Luxury Apparel & High Horology</div>' +
                '<div style="font-size:11px;color:#666;margin-top:6px;">Official Order Receipt & Packing Slip<br>Fulfillment Center</div>' +
            '</div>' +
            '<div class="inv-title-box">' +
                '<div class="inv-title">Commercial Invoice</div>' +
                '<div class="inv-meta-line"><strong>Order No:</strong> ' + esc(o.order_number) + '</div>' +
                '<div class="inv-meta-line"><strong>Date:</strong> ' + dateFormatted + '</div>' +
                '<div class="inv-meta-line" style="margin-top:5px;">' +
                    '<span class="badge ' + (o.payment_status === 'paid' ? 'badge-paid' : 'badge-pending') + '">Payment: ' + esc(o.payment_status) + '</span> ' +
                    '<span class="badge" style="background:#f0f1eb;color:#687158;">Status: ' + esc(o.status) + '</span>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="inv-grid">' +
            '<div class="inv-card">' +
                '<div class="inv-card-title">Billed To (Customer Details)</div>' +
                '<div style="font-size:13px;font-weight:700;color:#252820;">' + esc(customerName) + '</div>' +
                '<div style="color:#666;margin-top:2px;">Email: ' + esc(customerEmail) + '</div>' +
                '<div style="color:#666;margin-top:2px;">Phone: ' + esc(customerPhone) + '</div>' +
                '<div style="font-size:10px;color:#888;margin-top:5px;">Verified Session • IP: ' + esc(customerIp) + '</div>' +
            '</div>' +
            '<div class="inv-card">' +
                '<div class="inv-card-title">Fulfillment & Logistics</div>' +
                '<div><strong>Carrier:</strong> ' + esc(shipment.courier || 'White Glove Dispatch') + '</div>' +
                '<div style="margin-top:2px;"><strong>Tracking No:</strong> ' + esc(shipment.tracking_number || 'Awaiting Carrier Pick-up') + '</div>' +
                '<div style="margin-top:2px;"><strong>Fulfillment:</strong> ' + esc(o.status.toUpperCase()) + '</div>' +
            '</div>' +
        '</div>' +
        '<table class="table-items">' +
            '<thead><tr><th style="width:30px;">#</th><th>Product Item</th><th style="width:70px;text-align:center;">Qty</th><th style="width:110px;text-align:right;">Unit Price</th><th style="width:110px;text-align:right;">Subtotal</th></tr></thead>' +
            '<tbody>' + rowsHtml + '</tbody>' +
        '</table>' +
        '<div class="totals-wrap">' +
            '<table class="totals-table">' +
                '<tr><td style="color:#777;">Items Subtotal:</td><td style="text-align:right;font-weight:600;">' + esc(o.currency) + ' ' + calcSubtotal.toLocaleString(undefined, {minimumFractionDigits:2}) + '</td></tr>' +
                '<tr><td style="color:#777;">Luxury Shipping:</td><td style="text-align:right;color:#687158;font-weight:600;">Complimentary</td></tr>' +
                '<tr class="totals-total"><td>Grand Total:</td><td style="text-align:right;color:#687158;">' + esc(o.currency) + ' ' + Number(o.total).toLocaleString(undefined, {minimumFractionDigits:2}) + '</td></tr>' +
            '</table>' +
        '</div>' +
        '<div class="inv-footer">' +
            '<p><strong>Thank you for acquiring with ' + esc(storeName) + '.</strong></p>' +
            '<p style="margin-top:3px;">Every piece is inspected for craftsmanship authenticity. Complimentary returns honored within 30 days of delivery.</p>' +
        '</div>' +
        '<script>window.onload=function(){window.print();setTimeout(function(){window.close();},500);};<\/script>' +
    '</body></html>';

    const printWin = window.open('', '_blank', 'width=850,height=900');
    if (printWin) {
        printWin.document.open();
        printWin.document.write(printHtml);
        printWin.document.close();
    }
}

$(document).on('click', '.view-order', function() {
    openOrder($(this).data('id'));
}).on('click', '.order-page', function() {
    page = Number($(this).data('page'));
    loadOrders();
}).on('submit', '#update-order-form', function(e) {
    e.preventDefault();
    const btn = $('#save-order-btn');
    btn.prop('disabled', true).text('Saving…');

    const formData = $(this).serializeArray();
    formData.push({ name: '_csrf', value: csrf });

    $.post(base + '/admin/orders/status', formData).done(function(d) {
        toast(d.message || 'Order updated successfully.');
        loadOrders();
    }).fail(function(x) {
        toast((x.responseJSON || {}).message || 'Failed to update order.');
    }).always(function() {
        btn.prop('disabled', false).text('Save & Update Order Status');
    });
});

$('#close-order').on('click', () => $('#order-modal').addClass('hidden').removeClass('flex'));
$('#print-order').on('click', () => printOrderInvoice(currentOrderData));

$('#order-search').on('input', function() {
    clearTimeout(timer);
    timer = setTimeout(() => { page = 1; loadOrders(); }, 300);
});

$('#order-status, #order-payment').on('change', () => { page = 1; loadOrders(); });

$('#export-orders').on('click', function() {
    const q = $.param({
        search: $('#order-search').val(),
        status: $('#order-status').val(),
        payment_status: $('#order-payment').val()
    });
    this.href = base + '/admin/orders/export?' + q;
});

lucide.createIcons();
loadOrders();
</script>
</body>
</html>
