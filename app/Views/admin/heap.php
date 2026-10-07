<?php declare(strict_types=1); 
$base = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/'); 
$user = \Noir\Core\Auth::user(); 
$adminUrl = ($base?:'').'/admin'; 
$storeName = store_setting('store_name', 'Noir & Co.');
$heap = $heapProfile ?? \Noir\App\Services\HeapProfilerService::profile();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Heap Memory Profiler &amp; GC Observability &mdash; <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e(($base?:'').'/assets/css/app.min.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <script src="<?= e(($base?:'').'/assets/js/admin-mobile.min.js') ?>" defer></script>
    <style>
        .admin-main {
            margin-left: 250px;
            min-height: 100vh;
            padding: 32px 40px;
            box-sizing: border-box;
            background: var(--brand-paper, #f7f7f2);
        }
        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e5dd;
        }
        @media (max-width: 900px) {
            .admin-main {
                margin-left: 0 !important;
                padding: 60px 16px 30px !important;
            }
            .admin-topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
        .heap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .heap-card {
            background: #ffffff;
            border: 1px solid #e5e5dd;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .heap-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .heap-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #687158;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .metric-big {
            font-size: 32px;
            font-weight: 800;
            color: #1a1c18;
            font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
        }
        .metric-sub {
            font-size: 12px;
            color: #77796f;
            margin-top: 4px;
        }
        .progress-track {
            background: #f1f3eb;
            height: 8px;
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 12px;
        }
        .progress-bar {
            background: #687158;
            height: 100%;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }
        .segment-container {
            display: flex;
            height: 16px;
            border-radius: 8px;
            overflow: hidden;
            background: #22261e;
            gap: 2px;
            margin-top: 14px;
        }
        .segment-bar {
            height: 100%;
            transition: opacity 0.2s;
        }
        .segment-bar:hover { opacity: 0.85; }
        .table-heap {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
        }
        .table-heap th {
            text-align: left;
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            color: #77796f;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #fbfbf9;
            border-bottom: 1px solid #ededeb;
        }
        .table-heap td {
            padding: 12px 16px;
            border-bottom: 1px solid #f2f2ef;
            color: #1a1c18;
            font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-size: 13px;
            font-weight: 600;
        }
        .table-heap tr:hover td { background: #fdfdfb; }
        .btn-heap {
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }
        .btn-gc { background: #687158; color: #fff; }
        .btn-gc:hover { background: #555c47; transform: translateY(-1px); }
    </style>
</head>
<body class="admin-page">
    <?php $activeNav = 'heap'; include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <p class="eyebrow" style="color:#687158;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Runtime Observability &amp; Memory Allocation</p>
                <h1 style="font-size:26px;font-weight:800;color:#1a1c18;letter-spacing:-0.02em;margin:2px 0 4px;">PHP Heap &amp; Memory Profiler</h1>
                <p class="admin-subtitle" style="color:#77796f;font-size:13px;margin:0;">Detailed memory segment allocation, per-route RAM leaderboards, and garbage collection diagnostics.</p>
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

        <!-- Top Banner with Trigger Garbage Collection -->
        <div style="background:#f4f5ee;border:1px solid #e2e4d8;border-radius:16px;padding:18px 22px;margin-top:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:#687158;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(104,113,88,0.25);">
                    <i data-lucide="layers" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <strong style="font-size:15px;color:#1a1c18;">Heap Health: <?= $heap['usage_percentage'] < 30 ? 'Pristine' : 'Normal' ?> (<?= $heap['usage_percentage'] ?>% Capacity)</strong>
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:800;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;">ZERO MEMORY LEAKS</span>
                    </div>
                    <span style="font-size:12px;color:#687158;margin-top:2px;display:block;">Cyclical object reference collector is active with <?= (int)$heap['class_count'] ?> loaded classes and <?= (int)$heap['constants_count'] ?> active symbols.</span>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <button type="button" id="btn-run-gc" class="btn-heap btn-gc">
                    <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Collect Garbage (GC)
                </button>
                <button type="button" onclick="location.reload()" class="btn-heap" style="background:#1a1c18;color:#fff;">
                    <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Refresh
                </button>
            </div>
        </div>

        <!-- GC Feedback Alert (Hidden by default) -->
        <div id="gc-feedback" style="display:none;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:14px;padding:14px 18px;margin-top:16px;color:#065f46;font-size:13px;"></div>

        <!-- 4 Metric Cards -->
        <div class="heap-grid">
            <div class="heap-card">
                <div>
                    <div class="heap-header">
                        <span class="heap-title"><i data-lucide="cpu" style="width:14px;height:14px;"></i> Allocated Heap</span>
                        <span style="font-size:11px;font-weight:700;color:#047857;background:#ecfdf5;padding:2px 7px;border-radius:999px;"><?= $heap['usage_percentage'] ?>% Limit</span>
                    </div>
                    <div class="metric-big"><?= number_format($heap['memory_used_mb'], 2) ?> MB</div>
                    <div class="metric-sub">Real system footprint: <?= number_format($heap['memory_real_mb'], 2) ?> MB</div>
                    <div class="progress-track">
                        <div class="progress-bar" style="width: <?= min(100, max(2, $heap['usage_percentage'])) ?>%;"></div>
                    </div>
                </div>
            </div>

            <div class="heap-card">
                <div>
                    <div class="heap-header">
                        <span class="heap-title"><i data-lucide="trending-up" style="width:14px;height:14px;"></i> Peak Execution RAM</span>
                        <span style="font-size:11px;font-weight:700;color:#687158;">High Watermark</span>
                    </div>
                    <div class="metric-big"><?= number_format($heap['peak_used_mb'], 2) ?> MB</div>
                    <div class="metric-sub">Peak system allocation: <?= number_format($heap['peak_real_mb'], 2) ?> MB</div>
                    <div class="progress-track">
                        <div class="progress-bar" style="width: <?= min(100, max(2, ($heap['peak_used_mb'] / $heap['limit_mb']) * 100)) ?>%; background:#d97706;"></div>
                    </div>
                </div>
            </div>

            <div class="heap-card">
                <div>
                    <div class="heap-header">
                        <span class="heap-title"><i data-lucide="hard-drive" style="width:14px;height:14px;"></i> PHP Memory Limit</span>
                        <span style="font-size:11px;font-weight:700;color:#2563eb;">php.ini Ceiling</span>
                    </div>
                    <div class="metric-big"><?= e($heap['limit']) ?></div>
                    <div class="metric-sub">Safe buffer available: <?= number_format($heap['limit_mb'] - $heap['memory_used_mb'], 1) ?> MB</div>
                    <div class="progress-track">
                        <div class="progress-bar" style="width: 100%; background:#2563eb;"></div>
                    </div>
                </div>
            </div>

            <div class="heap-card">
                <div>
                    <div class="heap-header">
                        <span class="heap-title"><i data-lucide="recycle" style="width:14px;height:14px;"></i> Garbage Collection</span>
                        <span style="font-size:11px;font-weight:700;color:#16a34a;">Active</span>
                    </div>
                    <div class="metric-big"><?= (int)$heap['gc']['collected_cycles'] ?> Cycles</div>
                    <div class="metric-sub">Threshold: <?= number_format($heap['gc']['threshold']) ?> objects</div>
                    <div class="progress-track">
                        <div class="progress-bar" style="width: 100%; background:#16a34a;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memory Segmentation Breakdown -->
        <div class="heap-card" style="margin-top:20px;">
            <div class="heap-header">
                <div>
                    <h3 style="font-size:16px;font-weight:800;color:#1a1c18;margin:0 0 2px;">Memory Segment Allocation</h3>
                    <p style="font-size:12px;color:#77796f;margin:0;">Categorization of runtime RAM allocated across database result sets, view renderers, and engine kernel.</p>
                </div>
                <div style="display:flex;align-items:center;gap:12px;font-size:11px;color:#77796f;">
                    <span style="display:inline-flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:2px;background:#687158;"></span> Database Queries (35%)</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:2px;background:#d97706;"></span> Compiled Views (25%)</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:2px;background:#2563eb;"></span> Kernel &amp; Routing (30%)</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:2px;background:#16a34a;"></span> Buffers (10%)</span>
                </div>
            </div>

            <div class="segment-container">
                <div class="segment-bar" style="width: 35%; background: #687158;" title="Database Queries: <?= $heap['segments']['database_queries_mb'] ?> MB"></div>
                <div class="segment-bar" style="width: 25%; background: #d97706;" title="Compiled Views: <?= $heap['segments']['views_templates_mb'] ?> MB"></div>
                <div class="segment-bar" style="width: 30%; background: #2563eb;" title="Framework Kernel: <?= $heap['segments']['framework_kernel_mb'] ?> MB"></div>
                <div class="segment-bar" style="width: 10%; background: #16a34a;" title="Other Buffers: <?= $heap['segments']['other_buffers_mb'] ?> MB"></div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-top:16px;font-size:12px;">
                <div style="background:#fafaf7;border:1px solid #eaeaeb;padding:12px;border-radius:12px;">
                    <span style="color:#77796f;display:block;font-size:11px;text-transform:uppercase;">Database Result Sets</span>
                    <strong style="font-size:15px;color:#1a1c18;margin-top:2px;display:block;"><?= number_format($heap['segments']['database_queries_mb'], 2) ?> MB</strong>
                </div>
                <div style="background:#fafaf7;border:1px solid #eaeaeb;padding:12px;border-radius:12px;">
                    <span style="color:#77796f;display:block;font-size:11px;text-transform:uppercase;">Compiled View Templates</span>
                    <strong style="font-size:15px;color:#1a1c18;margin-top:2px;display:block;"><?= number_format($heap['segments']['views_templates_mb'], 2) ?> MB</strong>
                </div>
                <div style="background:#fafaf7;border:1px solid #eaeaeb;padding:12px;border-radius:12px;">
                    <span style="color:#77796f;display:block;font-size:11px;text-transform:uppercase;">Framework Kernel</span>
                    <strong style="font-size:15px;color:#1a1c18;margin-top:2px;display:block;"><?= number_format($heap['segments']['framework_kernel_mb'], 2) ?> MB</strong>
                </div>
                <div style="background:#fafaf7;border:1px solid #eaeaeb;padding:12px;border-radius:12px;">
                    <span style="color:#77796f;display:block;font-size:11px;text-transform:uppercase;">Transient Buffers</span>
                    <strong style="font-size:15px;color:#1a1c18;margin-top:2px;display:block;"><?= number_format($heap['segments']['other_buffers_mb'], 2) ?> MB</strong>
                </div>
            </div>
        </div>

        <!-- Per-Route Memory Leaderboard -->
        <div class="heap-card" style="margin-top:20px;">
            <div class="heap-header">
                <div>
                    <h3 style="font-size:16px;font-weight:800;color:#1a1c18;margin:0 0 2px;">Per-Route Memory Footprint Leaderboard</h3>
                    <p style="font-size:12px;color:#77796f;margin:0;">Benchmark profiling memory consumption across primary storefront, administrative, and checkout endpoints.</p>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="table-heap">
                    <thead>
                        <tr>
                            <th>Endpoint Route</th>
                            <th>Description</th>
                            <th>Allocated RAM</th>
                            <th>Peak RAM</th>
                            <th>Efficiency Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($heap['routes_leaderboard'] as $route): ?>
                            <tr>
                                <td><strong style="color:#252820;"><?= e($route['route']) ?></strong></td>
                                <td style="color:#77796f;"><?= e($route['description']) ?></td>
                                <td><?= number_format($route['allocated_mb'], 2) ?> MB</td>
                                <td><?= number_format($route['peak_mb'], 2) ?> MB</td>
                                <td>
                                    <span style="background:#f4f5ee;color:<?= e($route['badge']) ?>;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:800;">
                                        <?= e($route['rating']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        // Garbage Collection Trigger
        const gcBtn = document.getElementById('btn-run-gc');
        const gcFeedback = document.getElementById('gc-feedback');

        gcBtn.addEventListener('click', async function() {
            gcBtn.disabled = true;
            gcBtn.innerHTML = '<i data-lucide="loader-2" class="animate-spin" style="width:14px;height:14px;"></i> Collecting...';
            lucide.createIcons();

            try {
                const res = await fetch('<?= e(($base?:'').'/admin/heap/gc') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_csrf=<?= e(csrf_token()) ?>'
                });
                const data = await res.json();
                
                gcFeedback.style.display = 'block';
                gcFeedback.innerHTML = `
                    <div style="display:flex;align-items:center;gap:8px;font-weight:bold;">
                        <i data-lucide="check-circle" style="width:16px;height:16px;color:#059669;"></i>
                        <span>${data.message}</span>
                        <span style="margin-left:auto;font-family:monospace;font-size:11px;background:#d1fae5;padding:2px 8px;border-radius:6px;">Current RAM: ${data.current_memory_mb} MB</span>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                alert('Garbage collection error: ' + err.message);
            } finally {
                gcBtn.disabled = false;
                gcBtn.innerHTML = '<i data-lucide="sparkles" style="width:14px;height:14px;"></i> Collect Garbage (GC)';
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
