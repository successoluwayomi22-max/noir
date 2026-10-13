<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = ($base === '/' || $base === '\\') ? '' : $base;
$user = \Noir\Core\Auth::user();
$adminUrl = ($base ?: '') . '/admin';
$aiUrl = ($base ?: '') . '/admin/ai-intelligence';
$currentPeriod = $period ?? 'all';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Intelligence & Market Demands — <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/admin-mobile.min.js') ?>" defer></script>
    <style>
        .tab-btn {
            color: #77796f;
            border-bottom: 2px solid transparent;
            padding-bottom: 10px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .tab-btn:hover {
            color: var(--brand-primary, #687158);
        }
        .tab-btn.active {
            color: var(--brand-primary, #687158);
            border-bottom-color: var(--brand-primary, #687158);
            font-weight: 700;
        }
        .period-pill {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #deded6;
            background: #fff;
            color: #686b62;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .period-pill:hover {
            border-color: var(--brand-primary, #687158);
            color: var(--brand-primary, #687158);
        }
        .period-pill.active {
            background: var(--brand-primary, #687158);
            color: #ffffff;
            border-color: var(--brand-primary, #687158);
        }
    </style>
</head>
<body class="admin-page bg-[#f6f6f1]">

    <?php $activeNav = 'ai-intelligence'; include __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Main Workspace Content -->
    <main class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div>
                <p class="eyebrow">Stylist Intelligence</p>
                <h1>AI Market Demand & Client Hub</h1>
                <p class="admin-subtitle">Live customer conversations and predictive production demand forecasting.</p>
            </div>
            <div class="admin-user flex items-center gap-3">
                <a href="<?= e($aiUrl . '/export') ?>" class="rounded-xl border border-[#d9d9d0] bg-white px-3.5 py-2 text-xs font-semibold text-[#687158] hover:bg-[#f0f1eb] flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Export CSV
                </a>
                <a href="<?= e(($base ?: '') . '/store#catalogue') ?>" target="_blank" class="rounded-xl bg-[#687158] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#596249] flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    Test AI Stylist
                </a>
            </div>
        </header>

        <section class="p-5 lg:p-10 space-y-6">

            <!-- Filter Bar & Period Selection -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-[#e3e3da] pb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-[#85877d] uppercase tracking-wider mr-1">Timeframe:</span>
                    <a href="<?= e($aiUrl) ?>?period=today&tab=<?= e($currentTab ?? 'demands') ?>" data-period="today" class="period-pill <?= in_array($currentPeriod, ['today', '1d', 'day'], true) ? 'active' : '' ?>">Today</a>
                    <a href="<?= e($aiUrl) ?>?period=7d&tab=<?= e($currentTab ?? 'demands') ?>" data-period="7d" class="period-pill <?= in_array($currentPeriod, ['7d', 'week'], true) ? 'active' : '' ?>">7 Days</a>
                    <a href="<?= e($aiUrl) ?>?period=30d&tab=<?= e($currentTab ?? 'demands') ?>" data-period="30d" class="period-pill <?= in_array($currentPeriod, ['30d', 'month'], true) ? 'active' : '' ?>">30 Days</a>
                    <a href="<?= e($aiUrl) ?>?period=1y&tab=<?= e($currentTab ?? 'demands') ?>" data-period="1y" class="period-pill <?= in_array($currentPeriod, ['1y', 'year'], true) ? 'active' : '' ?>">1 Year</a>
                    <a href="<?= e($aiUrl) ?>?period=all&tab=<?= e($currentTab ?? 'demands') ?>" data-period="all" class="period-pill <?= $currentPeriod === 'all' ? 'active' : '' ?>">All Time</a>
                </div>

                <!-- Sub-tab Switching -->
                <div class="flex items-center gap-6">
                    <button onclick="switchTab('demands')" id="tab-btn-demands" class="tab-btn active flex items-center gap-1.5">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        Customer Demands
                    </button>
                    <button onclick="switchTab('conversations')" id="tab-btn-conversations" class="tab-btn flex items-center gap-1.5">
                        <i data-lucide="messages-square" class="w-4 h-4"></i>
                        Live Client Chats
                    </button>
                    <button onclick="switchTab('knowledge')" id="tab-btn-knowledge" class="tab-btn flex items-center gap-1.5">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        Knowledge & FAQs
                    </button>
                    <button onclick="switchTab('settings')" id="tab-btn-settings" class="tab-btn flex items-center gap-1.5">
                        <i data-lucide="sliders" class="w-4 h-4"></i>
                        Scoring Weights
                    </button>
                </div>
            </div>

            <!-- Highlights Metrics Grid (Website Clean Card Style) -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div class="rounded-2xl border border-[#e3e3da] bg-white p-4 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">Conversations</p>
                        <i data-lucide="messages-square" class="h-4 w-4 text-[#687158]"></i>
                    </div>
                    <strong class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($totalConversations) ?></strong>
                    <small class="text-xs text-[#85877d] mt-1 block"><?= number_format($totalMessages) ?> messages exchanged</small>
                </div>

                <div class="rounded-2xl border border-[#e3e3da] bg-white p-4 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">Demand Signals</p>
                        <i data-lucide="sparkles" class="h-4 w-4 text-emerald-600"></i>
                    </div>
                    <strong class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($totalDemands) ?></strong>
                    <small class="text-xs text-[#85877d] mt-1 block">Style & product requests</small>
                </div>

                <div class="rounded-2xl border border-[#e3e3da] bg-white p-4 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">Unmet Demands</p>
                        <i data-lucide="alert-circle" class="h-4 w-4 text-[#ad6763]"></i>
                    </div>
                    <strong class="mt-2 block text-2xl font-bold text-[#ad6763]"><?= number_format($unmetDemands) ?></strong>
                    <small class="text-xs text-[#85877d] mt-1 block"><?= $unmetPct ?>% queries had 0 inventory</small>
                </div>

                <div class="rounded-2xl border border-[#e3e3da] bg-white p-4 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">In Production</p>
                        <i data-lucide="scissors" class="h-4 w-4 text-indigo-600"></i>
                    </div>
                    <strong class="mt-2 block text-2xl font-bold text-[#252820]"><?= number_format($inProductionCount) ?></strong>
                    <small class="text-xs text-[#85877d] mt-1 block">Queued for next drop</small>
                </div>

                <div class="rounded-2xl border border-[#e3e3da] bg-white p-4 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">Knowledge Items</p>
                        <i data-lucide="book-open" class="h-4 w-4 text-[#687158]"></i>
                    </div>
                    <strong class="mt-2 block text-2xl font-bold text-[#252820]"><?= count($knowledgeItems) ?></strong>
                    <small class="text-xs text-[#85877d] mt-1 block">Policies & FAQ topics</small>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 1: DEMAND INTELLIGENCE -->
            <!-- ============================================================ -->
            <div id="tab-pane-demands" class="space-y-6">

                <!-- Aggregated Demand Trends -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Top Categories -->
                    <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                        <h4 class="text-xs uppercase tracking-wider font-bold text-[#85877d] mb-4 flex items-center gap-2">
                            <i data-lucide="layers" class="w-4 h-4 text-[#687158]"></i>
                            Top Demanded Categories
                        </h4>
                        <div class="space-y-3">
                            <?php if (empty($categoryDemands)): ?>
                                <p class="text-xs text-[#85877d]">No demand signals recorded for this period.</p>
                            <?php else: ?>
                                <?php foreach ($categoryDemands as $cat): ?>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-[#252820]"><?= e($cat['category_name']) ?></span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-[#f0f1eb] text-[#687158] font-bold"><?= $cat['request_count'] ?> req</span>
                                            <?php if ($cat['unmet_count'] > 0): ?>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-[#f2dddd] text-[#a35e5e] font-semibold"><?= $cat['unmet_count'] ?> unmet</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Colors -->
                    <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                        <h4 class="text-xs uppercase tracking-wider font-bold text-[#85877d] mb-4 flex items-center gap-2">
                            <i data-lucide="palette" class="w-4 h-4 text-emerald-600"></i>
                            Demanded Colors
                        </h4>
                        <div class="space-y-3">
                            <?php if (empty($colorDemands)): ?>
                                <p class="text-xs text-[#85877d]">No color signals recorded for this period.</p>
                            <?php else: ?>
                                <?php foreach ($colorDemands as $col): ?>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-[#252820]"><?= e($col['color_name']) ?></span>
                                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-[#f0f1eb] text-[#687158] font-bold"><?= $col['request_count'] ?> req</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Styles -->
                    <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                        <h4 class="text-xs uppercase tracking-wider font-bold text-[#85877d] mb-4 flex items-center gap-2">
                            <i data-lucide="compass" class="w-4 h-4 text-blue-600"></i>
                            Aesthetic Styles
                        </h4>
                        <div class="space-y-3">
                            <?php if (empty($styleDemands)): ?>
                                <p class="text-xs text-[#85877d]">No style signals recorded for this period.</p>
                            <?php else: ?>
                                <?php foreach ($styleDemands as $sty): ?>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-[#252820]"><?= e($sty['style_name']) ?></span>
                                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-[#f0f1eb] text-[#687158] font-bold"><?= $sty['request_count'] ?> req</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Occasions -->
                    <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                        <h4 class="text-xs uppercase tracking-wider font-bold text-[#85877d] mb-4 flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-purple-600"></i>
                            Top Occasions
                        </h4>
                        <div class="space-y-3">
                            <?php if (empty($occasionDemands)): ?>
                                <p class="text-xs text-[#85877d]">No occasion signals recorded for this period.</p>
                            <?php else: ?>
                                <?php foreach ($occasionDemands as $occ): ?>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-[#252820]"><?= e(ucfirst((string)$occ['occasion_name'])) ?></span>
                                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-[#f0f1eb] text-[#687158] font-bold"><?= $occ['request_count'] ?> req</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Customer Demand Stream Table -->
                <section class="rounded-2xl border border-[#e3e3da] bg-white shadow-[0_8px_30px_rgba(37,40,32,.04)] overflow-hidden">
                    <div class="p-5 border-b border-[#ecece5] flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-[#252820]">Real Customer Shopping Demands</h3>
                            <p class="text-xs text-[#85877d]">Actual requests asked by visitors & clients to the AI Stylist</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-[#fbfbf8] text-[11px] uppercase tracking-[.14em] text-[#92948a] border-b border-[#ecece5]">
                                <tr>
                                    <th class="py-3.5 px-5">Customer Request</th>
                                    <th class="py-3.5 px-4">Extracted Category</th>
                                    <th class="py-3.5 px-4">Style / Color</th>
                                    <th class="py-3.5 px-4">Budget</th>
                                    <th class="py-3.5 px-4">Catalog Match</th>
                                    <th class="py-3.5 px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#eeeeE8]">
                                <?php if (empty($recentDemands)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-[#85877d]">No customer requests recorded for this period.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentDemands as $d): ?>
                                        <tr class="transition hover:bg-[#fcfcf9]">
                                            <td class="py-4 px-5">
                                                <strong class="text-sm text-[#252820] font-medium block">"<?= e($d['raw_query']) ?>"</strong>
                                                <span class="text-xs text-[#85877d]"><?= e($d['created_at']) ?></span>
                                            </td>
                                            <td class="py-4 px-4">
                                                <span class="rounded-full px-2.5 py-1 text-[11px] font-medium bg-[#f0f1eb] text-[#687158]">
                                                    <?= e($d['category'] ?: 'General') ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-xs text-[#686b62]">
                                                <?= e(($d['style'] ?: '—') . ' / ' . ($d['color'] ?: '—')) ?>
                                            </td>
                                            <td class="py-4 px-4 text-sm font-semibold text-[#252820]">
                                                <?= $d['budget'] ? '₦' . number_format((float)$d['budget'], 0) : '—' ?>
                                            </td>
                                            <td class="py-4 px-4">
                                                <?php if ($d['had_catalog_match']): ?>
                                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-medium bg-[#e7efe3] text-[#55704e]">In Stock</span>
                                                <?php else: ?>
                                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-medium bg-[#f2dddd] text-[#a35e5e]">Unmet Demand</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-4">
                                                <?php if ($d['status'] === 'in_production'): ?>
                                                    <span class="text-xs font-bold text-indigo-700">In Production</span>
                                                <?php else: ?>
                                                    <button onclick="queueProduction(<?= (int)$d['id'] ?>)" class="rounded-lg border border-[#d9d9d0] bg-white px-3 py-1 text-xs font-semibold text-[#687158] hover:bg-[#f0f1eb]">
                                                        Queue for Drop
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 2: LIVE CLIENT CONVERSATIONS -->
            <!-- ============================================================ -->
            <div id="tab-pane-conversations" class="hidden space-y-4">
                <section class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_30px_rgba(37,40,32,.04)]">
                    <h3 class="text-base font-bold text-[#252820] mb-1">Live Client Styling Sessions</h3>
                    <p class="text-xs text-[#85877d] mb-4">Actual conversations between visitors, VIP clients, and the AI Personal Stylist</p>

                    <div class="space-y-3">
                        <?php if (empty($conversations)): ?>
                            <p class="text-sm text-[#85877d] text-center py-12">No styling conversations recorded for this period.</p>
                        <?php else: ?>
                            <?php foreach ($conversations as $c): ?>
                                <div class="p-4 rounded-xl border border-[#e3e3da] bg-[#fbfbf8] hover:bg-white hover:shadow-md transition flex items-center justify-between">
                                    <div class="space-y-1 max-w-xl">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-[#252820] text-sm"><?= e($c['user_name'] ?: 'Guest Visitor') ?></span>
                                            <span class="text-xs text-[#85877d]">#<?= e(substr($c['session_id'], 0, 14)) ?>...</span>
                                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-[#eef0e9] text-[#687158] font-semibold"><?= $c['message_count'] ?> messages</span>
                                        </div>
                                        <p class="text-xs text-[#686b62] truncate">Last message: "<?= e($c['last_message'] ?: 'Conversation initiated') ?>"</p>
                                        <span class="text-[10px] text-[#85877d]"><?= e($c['updated_at']) ?></span>
                                    </div>

                                    <button onclick="viewConversation(<?= (int)$c['id'] ?>)" class="rounded-xl bg-[#687158] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#596249] flex items-center gap-1.5 shadow-sm">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        Inspect Transcript
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 3: KNOWLEDGE BASE & FAQS -->
            <!-- ============================================================ -->
            <div id="tab-pane-knowledge" class="hidden space-y-4">
                <section class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_30px_rgba(37,40,32,.04)]">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-[#252820]">AI Stylist Knowledge Base</h3>
                            <p class="text-xs text-[#85877d]">Policies, sizing guides, navigation links, and FAQ answers</p>
                        </div>
                        <button onclick="openKnowledgeModal()" class="rounded-xl bg-[#687158] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#596249] flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add Topic
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($knowledgeItems as $item): ?>
                            <div class="p-4 rounded-xl border border-[#e3e3da] bg-[#fbfbf8] space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-[#252820] text-sm"><?= e($item['title']) ?></span>
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-[#f0f1eb] text-[#687158] font-medium"><?= e($item['category']) ?></span>
                                </div>
                                <p class="text-xs text-[#686b62]"><?= e(mb_substr((string)$item['content'], 0, 150)) ?>...</p>
                                <div class="text-[11px] text-[#85877d]">Keywords: <?= e($item['keywords']) ?></div>
                                <?php if (!empty($item['action_destination'])): ?>
                                    <div class="text-[11px] text-[#687158] font-medium">Action: <?= e($item['action_type']) ?> &rarr; <?= e($item['action_destination']) ?></div>
                                <?php endif; ?>
                                <div class="pt-2 flex justify-end gap-2">
                                    <button onclick='editKnowledge(<?= json_encode($item) ?>)' class="text-xs px-2.5 py-1 border border-[#deded6] rounded bg-white text-[#252820] hover:bg-[#f0f1eb]">Edit</button>
                                    <button onclick="deleteKnowledge(<?= (int)$item['id'] ?>)" class="text-xs px-2.5 py-1 bg-[#f2dddd] text-[#a35e5e] rounded hover:bg-rose-600 hover:text-white">Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 4: STYLIST SCORING WEIGHTS -->
            <!-- ============================================================ -->
            <div id="tab-pane-settings" class="hidden space-y-4">
                <section class="rounded-2xl border border-[#e3e3da] bg-white p-6 max-w-2xl mx-auto shadow-[0_8px_30px_rgba(37,40,32,.04)]">
                    <h3 class="text-base font-bold text-[#252820] mb-1">Fashion Stylist Scoring Weights</h3>
                    <p class="text-xs text-[#85877d] mb-6">Tune how the AI balances occasion, budget, colors, and styles</p>

                    <form onsubmit="saveWeights(event)" class="space-y-5">
                        <?php foreach ($weights as $key => $val): ?>
                            <div class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-[#252820]"><?= ucfirst(str_replace('_', ' ', $key)) ?> Weight</span>
                                    <span id="label-<?= $key ?>" class="text-[#687158] font-bold"><?= round((float)$val * 100) ?>%</span>
                                </div>
                                <input type="range" name="weights[<?= $key ?>]" min="0" max="1" step="0.05" value="<?= $val ?>" oninput="updateRangeLabel('<?= $key ?>', this.value)" class="w-full accent-[#687158]">
                            </div>
                        <?php endforeach; ?>

                        <div class="pt-4 flex justify-end">
                            <button type="submit" class="rounded-xl bg-[#687158] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#596249] shadow-sm">Save Weights</button>
                        </div>
                    </form>
                </section>
            </div>

        </section>
    </main>

    <!-- Conversation Inspector Modal -->
    <div id="conv-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="rounded-2xl bg-white max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden shadow-2xl border border-[#e3e3da]">
            <div class="p-4 border-b border-[#ecece5] flex items-center justify-between bg-[#fbfbf8]">
                <div>
                    <h3 id="modal-conv-title" class="font-bold text-[#252820] text-base">Client Conversation</h3>
                    <p id="modal-conv-subtitle" class="text-xs text-[#85877d]">Session ID</p>
                </div>
                <button onclick="closeModal('conv-modal')" class="p-1 rounded-lg text-[#85877d] hover:text-[#252820]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div id="modal-conv-body" class="p-5 overflow-y-auto flex-1 space-y-3 bg-[#f6f6f1]">
                <!-- Messages populated dynamically -->
            </div>
        </div>
    </div>

    <!-- Knowledge Base Modal -->
    <div id="kb-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="rounded-2xl bg-white max-w-xl w-full p-6 shadow-2xl border border-[#e3e3da]">
            <div class="flex items-center justify-between mb-4 border-b border-[#ecece5] pb-3">
                <h3 id="kb-modal-title" class="font-bold text-[#252820] text-base">Add Knowledge Base Topic</h3>
                <button onclick="closeModal('kb-modal')" class="p-1 rounded-lg text-[#85877d] hover:text-[#252820]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form onsubmit="saveKnowledgeTopic(event)" class="space-y-4 text-xs">
                <input type="hidden" id="kb-id" name="id" value="0">

                <div>
                    <label class="block text-[#686b62] mb-1 font-semibold">Category</label>
                    <input type="text" id="kb-category" name="category" required class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#687158]" placeholder="e.g. shipping, returns, styling">
                </div>

                <div>
                    <label class="block text-[#686b62] mb-1 font-semibold">Topic Title</label>
                    <input type="text" id="kb-title" name="title" required class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#687158]" placeholder="e.g. Return Policy">
                </div>

                <div>
                    <label class="block text-[#686b62] mb-1 font-semibold">Trigger Keywords (comma-separated)</label>
                    <input type="text" id="kb-keywords" name="keywords" required class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#687158]" placeholder="return, refund, exchange">
                </div>

                <div>
                    <label class="block text-[#686b62] mb-1 font-semibold">Content / Response</label>
                    <textarea id="kb-content" name="content" rows="4" required class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#687158]" placeholder="Detailed answer given to customer..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#686b62] mb-1 font-semibold">Action Type</label>
                        <select id="kb-action-type" name="action_type" class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#687158]">
                            <option value="">None</option>
                            <option value="navigate">Direct Navigation</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#686b62] mb-1 font-semibold">Destination URL</label>
                        <input type="text" id="kb-action-destination" name="action_destination" class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#687158]" placeholder="/track-order">
                    </div>
                </div>
                <div class="pt-3 flex justify-end gap-2 border-t border-[#ecece5]">
                    <button type="button" onclick="closeModal('kb-modal')" class="rounded-xl border border-[#deded6] px-4 py-2 text-sm text-[#686b62] hover:bg-[#f0f1eb]">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#687158] px-5 py-2 text-sm font-semibold text-white hover:bg-[#596249]">Save Topic</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('[id^="tab-pane-"]').forEach(pane => pane.classList.add('hidden'));

            const btn = document.getElementById('tab-btn-' + tabId);
            const pane = document.getElementById('tab-pane-' + tabId);
            if (btn && pane) {
                btn.classList.add('active');
                pane.classList.remove('hidden');
            }

            // Keep timeframe pill links synchronized with the active tab
            document.querySelectorAll('.period-pill').forEach(a => {
                const period = a.getAttribute('data-period') || '7d';
                a.href = '<?= e($aiUrl) ?>?period=' + encodeURIComponent(period) + '&tab=' + encodeURIComponent(tabId);
            });

            // Update browser URL query param without refreshing
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.replaceState(null, '', url.toString());
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const targetTab = urlParams.get('tab') || '<?= e($currentTab ?? 'demands') ?>';
            if (targetTab && targetTab !== 'demands') {
                switchTab(targetTab);
            }
        });

        function updateRangeLabel(name, val) {
            document.getElementById('label-' + name).textContent = Math.round(val * 100) + '%';
        }

        async function updateDemandStatus(id, status) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);

            try {
                const res = await fetch('<?= e($aiUrl . '/demand-status') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
            } catch (err) {
                console.error(err);
            }
        }

        function queueProduction(id) {
            updateDemandStatus(id, 'in_production');
            alert('Demand queued for upcoming capsule production!');
            location.reload();
        }

        async function viewConversation(id) {
            const modal = document.getElementById('conv-modal');
            const body = document.getElementById('modal-conv-body');
            body.innerHTML = '<div class="text-center py-8 text-[#85877d]">Loading transcript...</div>';
            modal.classList.remove('hidden');

            try {
                const res = await fetch('<?= e($aiUrl . '/conversation') ?>?id=' + id);
                const data = await res.json();
                if (!data.success) {
                    body.innerHTML = '<div class="text-rose-600 text-center py-8">Failed to load transcript.</div>';
                    return;
                }

                document.getElementById('modal-conv-title').textContent = data.conversation.user_name || data.conversation.user_email || 'Client Consultation';
                document.getElementById('modal-conv-subtitle').textContent = 'Session: ' + data.conversation.session_id;

                let html = '';
                data.messages.forEach(m => {
                    const isUser = m.sender === 'user';
                    html += `
                        <div class="flex flex-col ${isUser ? 'items-end' : 'items-start'} space-y-1">
                            <span class="text-[10px] text-[#85877d] uppercase font-bold">${isUser ? 'Client' : 'Noir Stylist'} • ${m.created_at}</span>
                            <div class="p-3.5 rounded-2xl max-w-[85%] text-sm ${isUser ? 'bg-[#687158] text-white font-medium shadow-sm' : 'bg-white border border-[#e3e3da] text-[#252820] shadow-sm'}">
                                ${m.content}
                            </div>
                        </div>
                    `;
                });
                body.innerHTML = html;
            } catch (e) {
                body.innerHTML = '<div class="text-rose-600 text-center py-8">Error loading transcript.</div>';
            }
        }

        function openKnowledgeModal() {
            document.getElementById('kb-id').value = '0';
            document.getElementById('kb-title').value = '';
            document.getElementById('kb-keywords').value = '';
            document.getElementById('kb-content').value = '';
            document.getElementById('kb-action-type').value = '';
            document.getElementById('kb-action-destination').value = '';
            document.getElementById('kb-modal-title').textContent = 'Add Knowledge Base Topic';
            document.getElementById('kb-modal').classList.remove('hidden');
        }

        function editKnowledge(item) {
            document.getElementById('kb-id').value = item.id;
            document.getElementById('kb-category').value = item.category;
            document.getElementById('kb-title').value = item.title;
            document.getElementById('kb-keywords').value = item.keywords;
            document.getElementById('kb-content').value = item.content;
            document.getElementById('kb-action-type').value = item.action_type || '';
            document.getElementById('kb-action-destination').value = item.action_destination || '';
            document.getElementById('kb-modal-title').textContent = 'Edit Knowledge Base Topic';
            document.getElementById('kb-modal').classList.remove('hidden');
        }

        async function saveKnowledgeTopic(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            try {
                const res = await fetch('<?= e($aiUrl . '/save-knowledge') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Failed to save topic.');
                }
            } catch (err) {
                alert('An error occurred.');
            }
        }

        async function deleteKnowledge(id) {
            if (!confirm('Are you sure you want to remove this topic from the knowledge base?')) return;
            const formData = new FormData();
            formData.append('id', id);

            try {
                const res = await fetch('<?= e($aiUrl . '/delete-knowledge') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                }
            } catch (err) {
                alert('Failed to delete topic.');
            }
        }

        async function saveWeights(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const res = await fetch('<?= e($aiUrl . '/save-weights') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                alert(data.message || 'Scoring weights updated!');
            } catch (err) {
                alert('Failed to update scoring weights.');
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>
