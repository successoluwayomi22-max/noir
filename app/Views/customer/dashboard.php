<?php
declare(strict_types=1);
$orders = $orders ?? $recentOrders ?? [];
$products = $products ?? [];
$stats = $stats ?? [];
$stats['pending'] = $stats['pending'] ?? $stats['active_orders'] ?? 0;
$stats['delivered'] = $stats['delivered'] ?? $stats['delivered_orders'] ?? 0;
$stats['points'] = $stats['points'] ?? ($customerStats['points'] ?? 0);
$stats['wishlist'] = $stats['wishlist'] ?? 0;
?>
<!-- Luxury Client Overview Header -->
<div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-r from-[#171914] via-[#21241c] to-[#1a1d16] p-7 sm:p-9 text-white shadow-xl border border-white/10">
    <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-[#687158]/20 blur-3xl pointer-events-none"></div>
    <div class="absolute right-32 -bottom-16 h-48 w-48 rounded-full bg-[#a89d70]/15 blur-2xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-2.5 mb-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-[#c7ceb8] backdrop-blur-md border border-white/10">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#a89d70] animate-pulse"></span>
                    Private Client Atelier
                </span>
                <span class="text-xs text-white/40">&bull;</span>
                <span class="text-xs font-medium text-white/60">Dossier #<?= e(substr(md5((string)($customer['id'] ?? 1)), 0, 8)) ?></span>
            </div>
            
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold tracking-tight text-white leading-tight">
                <?= __('Welcome back') ?>, <span class="font-serif-luxury italic font-normal text-[#e6e2d3]"><?= e(explode(' ', $customer['name'])[0]) ?></span>.
            </h1>
            <p class="mt-2 text-xs sm:text-sm text-white/70 max-w-xl leading-relaxed">
                <?= __('Your private maison portal for order dispatches, concierge curation, and VIP privilege privileges.') ?>
            </p>
        </div>

        <!-- Quick Access CTAs -->
        <div class="flex flex-wrap items-center gap-3">
            <a href="<?= e(($base ?: '') . '/customer/saved-items') ?>" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-xs font-semibold text-white shadow-sm backdrop-blur-md transition duration-200 hover:bg-white/20 hover:border-white/30">
                <i data-lucide="bookmark" class="h-3.5 w-3.5 text-[#a89d70]"></i>
                <span><?= __('Saved Pieces') ?></span>
                <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.2 text-[10px] font-bold"><?= (int)$stats['wishlist'] ?></span>
            </a>
            <a href="<?= e(($base ?: '') . '/customer/orders') ?>" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] px-5 py-2.5 text-xs font-semibold text-white shadow-lg shadow-[#687158]/30 transition duration-200 hover:bg-[#575f49] hover:scale-[1.02]">
                <i data-lucide="shopping-bag" class="h-3.5 w-3.5"></i>
                <span><?= __('Order Dossier') ?> &rarr;</span>
            </a>
        </div>
    </div>
</div>

<?php if (!empty($unreadNotifications) && (int)$unreadNotifications > 0 && !empty($notifications)): ?>
    <?php $latestAlert = $notifications[0]; ?>
    <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-[#a89d70]/30 bg-gradient-to-r from-[#20231b] to-[#2b3024] p-4 sm:p-5 text-white shadow-md">
        <div class="flex items-center gap-3.5">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#a89d70]/20 text-[#a89d70] border border-[#a89d70]/30">
                <i data-lucide="sparkles" class="h-5 w-5"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-[#a89d70]/25 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-[#d8cfab]">Concierge Bulletin</span>
                    <small class="text-[10px] text-white/50"><?= e($latestAlert['time'] ?? 'Just now') ?></small>
                </div>
                <h4 class="mt-0.5 text-sm font-semibold text-white"><?= e($latestAlert['title']) ?></h4>
                <p class="text-xs text-white/70 line-clamp-1"><?= e($latestAlert['body']) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-auto">
            <a href="<?= e(($base ?: '') . '/customer/notifications') ?>" class="rounded-xl bg-[#a89d70] px-4 py-2 text-xs font-bold text-[#1a1d16] hover:bg-[#b8ac7c] transition shadow">
                View Update &rarr;
            </a>
            <button class="customer-ajax rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white hover:bg-white/20 transition" data-id="<?= (int)$latestAlert['id'] ?>" data-url="<?= e(($base ?: '') . '/customer/ajax/notification-read') ?>">
                Dismiss
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- 5 Enhanced Luxury Metric Cards -->
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <div class="customer-card-hover rounded-2xl border border-[#e5e7de] bg-white p-5 shadow-sm hover:border-[#687158]/40">
        <div class="mb-3 flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9383]"><?= __('Total Orders') ?></span>
            <span class="rounded-xl bg-[#687158]/10 p-2 text-[#687158]"><i data-lucide="shopping-bag" class="h-4 w-4"></i></span>
        </div>
        <div class="flex items-baseline gap-2">
            <strong class="text-2xl font-bold tracking-tight text-[#1c1e18]"><?= (int)$stats['orders'] ?></strong>
            <span class="text-[10px] font-semibold text-[#687158]">Maison Orders</span>
        </div>
        <small class="mt-1 block text-[11px] text-[#8e9383]"><?= __('All-time archive purchases') ?></small>
    </div>

    <div class="customer-card-hover rounded-2xl border border-[#e5e7de] bg-white p-5 shadow-sm hover:border-[#a89d70]/40">
        <div class="mb-3 flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9383]"><?= __('In Dispatch') ?></span>
            <span class="rounded-xl bg-[#a89d70]/15 p-2 text-[#8c8052]"><i data-lucide="clock-3" class="h-4 w-4"></i></span>
        </div>
        <div class="flex items-baseline gap-2">
            <strong class="text-2xl font-bold tracking-tight text-[#1c1e18]"><?= (int)$stats['pending'] ?></strong>
            <?php if((int)$stats['pending'] > 0): ?>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-bold text-amber-700 border border-amber-200">Active</span>
            <?php endif; ?>
        </div>
        <small class="mt-1 block text-[11px] text-[#8e9383]"><?= __('En route or being tailored') ?></small>
    </div>

    <div class="customer-card-hover rounded-2xl border border-[#e5e7de] bg-white p-5 shadow-sm hover:border-[#57724d]/40">
        <div class="mb-3 flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9383]"><?= __('Delivered') ?></span>
            <span class="rounded-xl bg-[#e4ede1] p-2 text-[#57724d]"><i data-lucide="package-check" class="h-4 w-4"></i></span>
        </div>
        <div class="flex items-baseline gap-2">
            <strong class="text-2xl font-bold tracking-tight text-[#1c1e18]"><?= (int)$stats['delivered'] ?></strong>
            <span class="text-[10px] font-semibold text-[#57724d]">Completed</span>
        </div>
        <small class="mt-1 block text-[11px] text-[#8e9383]"><?= __('Received into wardrobe') ?></small>
    </div>

    <div class="customer-card-hover rounded-2xl border border-[#e5e7de] bg-white p-5 shadow-sm hover:border-rose-200">
        <div class="mb-3 flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9383]"><?= __('Curated Wishlist') ?></span>
            <span class="rounded-xl bg-[#f7ebea] p-2 text-[#b04b3f]"><i data-lucide="heart" class="h-4 w-4"></i></span>
        </div>
        <div class="flex items-baseline gap-2">
            <strong class="text-2xl font-bold tracking-tight text-[#1c1e18]"><?= (int)$stats['wishlist'] ?></strong>
            <span class="text-[10px] font-semibold text-[#b04b3f]">Saved</span>
        </div>
        <small class="mt-1 block text-[11px] text-[#8e9383]"><?= __('Personal fashion collection') ?></small>
    </div>

    <div class="customer-card-hover rounded-2xl border border-[#a89d70]/40 bg-gradient-to-br from-[#faf7f0] via-[#f7f3e8] to-[#eee8d5] p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#7a6f43]"><?= __('Privilege Points') ?></span>
            <span class="rounded-xl bg-[#a89d70]/25 p-2 text-[#685e34]"><i data-lucide="sparkles" class="h-4 w-4"></i></span>
        </div>
        <div class="flex items-baseline gap-2">
            <strong class="text-2xl font-bold tracking-tight text-[#1c1e18]"><?= number_format((int)$stats['points']) ?></strong>
            <span class="text-[10px] font-semibold text-[#7a6f43]">VIP Pts</span>
        </div>
        <small class="mt-1 block text-[11px] text-[#7a6f43]"><?= __('Redeemable for store vouchers') ?></small>
    </div>
</div>

<!-- Middle Row: Recent Orders & Privilege Rewards Showcase -->
<div class="mt-8 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
    <!-- Recent Orders Showcase Card -->
    <section class="rounded-3xl border border-[#e5e7de] bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#687158]"></span>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#687158]"><?= __('Recent Acquisitions') ?></p>
                </div>
                <h2 class="mt-1 text-lg font-semibold text-[#1c1e18]"><?= __('Order History & Tracking') ?></h2>
            </div>
            <a href="<?= e(($base ?: '') . '/customer/orders') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#687158] hover:text-[#525a44] transition">
                <span><?= __('View complete history') ?></span>
                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
            </a>
        </div>

        <?php if(!$orders): ?>
            <div class="rounded-2xl border border-dashed border-[#e2e4dc] bg-[#fafaf8] p-10 text-center">
                <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-white shadow-sm text-[#8f9385] border border-[#e8eae2]">
                    <i data-lucide="package-open" class="h-6 w-6"></i>
                </div>
                <h3 class="text-sm font-semibold text-[#1c1e18]"><?= __('No orders placed yet') ?></h3>
                <p class="mt-1 text-xs text-[#787c70] max-w-sm mx-auto"><?= __('Begin exploring our curated boutique collection and have your timeless pieces tailored to perfection.') ?></p>
                <a href="<?= e(($base ?: '') . '/store') ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#687158] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#525a44] transition">
                    <i data-lucide="store" class="h-3.5 w-3.5"></i>
                    <span><?= __('Explore Boutique') ?></span>
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-3.5">
                <?php foreach(array_slice($orders, 0, 4) as $order): ?>
                    <div class="group flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border border-[#ebece5] bg-[#fafaf7] p-4 transition-all duration-200 hover:border-[#687158]/40 hover:bg-white hover:shadow-md">
                        <div class="flex items-center gap-3.5">
                            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white border border-[#e5e7de] text-[#687158] shadow-sm group-hover:bg-[#687158] group-hover:text-white transition">
                                <i data-lucide="package" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <strong class="font-semibold text-sm text-[#1c1e18]"><?= e($order['number']) ?></strong>
                                    <?= customer_badge($order['status']) ?>
                                </div>
                                <div class="mt-1 flex items-center gap-3 text-xs text-[#7a7e72]">
                                    <span><?= e($order['date']) ?></span>
                                    <span>&bull;</span>
                                    <span class="font-semibold text-[#1c1e18]"><?= e(customer_money($order['total'], $order['currency'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 self-end sm:self-auto">
                            <a href="<?= e(($base ?: '') . '/customer/orders/' . $order['number']) ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-[#e1e3da] bg-white px-3.5 py-2 text-xs font-semibold text-[#484b40] shadow-sm transition hover:bg-[#687158] hover:text-white hover:border-[#687158]">
                                <span><?= __('Tracking Details') ?></span>
                                <i data-lucide="chevron-right" class="h-3 w-3"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- VIP Membership & Privilege Card -->
    <section class="rounded-3xl bg-gradient-to-br from-[#1b1e16] via-[#262a20] to-[#171913] p-7 text-[#f4f3ed] shadow-xl flex flex-col justify-between relative overflow-hidden border border-white/10">
        <div class="absolute -right-10 -top-10 h-44 w-44 rounded-full bg-[#a89d70]/20 blur-3xl pointer-events-none"></div>
        
        <div>
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#a89d70]/20 border border-[#a89d70]/30 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-[#d6cdab]">
                    <i data-lucide="crown" class="h-3 w-3 text-[#a89d70]"></i>
                    Private Client Circle
                </span>
                <i data-lucide="sparkles" class="h-4 w-4 text-[#a89d70]"></i>
            </div>

            <div class="mt-6">
                <p class="text-xs uppercase tracking-wider text-white/50"><?= __('Available Privilege Balance') ?></p>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="font-serif-luxury text-4xl sm:text-5xl italic text-[#f1ede2]"><?= number_format((int)$stats['points']) ?></span>
                    <span class="text-sm font-semibold text-[#a89d70]">Pts</span>
                </div>
            </div>

            <!-- Tier progress indicator -->
            <div class="mt-6">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="text-white/70">Circle Progression</span>
                    <span class="text-[#a89d70] font-semibold"><?= min(100, round(((int)$stats['points'] / 5000) * 100)) ?>%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-[#a89d70] to-[#d8cfab] transition-all duration-500" style="width: <?= min(100, max(8, round(((int)$stats['points'] / 5000) * 100))) ?>%"></div>
                </div>
                <p class="mt-2 text-[11px] text-white/50">Next Milestone: <strong>Haute Couturier Tier</strong> at 5,000 points.</p>
            </div>
        </div>

        <div class="mt-8 pt-5 border-t border-white/10 flex items-center justify-between">
            <span class="text-xs text-white/60 flex items-center gap-1">
                <i data-lucide="shield-check" class="h-3.5 w-3.5 text-[#a89d70]"></i>
                <?= __('Complimentary Worldwide Express') ?>
            </span>
            <a href="<?= e(($base ?: '') . '/customer/coupons') ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-[#a89d70] px-4 py-2 text-xs font-bold text-[#1a1d16] shadow-md transition hover:bg-[#bba97e] hover:scale-[1.02]">
                <span><?= __('Redeem') ?></span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
            </a>
        </div>
    </section>
</div>

<!-- Bottom Row: Concierge Notifications & Curated Recommendations -->
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <!-- Concierge Bulletin -->
    <section class="rounded-3xl border border-[#e5e7de] bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#687158]"></span>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#687158]"><?= __('Concierge Feed') ?></p>
                </div>
                <h2 class="mt-1 text-lg font-semibold text-[#1c1e18]"><?= __('Notifications & Updates') ?></h2>
            </div>
            <div class="flex items-center gap-3">
                <?php if((int)$unreadNotifications > 0): ?>
                    <span class="rounded-full bg-[#f1f4eb] px-2.5 py-0.5 text-[10px] font-bold text-[#556547]"><?= (int)$unreadNotifications ?> <?= __('unread') ?></span>
                <?php endif; ?>
                <a href="<?= e(($base ?: '') . '/customer/notifications') ?>" class="text-xs font-semibold text-[#687158] hover:underline"><?= __('View all') ?> &rarr;</a>
            </div>
        </div>

        <?php if(!$notifications): ?>
            <div class="rounded-2xl border border-dashed border-[#e2e4dc] bg-[#fafaf8] p-8 text-center">
                <i data-lucide="bell-off" class="mx-auto h-7 w-7 text-[#b0b4a8] mb-2"></i>
                <p class="text-xs text-[#74786c]"><?= __('Your concierge notification tray is pristine and clear.') ?></p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach(array_slice($notifications, 0, 3) as $notification): ?>
                    <article class="notification-preview flex items-start gap-3 rounded-2xl border p-4 transition-all duration-200 <?= $notification['read_at'] ? 'border-[#eeeee7] bg-[#fbfbf9]' : 'border-[#dce2d2] bg-[#f7f9f2] shadow-sm' ?>" data-notification-id="<?= (int)$notification['id'] ?>">
                        <span class="rounded-xl p-2 mt-0.5 shrink-0 <?= $notification['type']==='success' ? 'bg-[#e5eee2] text-[#4d6c44]' : ($notification['type']==='reward' ? 'bg-[#f5f1e4] text-[#8a7a44]' : 'bg-[#eef0ea] text-[#687158]') ?>">
                            <i data-lucide="<?= $notification['type']==='success' ? 'check-circle-2' : ($notification['type']==='reward' ? 'sparkles' : 'bell') ?>" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-[#1c1e18]"><?= e($notification['title']) ?></p>
                            <?php if(!empty($notification['body'])): ?>
                                <p class="text-[11px] text-[#74786c] mt-0.5 line-clamp-2"><?= e($notification['body']) ?></p>
                            <?php endif; ?>
                            <small class="mt-1 block text-[10px] text-[#8e9383]"><?= e($notification['time']) ?></small>
                        </div>
                        <?php if(!$notification['read_at']): ?>
                            <button class="customer-ajax text-[10px] font-semibold text-[#687158] hover:underline shrink-0" data-id="<?= (int)$notification['id'] ?>" data-url="<?= e(($base ?: '') . '/customer/ajax/notification-read') ?>">
                                <?= __('Mark read') ?>
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Curated Product Recommendations -->
    <section class="rounded-3xl border border-[#e5e7de] bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#687158]"></span>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#687158]"><?= __('Curated Curation') ?></p>
                </div>
                <h2 class="mt-1 text-lg font-semibold text-[#1c1e18]"><?= __('Recommended for Your Wardrobe') ?></h2>
            </div>
            <a href="<?= e(($base ?: '') . '/store') ?>" class="text-xs font-semibold text-[#687158] hover:underline flex items-center gap-1">
                <span><?= __('Shop All') ?></span>
                <i data-lucide="arrow-right" class="h-3 w-3"></i>
            </a>
        </div>

        <?php if(!$products): ?>
            <p class="py-8 text-center text-xs text-[#74786c]"><?= __('New season arrivals will appear here once published.') ?></p>
        <?php else: ?>
            <div class="grid grid-cols-3 gap-3.5">
                <?php foreach(array_slice($products, 0, 3) as $product): 
                    $rawImg = !empty($product['featured_image']) ? $product['featured_image'] : (!empty($product['image']) ? $product['image'] : catalogue_image_for($product));
                    $img = media_url($base ?: '', $rawImg);
                ?>
                    <a href="<?= e(($base ?: '') . '/store/product/' . ($product['slug'] ?? $product['id'])) ?>" class="group block text-center">
                        <div class="aspect-square overflow-hidden rounded-2xl bg-[#edece4] relative border border-[#e6e7df] shadow-sm transition duration-300 group-hover:border-[#687158] group-hover:shadow-md">
                            <img src="<?= e($img) ?>" alt="<?= e($product['name']) ?>" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&q=80';">
                        </div>
                        <p class="mt-2.5 truncate text-xs font-semibold text-[#1c1e18] group-hover:text-[#687158] transition"><?= e($product['name']) ?></p>
                        <small class="block text-[11px] font-bold text-[#687158] mt-0.5"><?= format_currency($product['price']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
