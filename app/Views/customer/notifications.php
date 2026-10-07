<?php
$db = \Noir\Core\Container::get('db');
$dropsStmt = $db->query("SELECT * FROM drop_alerts WHERE status IN ('live', 'upcoming') ORDER BY CASE status WHEN 'live' THEN 1 ELSE 2 END, starts_at ASC LIMIT 4");
$activeDrops = $dropsStmt ? $dropsStmt->fetchAll(\PDO::FETCH_ASSOC) : [];
?>
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#c5a880]"><?= __('My Account') ?></p>
        <h1 class="mt-2 text-3xl font-medium"><?= __('Notifications') ?></h1>
        <p class="mt-2 text-sm text-[#77796f]"><?= __('Real-time alerts for exclusive runway drops, flash sales, and order tracking.') ?></p>
    </div>
    <button class="customer-ajax rounded-xl border border-[#dcdcd3] bg-[#fbfbf7] px-4 py-2.5 text-xs font-semibold text-[#687158] hover:bg-[#eaeae2] transition" data-url="<?= e(($base ?: '') . '/customer/ajax/notification-read-all') ?>">
        <?= __('Mark as read') ?>
    </button>
</div>

<!-- ── ACTIVE VIP DROPS & FLASH SALES CAROUSEL ─────────────────────────────── -->
<?php if (!empty($activeDrops)): ?>
<div class="mb-8">
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="zap" class="h-4 w-4 text-[#c5a880]"></i>
            <h2 class="text-base font-semibold text-[#1c2018]">Live & Upcoming Runway Drops</h2>
        </div>
        <span class="text-xs text-[#c5a880] font-medium">VIP Early Access Active</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($activeDrops as $drop): ?>
            <?php 
            $isLive = ($drop['status'] === 'live');
            $startTime = strtotime($drop['starts_at']);
            $endTime = $drop['ends_at'] ? strtotime($drop['ends_at']) : null;
            $diffSeconds = $isLive ? ($endTime ? max(0, $endTime - time()) : 0) : max(0, $startTime - time());
            $hours = floor($diffSeconds / 3600);
            $mins = floor(($diffSeconds % 3600) / 60);
            ?>
            <div class="relative overflow-hidden rounded-2xl border <?= $isLive ? 'border-emerald-500/40 bg-[#161a14] text-white' : 'border-[#2d3227] bg-[#1a1e17] text-white' ?> p-5 shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full <?= $isLive ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-[#c5a880]/20 text-[#c5a880] border border-[#c5a880]/30' ?> px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                                <?= $isLive ? '● LIVE FLASH SALE' : '⏳ UPCOMING DROP' ?>
                            </span>
                            <?php if (!empty($drop['discount_percentage']) && $drop['discount_percentage'] > 0): ?>
                                <span class="rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 px-2 py-0.5 text-[10px] font-bold">
                                    -<?= (int)$drop['discount_percentage'] ?>% OFF
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="mt-2.5 text-base font-semibold text-white"><?= e($drop['title']) ?></h3>
                        <p class="mt-1 text-xs text-gray-300 line-clamp-2"><?= e($drop['subtitle'] ?: $drop['description']) ?></p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-3">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-400"><?= $isLive ? 'Ends In:' : 'Starts In:' ?></span>
                        <span class="font-mono font-bold text-amber-300"><?= sprintf('%02dh %02dm', $hours, $mins) ?></span>
                        <?php if (!empty($drop['stock_limit'])): ?>
                            <span class="text-[10px] text-gray-400">• <?= (int)$drop['stock_limit'] ?> pieces</span>
                        <?php endif; ?>
                    </div>

                    <a href="<?= e(($base ?: '') . '/catalogue') ?>" class="rounded-xl <?= $isLive ? 'bg-emerald-500 hover:bg-emerald-400 text-black' : 'bg-[#c5a880] hover:bg-[#b0936b] text-black' ?> px-3.5 py-1.5 text-xs font-bold transition shadow">
                        <?= $isLive ? 'Shop Drop Now →' : 'View Collection' ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── NOTIFICATIONS FEED ─────────────────────────────────────────────────── -->
<div class="mb-4 flex items-center justify-between">
    <h2 class="text-base font-semibold text-[#1c2018]">Activity & Order Updates</h2>
    <span class="text-xs text-[#77796f]"><?= count($notifications) ?> notification(s)</span>
</div>

<section class="space-y-3">
    <?php if (empty($notifications)): ?>
        <div class="rounded-2xl border border-dashed border-[#c9cdbd] bg-[#fbfbf7] p-10 text-center">
            <i data-lucide="bell-off" class="mx-auto h-8 w-8 text-[#92948a]"></i>
            <h2 class="mt-4 font-medium"><?= __('You are all caught up.') ?></h2>
            <p class="mt-2 text-sm text-[#77796f]"><?= __('New account, drop, and order updates will appear here.') ?></p>
        </div>
    <?php endif; ?>

    <?php foreach ($notifications as $notification): ?>
        <article class="notification-item flex items-start gap-4 rounded-2xl border <?= $notification['read_at'] ? 'border-[#e5e5dd] bg-[#fbfbf7]' : 'border-[#c9d2bb] bg-[#f1f4eb]' ?> p-5 transition hover:shadow-sm" data-notification-id="<?= (int)$notification['id'] ?>">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full <?= $notification['type'] === 'success' ? 'bg-[#dfead8] text-[#59704e]' : ($notification['type'] === 'reward' || $notification['type'] === 'vip_drop' ? 'bg-[#f0e6c9] text-[#9b7b3f]' : 'bg-[#e6e9e0] text-[#687158]') ?>">
                <i data-lucide="<?= $notification['type'] === 'success' ? 'check-circle-2' : ($notification['type'] === 'reward' || $notification['type'] === 'vip_drop' ? 'sparkles' : 'bell') ?>" class="h-4 w-4"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap justify-between gap-3">
                    <h2 class="text-sm font-medium"><?= e($notification['title']) ?></h2>
                    <small class="text-[10px] text-[#92948a]"><?= e($notification['time'] ?? date('M j, Y • g:i A', strtotime($notification['created_at'] ?? 'now'))) ?></small>
                </div>
                <?php if (!empty($notification['body'])): ?>
                    <p class="mt-2 text-xs leading-5 text-[#77796f]"><?= e($notification['body']) ?></p>
                <?php endif; ?>
                <?php if (empty($notification['read_at'])): ?>
                    <button class="customer-ajax mt-3 text-xs font-semibold text-[#687158] hover:underline" data-id="<?= (int)$notification['id'] ?>" data-url="<?= e(($base ?: '') . '/customer/ajax/notification-read') ?>">
                        <?= __('Mark as read') ?>
                    </button>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
