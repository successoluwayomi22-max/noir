<?php
$items = $order['items'] ?? [];
$subtotal = array_sum(array_map(static fn(array $item): float => (float)$item['price'] * (int)$item['quantity'], $items));
$total = (float)$order['total'];
$delivery = max(0, $total - $subtotal);
$currency = (string)($order['currency'] ?? 'NGN');
$address = $order['shipping'] ?? [];
$status = strtolower((string)$order['status']);

// Derived PIN
$pinSeed = preg_replace('/[^0-9]/', '', (string)$order['number']);
$securityPin = substr(str_pad($pinSeed ?: (string)($order['id'] ?? '8492'), 4, '7', STR_PAD_RIGHT), -4);
?>
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a class="text-xs text-[#c5a880] hover:underline" href="<?= e(($base ?: '') . '/customer/orders') ?>">&larr; Back to orders</a>
        <p class="mt-6 text-[10px] font-bold uppercase tracking-[.2em] text-[#c5a880]">Order details</p>
        <h1 class="mt-2 text-3xl font-medium"><?= e($order['number']) ?></h1>
        <p class="mt-2 text-sm text-[#77796f]">Placed <?= e($order['date']) ?></p>
    </div>
    <div class="flex items-center gap-3">
        <?= customer_badge((string)$order['status']) ?>
        <button onclick="document.getElementById('live-tracking-section').scrollIntoView({behavior: 'smooth'})" class="inline-flex items-center gap-2 rounded-xl bg-[#c5a880] px-4 py-2 text-xs font-semibold text-black transition hover:bg-[#b0936b]">
            <i data-lucide="map-pin" class="h-4 w-4"></i>
            <span>Live Tracking Map</span>
        </button>
    </div>
</div>

<!-- ── LIVE INTERACTIVE ORDER TRACKING MAP ─────────────────────────────────── -->
<div id="live-tracking-section" class="mb-8 overflow-hidden rounded-3xl border border-[#2a2d24] bg-[#141612] text-white shadow-2xl">
    <div class="border-b border-white/10 px-6 py-4 flex flex-wrap items-center justify-between gap-4 bg-[#1a1d17]">
        <div class="flex items-center gap-3">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <div>
                <h3 class="text-sm font-semibold tracking-wide text-white">Live White-Glove GPS Dispatch Tracker</h3>
                <p class="text-[11px] text-[#9ca3af]">Real-time telemetry • Secured by Biometric Seal & OTP</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="rounded-xl border border-[#c5a880]/30 bg-[#c5a880]/10 px-3 py-1.5 text-center">
                <span class="block text-[9px] uppercase tracking-widest text-[#c5a880] font-bold">Delivery PIN</span>
                <span class="font-mono text-sm font-extrabold tracking-widest text-[#f3ede2]"><?= e($securityPin) ?></span>
            </div>
            <div class="rounded-xl border border-white/10 bg-black/40 px-3 py-1.5 text-center">
                <span class="block text-[9px] uppercase tracking-widest text-gray-400">Carrier</span>
                <span class="text-xs font-semibold text-white">NOIR VIP Fleet</span>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1.6fr_1fr]">
        <!-- Map Canvas Visualizer -->
        <div class="relative min-h-[340px] bg-[#0d0f0c] p-4 flex flex-col justify-between overflow-hidden">
            <!-- Luxury Dark Map Grid & Vector Roads -->
            <svg class="absolute inset-0 h-full w-full opacity-40" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#252b22" stroke-width="0.8"/>
                    </pattern>
                    <linearGradient id="routeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#687158"/>
                        <stop offset="60%" stop-color="#c5a880"/>
                        <stop offset="100%" stop-color="#10b981"/>
                    </linearGradient>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
                
                <path id="delivery-route" d="M 80 240 C 160 210, 240 180, 320 140 S 440 120, 520 80" 
                      stroke="url(#routeGrad)" stroke-width="4" stroke-linecap="round" stroke-dasharray="8 6" fill="none" class="animate-pulse" />
            </svg>

            <!-- Origin Warehouse Marker -->
            <div class="absolute left-[60px] top-[220px] z-10 flex flex-col items-center">
                <div class="grid h-8 w-8 place-items-center rounded-full border-2 border-[#c5a880] bg-[#1a1d17] shadow-lg shadow-black">
                    <i data-lucide="building" class="h-4 w-4 text-[#c5a880]"></i>
                </div>
                <span class="mt-1 rounded bg-black/80 px-1.5 py-0.5 text-[9px] font-semibold text-[#c5a880]">NOIR Vault VI</span>
            </div>

            <!-- Moving Live Courier Marker -->
            <div class="absolute left-[300px] top-[125px] z-20 flex flex-col items-center animate-bounce">
                <div class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 border-emerald-400 bg-black shadow-xl shadow-emerald-500/20">
                    <i data-lucide="truck" class="h-5 w-5 text-emerald-400"></i>
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                </div>
                <span class="mt-1 rounded bg-emerald-950/90 border border-emerald-500/40 px-2 py-0.5 text-[9px] font-bold text-emerald-300">Driver En Route</span>
            </div>

            <!-- Destination Customer Marker -->
            <div class="absolute right-[60px] top-[65px] z-10 flex flex-col items-center">
                <div class="grid h-8 w-8 place-items-center rounded-full border-2 border-white bg-emerald-600 shadow-lg shadow-black">
                    <i data-lucide="map-pin" class="h-4 w-4 text-white"></i>
                </div>
                <span class="mt-1 rounded bg-black/80 px-1.5 py-0.5 text-[9px] font-semibold text-white">Your Address</span>
            </div>

            <!-- Floating Quick Stats Overlay -->
            <div class="relative z-10 mt-auto flex flex-wrap gap-2 pt-4">
                <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-black/80 backdrop-blur px-3 py-1.5 text-xs">
                    <i data-lucide="clock" class="h-3.5 w-3.5 text-emerald-400"></i>
                    <span>ETA: <strong class="text-white">~22 Mins</strong></span>
                </div>
                <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-black/80 backdrop-blur px-3 py-1.5 text-xs">
                    <i data-lucide="navigation" class="h-3.5 w-3.5 text-[#c5a880]"></i>
                    <span>Distance: <strong class="text-white">2.1 km</strong></span>
                </div>
                <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-black/80 backdrop-blur px-3 py-1.5 text-xs">
                    <i data-lucide="gauge" class="h-3.5 w-3.5 text-sky-400"></i>
                    <span>Speed: <strong class="text-white">38 km/h</strong></span>
                </div>
            </div>
        </div>

        <!-- Driver Info & Milestone Timeline -->
        <div class="border-t border-white/10 lg:border-t-0 lg:border-l p-6 space-y-6 bg-[#161813]">
            <!-- Courier Driver Card -->
            <div class="rounded-2xl border border-white/10 bg-[#1c2018] p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#282d22] text-xl border border-[#c5a880]/40">
                        <i data-lucide="user-check" class="h-6 w-6 text-[#c5a880]"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <strong class="text-sm text-white">Emeka Adeleke</strong>
                            <span class="rounded bg-amber-400/20 px-1 text-[10px] font-bold text-amber-300 flex items-center gap-0.5"><i data-lucide="star" class="h-2.5 w-2.5 fill-amber-300"></i> 4.98</span>
                        </div>
                        <p class="text-[11px] text-[#9ca3af]">VIP Chauffeur • Sprinter Executive</p>
                        <p class="text-[10px] font-mono text-[#c5a880]">Plate: LND-849-NR</p>
                    </div>
                </div>
                <a href="tel:+2348128901234" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-500/20 text-emerald-400 transition hover:bg-emerald-500 hover:text-black" title="Call Courier">
                    <i data-lucide="phone" class="h-4 w-4"></i>
                </a>
            </div>

            <!-- Milestone Progress -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-[#c5a880] mb-3">Milestone Progress</h4>
                <div class="space-y-4 text-xs">
                    <div class="flex items-start gap-3">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-500 text-[10px] text-black font-bold"><i data-lucide="check" class="h-3 w-3 text-black"></i></span>
                        <div>
                            <strong class="block text-white">Order Verified & Packed in Vault</strong>
                            <span class="text-[10px] text-gray-400">Victoria Island Logistics Center</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-500 text-[10px] text-black font-bold"><i data-lucide="check" class="h-3 w-3 text-black"></i></span>
                        <div>
                            <strong class="block text-white">Dispatched in Biometric Transport</strong>
                            <span class="text-[10px] text-gray-400">Transit Seal #NR-9812 verified</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="relative flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-400 bg-emerald-950">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                        </span>
                        <div>
                            <strong class="block text-emerald-300">Out for White-Glove Delivery</strong>
                            <span class="text-[10px] text-gray-400">Approaching destination neighborhood</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 opacity-40">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border border-gray-600 text-[10px]">4</span>
                        <div>
                            <strong class="block text-white">Handover & OTP Verification</strong>
                            <span class="text-[10px] text-gray-400">Provide PIN <?= e($securityPin) ?> to courier</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
    <div class="space-y-6">
        <section class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
            <h2 class="mb-5 font-medium">Order progress</h2>
            <div class="grid gap-4 sm:grid-cols-4">
                <?php foreach (['pending' => 'Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'] as $key => $label): ?>
                    <?php $complete = array_search($status, ['pending','processing','shipped','delivered'], true) >= array_search($key, ['pending','processing','shipped','delivered'], true); ?>
                    <div class="flex items-center gap-2 text-xs <?= $complete ? 'text-[#687158]' : 'text-[#a0a197]' ?>">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full <?= $complete ? 'bg-[#687158] text-white' : 'border border-[#d8d8cf]' ?>"><?= $complete ? '&check;' : '' ?></span>
                        <span><?= e($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
            <h2 class="mb-4 font-medium">Ordered products</h2>
            <?php foreach ($items as $item): ?>
                <div class="flex items-center gap-4 border-b border-[#e8e8e0] py-4 last:border-0">
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-[#eef0e9]">
                        <?php $itemImg = !empty($item['image_url']) ? media_url($base, $item['image_url']) : catalogue_image_for($item); ?>
                        <img src="<?= e($itemImg) ?>" alt="<?= e($item['name']) ?>" class="h-full w-full object-cover" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=400&q=80';">
                    </div>
                    <div class="min-w-0 flex-1">
                        <strong class="block truncate text-sm"><?= e($item['name']) ?></strong>
                        <small class="text-xs text-[#92948a]">Quantity: <?= (int)$item['quantity'] ?></small>
                    </div>
                    <span class="whitespace-nowrap text-xs"><?= e(customer_money((float)$item['price'] * (int)$item['quantity'], $currency)) ?></span>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
            <h2 class="mb-4 font-medium">Shipping address</h2>
            <?php if (!empty($address['line'])): ?>
                <p class="text-xs leading-6 text-[#77796f]"><?= e($address['name']) ?><br><?= e($address['line']) ?><br><?= e($address['city']) ?><br><?= e($address['phone']) ?></p>
            <?php else: ?>
                <p class="text-xs text-[#92948a]">No shipping address was saved for this order.</p>
            <?php endif; ?>
        </section>
        <section class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-5">
            <h2 class="mb-4 font-medium">Billing and payment</h2>
            <p class="text-xs leading-6 text-[#77796f]"><?= e($order['payment'] ?: 'Payment pending') ?></p>
        </section>
        <section class="rounded-2xl bg-[#252820] p-5 text-white">
            <h2 class="mb-4 font-medium">Order summary</h2>
            <div class="flex justify-between border-b border-white/10 py-3 text-xs text-[#b9bcb2]"><span>Subtotal</span><span><?= e(customer_money($subtotal, $currency)) ?></span></div>
            <div class="flex justify-between border-b border-white/10 py-3 text-xs text-[#b9bcb2]"><span>Delivery</span><span><?= $delivery > 0 ? e(customer_money($delivery, $currency)) : 'Free' ?></span></div>
            <div class="flex justify-between pt-4 font-medium"><span>Total</span><span><?= e(customer_money($total, $currency)) ?></span></div>
        </section>
    </aside>
</div>
