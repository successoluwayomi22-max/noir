<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$storeName = store_setting('store_name', 'Noir & Co.');

$totalMessages = count($messages);
$pendingMessages = 0;
$resolvedMessages = 0;
foreach ($messages as $m) {
    if (strtolower($m['status'] ?? 'pending') === 'resolved') {
        $resolvedMessages++;
    } else {
        $pendingMessages++;
    }
}
$totalSubscribers = count($subscribers);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Customer Messages &amp; Audience — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= store_brand_style() ?>
    <style>
        body { font-family: 'Urbanist', ui-sans-serif, system-ui; }
        .tab-btn.active {
            background-color: var(--brand-primary, #687158);
            color: var(--brand-on-primary, #ffffff);
            box-shadow: 0 4px 12px color-mix(in srgb, var(--brand-primary, #687158) 30%, transparent);
        }
        .filter-btn.active {
            background-color: var(--brand-primary, #687158);
            color: #ffffff;
            border-color: var(--brand-primary, #687158);
        }
    </style>
</head>
<body class="admin-page bg-[#f6f6f1] text-[#252820]">
<?php $activeNav = 'messages'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <header class="admin-topbar">
        <div>
            <p class="eyebrow text-[#687158] font-bold">Communications &amp; Inbox</p>
            <h1 class="text-3xl font-extrabold tracking-tight">Customer Messages</h1>
            <p class="admin-subtitle text-sm text-[#77796f]">Review incoming contact inquiries, order questions, and subscriber audience.</p>
        </div>
        <div class="admin-user flex items-center gap-3">
            <span><?= e($user['email'] ?? '') ?></span>
            <form method="post" action="<?= e(($base ?: '') . '/logout') ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="text-xs font-semibold text-[#687158] hover:underline">Sign out</button>
            </form>
        </div>
    </header>

    <div class="p-6 lg:p-10 space-y-6 max-w-7xl mx-auto">
        <!-- Metric Cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#8e9187]">Total Inquiries</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f1eb] text-[#687158]">
                        <i data-lucide="mail" class="h-4 w-4"></i>
                    </div>
                </div>
                <strong class="mt-3 block text-3xl font-bold text-[#252820]"><?= number_format($totalMessages) ?></strong>
                <p class="mt-1 text-xs text-[#8e9187]">Direct customer requests</p>
            </div>

            <div class="rounded-2xl border border-[#f1ebd6] bg-[#fffdfa] p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#9f7823]">Pending Inquiries</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#faedd0] text-[#9f7823]">
                        <i data-lucide="clock" class="h-4 w-4"></i>
                    </div>
                </div>
                <strong class="mt-3 block text-3xl font-bold text-[#8d691c]"><?= number_format($pendingMessages) ?></strong>
                <p class="mt-1 text-xs text-[#9f7823]/80">Awaiting your response</p>
            </div>

            <div class="rounded-2xl border border-[#ddead5] bg-[#fafdfa] p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#497042]">Resolved Inquiries</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#dfeedc] text-[#497042]">
                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                    </div>
                </div>
                <strong class="mt-3 block text-3xl font-bold text-[#385c32]"><?= number_format($resolvedMessages) ?></strong>
                <p class="mt-1 text-xs text-[#497042]/80">Marked as handled</p>
            </div>

            <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#8e9187]">Newsletter Audience</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f1eb] text-[#687158]">
                        <i data-lucide="send" class="h-4 w-4"></i>
                    </div>
                </div>
                <strong class="mt-3 block text-3xl font-bold text-[#252820]"><?= number_format($totalSubscribers) ?></strong>
                <p class="mt-1 text-xs text-[#8e9187]">Subscribed email members</p>
            </div>
        </div>

        <!-- Section Navigation Switcher -->
        <div class="flex items-center gap-2 border-b border-[#e5e5dd] pb-4">
            <button type="button" onclick="switchSection('inbox', this)" class="tab-btn active flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition-all">
                <i data-lucide="inbox" class="h-4 w-4"></i>
                <span>Customer Inquiries</span>
                <span class="rounded-full bg-white/25 px-2 py-0.5 text-[10px]"><?= $totalMessages ?></span>
            </button>
            <button type="button" onclick="switchSection('subscribers', this)" class="tab-btn flex items-center gap-2 rounded-xl border border-[#deded6] bg-white px-4 py-2.5 text-xs font-bold text-[#6f7267] transition-all hover:bg-[#fbfbf8]">
                <i data-lucide="newspaper" class="h-4 w-4"></i>
                <span>Newsletter Subscribers</span>
                <span class="rounded-full bg-[#eee] px-2 py-0.5 text-[10px] text-[#555]"><?= $totalSubscribers ?></span>
            </button>
        </div>

        <!-- Tab 1: Customer Inquiries -->
        <div id="section-inbox" class="space-y-4">
            <!-- Search & Filter Controls -->
            <div class="flex flex-col gap-3 rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                <div class="relative flex-1 max-w-md">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#92948a]"></i>
                    <input 
                        type="search" 
                        id="message-search" 
                        placeholder="Search by sender, email, subject, or message content…" 
                        class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] py-2.5 pl-10 pr-4 text-xs outline-none focus:border-[#687158] focus:ring-2 focus:ring-[#687158]/10"
                        oninput="filterMessages()"
                    >
                </div>
                <div class="inline-flex rounded-xl border border-[#deded6] bg-[#fbfbf8] p-1 text-xs font-semibold">
                    <button type="button" class="filter-btn active rounded-lg px-3 py-1.5 transition-colors" data-msg-status="all" onclick="setMsgFilter('all', this)">All</button>
                    <button type="button" class="filter-btn rounded-lg px-3 py-1.5 text-[#6f7267] transition-colors" data-msg-status="pending" onclick="setMsgFilter('pending', this)">Pending (<?= $pendingMessages ?>)</button>
                    <button type="button" class="filter-btn rounded-lg px-3 py-1.5 text-[#6f7267] transition-colors" data-msg-status="resolved" onclick="setMsgFilter('resolved', this)">Resolved (<?= $resolvedMessages ?>)</button>
                </div>
            </div>

            <!-- Inquiries Table -->
            <div class="overflow-hidden rounded-2xl border border-[#e5e5dd] bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#e8e8e0] bg-[#fafaf6] text-[10px] font-bold uppercase tracking-[.15em] text-[#8e9187]">
                            <tr>
                                <th class="py-4 pl-6 pr-3">Sender</th>
                                <th class="px-3 py-4">Subject</th>
                                <th class="px-3 py-4">Message Snippet</th>
                                <th class="px-3 py-4">Date</th>
                                <th class="px-3 py-4">Status</th>
                                <th class="py-4 pl-3 pr-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="messages-table-body" class="divide-y divide-[#eeeeE8]">
                            <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="6" class="py-16 text-center text-[#8e9187]">
                                        <i data-lucide="mail-check" class="mx-auto h-8 w-8 text-[#b3b6aa]"></i>
                                        <p class="mt-2 text-sm font-semibold text-[#252820]">Inbox is clear</p>
                                        <p class="text-xs text-[#77796f]">No contact inquiries have been submitted yet.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($messages as $m): 
                                    $mJson = htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8');
                                    $st = strtolower($m['status'] ?? 'pending');
                                    $initial = strtoupper(substr(trim((string)$m['name']), 0, 1)) ?: 'U';
                                ?>
                                <tr 
                                    class="msg-row transition hover:bg-[#fafaf7]"
                                    data-msg-status="<?= $st ?>"
                                    data-msg-search="<?= e(strtolower($m['name'] . ' ' . $m['email'] . ' ' . $m['subject'] . ' ' . ($m['order_number'] ?? '') . ' ' . $m['message'])) ?>"
                                    data-msg='<?= $mJson ?>'
                                >
                                    <td class="py-4 pl-6 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#eef0e8] font-bold text-xs text-[#5a644c] border border-[#deded6]">
                                                <?= $initial ?>
                                            </div>
                                            <div class="min-w-0">
                                                <strong class="block truncate text-sm font-bold text-[#252820]"><?= e($m['name']) ?></strong>
                                                <span class="block truncate text-[11px] text-[#8e9187]"><?= e($m['email']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <span class="inline-block font-semibold text-[#252820]"><?= e($m['subject'] ?: 'General Inquiry') ?></span>
                                        <?php if (!empty($m['order_number'])): ?>
                                            <span class="block mt-0.5 font-mono text-[10px] text-[#687158]">Order: <?= e($m['order_number']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-4 max-w-xs truncate text-[#555]">
                                        <?= e(mb_strimwidth((string)$m['message'], 0, 110, '…')) ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-[#8e9187]">
                                        <span class="block font-medium text-[#252820]"><?= e(date('d M Y', strtotime($m['created_at']))) ?></span>
                                        <span class="text-[10px]"><?= e(date('H:i', strtotime($m['created_at']))) ?></span>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <?php if ($st === 'resolved'): ?>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-[#dcf1dc] px-2.5 py-0.5 text-[11px] font-bold text-[#325b2c]">
                                                <i data-lucide="check" class="h-3 w-3"></i> Resolved
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#fcf2d9] px-2.5 py-0.5 text-[11px] font-bold text-[#916b15]">
                                                <span class="h-1.5 w-1.5 rounded-full bg-[#b88c27]"></span>
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 pl-3 pr-6 text-right whitespace-nowrap">
                                        <button 
                                            type="button" 
                                            onclick='openMessageDetail(<?= $mJson ?>)' 
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-[#deded6] bg-white px-3 py-1.5 text-xs font-bold text-[#687158] shadow-sm hover:bg-[#f5f5f0] transition-colors"
                                        >
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            Read Message
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="no-msg-matches" class="hidden py-12 text-center text-[#8e9187]">
                    <p class="text-sm font-semibold">No messages match your search query.</p>
                </div>
            </div>
        </div>

        <!-- Tab 2: Newsletter Subscribers -->
        <div id="section-subscribers" class="hidden space-y-4">
            <div class="flex items-center justify-between rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm">
                <div>
                    <h3 class="text-sm font-bold text-[#252820]">Active Mailing List</h3>
                    <p class="text-xs text-[#8e9187]">Customers who opted into your marketing campaign and product releases.</p>
                </div>
                <button type="button" onclick="exportSubscribers()" class="inline-flex items-center gap-1.5 rounded-xl border border-[#deded6] bg-white px-3.5 py-2 text-xs font-bold text-[#687158] hover:bg-[#f5f5f0]">
                    <i data-lucide="download" class="h-3.5 w-3.5"></i> Export Emails
                </button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-[#e5e5dd] bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#e8e8e0] bg-[#fafaf6] text-[10px] font-bold uppercase tracking-[.15em] text-[#8e9187]">
                            <tr>
                                <th class="py-4 pl-6 pr-3">Subscriber Email</th>
                                <th class="px-3 py-4">Status</th>
                                <th class="px-3 py-4">Subscription Date</th>
                                <th class="py-4 pl-3 pr-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#eeeeE8]">
                            <?php if (empty($subscribers)): ?>
                                <tr>
                                    <td colspan="4" class="py-14 text-center text-[#8e9187]">
                                        <i data-lucide="mail-x" class="mx-auto h-8 w-8 text-[#b3b6aa]"></i>
                                        <p class="mt-2 text-sm font-semibold text-[#252820]">No newsletter subscribers yet</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subscribers as $s): ?>
                                <tr>
                                    <td class="py-4 pl-6 pr-3 font-semibold text-[#252820]">
                                        <?= e($s['email']) ?>
                                    </td>
                                    <td class="px-3 py-4">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#dcf1dc] px-2.5 py-0.5 text-[11px] font-bold text-[#325b2c]">
                                            <i data-lucide="check-circle-2" class="h-3 w-3"></i> <?= e(ucfirst($s['status'] ?? 'active')) ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 text-[#8e9187]">
                                        <?= e(date('d M Y', strtotime($s['created_at']))) ?>
                                    </td>
                                    <td class="py-4 pl-3 pr-6 text-right">
                                        <a href="mailto:<?= e($s['email']) ?>" class="inline-flex items-center gap-1 text-xs font-bold text-[#687158] hover:underline">
                                            <i data-lucide="mail" class="h-3 w-3"></i> Send Email
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Message Reader Modal -->
<div id="msg-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#252820]/60 p-4 backdrop-blur-sm overflow-y-auto">
    <div class="my-auto w-full max-w-lg rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-2xl flex flex-col max-h-[90vh]">
        <div class="flex items-start justify-between border-b border-[#e8e8e0] pb-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Inquiry Detail</p>
                <h3 id="modal-subject" class="mt-0.5 text-lg font-extrabold text-[#252820]">Message Subject</h3>
            </div>
            <button type="button" onclick="closeMsgModal()" class="rounded-lg p-1 text-[#92948a] hover:bg-[#f0f1ec]">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto py-4 space-y-4" style="overscroll-behavior: contain;">
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl border border-[#e8e8e0] bg-[#fafaf7] p-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Sender Name</span>
                    <p id="modal-name" class="font-bold mt-0.5 text-[#252820]">—</p>
                </div>
                <div class="rounded-xl border border-[#e8e8e0] bg-[#fafaf7] p-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Sender Email</span>
                    <p id="modal-email" class="font-semibold mt-0.5 truncate text-[#252820]">—</p>
                </div>
                <div id="modal-order-box" class="rounded-xl border border-[#e8e8e0] bg-[#fafaf7] p-3 col-span-2 hidden">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Associated Order Number</span>
                    <p id="modal-order" class="font-mono font-bold mt-0.5 text-[#687158]">—</p>
                </div>
            </div>

            <div>
                <span class="text-xs font-bold text-[#252820]">Full Customer Message:</span>
                <div id="modal-body" class="mt-2 rounded-xl border border-[#e8e8e0] bg-[#fbfbf8] p-4 text-xs leading-relaxed text-[#444] whitespace-pre-wrap max-h-56 overflow-y-auto">
                    Message text
                </div>
            </div>
        </div>

        <div class="border-t border-[#e8e8e0] pt-4 flex items-center justify-between">
            <button type="button" onclick="closeMsgModal()" class="rounded-xl border border-[#deded6] px-4 py-2 text-xs font-semibold text-[#55584f] hover:bg-[#f5f5f0]">Close</button>
            <a id="modal-reply-btn" href="#" class="inline-flex items-center gap-1.5 rounded-xl bg-[#687158] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#596249]">
                <i data-lucide="reply" class="h-3.5 w-3.5"></i>
                Reply via Email
            </a>
        </div>
    </div>
</div>

<script>
let currentMsgStatus = 'all';

function switchSection(sec, btn) {
    document.getElementById('section-inbox').classList.toggle('hidden', sec !== 'inbox');
    document.getElementById('section-subscribers').classList.toggle('hidden', sec !== 'subscribers');
    
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
        b.classList.add('border', 'border-[#deded6]', 'bg-white', 'text-[#6f7267]');
    });
    btn.classList.add('active');
    btn.classList.remove('border', 'border-[#deded6]', 'bg-white', 'text-[#6f7267]');
    lucide.createIcons();
}

function setMsgFilter(st, btn) {
    currentMsgStatus = st;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterMessages();
}

function filterMessages() {
    const q = (document.getElementById('message-search').value || '').trim().toLowerCase();
    const rows = document.querySelectorAll('.msg-row');
    let visible = 0;

    rows.forEach(r => {
        const rStatus = r.dataset.msgStatus;
        const rSearch = r.dataset.msgSearch || '';

        const matchStatus = (currentMsgStatus === 'all') || (rStatus === currentMsgStatus);
        const matchSearch = (!q) || (rSearch.includes(q));

        if (matchStatus && matchSearch) {
            r.style.display = '';
            visible++;
        } else {
            r.style.display = 'none';
        }
    });

    const noBox = document.getElementById('no-msg-matches');
    if (noBox) {
        noBox.classList.toggle('hidden', visible > 0 || rows.length === 0);
    }
}

function openMessageDetail(m) {
    document.getElementById('modal-subject').textContent = m.subject || 'General Inquiry';
    document.getElementById('modal-name').textContent = m.name || 'Anonymous';
    document.getElementById('modal-email').textContent = m.email || 'No email';
    document.getElementById('modal-body').textContent = m.message || '';

    const orderBox = document.getElementById('modal-order-box');
    const orderTxt = document.getElementById('modal-order');
    if (m.order_number) {
        orderBox.classList.remove('hidden');
        orderTxt.textContent = m.order_number;
    } else {
        orderBox.classList.add('hidden');
    }

    const replyBtn = document.getElementById('modal-reply-btn');
    replyBtn.href = `mailto:${encodeURIComponent(m.email)}?subject=${encodeURIComponent('Re: ' + (m.subject || 'Your Inquiry with ' + '<?= e($storeName) ?>'))}`;

    const modal = document.getElementById('msg-detail-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    lucide.createIcons();
}

function closeMsgModal() {
    const modal = document.getElementById('msg-detail-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function exportSubscribers() {
    const rows = [];
    rows.push(["Email", "Status", "Date Subscribed"]);
    <?php foreach ($subscribers as $s): ?>
    rows.push([<?= json_encode($s['email']) ?>, <?= json_encode($s['status']) ?>, <?= json_encode($s['created_at']) ?>]);
    <?php endforeach; ?>

    let csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
    let encodedUri = encodeURI(csvContent);
    let link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "newsletter_subscribers.csv");
    document.body.appendChild(link);
    link.click();
    link.remove();
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>
</body>
</html>
