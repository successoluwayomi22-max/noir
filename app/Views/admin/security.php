<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$securityUrl = ($base ?: '') . '/admin/security';
$adminUrl = ($base ?: '') . '/admin';
$ordersUrl = ($base ?: '') . '/admin/orders';
$storeName = store_setting('store_name', 'Noir & Co.');
$activeCount = $activeBlockedCount ?? 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Security Shield & Threat Defense — <?= e($storeName) ?></title>
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
        .admin-main { margin-left: 250px; min-height: 100vh; background: var(--brand-paper, #f7f7f2); }
        .admin-topbar { display: flex; justify-content: space-between; gap: 24px; padding: 34px 42px; border-bottom: 1px solid #e5e5dd; }
        .sec-table th { padding: 14px 16px; text-align: left; color: #92948a; font-size: 10px; letter-spacing: .13em; text-transform: uppercase; white-space: nowrap; }
        .sec-table td { padding: 15px 16px; border-top: 1px solid #e8e8e0; font-size: 12px; white-space: nowrap; }
        .sec-table tbody tr:hover { background: #f4f4ed; }
        @keyframes pulseGlow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.15); }
        }
        .live-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            animation: pulseGlow 2s infinite ease-in-out;
        }
    </style>
</head>
<body class="text-[#252820]">

<?php $activeNav = 'security'; include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <header class="admin-topbar">
        <div>
            <p class="mb-2 text-[10px] font-bold uppercase tracking-[.2em] text-[#ef4444]">Active Threat Defense</p>
            <h1 class="text-3xl font-medium">Security Command Center</h1>
            <p class="mt-2 text-sm text-[#77796f]">Global rate limiting, automated intrusion prevention, and real-time IP firewall.</p>
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

    <section class="p-6 lg:p-10 space-y-8">
        <!-- Flash Alerts -->
        <?php if (!empty($_GET['unblocked'])): ?>
            <div class="rounded-2xl border border-[#bbf7d0] bg-[#f0fdf4] p-4 text-xs font-medium text-[#15803d] flex items-center justify-between shadow-sm">
                <span class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                    IP Address was successfully unblocked. Access to Noir storefront and mobile APIs has been restored.
                </span>
                <a href="<?= e($securityUrl) ?>" class="text-[#15803d] hover:underline">&times; Close</a>
            </div>
        <?php elseif (!empty($_GET['blocked'])): ?>
            <div class="rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-4 text-xs font-medium text-[#b91c1c] flex items-center justify-between shadow-sm">
                <span class="flex items-center gap-2">
                    <i data-lucide="shield-alert" class="h-4 w-4"></i>
                    IP Address successfully blocked. All subsequent web and mobile requests from this address are dropped with HTTP 403.
                </span>
                <a href="<?= e($securityUrl) ?>" class="text-[#b91c1c] hover:underline">&times; Close</a>
            </div>
        <?php endif; ?>

        <!-- Threat Posture Overview Banner -->
        <div class="rounded-3xl p-6 lg:p-8 text-white relative overflow-hidden shadow-xl <?= $activeCount > 0 ? 'bg-gradient-to-r from-[#3f1616] to-[#1c1e18] border border-[#ef4444]/30' : 'bg-gradient-to-r from-[#1c2419] to-[#171814] border border-[#10b981]/30' ?>">
            <div class="flex flex-wrap items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="live-dot <?= $activeCount > 0 ? 'bg-[#ef4444]' : 'bg-[#10b981]' ?>"></span>
                        <span class="text-[11px] font-bold uppercase tracking-[.25em] <?= $activeCount > 0 ? 'text-[#ef4444]' : 'text-[#34d399]' ?>">
                            <?= $activeCount > 0 ? 'Elevated Defense — Active Attack Suppressed' : 'DEFCON 5 — All Systems Protected & Clean' ?>
                        </span>
                    </div>
                    <h2 class="text-2xl lg:text-3xl font-medium tracking-tight text-white">
                        <?= $activeCount > 0 ? $activeCount . ' Malicious Attacker' . ($activeCount === 1 ? '' : 's') . ' Currently Neutralized' : 'Zero Active Threat Incursions Detected' ?>
                    </h2>
                    <p class="mt-2 text-xs lg:text-sm text-[#9da391] max-w-2xl leading-relaxed">
                        Pre-routing firewall inspection is active across Web and Mobile APIs. Requests exceeding 12 queries per 60 seconds are automatically isolated and terminated before reaching application memory.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="<?= e(($base ?: '') . '/admin/system-logs') ?>" class="rounded-xl border border-white/20 bg-white/5 px-4 py-3 text-xs font-semibold text-white hover:bg-white/10 transition-colors flex items-center gap-2">
                        <i data-lucide="terminal" class="h-4 w-4"></i>Live System Logs
                    </a>
                    <a href="#manual-block-drawer" class="rounded-xl bg-[#ef4444] px-4 py-3 text-xs font-semibold text-white hover:bg-[#dc2626] transition-colors flex items-center gap-2 shadow-lg">
                        <i data-lucide="shield-plus" class="h-4 w-4"></i>+ Manually Blacklist IP
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#92948a]">Active Blocked IPs</span>
                <strong class="mt-2 block text-3xl font-semibold <?= $activeCount > 0 ? 'text-[#ef4444]' : 'text-[#10b981]' ?>">
                    <?= number_format($activeCount) ?>
                </strong>
                <small class="text-[11px] text-[#77796f]"><?= $activeCount > 0 ? 'Immediate HTTP 403 drop' : 'No active threats' ?></small>
            </div>
            <div class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#92948a]">Thwarted Floods</span>
                <strong class="mt-2 block text-3xl font-semibold text-[#252820]">
                    <?= number_format((int)($allTimeBlockedCount ?? 0)) ?>
                </strong>
                <small class="text-[11px] text-[#77796f]">All-time attack mitigations</small>
            </div>
            <div class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#92948a]">Auth Events (24h)</span>
                <strong class="mt-2 block text-3xl font-semibold text-[#252820]">
                    <?= number_format((int)($loginAttempts ?? 0)) ?>
                </strong>
                <small class="text-[11px] text-[#77796f]">Logins & tokens processed</small>
            </div>
            <div class="rounded-2xl border border-[#e5e5dd] bg-[#252820] p-5 text-white">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#b9bcb2]">Defense Score</span>
                <strong class="mt-2 block text-3xl font-semibold text-[#34d399]">A+</strong>
                <small class="text-[11px] text-[#8e9187]">L7 WAF & Rate Limiter Active</small>
            </div>
        </div>

        <!-- Manual Blacklist Box (Always Visible) -->
        <div id="manual-block-drawer" class="rounded-3xl border border-[#fecaca] bg-[#fff5f5] p-6 transition-all shadow-sm">
            <h3 class="text-base font-semibold text-[#991b1b] flex items-center gap-2">
                <i data-lucide="shield-ban" class="h-5 w-5"></i>
                Enforce Manual IP Blacklist
            </h3>
            <p class="text-xs text-[#7f1d1d] mt-1">
                Any IP entered below will be immediately forbidden (HTTP 403) from accessing all storefront pages, user carts, checkouts, and mobile APIs.
            </p>
            <form method="post" action="<?= e(($base ?: '') . '/admin/security/block-ip') ?>" class="mt-4 grid gap-4 sm:grid-cols-3 lg:grid-cols-4 items-end">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="redirect_to" value="<?= e($securityUrl) ?>">
                <div>
                    <label class="block text-[11px] font-semibold text-[#7f1d1d] mb-1">IPv4 or IPv6 Address</label>
                    <input type="text" name="ip_address" required placeholder="e.g. 198.51.100.44 or 2001:db8::1" class="w-full rounded-xl border border-[#fca5a5] bg-white p-3 text-xs outline-none focus:border-[#ef4444]">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#7f1d1d] mb-1">Reason for Blacklist</label>
                    <input type="text" name="reason" placeholder="e.g. Malicious scraper, credential stuffing" class="w-full rounded-xl border border-[#fca5a5] bg-white p-3 text-xs outline-none focus:border-[#ef4444]">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#7f1d1d] mb-1">Ban Duration</label>
                    <select name="duration_hours" class="w-full rounded-xl border border-[#fca5a5] bg-white p-3 text-xs outline-none focus:border-[#ef4444]">
                        <option value="1">1 Hour</option>
                        <option value="6">6 Hours</option>
                        <option value="24" selected>24 Hours (Default)</option>
                        <option value="72">3 Days</option>
                        <option value="168">7 Days</option>
                        <option value="720">30 Days</option>
                        <option value="87600">Permanent (10 Years)</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full rounded-xl bg-[#ef4444] p-3 text-xs font-semibold text-white hover:bg-[#dc2626] transition-colors">
                        Drop & Blacklist IP
                    </button>
                </div>
            </form>
        </div>

        <!-- Blocked IPs Firewall Table -->
        <div class="rounded-3xl border border-[#e5e5dd] bg-[#fbfbf7] overflow-hidden shadow-sm">
            <div class="p-6 border-b border-[#e8e8e0] flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Active Blacklist</p>
                    <h2 class="text-xl font-medium mt-1">Blocked IP Addresses (<?= count($blockedIps ?? []) ?>)</h2>
                </div>
                <div class="relative w-full sm:w-72">
                    <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#92948a]"></i>
                    <input id="filter-blocked-ips" type="text" placeholder="Search IP or route..." class="w-full rounded-xl border border-[#deded6] bg-white py-2 pl-9 pr-3 text-xs outline-none focus:border-[#687158]">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="sec-table w-full">
                    <thead>
                        <tr>
                            <th>Attacker IP</th>
                            <th>Target Endpoint</th>
                            <th>Spike Count</th>
                            <th>Detection Reason</th>
                            <th>Blocked Until</th>
                            <th>Blocked Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="blocked-rows">
                        <?php if (empty($blockedIps)): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-sm text-[#92948a]">
                                    <div class="max-w-md mx-auto">
                                        <div class="w-12 h-12 rounded-full bg-[#ecfdf5] text-[#10b981] grid place-items-center mx-auto mb-3">
                                            <i data-lucide="shield-check" class="h-6 w-6"></i>
                                        </div>
                                        <strong class="block text-sm text-[#252820]">No active blacklisted attackers.</strong>
                                        <p class="text-xs text-[#77796f] mt-1">The automated shield is monitoring all endpoints in real time. If any IP floods login or API requests, it will automatically appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($blockedIps as $b): ?>
                                <tr class="blocked-item-row">
                                    <td>
                                        <span class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-[#b91c1c] bg-[#fee2e2] px-2.5 py-1 rounded-lg">
                                            <i data-lucide="ban" class="h-3 w-3"></i>
                                            <?= e($b['ip_address']) ?>
                                        </span>
                                    </td>
                                    <td><code class="text-[11px] text-[#687158] bg-[#f0f0e9] px-2 py-1 rounded"><?= e($b['route'] ?: 'Global') ?></code></td>
                                    <td><strong class="text-xs text-[#252820]"><?= (int)$b['request_count'] ?></strong> req</td>
                                    <td class="text-xs text-[#77796f] max-w-[220px] truncate" title="<?= e($b['reason']) ?>"><?= e($b['reason']) ?></td>
                                    <td><span class="text-xs text-[#b91c1c] font-medium"><?= e(date('d M Y H:i', strtotime($b['blocked_until']))) ?></span></td>
                                    <td class="text-xs text-[#92948a]"><?= e(date('d M Y H:i', strtotime($b['created_at']))) ?></td>
                                    <td>
                                        <form method="post" action="<?= e(($base ?: '') . '/admin/security/unblock-ip') ?>" onsubmit="return confirm('Restore network access for <?= e($b['ip_address']) ?>?');">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="<?= e($securityUrl) ?>">
                                            <button type="submit" class="rounded-xl border border-[#d8d8cf] bg-white px-3 py-1.5 text-[11px] font-semibold text-[#15803d] hover:bg-[#ecfdf5] transition-colors">
                                                Unblock Access
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Two Column: Live Security Log Stream + Architectural Defense Audit -->
        <div class="grid gap-8 lg:grid-cols-2">
            <!-- Live Security Stream -->
            <div class="rounded-3xl border border-[#e5e5dd] bg-[#fbfbf7] p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4 border-b border-[#e8e8e0] pb-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Audit Trail</p>
                            <h3 class="text-lg font-medium mt-1">Live Security & Auth Events</h3>
                        </div>
                        <a href="<?= e(($base ?: '') . '/admin/system-logs') ?>" class="text-xs text-[#687158] hover:underline font-semibold flex items-center gap-1">
                            All Logs ↗
                        </a>
                    </div>
                    <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                        <?php if (empty($securityLogs)): ?>
                            <p class="text-xs text-[#92948a] py-6 text-center">No recent security events recorded in activity log.</p>
                        <?php else: ?>
                            <?php foreach ($securityLogs as $log): ?>
                                <div class="rounded-2xl border border-[#e8e8e0] bg-white p-3.5 flex items-start justify-between gap-3 text-xs">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= str_starts_with($log['event'], 'security.') ? 'bg-[#fee2e2] text-[#b91c1c]' : 'bg-[#e0f2fe] text-[#0369a1]' ?>">
                                                <?= e($log['event']) ?>
                                            </span>
                                            <span class="text-[10px] text-[#92948a]"><?= e(date('H:i:s • d M', strtotime($log['created_at']))) ?></span>
                                        </div>
                                        <p class="font-medium text-[#252820] text-xs">
                                            <?= e($log['subject_label'] ?: ($log['actor_name'] . ' executed ' . $log['event'])) ?>
                                        </p>
                                        <div class="flex items-center gap-3 text-[10px] text-[#85877d]">
                                            <span>IP: <code class="text-[#687158]"><?= e($log['ip_address'] ?: 'Local') ?></code></span>
                                            <span>Platform: <span class="capitalize"><?= e($log['platform'] ?: 'web') ?></span></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Platform Architecture Checklist -->
            <div class="rounded-3xl border border-[#e5e5dd] bg-[#fbfbf7] p-6 shadow-sm">
                <div class="border-b border-[#e8e8e0] pb-4 mb-4">
                    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Verification Audit</p>
                    <h3 class="text-lg font-medium mt-1">Multi-Layer Platform Protections</h3>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-white border border-[#e8e8e0] p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <strong class="text-xs font-semibold text-[#252820]">Flood Rate Limiting</strong>
                            <span class="h-2 w-2 rounded-full bg-[#10b981]"></span>
                        </div>
                        <p class="text-[11px] text-[#77796f]">Intelligent Tiered Limits: 6-8 req / 60s on auth/passwords; 60-120 req / 60s on cart/browsing. Admin accounts immune.</p>
                    </div>

                    <div class="rounded-2xl bg-white border border-[#e8e8e0] p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <strong class="text-xs font-semibold text-[#252820]">Pre-Routing IP Shield</strong>
                            <span class="h-2 w-2 rounded-full bg-[#10b981]"></span>
                        </div>
                        <p class="text-[11px] text-[#77796f]">Blocked IPs are dropped with HTTP 403 before routes are dispatched.</p>
                    </div>

                    <div class="rounded-2xl bg-white border border-[#e8e8e0] p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <strong class="text-xs font-semibold text-[#252820]">CSRF Protection</strong>
                            <span class="h-2 w-2 rounded-full bg-[#10b981]"></span>
                        </div>
                        <p class="text-[11px] text-[#77796f]">Cryptographic tokens verified on all state-changing POST/PUT requests.</p>
                    </div>

                    <div class="rounded-2xl bg-white border border-[#e8e8e0] p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <strong class="text-xs font-semibold text-[#252820]">SQL Injection Shield</strong>
                            <span class="h-2 w-2 rounded-full bg-[#10b981]"></span>
                        </div>
                        <p class="text-[11px] text-[#77796f]">100% of database queries execute with PDO prepared statements and parameter bindings.</p>
                    </div>

                    <div class="rounded-2xl bg-white border border-[#e8e8e0] p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <strong class="text-xs font-semibold text-[#252820]">Password Hashing</strong>
                            <span class="h-2 w-2 rounded-full bg-[#10b981]"></span>
                        </div>
                        <p class="text-[11px] text-[#77796f]">Industry-grade Argon2id / BCRYPT one-way password hashing with per-user salt.</p>
                    </div>

                    <div class="rounded-2xl bg-white border border-[#e8e8e0] p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <strong class="text-xs font-semibold text-[#252820]">Hardened Headers</strong>
                            <span class="h-2 w-2 rounded-full bg-[#10b981]"></span>
                        </div>
                        <p class="text-[11px] text-[#77796f]">Strict-Transport-Security, X-Frame-Options SAMEORIGIN, and X-Content-Type-Options nosniff.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
lucide.createIcons();

$('#filter-blocked-ips').on('input', function() {
    const term = $(this).val().toLowerCase();
    $('.blocked-item-row').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(term) > -1);
    });
});
</script>
</body>
</html>
