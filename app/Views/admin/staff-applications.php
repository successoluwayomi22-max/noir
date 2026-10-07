<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$url = ($base ?: '') . '/admin/staff-applications';
$storeName = store_setting('store_name', 'Noir & Co.');

// Calculate metrics
$totalCount = count($applications);
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$positions = [];

foreach ($applications as $app) {
    $st = strtolower($app['status'] ?? 'pending');
    if ($st === 'approved') {
        $approvedCount++;
    } elseif ($st === 'rejected') {
        $rejectedCount++;
    } else {
        $pendingCount++;
    }

    $pos = trim((string)($app['position'] ?? ''));
    if ($pos !== '' && !in_array($pos, $positions, true)) {
        $positions[] = $pos;
    }
}
sort($positions);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Applications — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= store_brand_style() ?>
    <style>
        body { font-family: 'Urbanist', ui-sans-serif, system-ui; }
        .filter-btn.active {
            background-color: #687158;
            color: #ffffff;
            border-color: #687158;
        }
        .filter-btn.active .count-pill {
            background-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
    </style>
</head>
<body class="admin-page bg-[#f6f6f1] text-[#252820]">
<?php $activeNav = 'staff-applications'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
        <div>
            <p class="eyebrow text-[#687158] font-bold">Talent &amp; Recruitment</p>
            <h1 class="text-3xl font-extrabold tracking-tight">Staff Applications</h1>
            <p class="admin-subtitle text-sm text-[#77796f]">Review applicant profiles, verify CV credentials, and assign operational roles.</p>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <?php $adminAvatar = (string)($user['profile_image'] ?? $user['avatar'] ?? store_setting('admin_profile_image_' . (int)($user['id'] ?? $user['user_id'] ?? 0)) ?: store_setting('admin_profile_image_0')); ?>
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

    <div class="p-6 lg:p-10 space-y-6 max-w-7xl mx-auto">

        <?php if (isset($_GET['saved'])): ?>
            <?php if ($_GET['saved'] === 'approved'): ?>
                <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-semibold text-emerald-900 shadow-sm">
                    <i data-lucide="check-circle" class="h-5 w-5 text-emerald-600 shrink-0"></i>
                    <div>
                        <strong class="block text-sm font-bold text-emerald-950">Application Approved &amp; Candidate Notified</strong>
                        <span>The candidate has been hired, their staff portal credentials are active, and an acceptance message with login instructions has been dispatched.</span>
                    </div>
                </div>
            <?php elseif ($_GET['saved'] === 'rejected'): ?>
                <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs font-semibold text-rose-900 shadow-sm">
                    <i data-lucide="check-circle" class="h-5 w-5 text-rose-600 shrink-0"></i>
                    <div>
                        <strong class="block text-sm font-bold text-rose-950">Application Rejected &amp; Candidate Notified</strong>
                        <span>The candidate has been notified with your decision and feedback notice.</span>
                    </div>
                </div>
            <?php endif; ?>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-xs font-semibold text-amber-900 shadow-sm">
                <i data-lucide="alert-circle" class="h-5 w-5 text-amber-600 shrink-0"></i>
                <div>
                    <strong class="block text-sm font-bold text-amber-950">Action Could Not Be Completed</strong>
                    <span>
                        <?php if ($_GET['error'] === 'account-exists'): ?>
                            An active user account with this email or username already exists in the system.
                        <?php elseif ($_GET['error'] === 'invalid'): ?>
                            The candidate application or role could not be resolved.
                        <?php else: ?>
                            An unexpected system error occurred while processing this candidate.
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <!-- Total -->
            <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#8e9187]">Total Candidates</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f1eb] text-[#687158]">
                        <i data-lucide="users" class="h-4 w-4"></i>
                    </div>
                </div>
                <strong class="mt-3 block text-3xl font-bold text-[#252820]"><?= number_format($totalCount) ?></strong>
                <p class="mt-1 text-xs text-[#8e9187]">All submissions received</p>
            </div>

            <!-- Pending -->
            <div class="rounded-2xl border border-[#f1ebd6] bg-[#fffdfa] p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#9f7823]">Pending Review</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#faedd0] text-[#9f7823]">
                        <i data-lucide="clock" class="h-4 w-4"></i>
                    </div>
                </div>
                <strong class="mt-3 block text-3xl font-bold text-[#8d691c]"><?= number_format($pendingCount) ?></strong>
                <p class="mt-1 text-xs text-[#9f7823]/80">Awaiting your evaluation</p>
            </div>

            <!-- Approved -->
            <div class="rounded-2xl border border-[#ddead5] bg-[#fafdfa] p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#497042]">Approved &amp; Hired</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#dfeedc] text-[#497042]">
                        <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                    </div>
                </div>
                <strong class="mt-3 block text-3xl font-bold text-[#385c32]"><?= number_format($approvedCount) ?></strong>
                <p class="mt-1 text-xs text-[#497042]/80">Assigned active roles</p>
            </div>

            <!-- Rejected -->
            <div class="rounded-2xl border border-[#f0e3e2] bg-[#fdfbfb] p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#9d534b]">Rejected</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#fbe7e6] text-[#9d534b]">
                        <i data-lucide="x-circle" class="h-4 w-4"></i>
                    </div>
                </div>
                <strong class="mt-3 block text-3xl font-bold text-[#8d443c]"><?= number_format($rejectedCount) ?></strong>
                <p class="mt-1 text-xs text-[#9d534b]/80">Archived applications</p>
            </div>
        </div>

        <!-- Filter & Search Controls Bar -->
        <div class="flex flex-col gap-4 rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
            <!-- Search Box -->
            <div class="relative flex-1 max-w-md">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#92948a]"></i>
                <input 
                    type="search" 
                    id="app-search" 
                    placeholder="Search candidate name, email, username, or position…" 
                    class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] py-2.5 pl-10 pr-4 text-xs outline-none transition focus:border-[#687158] focus:ring-2 focus:ring-[#687158]/10"
                    oninput="filterApplications()"
                >
            </div>

            <!-- Status Tabs & Position Filter -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl border border-[#deded6] bg-[#fbfbf8] p-1 text-xs font-semibold">
                    <button type="button" class="filter-btn active flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-colors" data-status="all" onclick="setStatusFilter('all', this)">
                        <span>All</span>
                        <span class="count-pill rounded-full bg-[#deded6] px-1.5 py-0.2 text-[10px] font-bold text-[#555]"><?= $totalCount ?></span>
                    </button>
                    <button type="button" class="filter-btn flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[#6f7267] transition-colors" data-status="pending" onclick="setStatusFilter('pending', this)">
                        <span>Pending</span>
                        <span class="count-pill rounded-full bg-[#ebd7ab] px-1.5 py-0.2 text-[10px] font-bold text-[#846317]"><?= $pendingCount ?></span>
                    </button>
                    <button type="button" class="filter-btn flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[#6f7267] transition-colors" data-status="approved" onclick="setStatusFilter('approved', this)">
                        <span>Approved</span>
                        <span class="count-pill rounded-full bg-[#d2e4d0] px-1.5 py-0.2 text-[10px] font-bold text-[#3f6539]"><?= $approvedCount ?></span>
                    </button>
                    <button type="button" class="filter-btn flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[#6f7267] transition-colors" data-status="rejected" onclick="setStatusFilter('rejected', this)">
                        <span>Rejected</span>
                        <span class="count-pill rounded-full bg-[#f0d0ce] px-1.5 py-0.2 text-[10px] font-bold text-[#863e36]"><?= $rejectedCount ?></span>
                    </button>
                </div>

                <!-- Position Dropdown -->
                <?php if (!empty($positions)): ?>
                    <select id="position-filter" class="rounded-xl border border-[#deded6] bg-[#fbfbf8] px-3 py-2 text-xs font-semibold text-[#555] outline-none transition focus:border-[#687158]" onchange="filterApplications()">
                        <option value="all">All Positions (<?= count($positions) ?>)</option>
                        <?php foreach ($positions as $pos): ?>
                            <option value="<?= e(strtolower($pos)) ?>"><?= e($pos) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-hidden rounded-2xl border border-[#e5e5dd] bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#252820]">
                    <thead class="border-b border-[#e8e8e0] bg-[#fafaf6] text-[10px] font-bold uppercase tracking-[.15em] text-[#8e9187]">
                        <tr>
                            <th class="py-4 pl-6 pr-3">Candidate</th>
                            <th class="px-3 py-4">Applied Role</th>
                            <th class="px-3 py-4">Contact</th>
                            <th class="px-3 py-4">Credentials &amp; Cover</th>
                            <th class="px-3 py-4">Submitted</th>
                            <th class="px-3 py-4">Status</th>
                            <th class="py-4 pl-3 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="applications-table-body" class="divide-y divide-[#eeeeE8]">
                        <?php if (empty($applications)): ?>
                            <tr id="empty-state-row">
                                <td colspan="7" class="py-16 text-center text-[#8e9187]">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f0f1eb] text-[#687158]">
                                        <i data-lucide="user-x" class="h-6 w-6"></i>
                                    </div>
                                    <h3 class="mt-3 text-base font-bold text-[#252820]">No applications yet</h3>
                                    <p class="mt-1 text-xs text-[#77796f]">New staff applications from the careers portal will show up here.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $application): ?>
                                <?php
                                    $st = strtolower($application['status'] ?? 'pending');
                                    $posLower = strtolower(trim((string)$application['position']));
                                    $appJson = htmlspecialchars(json_encode($application), ENT_QUOTES, 'UTF-8');
                                    $initial = strtoupper(substr(trim((string)$application['name']), 0, 1));
                                ?>
                                <tr 
                                    class="app-row transition hover:bg-[#fafaf7]" 
                                    id="row-<?= (int)$application['id'] ?>"
                                    data-status="<?= $st ?>"
                                    data-position="<?= e($posLower) ?>"
                                    data-search="<?= e(strtolower($application['name'] . ' ' . $application['email'] . ' ' . $application['username'] . ' ' . $application['application_number'] . ' ' . $application['position'])) ?>"
                                    data-app="<?= $appJson ?>"
                                >
                                    <!-- Candidate -->
                                    <td class="py-4 pl-6 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#eef0e8] font-bold text-sm text-[#5a644c] border border-[#deded6]">
                                                <?= $initial ?: 'A' ?>
                                            </div>
                                            <div class="min-w-0">
                                                <strong class="block truncate text-sm font-bold text-[#252820]"><?= e($application['name']) ?></strong>
                                                <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-[#8e9187]">
                                                    <span class="font-mono"><?= e($application['application_number']) ?></span>
                                                    <span>•</span>
                                                    <span>@<?= e($application['username']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Position -->
                                    <td class="px-3 py-4">
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-[#f0f1ec] px-2.5 py-1 text-xs font-bold text-[#555d49] border border-[#e2e4dc]">
                                            <i data-lucide="briefcase" class="h-3 w-3"></i>
                                            <?= e($application['position']) ?>
                                        </span>
                                    </td>

                                    <!-- Contact -->
                                    <td class="px-3 py-4">
                                        <a href="mailto:<?= e($application['email']) ?>" class="block font-medium text-[#252820] hover:text-[#687158] hover:underline">
                                            <?= e($application['email']) ?>
                                        </a>
                                        <?php if (!empty($application['phone'])): ?>
                                            <a href="tel:<?= e($application['phone']) ?>" class="block mt-0.5 text-[11px] text-[#8e9187] hover:text-[#252820]">
                                                <?= e($application['phone']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Credentials & Cover -->
                                    <td class="px-3 py-4">
                                        <div class="flex flex-col gap-1.5 items-start">
                                            <?php if (!empty($application['resume_path'])): ?>
                                                <a 
                                                    href="<?= e($url . '/' . $application['id'] . '/resume') ?>" 
                                                    target="_blank" 
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-[#deded6] bg-white px-2.5 py-1 text-[11px] font-bold text-[#687158] shadow-sm hover:bg-[#f5f5f0] transition-colors"
                                                    title="Download CV / Resume"
                                                >
                                                    <i data-lucide="file-text" class="h-3 w-3"></i>
                                                    View CV Document
                                                </a>
                                            <?php else: ?>
                                                <span class="text-[11px] text-[#92948a] italic">No CV attached</span>
                                            <?php endif; ?>

                                            <?php if (!empty($application['message'])): ?>
                                                <button 
                                                    type="button" 
                                                    onclick="openCoverLetter(<?= (int)$application['id'] ?>)" 
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#8e9187] hover:text-[#252820] hover:underline"
                                                >
                                                    <i data-lucide="message-square" class="h-3 w-3"></i>
                                                    Read Cover Note
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Submitted Date -->
                                    <td class="px-3 py-4 whitespace-nowrap text-[#77796f]">
                                        <span class="block font-medium"><?= e(date('d M Y', strtotime($application['created_at']))) ?></span>
                                        <span class="text-[10px] text-[#92948a]"><?= e(date('H:i', strtotime($application['created_at']))) ?></span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-3 py-4 whitespace-nowrap" id="status-cell-<?= (int)$application['id'] ?>">
                                        <?php if ($st === 'approved'): ?>
                                            <div class="inline-flex flex-col items-start gap-1">
                                                <span class="inline-flex items-center gap-1 rounded-full bg-[#dcf1dc] px-2.5 py-0.5 text-[11px] font-bold text-[#325b2c]">
                                                    <i data-lucide="check" class="h-3 w-3"></i> Approved
                                                </span>
                                                <?php if (!empty($application['role_name'])): ?>
                                                    <span class="text-[10px] font-semibold text-[#687158] uppercase tracking-wider">Role: <?= e($application['role_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($st === 'rejected'): ?>
                                            <div class="inline-flex flex-col items-start gap-1">
                                                <span class="inline-flex items-center gap-1 rounded-full bg-[#fae2e0] px-2.5 py-0.5 text-[11px] font-bold text-[#8e3830]">
                                                    <i data-lucide="x" class="h-3 w-3"></i> Rejected
                                                </span>
                                                <?php if (!empty($application['rejection_reason'])): ?>
                                                    <span class="text-[10px] text-[#999] truncate max-w-[140px]" title="<?= e($application['rejection_reason']) ?>"><?= e($application['rejection_reason']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#fcf2d9] px-2.5 py-0.5 text-[11px] font-bold text-[#916b15]">
                                                <span class="h-1.5 w-1.5 rounded-full bg-[#b88c27] animate-pulse"></span>
                                                Pending Review
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action Buttons -->
                                    <td class="py-4 pl-3 pr-6 text-right whitespace-nowrap" id="action-cell-<?= (int)$application['id'] ?>">
                                        <?php if ($st === 'pending'): ?>
                                            <div class="flex items-center justify-end gap-2">
                                                <button 
                                                    type="button" 
                                                    onclick="openApproveModal(<?= (int)$application['id'] ?>)" 
                                                    class="inline-flex items-center gap-1 rounded-xl bg-[#687158] px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-[#596249] transition-all"
                                                >
                                                    <i data-lucide="user-check" class="h-3.5 w-3.5"></i>
                                                    Approve
                                                </button>
                                                <button 
                                                    type="button" 
                                                    onclick="openRejectModal(<?= (int)$application['id'] ?>)" 
                                                    class="inline-flex items-center gap-1 rounded-xl border border-[#deded6] bg-white px-3 py-1.5 text-xs font-bold text-[#9d534b] hover:bg-[#fff7f7] hover:border-[#f0c2be] transition-all"
                                                >
                                                    <i data-lucide="user-x" class="h-3.5 w-3.5"></i>
                                                    Reject
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <button 
                                                type="button" 
                                                onclick="openDetailsModal(<?= (int)$application['id'] ?>)" 
                                                class="inline-flex items-center gap-1 rounded-xl border border-[#deded6] bg-white px-3 py-1.5 text-xs font-semibold text-[#666] hover:bg-[#f5f5f0]"
                                            >
                                                <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                                Details
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- No Filter Matches Row -->
            <div id="no-filter-matches" class="hidden py-14 text-center text-[#8e9187]">
                <i data-lucide="filter-x" class="mx-auto h-8 w-8 text-[#b3b6aa]"></i>
                <p class="mt-2 text-sm font-semibold text-[#252820]">No applications match the selected filters.</p>
                <button type="button" onclick="resetFilters()" class="mt-3 text-xs font-bold text-[#687158] hover:underline">Clear all filters</button>
            </div>

            <!-- Footer Counter -->
            <div class="flex items-center justify-between border-t border-[#e8e8e0] bg-[#fafaf6] px-6 py-3.5 text-xs text-[#8e9187]">
                <span id="filtered-count-label">Showing <?= $totalCount ?> total applicants</span>
                <span class="text-[11px]">Noir &amp; Co. Talent Administration</span>
            </div>
        </div>
    </div>
</main>

<!-- Modal: Approve Application & Assign Role -->
<div id="approve-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#252820]/60 p-4 backdrop-blur-sm overflow-y-auto">
    <div class="my-auto w-full max-w-md rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between border-b border-[#e8e8e0] pb-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Hiring Approval</p>
                <h2 class="mt-0.5 text-xl font-extrabold text-[#252820]">Approve Applicant</h2>
            </div>
            <button type="button" onclick="closeModal('approve-modal')" class="rounded-lg p-1 text-[#92948a] hover:bg-[#f0f1ec]">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form id="approve-form" class="mt-4 space-y-4" onsubmit="submitApproval(event)">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="application_id" id="approve-app-id" value="">

            <div class="rounded-xl border border-[#e8e8e0] bg-[#fafaf7] p-3.5">
                <p class="text-xs text-[#8e9187] uppercase font-bold tracking-wider">Candidate Summary</p>
                <h4 id="approve-cand-name" class="mt-1 text-sm font-bold text-[#252820]">Candidate Name</h4>
                <p id="approve-cand-pos" class="text-xs text-[#687158] font-semibold mt-0.5">Applied Position</p>
                <p id="approve-cand-email" class="text-[11px] text-[#77796f]">candidate@example.com</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#252820] mb-1.5">
                    Assign Operational Role <span class="text-red-500">*</span>
                </label>
                <select name="role_id" id="approve-role-select" required class="w-full rounded-xl border border-[#deded6] bg-white p-3 text-xs font-semibold outline-none focus:border-[#687158] focus:ring-2 focus:ring-[#687158]/10">
                    <option value="">-- Choose Access Level / Role --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int)$role['id'] ?>"><?= e($role['name']) ?> (<?= e($role['slug']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small class="mt-1 block text-[11px] text-[#8e9187]">This grants the applicant active staff permissions and triggers an acceptance email.</small>
            </div>

            <div class="mt-6 flex justify-end gap-2.5 border-t border-[#e8e8e0] pt-4">
                <button type="button" onclick="closeModal('approve-modal')" class="rounded-xl border border-[#deded6] px-4 py-2.5 text-xs font-semibold text-[#55584f] hover:bg-[#f5f5f0]">Cancel</button>
                <button type="submit" id="approve-submit-btn" class="rounded-xl bg-[#687158] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#596249] transition-all">Confirm Approval</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reject Application -->
<div id="reject-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#252820]/60 p-4 backdrop-blur-sm overflow-y-auto">
    <div class="my-auto w-full max-w-md rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between border-b border-[#e8e8e0] pb-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#9d534b]">Candidate Archive</p>
                <h2 class="mt-0.5 text-xl font-extrabold text-[#252820]">Reject Application</h2>
            </div>
            <button type="button" onclick="closeModal('reject-modal')" class="rounded-lg p-1 text-[#92948a] hover:bg-[#f0f1ec]">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form id="reject-form" class="mt-4 space-y-4" onsubmit="submitRejection(event)">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="application_id" id="reject-app-id" value="">

            <div class="rounded-xl border border-[#f0e3e2] bg-[#fdfbfb] p-3.5">
                <h4 id="reject-cand-name" class="text-sm font-bold text-[#252820]">Candidate Name</h4>
                <p id="reject-cand-pos" class="text-xs text-[#9d534b] font-semibold mt-0.5">Applied Position</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#252820] mb-1.5">
                    Feedback / Rejection Reason <span class="text-gray-400 font-normal">(Optional)</span>
                </label>
                <textarea 
                    name="reason" 
                    id="reject-reason" 
                    rows="3" 
                    placeholder="e.g. Position has been filled, or requirements not met at this time…"
                    class="w-full rounded-xl border border-[#deded6] bg-white p-3 text-xs outline-none focus:border-[#9d534b] focus:ring-2 focus:ring-[#9d534b]/10"
                ></textarea>
                <small class="mt-1 block text-[11px] text-[#8e9187]">Candidate will receive a formal notification email with this notice.</small>
            </div>

            <div class="mt-6 flex justify-end gap-2.5 border-t border-[#e8e8e0] pt-4">
                <button type="button" onclick="closeModal('reject-modal')" class="rounded-xl border border-[#deded6] px-4 py-2.5 text-xs font-semibold text-[#55584f] hover:bg-[#f5f5f0]">Cancel</button>
                <button type="submit" id="reject-submit-btn" class="rounded-xl bg-[#9d534b] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#88453e] transition-all">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Cover Letter & Full Details -->
<div id="details-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#252820]/60 p-4 backdrop-blur-sm overflow-y-auto">
    <div class="my-auto w-full max-w-lg rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-2xl flex flex-col max-h-[90vh]">
        <div class="flex items-start justify-between border-b border-[#e8e8e0] pb-4 shrink-0">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Candidate Profile</p>
                <h2 id="details-cand-name" class="mt-0.5 text-xl font-extrabold text-[#252820]">Application Details</h2>
            </div>
            <button type="button" onclick="closeModal('details-modal')" class="rounded-lg p-1 text-[#92948a] hover:bg-[#f0f1ec]">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto py-4 space-y-4" style="overscroll-behavior: contain;">
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl border border-[#e8e8e0] bg-[#fafaf7] p-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Application ID</span>
                    <p id="details-app-num" class="font-mono font-bold mt-0.5 text-[#252820]">—</p>
                </div>
                <div class="rounded-xl border border-[#e8e8e0] bg-[#fafaf7] p-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Target Position</span>
                    <p id="details-app-pos" class="font-bold mt-0.5 text-[#687158]">—</p>
                </div>
                <div class="rounded-xl border border-[#e8e8e0] bg-[#fafaf7] p-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Email</span>
                    <p id="details-app-email" class="font-semibold mt-0.5 truncate text-[#252820]">—</p>
                </div>
                <div class="rounded-xl border border-[#e8e8e0] bg-[#fafaf7] p-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Phone</span>
                    <p id="details-app-phone" class="font-semibold mt-0.5 text-[#252820]">—</p>
                </div>
            </div>

            <div>
                <span class="text-xs font-bold text-[#252820]">Candidate Cover Note / Statement:</span>
                <div id="details-app-msg" class="mt-2 rounded-xl border border-[#e8e8e0] bg-[#fbfbf8] p-4 text-xs leading-relaxed text-[#444] whitespace-pre-wrap max-h-48 overflow-y-auto">
                    No message supplied.
                </div>
            </div>

            <div id="details-resume-box" class="rounded-xl border border-[#deded6] bg-[#f5f6f1] p-3.5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="file-check" class="h-5 w-5 text-[#687158]"></i>
                    <div>
                        <strong class="block text-xs text-[#252820]">CV / Resume Document</strong>
                        <span class="text-[10px] text-[#77796f]">Verified format attached</span>
                    </div>
                </div>
                <a id="details-resume-link" href="#" target="_blank" class="rounded-lg bg-[#687158] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#586048]">Download CV</a>
            </div>
        </div>

        <div class="border-t border-[#e8e8e0] pt-3 shrink-0 flex justify-end">
            <button type="button" onclick="closeModal('details-modal')" class="rounded-xl bg-[#252820] px-5 py-2.5 text-xs font-bold text-white hover:bg-black">Close</button>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="staff-toast" class="fixed bottom-6 right-6 z-[60] hidden items-center gap-2 rounded-xl bg-[#252820] px-5 py-3.5 text-xs font-semibold text-white shadow-2xl transition-all duration-200">
    <i data-lucide="check-circle" class="h-4 w-4 text-emerald-400"></i>
    <span id="toast-message">Application updated</span>
</div>

<script>
let currentStatus = 'all';
const approveUrl = '<?= e($url . '/approve') ?>';
const rejectUrl = '<?= e($url . '/reject') ?>';
const baseUrl = '<?= e($url) ?>';

function setStatusFilter(st, btn) {
    currentStatus = st;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    filterApplications();
}

function filterApplications() {
    const q = (document.getElementById('app-search').value || '').trim().toLowerCase();
    const posFilter = (document.getElementById('position-filter') ? document.getElementById('position-filter').value : 'all').toLowerCase();
    
    const rows = document.querySelectorAll('.app-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowStatus = row.dataset.status;
        const rowPos = row.dataset.position;
        const rowSearch = row.dataset.search || '';

        const matchStatus = (currentStatus === 'all') || (rowStatus === currentStatus);
        const matchPos = (posFilter === 'all') || (rowPos === posFilter);
        const matchSearch = (!q) || (rowSearch.includes(q));

        if (matchStatus && matchPos && matchSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noMatchesBox = document.getElementById('no-filter-matches');
    if (noMatchesBox) {
        if (visibleCount === 0 && rows.length > 0) {
            noMatchesBox.classList.remove('hidden');
        } else {
            noMatchesBox.classList.add('hidden');
        }
    }

    const countLabel = document.getElementById('filtered-count-label');
    if (countLabel) {
        countLabel.textContent = `Showing ${visibleCount} of ${rows.length} applicants`;
    }
}

function resetFilters() {
    document.getElementById('app-search').value = '';
    if (document.getElementById('position-filter')) {
        document.getElementById('position-filter').value = 'all';
    }
    const allBtn = document.querySelector('.filter-btn[data-status="all"]');
    setStatusFilter('all', allBtn);
}

function openModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.remove('hidden');
        m.classList.add('flex');
        lucide.createIcons();
    }
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
}

function openApproveModal(id) {
    const row = document.getElementById(`row-${id}`);
    if (!row) return;
    const app = JSON.parse(row.dataset.app);

    document.getElementById('approve-app-id').value = id;
    document.getElementById('approve-cand-name').textContent = app.name || 'Candidate';
    document.getElementById('approve-cand-pos').textContent = app.position || 'Applicant';
    document.getElementById('approve-cand-email').textContent = app.email || '';
    document.getElementById('approve-role-select').value = '';

    openModal('approve-modal');
}

function openRejectModal(id) {
    const row = document.getElementById(`row-${id}`);
    if (!row) return;
    const app = JSON.parse(row.dataset.app);

    document.getElementById('reject-app-id').value = id;
    document.getElementById('reject-cand-name').textContent = app.name || 'Candidate';
    document.getElementById('reject-cand-pos').textContent = app.position || 'Applicant';
    document.getElementById('reject-reason').value = '';

    openModal('reject-modal');
}

function openCoverLetter(id) {
    openDetailsModal(id);
}

function openDetailsModal(id) {
    const row = document.getElementById(`row-${id}`);
    if (!row) return;
    const app = JSON.parse(row.dataset.app);

    document.getElementById('details-cand-name').textContent = app.name || 'Candidate Profile';
    document.getElementById('details-app-num').textContent = app.application_number || 'N/A';
    document.getElementById('details-app-pos').textContent = app.position || 'Applicant';
    document.getElementById('details-app-email').textContent = app.email || '—';
    document.getElementById('details-app-phone').textContent = app.phone || '—';
    document.getElementById('details-app-msg').textContent = app.message || 'No additional cover note provided.';

    const resumeBox = document.getElementById('details-resume-box');
    const resumeLink = document.getElementById('details-resume-link');
    if (app.resume_path) {
        resumeBox.classList.remove('hidden');
        resumeLink.href = `${baseUrl}/${id}/resume`;
    } else {
        resumeBox.classList.add('hidden');
    }

    openModal('details-modal');
}

async function submitApproval(e) {
    e.preventDefault();
    const form = document.getElementById('approve-form');
    const btn = document.getElementById('approve-submit-btn');
    const id = document.getElementById('approve-app-id').value;
    const roleSelect = document.getElementById('approve-role-select');
    const roleName = roleSelect.options[roleSelect.selectedIndex].text;

    btn.disabled = true;
    btn.textContent = 'Approving…';

    try {
        const formData = new FormData(form);
        const res = await fetch(approveUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Approval failed');
        }

        closeModal('approve-modal');
        showToast(data.message || 'Application approved successfully!');

        // Update row status and actions
        const row = document.getElementById(`row-${id}`);
        if (row) {
            row.dataset.status = 'approved';
            
            const statusCell = document.getElementById(`status-cell-${id}`);
            if (statusCell) {
                statusCell.innerHTML = `
                    <div class="inline-flex flex-col items-start gap-1">
                        <span class="inline-flex items-center gap-1 rounded-full bg-[#dcf1dc] px-2.5 py-0.5 text-[11px] font-bold text-[#325b2c]">
                            <i data-lucide="check" class="h-3 w-3"></i> Approved
                        </span>
                        <span class="text-[10px] font-semibold text-[#687158] uppercase tracking-wider">Role: ${data.role || roleName}</span>
                    </div>
                `;
            }

            const actionCell = document.getElementById(`action-cell-${id}`);
            if (actionCell) {
                actionCell.innerHTML = `
                    <button type="button" onclick="openDetailsModal(${id})" class="inline-flex items-center gap-1 rounded-xl border border-[#deded6] bg-white px-3 py-1.5 text-xs font-semibold text-[#666] hover:bg-[#f5f5f0]">
                        <i data-lucide="eye" class="h-3.5 w-3.5"></i> Details
                    </button>
                `;
            }
            lucide.createIcons();
            filterApplications();
        }
    } catch (err) {
        showToast(err.message || 'Error approving application', true);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Confirm Approval';
    }
}

async function submitRejection(e) {
    e.preventDefault();
    const form = document.getElementById('reject-form');
    const btn = document.getElementById('reject-submit-btn');
    const id = document.getElementById('reject-app-id').value;
    const reason = document.getElementById('reject-reason').value.trim();

    btn.disabled = true;
    btn.textContent = 'Rejecting…';

    try {
        const formData = new FormData(form);
        const res = await fetch(rejectUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Rejection failed');
        }

        closeModal('reject-modal');
        showToast(data.message || 'Application rejected successfully.');

        // Update row status and actions
        const row = document.getElementById(`row-${id}`);
        if (row) {
            row.dataset.status = 'rejected';
            
            const statusCell = document.getElementById(`status-cell-${id}`);
            if (statusCell) {
                statusCell.innerHTML = `
                    <div class="inline-flex flex-col items-start gap-1">
                        <span class="inline-flex items-center gap-1 rounded-full bg-[#fae2e0] px-2.5 py-0.5 text-[11px] font-bold text-[#8e3830]">
                            <i data-lucide="x" class="h-3 w-3"></i> Rejected
                        </span>
                        ${reason ? `<span class="text-[10px] text-[#999] truncate max-w-[140px]" title="${reason}">${reason}</span>` : ''}
                    </div>
                `;
            }

            const actionCell = document.getElementById(`action-cell-${id}`);
            if (actionCell) {
                actionCell.innerHTML = `
                    <button type="button" onclick="openDetailsModal(${id})" class="inline-flex items-center gap-1 rounded-xl border border-[#deded6] bg-white px-3 py-1.5 text-xs font-semibold text-[#666] hover:bg-[#f5f5f0]">
                        <i data-lucide="eye" class="h-3.5 w-3.5"></i> Details
                    </button>
                `;
            }
            lucide.createIcons();
            filterApplications();
        }
    } catch (err) {
        showToast(err.message || 'Error rejecting application', true);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Confirm Rejection';
    }
}

function showToast(msg, isErr = false) {
    const t = document.getElementById('staff-toast');
    const txt = document.getElementById('toast-message');
    txt.textContent = msg;
    t.style.background = isErr ? '#9d534b' : '#252820';
    t.classList.remove('hidden');
    t.classList.add('flex');
    setTimeout(() => {
        t.classList.add('hidden');
        t.classList.remove('flex');
    }, 3200);
}

// Initialize Lucide icons on page load
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>
</body>
</html>
