<?php declare(strict_types=1); 
$base = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/'); 
$user = \Noir\Core\Auth::user(); 
$adminUrl = ($base?:'').'/admin'; 
$storeName = store_setting('store_name', 'Noir & Co.');

// Calculate severity counts
$totalCount = count($parsedLogs);
$errorCount = 0;
$warningCount = 0;
$infoCount = 0;
foreach ($parsedLogs as $l) {
    if ($l['severity'] === 'ERROR') $errorCount++;
    elseif ($l['severity'] === 'WARNING') $warningCount++;
    else $infoCount++;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>System Logs &amp; AIOps Audit &mdash; <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e(($base?:'').'/assets/css/app.min.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <script src="<?= e(($base?:'').'/assets/js/admin-mobile.min.js') ?>" defer></script>
    <style>
        .stats-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-top: 20px;
        }
        .stat-card-log {
            background: #ffffff;
            border: 1px solid #e5e5dd;
            border-radius: 14px;
            padding: 16px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-card-log strong {
            display: block;
            font-size: 22px;
            font-weight: 800;
            color: #1a1c18;
            font-family: ui-monospace, monospace;
        }
        .stat-card-log span {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #77796f;
        }
        .ai-banner {
            background: #f4f5ee;
            border: 1px solid #e2e4d8;
            border-radius: 14px;
            padding: 14px 18px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 13px;
        }
        .logs-container {
            background: #141612;
            color: #e5e7eb;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #282c24;
            margin-top: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        .logs-toolbar {
            background: #1c201a;
            padding: 14px 18px;
            border-bottom: 1px solid #282c24;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            background: #181c15;
            padding: 14px 18px;
            border-bottom: 1px solid #242920;
            flex-wrap: wrap;
        }
        .filter-form select, .filter-form input {
            background: #22261e;
            color: #f3f4f6;
            border: 1px solid #363d30;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
        }
        .filter-form select:focus, .filter-form input:focus {
            outline: none;
            border-color: #687158;
        }
        .log-list {
            max-height: 65vh;
            overflow-y: auto;
            padding: 8px;
        }
        .log-entry {
            display: flex;
            flex-direction: column;
            padding: 10px 14px;
            border-bottom: 1px solid #1f231b;
            font-size: 12px;
            line-height: 1.5;
            transition: background 0.15s;
        }
        .log-entry:hover { background: #1a1e17; }
        .log-row-main {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .log-time {
            flex: 0 0 150px;
            color: #85897f;
            font-size: 11px;
        }
        .log-channel {
            flex: 0 0 130px;
            color: #9ba193;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        .log-badge-wrapper {
            flex: 0 0 75px;
        }
        .log-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .badge-error { background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .badge-warning { background: rgba(245,158,11,0.2); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .badge-info { background: rgba(59,130,246,0.2); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
        .log-msg {
            flex: 1 1 auto;
            color: #d1d5db;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .log-pre {
            background: #0f110d;
            color: #bef264;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #23271f;
            overflow-x: auto;
            margin-top: 6px;
            font-size: 11px;
        }
        /* AI Diagnosis Expansion Card */
        .ai-diagnosis-card {
            margin-top: 8px;
            background: #1e221b;
            border: 1px solid #333a2c;
            border-radius: 10px;
            padding: 12px 16px;
            color: #e5e7eb;
            font-family: inherit;
        }
        .btn-log-action {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-clear { background: #991b1b; color: white; }
        .btn-clear:hover { background: #b91c1c; }
        .btn-filter { background: #687158; color: white; }
        .btn-filter:hover { background: #555c47; }
        .btn-reset { background: #262b21; color: #a3a89b; border: 1px solid #3a4233; }
        .btn-reset:hover { color: #fff; background: #32392c; }
        .btn-ai-diagnose {
            background: #272d22;
            color: #a3e635;
            border: 1px solid #434e3a;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 8px;
        }
        .btn-ai-diagnose:hover { background: #343c2e; border-color: #a3e635; }
    </style>
</head>
<body class="admin-page">
    <?php $activeNav = 'system-logs'; include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <p class="eyebrow" style="color:#687158;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Security &amp; Audit Trail</p>
                <h1 style="font-size:26px;font-weight:800;color:#1a1c18;letter-spacing:-0.02em;margin:2px 0 4px;">Live System Logs &amp; AIOps Diagnostics</h1>
                <p class="admin-subtitle" style="color:#77796f;font-size:13px;margin:0;">AI root cause analysis, automated remediation plans, transaction loggers, and operational telemetry.</p>
            </div>
            <div class="admin-user" style="display:flex;align-items:center;gap:12px;">
                <?php $adminAvatar=(string)($user['profile_image']??$user['avatar']??store_setting('admin_profile_image_'.(int)($user['id']??$user['user_id']??0))?:store_setting('admin_profile_image_0')); ?>
                <?php if($adminAvatar!==''): ?>
                    <img src="<?= e(($base?:'').$adminAvatar) ?>" alt="<?= e($user['name']??'Admin') ?>" style="width:36px;height:36px;border-radius:9999px;object-fit:cover;border:1px solid rgba(0,0,0,0.1);">
                <?php else: ?>
                    <span style="display:inline-grid;place-items:center;width:36px;height:36px;border-radius:9999px;background:#687158;color:#fff;font-size:12px;font-weight:600;"><?= e(strtoupper(substr((string)($user['name']??'A'),0,1))) ?></span>
                <?php endif; ?>
                <div>
                    <strong style="display:block;font-size:13px;line-height:1.2;"><?= e($user['name']??'Admin') ?></strong>
                    <span style="font-size:11px;color:#85877d;"><?= e($user['email']??'') ?></span>
                </div>
                <form method="post" action="<?= e(($base?:'').'/logout') ?>" style="margin-left:8px;">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <button type="submit" style="background:transparent;border:none;color:#687158;cursor:pointer;font-size:12px;font-weight:600;">Sign out</button>
                </form>
            </div>
        </header>

        <!-- KPI Summary Strip -->
        <div class="stats-strip">
            <div class="stat-card-log">
                <div>
                    <span>Total Log Entries</span>
                    <strong><?= number_format($totalCount) ?></strong>
                </div>
                <div style="width:36px;height:36px;border-radius:10px;background:#f4f5ee;display:flex;align-items:center;justify-content:center;color:#687158;">
                    <i data-lucide="file-text" style="width:18px;height:18px;"></i>
                </div>
            </div>
            <div class="stat-card-log">
                <div>
                    <span>Critical Errors</span>
                    <strong style="color:<?= $errorCount > 0 ? '#dc2626' : '#16a34a' ?>;"><?= number_format($errorCount) ?></strong>
                </div>
                <div style="width:36px;height:36px;border-radius:10px;background:<?= $errorCount > 0 ? '#fef2f2' : '#f0fdf4' ?>;display:flex;align-items:center;justify-content:center;color:<?= $errorCount > 0 ? '#dc2626' : '#16a34a' ?>;">
                    <i data-lucide="alert-circle" style="width:18px;height:18px;"></i>
                </div>
            </div>
            <div class="stat-card-log">
                <div>
                    <span>Warnings</span>
                    <strong style="color:<?= $warningCount > 0 ? '#d97706' : '#52525b' ?>;"><?= number_format($warningCount) ?></strong>
                </div>
                <div style="width:36px;height:36px;border-radius:10px;background:#fffbeb;display:flex;align-items:center;justify-content:center;color:#d97706;">
                    <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i>
                </div>
            </div>
            <div class="stat-card-log">
                <div>
                    <span>Information Events</span>
                    <strong style="color:#2563eb;"><?= number_format($infoCount) ?></strong>
                </div>
                <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;">
                    <i data-lucide="info" style="width:18px;height:18px;"></i>
                </div>
            </div>
        </div>

        <!-- AIOps Diagnosis Strip -->
        <div class="ai-banner">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:32px;height:32px;border-radius:8px;background:#687158;color:#fff;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="bot" style="width:18px;height:18px;"></i>
                </div>
                <div>
                    <strong style="color:#1a1c18;font-size:14px;">AIOps Incident Scanner Active</strong>
                    <p style="margin:2px 0 0;color:#687158;font-size:12px;">Automated heuristic root-cause analyzer is continuously correlating exceptions, traces, and transaction logs.</p>
                </div>
            </div>
            <span style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;background:#ecfdf5;color:#047857;font-size:11px;font-weight:700;border:1px solid #a7f3d0;">
                <i data-lucide="check" style="width:12px;height:12px;"></i> Ready for Deep Analysis
            </span>
        </div>

        <!-- Terminal Logs Container -->
        <section class="logs-container">
            <form method="get" class="filter-form" action="<?= e(($base?:'').'/admin/system-logs') ?>">
                <select name="level">
                    <option value="">All Severities</option>
                    <option value="ERROR" <?= ($_GET['level'] ?? '') === 'ERROR' ? 'selected' : '' ?>>ERROR (Critical)</option>
                    <option value="WARNING" <?= ($_GET['level'] ?? '') === 'WARNING' ? 'selected' : '' ?>>WARNING (Caution)</option>
                    <option value="INFO" <?= ($_GET['level'] ?? '') === 'INFO' ? 'selected' : '' ?>>INFO (General)</option>
                </select>
                <select name="channel">
                    <option value="">All Channels</option>
                    <option value="Payment Failures" <?= ($_GET['channel'] ?? '') === 'Payment Failures' ? 'selected' : '' ?>>Payment Failures</option>
                    <option value="Mail Delivery" <?= ($_GET['channel'] ?? '') === 'Mail Delivery' ? 'selected' : '' ?>>Mail Delivery</option>
                    <option value="System Monitor" <?= ($_GET['channel'] ?? '') === 'System Monitor' ? 'selected' : '' ?>>System Monitor</option>
                    <option value="General Debug" <?= ($_GET['channel'] ?? '') === 'General Debug' ? 'selected' : '' ?>>General Debug</option>
                </select>
                <input type="text" name="search" placeholder="Search keywords, order IDs, or traces..." value="<?= e($_GET['search'] ?? '') ?>" style="flex-grow: 1; min-width: 200px;">
                <button type="submit" class="btn-log-action btn-filter">
                    <i data-lucide="filter" style="width:13px;height:13px;"></i> Apply Filters
                </button>
                <a href="<?= e(($base?:'').'/admin/system-logs') ?>" class="btn-log-action btn-reset">Reset</a>
            </form>

            <div class="logs-toolbar">
                <span style="display:flex;align-items:center;gap:12px;font-size:12px;color:#a3a89b;">
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                        <span style="width:8px;height:8px;border-radius:999px;background:#22c55e;display:inline-block;animation:pulse 2s infinite;"></span>
                        Live Stream Active
                    </span>
                    <span>&bull;</span>
                    <label style="color:#d1d5db;display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" id="live-update-toggle" checked style="accent-color:#687158;">
                        <span>Auto-refresh (Every 3s)</span>
                    </label>
                </span>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('log-list').innerText).then(()=>alert('Logs copied to clipboard!'))" class="btn-log-action btn-reset" style="font-size:11px;">
                        <i data-lucide="copy" style="width:12px;height:12px;"></i> Copy All
                    </button>
                    <form method="post" action="<?= e(($base?:'').'/admin/system-logs/clear') ?>" onsubmit="return confirm('Are you sure you want to clear all system logs? This action cannot be undone.');" style="margin:0;">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="btn-log-action btn-clear" style="font-size:11px;">
                            <i data-lucide="trash-2" style="width:12px;height:12px;"></i> Clear Logs
                        </button>
                    </form>
                </div>
            </div>

            <div class="log-list" id="log-list">
                <?php if (empty($parsedLogs)): ?>
                    <div style="padding: 40px; text-align: center; color: #77796f;">
                        <i data-lucide="check-circle" style="width:32px;height:32px;margin:0 auto 10px;display:block;color:#687158;"></i>
                        <strong>No system log entries found matching current criteria.</strong>
                        <p style="font-size:11px;margin-top:4px;color:#55594f;">All system components are executing with zero logged anomalies.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($parsedLogs as $idx => $log): ?>
                        <div class="log-entry" id="log-item-<?= $idx ?>">
                            <div class="log-row-main">
                                <span class="log-time"><?= e($log['timestamp']) ?></span>
                                <span class="log-channel"><?= e($log['type']) ?></span>
                                <span class="log-badge-wrapper">
                                    <span class="log-badge badge-<?= strtolower($log['severity']) ?>">
                                        <?= e($log['severity']) ?>
                                    </span>
                                </span>
                                <span class="log-msg">
                                    <?php if (!empty($log['is_json'])): ?>
                                        <pre class="log-pre"><?= e($log['formatted_json']) ?></pre>
                                    <?php else: ?>
                                        <?= e($log['message']) ?>
                                    <?php endif; ?>
                                </span>
                                <button type="button" class="btn-ai-diagnose" onclick="diagnoseLog(<?= $idx ?>, <?= htmlspecialchars(json_encode($log['message']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($log['type']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($log['severity']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i data-lucide="sparkles" style="width:10px;height:10px;"></i> AI Diagnose
                                </button>
                            </div>
                            <!-- Inline Diagnosis Box (Populated on click) -->
                            <div id="ai-box-<?= $idx ?>" style="display:none;" class="ai-diagnosis-card"></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        lucide.createIcons();

        // AI Diagnose Action
        async function diagnoseLog(idx, message, channel, severity) {
            const box = document.getElementById('ai-box-' + idx);
            if (box.style.display === 'block') {
                box.style.display = 'none';
                return;
            }

            box.style.display = 'block';
            box.innerHTML = '<span style="color:#a3a89b;"><i data-lucide="loader-2" class="animate-spin" style="width:12px;height:12px;display:inline-block;vertical-align:middle;"></i> Analyzing incident with AIOps engine...</span>';
            lucide.createIcons();

            try {
                const res = await fetch('<?= e(($base?:'').'/admin/system-logs/diagnose') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_csrf=<?= e(csrf_token()) ?>&message=' + encodeURIComponent(message) + '&channel=' + encodeURIComponent(channel) + '&severity=' + encodeURIComponent(severity)
                });
                const data = await res.json();
                const d = data.diagnosis;

                let remediations = '';
                (d.remediation || []).forEach(r => {
                    remediations += `<li style="margin-top:4px;">${r}</li>`;
                });

                box.innerHTML = `
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;border-bottom:1px solid #2d3426;padding-bottom:6px;">
                        <span style="color:#a3e635;font-weight:bold;display:flex;align-items:center;gap:6px;">
                            <i data-lucide="zap" style="width:12px;height:12px;"></i> AI Root Cause Analysis [${d.incident_code}] &bull; Confidence: ${d.confidence}
                        </span>
                        <span style="font-size:10px;color:#f87171;font-weight:bold;background:rgba(239,68,68,0.15);padding:2px 6px;border-radius:4px;">${d.severity_level}</span>
                    </div>
                    <div style="font-size:12px;margin-bottom:6px;"><strong style="color:#fff;">Diagnosis:</strong> ${d.summary}</div>
                    <div style="font-size:11px;color:#a3a89b;margin-bottom:8px;"><strong style="color:#cbd5e1;">Root Cause:</strong> ${d.root_cause}</div>
                    <div style="font-size:11px;color:#94a3b8;margin-bottom:8px;">${d.explanation}</div>
                    <div style="border-top:1px solid #2d3426;padding-top:6px;font-size:11px;">
                        <strong style="color:#a3e635;">Recommended Remediation:</strong>
                        <ul style="margin:4px 0 0;padding-left:18px;color:#e2e8f0;">${remediations}</ul>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                box.innerHTML = `<span style="color:#f87171;">Diagnostic scan error: ${err.message}</span>`;
            }
        }

        // Live Auto-Refresh
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('live-update-toggle');
            const logList = document.getElementById('log-list');
            const filterForm = document.querySelector('.filter-form');
            let fetchInterval = null;

            function escapeHtml(text) {
                const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            function fetchLogs() {
                if (!toggle.checked) return;

                const urlParams = new URLSearchParams(new FormData(filterForm));
                urlParams.append('ajax', '1');

                fetch('<?= e(($base?:'').'/admin/system-logs') ?>?' + urlParams.toString())
                    .then(r => r.json())
                    .then(data => {
                        if (!data.logs) return;
                        if (data.logs.length === 0) {
                            logList.innerHTML = '<div style="padding: 40px; text-align: center; color: #77796f;"><strong>No system log entries found.</strong></div>';
                            return;
                        }
                        
                        let html = '';
                        data.logs.forEach((log, idx) => {
                            const badgeClass = 'badge-' + log.severity.toLowerCase();
                            html += `
                                <div class="log-entry" id="log-item-${idx}">
                                    <div class="log-row-main">
                                        <span class="log-time">${escapeHtml(log.timestamp)}</span>
                                        <span class="log-channel">${escapeHtml(log.type)}</span>
                                        <span class="log-badge-wrapper">
                                            <span class="log-badge ${badgeClass}">${escapeHtml(log.severity)}</span>
                                        </span>
                                        <span class="log-msg">
                                            ${log.is_json ? `<pre class="log-pre">${escapeHtml(log.formatted_json)}</pre>` : escapeHtml(log.message)}
                                        </span>
                                        <button type="button" class="btn-ai-diagnose" onclick='diagnoseLog(${idx}, ${JSON.stringify(log.message)}, ${JSON.stringify(log.type)}, ${JSON.stringify(log.severity)})'>
                                            <i data-lucide="sparkles" style="width:10px;height:10px;"></i> AI Diagnose
                                        </button>
                                    </div>
                                    <div id="ai-box-${idx}" style="display:none;" class="ai-diagnosis-card"></div>
                                </div>
                            `;
                        });
                        logList.innerHTML = html;
                        lucide.createIcons();
                    })
                    .catch(e => console.error("Error fetching logs:", e));
            }

            toggle.addEventListener('change', function() {
                if (this.checked) {
                    fetchInterval = setInterval(fetchLogs, 3000);
                    fetchLogs();
                } else {
                    clearInterval(fetchInterval);
                }
            });

            if (toggle.checked) {
                fetchInterval = setInterval(fetchLogs, 3000);
            }
        });
    </script>
</body>
</html>
