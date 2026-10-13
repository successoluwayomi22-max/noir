<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$rolesUrl = ($base ?: '') . '/admin/roles';
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Staff &amp; Roles Directory — <?= e($storeName) ?></title>
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
        .table-wrap { overflow-x: auto; }
        .role-table th { padding: 14px 16px; text-align: left; color: #8e9187; font-size: 10px; letter-spacing: .13em; text-transform: uppercase; white-space: nowrap; }
        .role-table td { padding: 15px 16px; border-top: 1px solid #eef0ea; font-size: 12px; white-space: nowrap; }
        .modal-backdrop { background: rgba(37, 40, 32, .55); backdrop-filter: blur(4px); }
        .modal-backdrop.hidden { display: none !important; }
        .modal-backdrop.open { display: flex !important; }
        .action-trigger { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid #deded6; border-radius: 10px; background: #fff; color: #687158; cursor: pointer; }
        .action-trigger:hover { background: #f5f5f0; }
        .action-dropdown { position: absolute; right: 0; top: calc(100% + 6px); z-index: 30; display: none; width: 190px; border: 1px solid #e2e2da; border-radius: 12px; background: #fff; padding: 6px; box-shadow: 0 12px 30px rgba(0,0,0,0.12); }
        .action-menu.open .action-dropdown { display: block; }
        .action-item { display: flex; width: 100%; align-items: center; gap: 9px; border: 0; border-radius: 8px; background: transparent; padding: 8px 10px; color: #4d5148; font: inherit; font-size: 11px; text-align: left; cursor: pointer; font-weight: 600; }
        .action-item:hover { background: #f0f1ea; }
        .action-item.danger { color: #a75b52; }
        .action-item.disabled { color: #999b93; cursor: default; }
        .role-badge { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; background: #e7eadf; padding: 4px 10px; color: #5d6a51; font-size: 10px; font-weight: 700; }
        .role-badge.protected { background: #eee8d5; color: #877546; }
        .status-badge { display: inline-block; border-radius: 999px; background: #e2eadb; padding: 3px 9px; color: #59704e; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
        .status-badge.suspended { background: #fae2e0; color: #8e3830; }
        .status-badge.inactive { background: #e8e8e4; color: #73766d; }
        .stat-card { transition: transform .2s ease, box-shadow .2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.04); }
    </style>
</head>
<body class="text-[#252820]">
<?php $activeNav = 'roles'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <!-- Topbar Component -->
    <?php 
    $eyebrow = 'Access & Governance';
    $title = 'Staff & Role Management';
    $subtitle = 'Control team permissions, assign operational duties, and manage internal staff accounts.';
    include __DIR__ . '/partials/topbar.php'; 
    ?>

    <div class="p-6 lg:p-10 space-y-6 max-w-7xl mx-auto">
        
        <!-- Metrics Grid -->
        <section class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="stat-card rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Total Staff</span>
                    <i data-lucide="users" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong id="kpi-total-staff" class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($totalStaff ?? 0) ?></strong>
                <small class="text-[11px] text-[#8e9187]">Internal team accounts</small>
            </div>

            <div class="stat-card rounded-2xl border border-emerald-200/80 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-[#325b2c]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Active Operators</span>
                    <i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i>
                </div>
                <strong id="kpi-active-staff" class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($activeStaff ?? 0) ?></strong>
                <small class="text-[11px] text-emerald-700 font-semibold">Active credentials</small>
            </div>

            <div class="stat-card rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Configured Roles</span>
                    <i data-lucide="key" class="h-4 w-4 text-[#687158]"></i>
                </div>
                <strong id="kpi-role-count" class="mt-2 block text-2xl font-bold text-[#687158]"><?= number_format($roleCount ?? 0) ?></strong>
                <small class="text-[11px] text-[#8e9187]">Permission tiers</small>
            </div>

            <div class="stat-card rounded-2xl border border-amber-200/80 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-[#8e9187]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Suspended / Inactive</span>
                    <i data-lucide="user-x" class="h-4 w-4 text-amber-700"></i>
                </div>
                <strong id="kpi-suspended-staff" class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($suspendedStaff ?? 0) ?></strong>
                <small class="text-[11px] text-amber-700 font-semibold">Temporarily locked</small>
            </div>
        </section>

        <!-- Search & Staff Directory Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold text-[#252820]">Internal Staff Directory</h2>
                <p class="text-xs text-[#8e9187]">Protected super admins and assigned departmental staff.</p>
            </div>
            <div class="relative w-full max-w-md">
                <i data-lucide="search" class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#92948a]"></i>
                <input id="user-search" type="search" placeholder="Search email, username, name, or Staff ID..." class="w-full rounded-xl border border-[#deded6] bg-white py-3 pl-11 pr-4 text-xs outline-none focus:border-[#687158] shadow-sm">
            </div>
        </div>

        <!-- Staff Table Container -->
        <div class="overflow-visible rounded-2xl border border-[#e5e5dd] bg-white shadow-sm">
            <div id="table-status" class="hidden border-b border-[#eef0ea] px-5 py-3 text-xs text-[#77796f]"></div>
            <div class="table-wrap">
                <table class="role-table w-full">
                    <thead class="border-b border-[#eef0ea] bg-[#fcfcf9]">
                        <tr>
                            <th>Avatar</th>
                            <th>Full Name &amp; Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Current Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-rows">
                        <tr>
                            <td colspan="8" class="p-10 text-center text-sm text-[#92948a]">Loading staff directory…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#eef0ea] bg-[#fafaf7] px-6 py-4">
                <span id="pagination-summary" class="text-xs font-semibold text-[#8e9187]"></span>
                <div id="pagination" class="flex gap-1.5"></div>
            </div>
        </div>
    </div>
</main>

<!-- Assign Role Modal -->
<div id="assign-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto">
    <div class="relative w-full max-w-lg my-auto max-h-[92vh] flex flex-col rounded-3xl bg-white shadow-2xl border border-[#e5e5dd] overflow-hidden">
        <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-[#f0f1ea] flex items-start justify-between shrink-0 bg-white">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.18em] text-[#687158]">Governance &amp; Access</p>
                <h2 class="mt-1 text-xl sm:text-2xl font-extrabold text-[#252820]">Assign Operational Role</h2>
            </div>
            <button type="button" class="modal-close rounded-xl p-2 text-[#8e9187] hover:bg-[#f0f1ec] hover:text-[#252820] transition">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="assign-form" class="flex flex-col flex-1 overflow-hidden m-0">
            <div class="p-6 sm:p-8 space-y-5 overflow-y-auto flex-1">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="user_id" id="assign-user-id">
                <div id="assign-user" class="flex items-center gap-4 rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4 shadow-sm"></div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-2">Select Role &amp; Operational Dashboard
                        <select id="assign-role" name="role_id" required class="mt-1.5 w-full rounded-xl border border-[#deded6] bg-white p-3 text-xs font-semibold outline-none focus:border-[#687158] transition">
                            <?php foreach ($roles as $role): 
                                $dash = match($role['slug']) {
                                    'vendor' => '/vendor/dashboard (Vendor Workspace)',
                                    'delivery-manager' => '/delivery/dashboard (Logistics & Fleet)',
                                    'finance-manager' => '/finance/dashboard (Finance & Ledger)',
                                    'super-admin', 'administrator' => '/admin/dashboard (Complete System Access)',
                                    default => '/admin/dashboard (' . $role['name'] . ')'
                                };
                            ?>
                                <option value="<?= (int)$role['id'] ?>"><?= e($role['name']) ?> &mdash; <?= e($dash) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="mt-2 flex items-center gap-2 text-[11px] text-[#687158]">
                        <i data-lucide="layout-dashboard" class="h-3.5 w-3.5 shrink-0"></i>
                        <span>Staff credentials will route directly to the designated dashboard on next sign-in.</span>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 sm:px-8 sm:py-4 border-t border-[#f0f1ea] flex justify-end gap-3 shrink-0 bg-[#fafaf7]">
                <button type="button" class="modal-close rounded-xl border border-[#deded6] px-4 py-2.5 text-xs font-bold text-[#252820] hover:bg-[#f5f5f0] transition">Cancel</button>
                <button type="submit" class="rounded-xl bg-[#687158] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#586048] transition">Save Role Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Profile Modal -->
<div id="profile-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto">
    <div class="relative w-full max-w-lg my-auto max-h-[92vh] flex flex-col rounded-3xl bg-white shadow-2xl border border-[#e5e5dd] overflow-hidden">
        <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-[#f0f1ea] flex items-start justify-between shrink-0 bg-white">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.18em] text-[#687158]">Staff Record</p>
                <h2 id="profile-title" class="mt-1 text-xl sm:text-2xl font-extrabold text-[#252820]">Staff Profile</h2>
            </div>
            <button type="button" class="profile-close rounded-xl p-2 text-[#8e9187] hover:bg-[#f0f1ec] hover:text-[#252820] transition">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div id="profile-content" class="p-6 sm:p-8 overflow-y-auto flex-1">
            <p class="text-sm text-[#92948a] py-6 text-center">Loading profile…</p>
        </div>
    </div>
</div>

<!-- Permission Modal -->
<div id="permission-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto">
    <div class="relative w-full max-w-lg my-auto max-h-[92vh] flex flex-col rounded-3xl bg-white shadow-2xl border border-[#e5e5dd] overflow-hidden">
        <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-[#f0f1ea] flex items-start justify-between shrink-0 bg-white">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.18em] text-[#687158]">RBAC Policy</p>
                <h2 id="permission-title" class="mt-1 text-xl sm:text-2xl font-extrabold text-[#252820]">Role Permissions</h2>
            </div>
            <button type="button" class="permission-close rounded-xl p-2 text-[#8e9187] hover:bg-[#f0f1ec] hover:text-[#252820] transition">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div id="permission-list" class="p-6 sm:p-8 max-h-[60vh] overflow-y-auto space-y-2 flex-1"></div>
    </div>
</div>

<!-- Direct Staff Message Modal -->
<div id="message-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto">
    <div class="relative w-full max-w-lg my-auto max-h-[92vh] flex flex-col rounded-3xl bg-white shadow-2xl border border-[#e5e5dd] overflow-hidden">
        <div class="px-6 py-4 sm:px-8 sm:py-5 border-b border-[#f0f1ea] flex items-center justify-between shrink-0 bg-white">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-[#687158] text-white shadow-sm">
                    <i data-lucide="message-square" class="h-5 w-5"></i>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.18em] text-[#687158]">Direct Communication</p>
                    <h2 class="text-lg sm:text-xl font-extrabold text-[#252820]">Message Staff Member</h2>
                </div>
            </div>
            <button type="button" class="message-close rounded-xl p-2 text-[#8e9187] hover:bg-[#f0f1ec] hover:text-[#252820] transition">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form id="staff-message-form" class="flex flex-col flex-1 overflow-hidden m-0">
            <div class="p-5 sm:p-7 space-y-3.5 overflow-y-auto flex-1">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="staff_id" id="message-staff-id">

                <!-- Recipient Preview Chip -->
                <div id="message-recipient-chip" class="flex items-center gap-3.5 rounded-2xl border border-[#e8eae0] bg-[#fafaf7] p-3 shadow-sm">
                    <!-- Injected via JavaScript -->
                </div>

                <!-- Subject Input -->
                <div>
                    <label for="message-subject" class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-1">Communication Subject</label>
                    <input type="text" name="subject" id="message-subject" required placeholder="e.g. Schedule Update / Operational Directive / Concierge Review" class="w-full rounded-xl border border-[#deded6] bg-white px-3.5 py-2.5 text-xs text-[#252820] outline-none focus:border-[#687158] transition">
                </div>

                <!-- Priority Selector -->
                <div>
                    <label for="message-type" class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-1">Priority &amp; Category</label>
                    <select name="type" id="message-type" class="w-full rounded-xl border border-[#deded6] bg-white px-3.5 py-2.5 text-xs text-[#252820] outline-none focus:border-[#687158] transition">
                        <option value="info">General Directive (Info)</option>
                        <option value="warning">Important Notice / Urgent Review</option>
                        <option value="success">Commendation / Milestone</option>
                    </select>
                </div>

                <!-- Message Textarea -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="message-body" class="text-xs font-bold uppercase tracking-wider text-[#77796f]">Message Body</label>
                        <span id="message-char-count" class="text-[10px] text-[#8e9187]">0 / 2,000</span>
                    </div>
                    <textarea name="message" id="message-body" rows="3" required maxlength="2000" placeholder="Type your directive or inquiry for this staff member..." class="w-full rounded-xl border border-[#deded6] bg-white p-3 text-xs leading-relaxed text-[#252820] outline-none focus:border-[#687158] transition"></textarea>
                </div>

                <!-- Delivery Channels Info -->
                <div class="rounded-xl border border-[#e8eae0] bg-[#fafaf7] p-2.5 text-[11px] text-[#687158] flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="h-4 w-4 shrink-0 text-[#687158]"></i>
                    <span>Dispatches instantly via <strong>In-App Concierge Notification</strong> and <strong>Official Email</strong>.</span>
                </div>
            </div>

            <div class="px-6 py-3.5 sm:px-8 sm:py-4 border-t border-[#f0f1ea] flex justify-end gap-3 shrink-0 bg-[#fafaf7]">
                <button type="button" class="message-close rounded-xl border border-[#deded6] px-4 py-2.5 text-xs font-bold text-[#252820] hover:bg-[#f5f5f0] transition">Cancel</button>
                <button type="submit" id="send-message-btn" class="rounded-xl bg-[#687158] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#586048] flex items-center gap-2 transition">
                    <i data-lucide="send" class="h-4 w-4"></i>
                    <span id="send-btn-text">Send Message</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Staff Action Confirmation Modal -->
<div id="confirm-action-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto">
    <div class="relative w-full max-w-md my-auto max-h-[92vh] flex flex-col rounded-3xl bg-white shadow-2xl border border-[#e5e5dd] overflow-hidden">
        <div class="p-6 sm:p-7 overflow-y-auto flex-1">
            <div class="mb-4 flex items-center gap-3.5">
                <div id="confirm-icon-wrap" class="grid h-11 w-11 place-items-center rounded-2xl bg-amber-100 text-amber-800">
                    <i id="confirm-icon" data-lucide="alert-triangle" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 id="confirm-title" class="text-base font-bold text-[#252820]">Confirm Action</h3>
                    <p id="confirm-sub" class="text-xs text-[#8e9187]">Staff credential access modification.</p>
                </div>
            </div>

            <p id="confirm-desc" class="text-xs text-[#55584f] leading-relaxed mb-4">Are you sure you want to proceed?</p>

            <div id="confirm-reason-wrap" class="mb-2">
                <label for="confirm-reason" class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-1.5">Reason for Audit Log (Optional)</label>
                <input type="text" id="confirm-reason" placeholder="e.g. Scheduled administrative review" class="w-full rounded-xl border border-[#deded6] bg-white px-3.5 py-2.5 text-xs text-[#252820] outline-none focus:border-[#687158]">
            </div>
        </div>

        <div class="px-6 py-4 border-t border-[#f0f1ea] flex justify-end gap-2.5 shrink-0 bg-[#fafaf7]">
            <button type="button" class="confirm-cancel rounded-xl border border-[#deded6] px-4 py-2.5 text-xs font-bold text-[#252820] hover:bg-[#f5f5f0] transition">Cancel</button>
            <button type="button" id="confirm-submit-btn" class="rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-sm flex items-center gap-2 transition">
                <span id="confirm-btn-text">Confirm</span>
            </button>
        </div>
    </div>
</div>

<!-- Refined Floating Toast -->
<div id="toast" class="fixed bottom-6 right-6 z-[60] hidden items-center gap-3 rounded-2xl border border-white/10 bg-[#1c1e17] px-5 py-3.5 text-xs font-semibold text-white shadow-2xl transition-all duration-300">
    <i id="toast-icon" data-lucide="check-circle-2" class="h-4 w-4 text-emerald-400"></i>
    <span id="toast-message">Notification message</span>
</div>

<script>
const base = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const csrf = <?= json_encode(csrf_token()) ?>;
let page = 1, searchTimer;
let pendingStaffAction = null;

function esc(v) { return $('<div>').text(v ?? '').html(); }
function initials(n) { return (n || 'U').split(' ').map(x => x[0]).slice(0, 2).join('').toUpperCase(); }

function toast(m, isError = false) {
    $('#toast-message').text(m);
    if (isError) {
        $('#toast-icon').attr('data-lucide', 'alert-circle').removeClass('text-emerald-400').addClass('text-rose-400');
    } else {
        $('#toast-icon').attr('data-lucide', 'check-circle-2').removeClass('text-rose-400').addClass('text-emerald-400');
    }
    lucide.createIcons();
    $('#toast').stop(true, true).removeClass('hidden').css('display', 'flex').fadeIn(150).delay(2800).fadeOut(250);
}

function closeMenus() { $('.action-menu').removeClass('open'); }

function roleBadge(u) {
    const protectedUser = Number(u.is_super_admin) === 1 || u.role_slug === 'super-admin';
    const label = protectedUser ? 'Super Admin' : (u.role_name || 'Unassigned');
    return '<span class="role-badge ' + (protectedUser ? 'protected' : '') + '">' +
        (protectedUser ? '<i data-lucide="shield-alert" class="h-3 w-3"></i>' : '') +
        esc(label) +
        (protectedUser ? '<span class="ml-1 text-[9px] uppercase tracking-wider">Protected</span>' : '') +
        '</span>';
}

function statusBadge(status) {
    status = (status || 'active').toLowerCase();
    const label = status === 'inactive' ? 'Deactivated' : status;
    return '<span class="status-badge ' + esc(status) + '">' + esc(label) + '</span>';
}

window.staffDirectory = {};

function actionMenu(u) {
    const protectedUser = Number(u.is_super_admin) === 1 || u.role_slug === 'super-admin';
    let items = '<button type="button" class="action-item menu-action" data-action="view" data-id="' + u.id + '"><i data-lucide="user-round" class="h-4 w-4"></i>View Profile</button>';
    if (protectedUser) {
        items += '<span class="action-item disabled"><i data-lucide="shield-check" class="h-4 w-4"></i>Protected Account</span>';
    } else {
        const isLocked = u.status === 'suspended' || u.status === 'inactive';
        items += '<button type="button" class="action-item menu-action" data-action="message" data-id="' + u.id + '"><i data-lucide="message-square" class="h-4 w-4"></i>Message Staff</button>' +
            '<button type="button" class="action-item menu-action" data-action="assign" data-id="' + u.id + '"><i data-lucide="user-cog" class="h-4 w-4"></i>Assign Role</button>';

        if (isLocked) {
            items += '<button type="button" class="action-item menu-action text-emerald-700 font-bold" data-action="staff" data-id="' + u.id + '" data-staff-action="activate"><i data-lucide="user-check" class="h-4 w-4 text-emerald-600"></i>Activate Staff</button>';
        } else {
            items += '<button type="button" class="action-item danger menu-action" data-action="staff" data-id="' + u.id + '" data-staff-action="suspend"><i data-lucide="user-round-x" class="h-4 w-4"></i>Suspend Staff</button>' +
                '<button type="button" class="action-item danger menu-action" data-action="staff" data-id="' + u.id + '" data-staff-action="deactivate"><i data-lucide="user-minus" class="h-4 w-4"></i>Deactivate Staff</button>';
        }
    }
    return '<div class="action-menu"><button class="action-trigger" aria-label="Staff actions" type="button"><i data-lucide="more-vertical" class="h-4 w-4"></i></button><div class="action-dropdown">' + items + '</div></div>';
}

function loadStaff() {
    const status = $('#table-status');
    status.text('Updating staff roster…').removeClass('hidden');
    $.get(base + '/admin/roles/users', { search: $('#user-search').val(), page }).done(function(data) {
        page = data.pagination.page;
        window.staffDirectory = {};
        let rows = '';
        if (!data.users.length) {
            rows = '<tr><td colspan="8" class="p-12 text-center text-sm text-[#92948a]"><i data-lucide="user-x" class="mx-auto h-8 w-8 text-[#b3b6aa] mb-2"></i>No staff members matched your search.</td></tr>';
        } else {
            data.users.forEach(function(u) {
                window.staffDirectory[u.id] = u;
                rows += '<tr class="hover:bg-[#fafaf6] transition-colors">' +
                    '<td><span class="grid h-9 w-9 place-items-center rounded-full bg-[#687158] text-xs font-bold text-white shadow-sm">' + esc(initials(u.name)) + '</span></td>' +
                    '<td><strong class="text-xs font-bold text-[#252820]">' + esc(u.name) + '</strong><small class="block text-[10px] text-[#8e9187]">@' + esc(u.username) + '</small></td>' +
                    '<td class="font-medium text-[#55584f]">' + esc(u.email) + '</td>' +
                    '<td class="text-[#77796f]">' + esc(u.phone || '—') + '</td>' +
                    '<td><button class="role-link text-left" data-slug="' + esc(u.role_slug || '') + '">' + roleBadge(u) + '</button></td>' +
                    '<td>' + statusBadge(u.status) + '</td>' +
                    '<td class="text-[#8e9187]">' + esc(new Date(u.created_at.replace(' ', 'T')).toLocaleDateString()) + '</td>' +
                    '<td class="text-right">' + actionMenu(u) + '</td>' +
                    '</tr>';
            });
        }
        $('#user-rows').html(rows);
        $('#pagination-summary').text(data.pagination.total + ' staff member' + (data.pagination.total === 1 ? '' : 's'));

        // Real-time dynamic KPI metrics sync
        if (data.stats) {
            if (data.stats.total !== undefined) $('#kpi-total-staff').text(Number(data.stats.total).toLocaleString());
            if (data.stats.active !== undefined) $('#kpi-active-staff').text(Number(data.stats.active).toLocaleString());
            if (data.stats.suspended !== undefined) $('#kpi-suspended-staff').text(Number(data.stats.suspended).toLocaleString());
            if (data.stats.roles !== undefined) $('#kpi-role-count').text(Number(data.stats.roles).toLocaleString());
        }

        let p = '';
        for (let i = 1; i <= data.pagination.pages; i++) {
            p += '<button class="page-button h-8 min-w-[32px] rounded-lg px-2 text-xs font-bold transition-colors ' +
                (i === data.pagination.page ? 'bg-[#687158] text-white shadow-sm' : 'border border-[#deded6] bg-white text-[#55584f] hover:bg-[#f5f5f0]') +
                '" data-page="' + i + '">' + i + '</button>';
        }
        $('#pagination').html(p);
        lucide.createIcons();
        status.addClass('hidden');
    }).fail(() => status.text('Could not load staff. Please try again.').removeClass('hidden'));
}

function openAssign(u) {
    if (!u) return;
    closeMenus();
    $('#assign-user-id').val(u.id);
    $('#assign-role').val(u.role_id || '');
    $('#assign-user').html(
        '<span class="grid h-12 w-12 place-items-center rounded-full bg-[#687158] text-sm font-bold text-white shadow-sm">' + esc(initials(u.name)) + '</span>' +
        '<div><strong class="block text-sm font-bold text-[#252820]">' + esc(u.name) + '</strong>' +
        '<small class="text-xs text-[#77796f]">' + esc(u.email) + '</small>' +
        '<small class="mt-1 block text-xs font-semibold text-[#687158]">Current role: ' + esc(u.role_name || 'Unassigned') + '</small></div>'
    );
    $('#assign-modal').removeClass('hidden').addClass('open flex');
    lucide.createIcons();
}

function openProfile(id) {
    closeMenus();
    $('#profile-modal').removeClass('hidden').addClass('open flex');
    $('#profile-content').html('<p class="text-sm text-[#92948a] py-6 text-center">Loading staff profile…</p>');

    $.get(base + '/admin/roles/staff/' + id).done(d => {
        const s = d.staff;
        $('#profile-title').text(s.name);
        $('#profile-content').html(
            '<div class="grid gap-3 sm:grid-cols-2">' +
                '<div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4"><span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Staff ID</span><strong class="mt-1 block font-mono text-xs text-[#252820]">' + esc(s.public_id) + '</strong></div>' +
                '<div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4"><span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Email Address</span><strong class="mt-1 block break-all text-xs font-bold text-[#252820]">' + esc(s.email) + '</strong></div>' +
                '<div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4"><span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Phone Contact</span><strong class="mt-1 block text-xs text-[#252820]">' + esc(s.phone || '—') + '</strong></div>' +
                '<div class="rounded-2xl border border-[#e5e5dd] bg-[#fafaf7] p-4"><span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">Assigned Role</span><strong class="mt-1 block text-xs text-[#687158] font-bold">' + esc(s.roles || 'Unassigned') + '</strong></div>' +
            '</div>' +
            '<div class="mt-6 flex justify-end pt-4 border-t border-[#f0f1ea]"><button class="profile-close rounded-xl border border-[#deded6] px-4 py-2.5 text-xs font-bold text-[#252820] hover:bg-[#f5f5f0]">Close Record</button></div>'
        );
    }).fail(() => $('#profile-content').html('<p class="text-sm text-rose-600 py-6 text-center">Unable to load profile.</p>'));
}

function openMessageModal(u) {
    if (!u) return;
    closeMenus();
    $('#message-staff-id').val(u.id);
    $('#message-recipient-chip').html(
        '<span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#687158] text-xs font-bold text-white shadow-sm">' + esc(initials(u.name)) + '</span>' +
        '<div class="min-w-0 flex-1">' +
            '<div class="flex items-center gap-2">' +
                '<strong class="truncate text-xs font-bold text-[#252820]">' + esc(u.name) + '</strong>' +
                '<span class="text-[10px] text-[#8e9187]">@' + esc(u.username) + '</span>' +
            '</div>' +
            '<p class="truncate text-[11px] text-[#77796f]">' + esc(u.email) + '</p>' +
        '</div>' +
        '<span class="shrink-0 rounded-lg bg-[#e7eadf] px-2.5 py-1 text-[10px] font-bold text-[#5d6a51]">' + esc(u.role_name || 'Staff') + '</span>'
    );
    $('#message-subject').val('');
    $('#message-body').val('');
    $('#message-char-count').text('0 / 2,000');
    $('#message-modal').removeClass('hidden').addClass('open flex');
    lucide.createIcons();
    setTimeout(() => $('#message-subject').focus(), 100);
}

function openConfirmModal(u, action) {
    if (!u) return;
    closeMenus();
    pendingStaffAction = { id: u.id, action: action, name: u.name };
    $('#confirm-reason').val('');

    const iconWrap = $('#confirm-icon-wrap');
    const submitBtn = $('#confirm-submit-btn');

    if (action === 'activate') {
        iconWrap.attr('class', 'grid h-11 w-11 place-items-center rounded-2xl bg-emerald-100 text-emerald-800');
        $('#confirm-icon').attr('data-lucide', 'user-check');
        $('#confirm-title').text('Reactivate Staff Member');
        $('#confirm-sub').text('Restore operational credentials and privileges.');
        $('#confirm-desc').html('Are you sure you want to reactivate <strong>' + esc(u.name) + '</strong>? Their staff workspace and administrative access will be restored immediately.');
        $('#confirm-reason-wrap').addClass('hidden');
        submitBtn.attr('class', 'rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-800');
        $('#confirm-btn-text').text('Reactivate Staff');
    } else if (action === 'suspend') {
        iconWrap.attr('class', 'grid h-11 w-11 place-items-center rounded-2xl bg-amber-100 text-amber-800');
        $('#confirm-icon').attr('data-lucide', 'user-round-x');
        $('#confirm-title').text('Suspend Staff Member');
        $('#confirm-sub').text('Temporary lock on staff credential access.');
        $('#confirm-desc').html('Are you sure you want to temporarily suspend <strong>' + esc(u.name) + '</strong>? They will be unable to access the staff portal until reactivated.');
        $('#confirm-reason-wrap').removeClass('hidden');
        submitBtn.attr('class', 'rounded-xl bg-amber-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-amber-800');
        $('#confirm-btn-text').text('Suspend Staff');
    } else {
        iconWrap.attr('class', 'grid h-11 w-11 place-items-center rounded-2xl bg-rose-100 text-rose-800');
        $('#confirm-icon').attr('data-lucide', 'user-minus');
        $('#confirm-title').text('Deactivate Staff Member');
        $('#confirm-sub').text('Permanent deactivation of staff account.');
        $('#confirm-desc').html('Are you sure you want to deactivate <strong>' + esc(u.name) + '</strong>? Their account status will be set to inactive.');
        $('#confirm-reason-wrap').removeClass('hidden');
        submitBtn.attr('class', 'rounded-xl bg-rose-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-rose-800');
        $('#confirm-btn-text').text('Deactivate Staff');
    }

    $('#confirm-action-modal').removeClass('hidden').addClass('open flex');
    lucide.createIcons();
}

$(document).on('click', '.action-trigger', function(e) {
    e.stopPropagation();
    const menu = $(this).closest('.action-menu');
    $('.action-menu').not(menu).removeClass('open');
    menu.toggleClass('open');
}).on('click', '.menu-action', function(e) {
    e.preventDefault();
    e.stopPropagation();
    const b = $(this), action = b.data('action'), staffId = b.data('id');
    const u = window.staffDirectory[staffId];
    if (action === 'view') {
        openProfile(staffId);
    } else if (action === 'assign') {
        openAssign(u);
    } else if (action === 'message') {
        openMessageModal(u);
    } else if (action === 'staff') {
        const staffAction = b.data('staff-action');
        openConfirmModal(u, staffAction);
    }
}).on('click', '.page-button', function() {
    page = Number($(this).data('page'));
    loadStaff();
}).on('click', '.role-link', function() {
    const slug = $(this).data('slug');
    if (!slug) return;
    $('#permission-title').text('Permissions');
    $('#permission-list').html('<p class="text-sm text-[#92948a] py-6 text-center">Loading permissions…</p>');
    $('#permission-modal').removeClass('hidden').addClass('open flex');
    $.get(base + '/admin/roles/permissions/' + encodeURIComponent(slug)).done(d => {
        $('#permission-title').text(d.role.name + ' Permissions');
        $('#permission-list').html(d.permissions.length ?
            d.permissions.map(p => '<div class="flex items-center gap-3 rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3"><i data-lucide="check-circle-2" class="h-4 w-4 text-[#687158]"></i><div><strong class="block text-xs font-bold text-[#252820]">' + esc(p.name) + '</strong><small class="text-[10px] text-[#8e9187]">' + esc(p.slug) + '</small></div></div>').join('') :
            '<p class="rounded-xl bg-[#fafaf7] p-4 text-xs text-[#8e9187] text-center">No individual permissions assigned.</p>'
        );
        lucide.createIcons();
    }).fail(() => $('#permission-list').html('<p class="text-sm text-rose-600 py-6 text-center">Unable to load permissions.</p>'));
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('.action-menu').length) closeMenus();
});

// Modal Close Listeners
$('.modal-close').on('click', () => $('#assign-modal').addClass('hidden').removeClass('open flex'));
$(document).on('click', '.profile-close', () => $('#profile-modal').addClass('hidden').removeClass('open flex'));
$('.permission-close').on('click', () => $('#permission-modal').addClass('hidden').removeClass('open flex'));
$('.message-close').on('click', () => $('#message-modal').addClass('hidden').removeClass('open flex'));
$('.confirm-cancel').on('click', () => $('#confirm-action-modal').addClass('hidden').removeClass('open flex'));

// Close modals when clicking on backdrop
$('.modal-backdrop').on('click', function(e) {
    if ($(e.target).is(this)) {
        $(this).addClass('hidden').removeClass('open flex');
    }
});

// Character counter for message textarea
$('#message-body').on('input', function() {
    const len = $(this).val().length;
    $('#message-char-count').text(len + ' / 2,000');
});

// Staff Message Form Submit Handler
$('#staff-message-form').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#send-message-btn').prop('disabled', true);
    const oldText = $('#send-btn-text').text();
    $('#send-btn-text').text('Dispatching Directive…');

    $.post(base + '/admin/roles/staff/message', $(this).serialize())
        .done(d => {
            $('#message-modal').addClass('hidden').removeClass('open flex');
            toast(d.message);
        })
        .fail(x => {
            toast((x.responseJSON || {}).message || 'Failed to dispatch message.', true);
        })
        .always(() => {
            btn.prop('disabled', false);
            $('#send-btn-text').text(oldText);
        });
});

// Confirm Staff Action Execution
$('#confirm-submit-btn').on('click', function() {
    if (!pendingStaffAction) return;
    const btn = $(this).prop('disabled', true);
    const oldText = $('#confirm-btn-text').text();
    $('#confirm-btn-text').text('Processing…');

    $.post(base + '/admin/roles/staff/action', {
        _csrf: csrf,
        staff_id: pendingStaffAction.id,
        action: pendingStaffAction.action,
        reason: $('#confirm-reason').val()
    }).done(d => {
        $('#confirm-action-modal').addClass('hidden').removeClass('open flex');
        toast(d.message);
        loadStaff();
    }).fail(x => {
        toast((x.responseJSON || {}).message || 'Action failed.', true);
    }).always(() => {
        btn.prop('disabled', false);
        $('#confirm-btn-text').text(oldText);
        pendingStaffAction = null;
    });
});

// Assign Role Form Submit
$('#assign-form').on('submit', function(e) {
    e.preventDefault();
    const b = $(this).find('button[type=submit]').prop('disabled', true);
    $.post(base + '/admin/roles/assign', $(this).serialize())
        .done(d => {
            $('#assign-modal').addClass('hidden').removeClass('open flex');
            toast(d.message);
            loadStaff();
        })
        .fail(x => toast((x.responseJSON || {}).message || 'Unable to save role.', true))
        .always(() => b.prop('disabled', false));
});

$('#user-search').on('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { page = 1; loadStaff(); }, 300);
});

$(document).ready(() => {
    lucide.createIcons();
    loadStaff();
});
</script>
</body>
</html>
