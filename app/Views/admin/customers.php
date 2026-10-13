<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$customersUrl = ($base ?: '') . '/admin/customers';
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Customers Directory — <?= e($storeName) ?></title>
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <?= store_brand_style() ?>
    <style>
        body { font-family: Urbanist, ui-sans-serif, system-ui; }
        .admin-main { margin-left: 250px; min-height: 100vh; background: var(--brand-paper, #f7f7f2); }
        .admin-topbar { display: flex; justify-content: space-between; gap: 24px; padding: 34px 42px; border-bottom: 1px solid #e5e5dd; }
        .customer-table th { padding: 14px 16px; text-align: left; color: #8e9187; font-size: 10px; letter-spacing: .13em; text-transform: uppercase; white-space: nowrap; }
        .customer-table td { padding: 15px 16px; border-top: 1px solid #eef0ea; font-size: 12px; white-space: nowrap; }
        .customer-table tbody tr:hover { background: #fafaf6; }
        .modal-backdrop { background: rgba(37, 40, 32, .55); backdrop-filter: blur(4px); }
        .modal-backdrop.hidden { display: none !important; }
        .modal-backdrop.open { display: flex !important; }
        .stat-card { transition: transform .2s ease, box-shadow .2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.04); }
    </style>
</head>
<body class="text-[#252820]">
<?php $activeNav = 'customers'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <!-- Topbar Component -->
    <?php 
    $eyebrow = 'Clientele Directory';
    $title = 'Customer Accounts';
    $subtitle = 'Manage storefront accounts, verify client security, inspect orders, and oversee loyalty status.';
    include __DIR__ . '/partials/topbar.php'; 
    ?>

    <div class="p-6 lg:p-10 space-y-6 max-w-7xl mx-auto">
        
        <!-- Top Metrics Grid -->
        <section class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="stat-card rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Total Clientele</span>
                    <i data-lucide="users" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($totalCustomers ?? 0) ?></strong>
                <small class="text-[11px] text-[#8e9187]">All registered accounts</small>
            </div>

            <div class="stat-card rounded-2xl border border-emerald-200/80 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-[#325b2c]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Active Accounts</span>
                    <i data-lucide="user-check" class="h-4 w-4 text-emerald-600"></i>
                </div>
                <strong class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($activeCustomers ?? 0) ?></strong>
                <small class="text-[11px] text-emerald-700 font-semibold">In good standing</small>
            </div>

            <div class="stat-card rounded-2xl border border-amber-200/80 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-amber-800">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Suspended</span>
                    <i data-lucide="shield-alert" class="h-4 w-4 text-amber-600"></i>
                </div>
                <strong class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($suspendedCustomers ?? 0) ?></strong>
                <small class="text-[11px] text-amber-700 font-semibold">Access restricted</small>
            </div>

            <div class="stat-card rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">New (Last 30 Days)</span>
                    <i data-lucide="sparkles" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong class="mt-2 block text-2xl font-bold text-[#687158]"><?= number_format($newThisMonth ?? 0) ?></strong>
                <small class="text-[11px] text-[#8e9187]">Recent signups</small>
            </div>
        </section>

        <!-- Search, Filter & Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-1 flex-wrap items-center gap-3 max-w-2xl">
                <div class="relative flex-1 min-w-[240px]">
                    <i data-lucide="search" class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#92948a]"></i>
                    <input id="customer-search" type="search" placeholder="Search name, email, phone, username, or Customer ID..." class="w-full rounded-xl border border-[#deded6] bg-white py-3 pl-11 pr-4 text-xs outline-none focus:border-[#687158] shadow-sm">
                </div>
                <select id="customer-status" class="rounded-xl border border-[#deded6] bg-white px-4 py-3 text-xs text-[#55584f] outline-none focus:border-[#687158] shadow-sm">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="pending">Pending</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>

            <a id="export-customers" href="<?= e($customersUrl . '/export') ?>" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] px-4 py-3 text-xs font-bold text-white shadow-sm hover:bg-[#586048]">
                <i data-lucide="download" class="h-4 w-4"></i> Export CSV
            </a>
        </div>

        <!-- Customer Data Table Container -->
        <div class="overflow-hidden rounded-2xl border border-[#e5e5dd] bg-white shadow-sm">
            <div id="customer-status-message" class="hidden border-b border-[#eef0ea] px-5 py-3 text-xs text-[#77796f]"></div>
            <div class="overflow-x-auto">
                <table class="customer-table w-full">
                    <thead class="border-b border-[#eef0ea] bg-[#fcfcf9]">
                        <tr>
                            <th>Avatar</th>
                            <th>Full Name &amp; Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Customer ID</th>
                            <th>Status</th>
                            <th>Registered Date</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="customer-rows">
                        <tr>
                            <td colspan="8" class="p-10 text-center text-sm text-[#92948a]">Loading customers…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#eef0ea] bg-[#fafaf7] px-6 py-4">
                <span id="customer-summary" class="text-xs font-semibold text-[#8e9187]"></span>
                <div id="customer-pagination" class="flex gap-1.5"></div>
            </div>
        </div>
    </div>
</main>

<!-- Customer Profile Modal -->
<div id="customer-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto">
    <div class="relative w-full max-w-4xl my-auto max-h-[92vh] flex flex-col rounded-3xl bg-white shadow-2xl border border-[#e5e5dd] overflow-hidden">
        <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-[#f0f1ea] flex items-start justify-between shrink-0 bg-white">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Clientele Dossier</p>
                <h2 id="customer-title" class="mt-1 text-xl sm:text-2xl font-extrabold text-[#252820]">Customer Profile</h2>
            </div>
            <button id="close-customer" type="button" class="rounded-xl p-2 text-[#8e9187] hover:bg-[#f0f1ec] hover:text-[#252820] transition">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div id="customer-detail" class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6">
            <p class="text-sm text-[#92948a] py-8 text-center">Loading profile…</p>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-6 right-6 z-[60] hidden rounded-xl bg-[#252820] px-5 py-3 text-xs font-bold text-white shadow-xl"></div>

<script>
const base = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const csrf = <?= json_encode(csrf_token()) ?>;
let page = 1, timer, activeCustomerId = null;

function esc(v) {
    return $('<div>').text(v ?? '').html();
}

function initials(n) {
    return (n || 'U').split(' ').map(x => x[0]).slice(0, 2).join('').toUpperCase();
}

function toast(m) {
    $('#toast').text(m).stop(true, true).fadeIn(150).delay(2200).fadeOut(250);
}

function statusBadge(s) {
    s = (s || 'active').toLowerCase();
    let cls = 'bg-emerald-100 text-emerald-800';
    if (s === 'suspended') cls = 'bg-rose-100 text-rose-800';
    if (s === 'pending') cls = 'bg-amber-100 text-amber-800';
    if (s === 'deleted') cls = 'bg-gray-100 text-gray-700';
    return '<span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ' + cls + '">' + esc(s) + '</span>';
}

function loadCustomers() {
    const search = $('#customer-search').val();
    const status = $('#customer-status').val();
    $('#customer-status-message').text('Updating customer list…').removeClass('hidden');

    $.get(base + '/admin/customers/list', { search, status, page })
        .done(function(data) {
            page = data.pagination.page;
            let html = '';
            if (!data.customers.length) {
                html = '<tr><td colspan="8" class="p-12 text-center text-sm text-[#92948a]"><i data-lucide="users" class="mx-auto h-8 w-8 text-[#b3b6aa] mb-2"></i>No storefront customers matched your search.</td></tr>';
            } else {
                data.customers.forEach(c => {
                    html += '<tr class="hover:bg-[#fafaf6] transition-colors">' +
                        '<td><span class="grid h-9 w-9 place-items-center rounded-full bg-[#687158] text-xs font-bold text-white shadow-sm">' + esc(initials(c.name)) + '</span></td>' +
                        '<td><strong class="text-xs font-bold text-[#252820]">' + esc(c.name) + '</strong><small class="block text-[10px] text-[#8e9187]">@' + esc(c.username || 'client') + '</small></td>' +
                        '<td class="font-medium text-[#55584f]">' + esc(c.email) + '</td>' +
                        '<td class="text-[#77796f]">' + esc(c.phone || '—') + '</td>' +
                        '<td><code class="text-[11px] font-mono font-bold text-[#687158] bg-[#f5f6f1] px-2 py-0.5 rounded border border-[#e2e5dc]">' + esc(c.public_id) + '</code></td>' +
                        '<td>' + statusBadge(c.status) + '</td>' +
                        '<td class="text-[#8e9187]">' + esc(new Date(c.created_at.replace(' ', 'T')).toLocaleDateString()) + '</td>' +
                        '<td class="text-right"><button class="view-customer rounded-xl border border-[#deded6] bg-white px-3 py-1.5 text-[11px] font-bold text-[#687158] hover:bg-[#f5f5f0] shadow-sm" data-id="' + c.id + '">View Dossier ↗</button></td>' +
                        '</tr>';
                });
            }
            $('#customer-rows').html(html);
            $('#customer-summary').text(data.pagination.total + ' registered customer' + (data.pagination.total === 1 ? '' : 's'));

            let p = '';
            for (let i = 1; i <= data.pagination.pages; i++) {
                p += '<button class="customer-page h-8 min-w-[32px] rounded-lg px-2 text-xs font-bold transition-colors ' +
                    (i === data.pagination.page ? 'bg-[#687158] text-white shadow-sm' : 'border border-[#deded6] bg-white text-[#55584f] hover:bg-[#f5f5f0]') +
                    '" data-page="' + i + '">' + i + '</button>';
            }
            $('#customer-pagination').html(p);
            $('#customer-status-message').addClass('hidden');
            lucide.createIcons();
        })
        .fail(() => $('#customer-status-message').text('Could not load customers.').removeClass('hidden'));
}

function openCustomer(id) {
    activeCustomerId = id;
    $('#customer-modal').removeClass('hidden').addClass('open flex');
    $('#customer-detail').html('<p class="text-sm text-[#92948a] py-8 text-center">Loading customer dossier…</p>');

    $.get(base + '/admin/customers/' + id).done(function(data) {
        const c = data.customer;
        $('#customer-title').text(c.name);

        let orders = data.orders.length ?
            '<div class="overflow-x-auto rounded-2xl border border-[#eef0ea] bg-white shadow-sm"><table class="w-full text-left text-xs"><thead class="bg-[#fafaf7] text-[10px] font-bold uppercase text-[#8e9187] border-b border-[#eef0ea]"><tr><th class="py-3 px-3.5">Order #</th><th class="py-3 px-3.5">Date</th><th class="py-3 px-3.5">Fulfillment</th><th class="py-3 px-3.5">Payment</th><th class="py-3 px-3.5">Total</th><th class="py-3 px-3.5 text-right">Details</th></tr></thead><tbody class="divide-y divide-[#f2f3ee]">' +
            data.orders.map(o => {
                const orderId = o.id || o.order_number;
                const formattedDate = o.created_at ? new Date(o.created_at.replace(' ', 'T')).toLocaleDateString() : '—';
                const st = (o.status || 'pending').toLowerCase();
                let stBadge = '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800"><i data-lucide="clock" class="h-3 w-3"></i>' + esc(st) + '</span>';
                if (st === 'completed' || st === 'delivered') stBadge = '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800"><i data-lucide="check-circle" class="h-3 w-3"></i>' + esc(st) + '</span>';
                if (st === 'cancelled') stBadge = '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800"><i data-lucide="x-circle" class="h-3 w-3"></i>' + esc(st) + '</span>';

                const pay = (o.payment_status || 'pending').toLowerCase();
                let payBadge = '<span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">' + esc(pay) + '</span>';
                if (pay === 'paid') payBadge = '<span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">Paid</span>';

                return '<tr class="hover:bg-[#fafaf7] transition-colors">' +
                    '<td class="py-3 px-3.5 font-mono font-bold text-[#252820]"><a href="' + base + '/admin/orders/' + orderId + '" class="text-[#687158] hover:underline font-bold">' + esc(o.order_number) + '</a></td>' +
                    '<td class="py-3 px-3.5 text-[#8e9187] whitespace-nowrap">' + esc(formattedDate) + '</td>' +
                    '<td class="py-3 px-3.5 whitespace-nowrap">' + stBadge + '</td>' +
                    '<td class="py-3 px-3.5 whitespace-nowrap">' + payBadge + '</td>' +
                    '<td class="py-3 px-3.5 font-bold text-[#252820] whitespace-nowrap">' + esc(o.currency || 'NGN') + ' ' + Number(o.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                    '<td class="py-3 px-3.5 text-right"><a href="' + base + '/admin/orders/' + orderId + '" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#687158] hover:bg-[#eef0e7] border border-[#d8d8cf] px-2.5 py-1 rounded-lg shadow-sm transition">View ↗</a></td>' +
                    '</tr>';
            }).join('') +
            '</tbody></table></div>' :
            '<p class="text-xs text-[#8e9187] p-5 bg-[#fafaf7] rounded-2xl border border-dashed border-[#deded6] text-center">No orders recorded yet.</p>';

        let addresses = data.addresses.length ?
            data.addresses.map(a => '<div class="p-3.5 bg-[#fafaf7] rounded-2xl border border-[#f0f1ec] text-xs leading-relaxed text-[#55584f] shadow-sm"><strong class="block text-[#252820] font-bold mb-0.5">' + esc(a.label || 'Primary') + ' — ' + esc(a.recipient_name) + '</strong>' + esc(a.address_line) + ', ' + esc(a.city) + ', ' + esc(a.country) + '</div>').join('') :
            '<p class="text-xs text-[#8e9187] p-4 bg-[#fafaf7] rounded-2xl border border-dashed border-[#deded6] text-center">No delivery addresses saved.</p>';

        let wishlist = data.wishlist.length ?
            data.wishlist.map(p => '<span class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3 py-1.5 text-xs font-semibold text-[#252820] shadow-sm">' + esc(p.name) + '</span>').join('') :
            '<p class="text-xs text-[#8e9187] p-4 bg-[#fafaf7] rounded-2xl border border-dashed border-[#deded6] text-center w-full">No items in wishlist.</p>';

        let ipBox = '';
        if (c.last_ip) {
            ipBox = '<div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Security &amp; IP</p><div class="flex items-center justify-between mt-2"><code class="text-xs font-mono text-[#252820] bg-white px-2 py-0.5 rounded border border-[#deded6]">' + esc(c.last_ip) + '</code>' +
                (c.is_ip_blocked ?
                    '<span class="text-[10px] font-bold text-rose-700 bg-rose-100 px-2.5 py-0.5 rounded-full">Blocked</span> <button onclick="unblockCustIp(\'' + esc(c.last_ip) + '\')" class="text-[11px] font-bold text-[#687158] hover:underline">Unblock</button>' :
                    '<button onclick="blockCustIp(\'' + esc(c.last_ip) + '\')" class="text-[10px] font-bold text-rose-700 bg-rose-100 hover:bg-rose-200 border border-rose-200 px-2.5 py-1 rounded-lg">Block IP</button>') +
                '</div></div>';
        } else {
            ipBox = '<div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Security &amp; IP</p><strong class="mt-2 block text-xs text-[#8e9187]">No IP recorded</strong></div>';
        }

        $('#customer-detail').html(
            '<div class="grid gap-3 sm:grid-cols-4">' +
                '<div class="rounded-2xl bg-[#252820] p-4 text-white shadow-sm"><p class="text-[10px] font-bold uppercase tracking-wider text-[#b9bcb2]">Customer ID</p><strong class="mt-2 block font-mono text-sm">' + esc(c.public_id) + '</strong></div>' +
                '<div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Email Address</p><strong class="mt-2 block break-all text-xs font-bold text-[#252820]">' + esc(c.email) + '</strong></div>' +
                '<div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Account Status</p><div class="mt-2">' + statusBadge(c.status) + '</div></div>' +
                ipBox +
            '</div>' +
            '<div class="space-y-6">' +
                '<section><h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-[#687158] flex items-center gap-2"><i data-lucide="shopping-bag" class="h-4 w-4"></i>Order History</h3>' + orders + '</section>' +
                '<div class="grid gap-5 sm:grid-cols-2">' +
                    '<section><h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-[#687158] flex items-center gap-2"><i data-lucide="map-pin" class="h-4 w-4"></i>Saved Addresses</h3><div class="space-y-2">' + addresses + '</div></section>' +
                    '<div class="space-y-5">' +
                        '<section class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4 shadow-sm">' +
                            '<h3 class="text-xs font-bold uppercase tracking-wider text-[#687158] flex items-center gap-2"><i data-lucide="wallet" class="h-4 w-4"></i>Wallet &amp; Loyalty</h3>' +
                            '<div class="mt-3 flex items-center justify-between text-xs">' +
                                '<span>Balance: <strong class="text-[#252820] font-bold">₦' + Number(data.wallet.balance).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</strong></span>' +
                                '<span>Reward Points: <strong class="text-[#687158] font-bold">' + Number(data.wallet.reward_points).toLocaleString() + '</strong></span>' +
                            '</div>' +
                        '</section>' +
                        '<section class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4 shadow-sm">' +
                            '<h3 class="text-xs font-bold uppercase tracking-wider text-[#687158] flex items-center gap-2"><i data-lucide="heart" class="h-4 w-4"></i>Saved Wishlist</h3>' +
                            '<div class="mt-3 flex flex-wrap gap-2">' + wishlist + '</div>' +
                        '</section>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-[#f0f1ea]">' +
                '<button class="customer-action rounded-xl border border-[#deded6] px-4 py-2.5 text-xs font-bold text-[#252820] hover:bg-[#f5f5f0] transition" data-id="' + c.id + '" data-action="' + (c.status === 'suspended' ? 'activate' : 'suspend') + '">' +
                    (c.status === 'suspended' ? 'Activate Customer' : 'Suspend Customer') +
                '</button>' +
                '<button class="customer-action rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-rose-700 shadow-sm transition" data-id="' + c.id + '" data-action="delete">Delete Customer</button>' +
            '</div>'
        );
        lucide.createIcons();
    }).fail(() => $('#customer-detail').html('<p class="text-sm text-rose-600 py-6 text-center">Unable to load customer profile.</p>'));
}

function blockCustIp(ip) {
    if (!ip) return;
    if (ip === '127.0.0.1' || ip === '::1' || ip === 'localhost') {
        alert('Protection Alert: Cannot block localhost (' + ip + '). Doing so would lock your own computer out of the admin panel.');
        return;
    }
    if (!confirm('Blacklist and block IP ' + ip + '?')) return;
    $.post(base + '/admin/security/block-ip', { ip_address: ip, reason: 'Blocked from Customer profile', duration_hours: 168, is_ajax: 1, _csrf: csrf })
        .done(d => {
            toast(d.message || 'IP blocked successfully.');
            if (activeCustomerId) openCustomer(activeCustomerId);
        })
        .fail(x => toast((x.responseJSON || {}).message || 'Failed to block IP.'));
}

function unblockCustIp(ip) {
    if (!ip || !confirm('Unblock IP ' + ip + '?')) return;
    $.post(base + '/admin/security/unblock-ip', { ip_address: ip, is_ajax: 1, _csrf: csrf })
        .done(d => {
            toast(d.message || 'IP unblocked successfully.');
            if (activeCustomerId) openCustomer(activeCustomerId);
        })
        .fail(x => toast((x.responseJSON || {}).message || 'Failed to unblock.'));
}

$(document).on('click', '.view-customer', function() {
    openCustomer($(this).data('id'));
}).on('click', '.customer-page', function() {
    page = Number($(this).data('page'));
    loadCustomers();
}).on('click', '.customer-action', function() {
    const b = $(this), action = b.data('action');
    if (action === 'delete' && !confirm('Permanently delete this customer and their storefront records?')) return;
    b.prop('disabled', true);
    $.post(base + '/admin/customers/action', { _csrf: csrf, customer_id: b.data('id'), action })
        .done(d => {
            toast(d.message);
            $('#customer-modal').addClass('hidden').removeClass('open flex');
            loadCustomers();
        })
        .fail(x => toast((x.responseJSON || {}).message || 'Action failed.'))
        .always(() => b.prop('disabled', false));
});

$('#close-customer').on('click', () => $('#customer-modal').addClass('hidden').removeClass('open flex'));
$('#customer-modal').on('click', function(e) {
    if ($(e.target).is(this)) $(this).addClass('hidden').removeClass('open flex');
});

$('#customer-search').on('input', function() {
    clearTimeout(timer);
    timer = setTimeout(() => { page = 1; loadCustomers(); }, 300);
});

$('#customer-status').on('change', () => {
    page = 1;
    loadCustomers();
});

$('#export-customers').on('click', function() {
    const q = $.param({ search: $('#customer-search').val(), status: $('#customer-status').val() });
    this.href = base + '/admin/customers/export?' + q;
});

$(document).ready(() => {
    lucide.createIcons();
    loadCustomers();
});
</script>
</body>
</html>
