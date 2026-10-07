<?php
declare(strict_types=1);
/** @var array $couriers */
/** @var array $dispatches */
/** @var array $unassignedOrders */
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = ($base === '/' || $base === '\\') ? '' : $base;
$user = \Noir\Core\Auth::user();
$adminUrl = ($base ?: '') . '/admin';
$fleetUrl = ($base ?: '') . '/admin/fleet';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fleet & Couriers — <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/admin-mobile.min.js') ?>" defer></script>
    <style>
        .radar-grid {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(104, 113, 88, 0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(104, 113, 88, 0.12) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        .pin-marker {
            position: absolute;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .pin-marker:hover {
            transform: translate(-50%, -50%) scale(1.12);
            z-index: 10;
        }
        .pin-icon {
            width: 32px;
            height: 32px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .pin-label {
            background: #ffffff;
            border: 1px solid #deded6;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
            color: #252820;
            margin-top: 4px;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body class="admin-page bg-[#f6f6f1]">

    <?php $activeNav = 'fleet'; include __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Main Workspace Content -->
    <main class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div>
                <p class="eyebrow text-[#b18a43] font-bold">Logistics & Escorts</p>
                <h1>Fleet & Dispatch Command</h1>
                <p class="admin-subtitle">Real-time courier GPS tracking, white-glove hand delivery, and dispatch orchestration.</p>
            </div>
            <div class="admin-user flex items-center gap-3">
                <button onclick="openDriverModal()" class="rounded-xl bg-[#252820] px-4 py-2.5 text-xs font-semibold text-white hover:bg-[#181a15] flex items-center gap-1.5 shadow-sm transition">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5 text-[#b18a43]"></i>
                    Register Driver
                </button>
            </div>
        </header>

        <section class="p-5 lg:p-10 space-y-6">

            <!-- Status Alert Messages -->
            <?php if (isset($_GET['driver_registered'])): ?>
                <div class="rounded-xl bg-[#e7efe3] border border-[#bcd7b2] px-4 py-3 text-sm text-[#41633a] font-medium flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                    Driver successfully registered to your fleet.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['driver_deleted'])): ?>
                <div class="rounded-xl bg-[#f8ecec] border border-[#e8c6c6] px-4 py-3 text-sm text-[#a34444] font-medium flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4 text-rose-600"></i>
                    Driver removed from fleet.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['assigned'])): ?>
                <div class="rounded-xl bg-[#e7efe3] border border-[#bcd7b2] px-4 py-3 text-sm text-[#41633a] font-medium flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4 text-emerald-600"></i>
                    Order dispatched to driver successfully.
                </div>
            <?php endif; ?>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">Active Drivers</p>
                        <div class="p-1.5 rounded-lg bg-[#f5f5f0] text-[#252820]">
                            <i data-lucide="truck" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <strong class="mt-3 block text-2xl font-bold text-[#252820]">
                        <?= count($couriers) > 0 ? count($couriers) . ' Couriers' : '0 Registered' ?>
                    </strong>
                    <small class="text-xs <?= count($couriers) > 0 ? 'text-emerald-700 font-semibold' : 'text-[#85877d]' ?> mt-1 block">
                        <?= count($couriers) > 0 ? 'GPS Telemetry Active' : 'No drivers registered yet' ?>
                    </small>
                </div>

                <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">Dispatches En Route</p>
                        <div class="p-1.5 rounded-lg bg-[#f5f5f0] text-[#b18a43]">
                            <i data-lucide="navigation" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <strong class="mt-3 block text-2xl font-bold text-[#252820]">
                        <?= count($dispatches) > 0 ? count($dispatches) . ' Live' : '0 Dispatches' ?>
                    </strong>
                    <small class="text-xs text-[#85877d] mt-1 block">Direct white-glove hand delivery</small>
                </div>

                <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">Unassigned Orders</p>
                        <div class="p-1.5 rounded-lg bg-[#f5f5f0] text-amber-700">
                            <i data-lucide="clock" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <strong class="mt-3 block text-2xl font-bold <?= count($unassignedOrders) > 0 ? 'text-[#ad6763]' : 'text-[#252820]' ?>">
                        <?= count($unassignedOrders) > 0 ? count($unassignedOrders) . ' Pending' : '0 Pending' ?>
                    </strong>
                    <small class="text-xs text-[#85877d] mt-1 block">
                        <?= count($unassignedOrders) > 0 ? 'Requires courier assignment' : 'All orders assigned' ?>
                    </small>
                </div>

                <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_24px_rgba(37,40,32,.03)]">
                    <div class="flex items-center justify-between text-[#85877d]">
                        <p class="text-xs uppercase tracking-wider font-semibold">Service Standard</p>
                        <div class="p-1.5 rounded-lg bg-[#fbf5eb] text-[#b18a43]">
                            <i data-lucide="star" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <strong class="mt-3 block text-2xl font-bold text-[#b18a43]">
                        5.0 ★
                    </strong>
                    <small class="text-xs text-[#85877d] mt-1 block">
                        Dedicated luxury escort service
                    </small>
                </div>
            </div>

            <!-- Map Radar & Driver List Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Radar Map Card -->
                <div class="lg:col-span-2 rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_30px_rgba(37,40,32,.04)] space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-[#252820] flex items-center gap-2">
                            <i data-lucide="radar" class="w-4 h-4 text-[#b18a43]"></i>
                            Live Dispatch Radar
                        </h3>
                        <?php if (!empty($couriers)): ?>
                            <span class="text-xs px-2.5 py-1 rounded-full bg-[#e7efe3] text-[#41633a] font-semibold flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-ping"></span>
                                TELEMETRY ACTIVE
                            </span>
                        <?php else: ?>
                            <span class="text-xs px-2.5 py-1 rounded-full bg-[#eeeeE8] text-[#85877d] font-medium">
                                NO COURIERS ACTIVE
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="relative w-full h-[380px] bg-[#fbfbf8] rounded-xl border border-[#deded6] overflow-hidden">
                        <div class="radar-grid"></div>

                        <?php if (!empty($couriers)): ?>
                            <!-- Central Hub -->
                            <div class="pin-marker" style="top: 50%; left: 50%;">
                                <div class="pin-icon bg-[#252820] text-white">
                                    <i data-lucide="building" class="w-4 h-4 text-[#b18a43]"></i>
                                </div>
                                <div class="pin-label"><?= e(store_setting('store_name', 'NOIR')) ?> Central Hub</div>
                            </div>

                            <?php foreach ($couriers as $idx => $c): ?>
                                <?php 
                                    $topPos = 35 + (($idx * 27) % 50);
                                    $leftPos = 30 + (($idx * 31) % 55);
                                ?>
                                <div class="pin-marker" style="top: <?= $topPos ?>%; left: <?= $leftPos ?>%;">
                                    <div class="pin-icon bg-[#252820] text-white border border-[#b18a43]">
                                        <i data-lucide="truck" class="w-4 h-4 text-[#b18a43]"></i>
                                    </div>
                                    <div class="pin-label"><?= htmlspecialchars((string)$c['name']) ?> (<?= htmlspecialchars((string)$c['vehicle_plate']) ?>)</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="relative z-10 flex flex-col items-center justify-center h-full text-center p-6 space-y-3">
                                <div class="w-12 h-12 rounded-full bg-[#f4f4ec] flex items-center justify-center text-[#b18a43] shadow-sm">
                                    <i data-lucide="navigation-off" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-bold text-[#252820]">No Active Couriers on Radar</p>
                                <p class="text-xs text-[#85877d] max-w-sm">Register your drivers to enable real-time GPS telemetry, dispatch assignment, and order tracking.</p>
                                <button onclick="openDriverModal()" class="rounded-xl bg-[#252820] px-4 py-2 text-xs font-semibold text-white hover:bg-[#181a15] shadow-sm transition">
                                    + Register First Courier
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Registered Drivers List -->
                <div class="rounded-2xl border border-[#e3e3da] bg-white p-5 shadow-[0_8px_30px_rgba(37,40,32,.04)] space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-[#252820] flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-4 h-4 text-[#b18a43]"></i>
                            Registered Drivers
                        </h3>
                        <button onclick="openDriverModal()" class="text-xs text-[#b18a43] font-bold hover:underline">+ Add</button>
                    </div>

                    <div class="space-y-3">
                        <?php if (empty($couriers)): ?>
                            <div class="text-center py-16 text-[#85877d] space-y-3">
                                <i data-lucide="user-x" class="w-9 h-9 mx-auto text-[#b3b6aa]"></i>
                                <p class="text-sm font-semibold text-[#252820]">No Drivers</p>
                                <p class="text-xs">No drivers have been registered yet.</p>
                                <button onclick="openDriverModal()" class="rounded-xl bg-[#252820] px-4 py-2 text-xs font-semibold text-white hover:bg-[#181a15] shadow-sm transition">
                                    Register First Driver
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($couriers as $c): ?>
                                <div class="p-3.5 rounded-xl border border-[#e3e3da] bg-[#fbfbf8] hover:bg-white transition flex items-center justify-between">
                                    <div class="space-y-0.5">
                                        <div class="font-bold text-[#252820] text-sm"><?= htmlspecialchars((string)$c['name']) ?></div>
                                        <div class="text-xs text-[#85877d]"><?= htmlspecialchars((string)$c['vehicle_model']) ?> (<?= htmlspecialchars((string)$c['vehicle_plate']) ?>)</div>
                                        <div class="text-[11px] text-[#b18a43] font-semibold">★ <?= $c['rating'] ?> • <?= $c['total_deliveries'] ?> dispatches</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] px-2.5 py-1 rounded-full font-semibold uppercase <?= $c['status'] === 'in_transit' ? 'bg-[#fbf5eb] text-[#b18a43]' : 'bg-[#e7efe3] text-[#41633a]' ?>">
                                            <?= str_replace('_', ' ', (string)$c['status']) ?>
                                        </span>
                                        <form method="POST" action="<?= e(($base ?: '') . '/admin/fleet/delete-driver') ?>" onsubmit="return confirm('Remove driver?');">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="p-1 text-[#ad6763] hover:text-rose-700">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Active Dispatches Table -->
            <section class="rounded-2xl border border-[#e3e3da] bg-white shadow-[0_8px_30px_rgba(37,40,32,.04)] overflow-hidden">
                <div class="p-5 border-b border-[#ecece5] flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-[#252820] flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4 text-[#b18a43]"></i>
                            Active White-Glove Dispatches
                        </h3>
                        <p class="text-xs text-[#85877d]"><?= count($dispatches) ?> active deliveries in transit</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-[#fbfbf8] text-[11px] uppercase tracking-[.14em] text-[#92948a] border-b border-[#ecece5]">
                            <tr>
                                <th class="py-3.5 px-5">Order #</th>
                                <th class="py-3.5 px-4">Recipient</th>
                                <th class="py-3.5 px-4">Assigned Courier</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Security PIN</th>
                                <th class="py-3.5 px-4">ETA / Distance</th>
                                <th class="py-3.5 px-4">Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#eeeeE8]">
                            <?php if (empty($dispatches)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-[#85877d]">No active dispatches right now.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dispatches as $d): ?>
                                    <tr class="transition hover:bg-[#fcfcf9]">
                                        <td class="py-4 px-5 font-bold text-[#252820]"><?= htmlspecialchars((string)$d['order_number']) ?></td>
                                        <td class="py-4 px-4 font-medium text-[#252820]"><?= htmlspecialchars((string)($d['shipping_name'] ?? 'VIP Client')) ?></td>
                                        <td class="py-4 px-4">
                                            <strong class="text-[#252820] font-medium text-sm block"><?= htmlspecialchars((string)($d['courier_name'] ?? 'Unassigned')) ?></strong>
                                            <span class="text-xs text-[#85877d]"><?= htmlspecialchars((string)($d['vehicle_plate'] ?? '—')) ?></span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold bg-[#fbf5eb] text-[#b18a43]">
                                                <?= strtoupper((string)$d['dispatch_status']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 font-mono font-bold text-emerald-700 text-sm tracking-widest">
                                            <?= htmlspecialchars((string)$d['security_pin']) ?>
                                        </td>
                                        <td class="py-4 px-4 text-xs font-semibold text-[#252820]">
                                            <?= $d['distance_remaining_km'] ?> km en route
                                        </td>
                                        <td class="py-4 px-4 text-xs text-[#85877d] max-w-xs truncate">
                                            <?= htmlspecialchars((string)($d['shipping_address'] ?? 'Victoria Island, Lagos')) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Unassigned Pending Orders Table -->
            <?php if (!empty($unassignedOrders)): ?>
                <section class="rounded-2xl border border-[#e3e3da] bg-white shadow-[0_8px_30px_rgba(37,40,32,.04)] overflow-hidden">
                    <div class="p-5 border-b border-[#ecece5] bg-[#fbfbf8] flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-[#252820] flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4 text-[#b18a43]"></i>
                                Orders Awaiting Courier Assignment
                            </h3>
                            <p class="text-xs text-[#85877d]"><?= count($unassignedOrders) ?> orders ready for dispatch</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-[#fbfbf8] text-[11px] uppercase tracking-[.14em] text-[#92948a] border-b border-[#ecece5]">
                                <tr>
                                    <th class="py-3.5 px-5">Order #</th>
                                    <th class="py-3.5 px-4">Customer</th>
                                    <th class="py-3.5 px-4">Total</th>
                                    <th class="py-3.5 px-4">Destination</th>
                                    <th class="py-3.5 px-4">Assign Courier</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#eeeeE8]">
                                <?php foreach ($unassignedOrders as $uo): ?>
                                    <tr class="transition hover:bg-[#fcfcf9]">
                                        <td class="py-4 px-5 font-bold text-[#252820]"><?= htmlspecialchars((string)$uo['order_number']) ?></td>
                                        <td class="py-4 px-4 text-[#252820] font-medium text-sm"><?= htmlspecialchars((string)($uo['shipping_name'] ?? 'Guest Customer')) ?></td>
                                        <td class="py-4 px-4 text-sm font-semibold text-[#252820]">₦<?= number_format((float)$uo['total'], 2) ?></td>
                                        <td class="py-4 px-4 text-xs text-[#85877d] max-w-xs truncate"><?= htmlspecialchars((string)($uo['shipping_address'] ?? 'Lagos, Nigeria')) ?></td>
                                        <td class="py-4 px-4">
                                            <?php if (!empty($couriers)): ?>
                                                <form method="POST" action="<?= e(($base ?: '') . '/admin/fleet/assign') ?>" class="flex items-center gap-2">
                                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="order_number" value="<?= htmlspecialchars((string)$uo['order_number']) ?>">
                                                    <select name="courier_id" class="rounded-xl border border-[#deded6] bg-[#fbfbf8] px-3 py-1.5 text-xs text-[#252820] outline-none focus:border-[#252820]">
                                                        <?php foreach ($couriers as $c): ?>
                                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars((string)$c['name']) ?> (<?= htmlspecialchars((string)$c['vehicle_plate']) ?>)</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="rounded-xl bg-[#252820] px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-[#181a15] transition">
                                                        Assign & Dispatch
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-xs font-semibold text-[#ad6763]">No drivers registered</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

        </section>
    </main>

    <!-- Register Driver Modal -->
    <div id="driver-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="rounded-2xl bg-white max-w-md w-full p-6 shadow-2xl border border-[#e3e3da]">
            <div class="flex items-center justify-between mb-4 border-b border-[#ecece5] pb-3">
                <h3 class="font-bold text-[#252820] text-base">Register New Driver</h3>
                <button onclick="closeDriverModal()" class="p-1 rounded-lg text-[#85877d] hover:text-[#252820]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="<?= e(($base ?: '') . '/admin/fleet/add-driver') ?>" class="space-y-3.5 text-xs">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                <div>
                    <label class="block text-[#686b62] mb-1 font-semibold">Driver Full Name *</label>
                    <input type="text" name="name" required class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#252820]" placeholder="e.g. Tunde Johnson">
                </div>

                <div>
                    <label class="block text-[#686b62] mb-1 font-semibold">Phone Number *</label>
                    <input type="tel" name="phone" required class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#252820]" placeholder="+234 800 000 0000">
                </div>

                <div>
                    <label class="block text-[#686b62] mb-1 font-semibold">Email (Optional)</label>
                    <input type="email" name="email" class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#252820]" placeholder="driver@noir.test">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#686b62] mb-1 font-semibold">Vehicle Type</label>
                        <select name="vehicle_type" class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#252820]">
                            <option value="van">Van</option>
                            <option value="car" selected>Car</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="armored">Armored Escort</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#686b62] mb-1 font-semibold">Vehicle Plate *</label>
                        <input type="text" name="vehicle_plate" required class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#252820]" placeholder="LAG-123-NR">
                    </div>
                </div>

                <div>
                    <label class="block text-[#686b62] mb-1 font-semibold">Vehicle Model</label>
                    <input type="text" name="vehicle_model" class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-2.5 text-sm text-[#252820] outline-none focus:border-[#252820]" placeholder="Toyota Corolla / Mercedes Sprinter">
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-[#ecece5]">
                    <button type="button" onclick="closeDriverModal()" class="rounded-xl border border-[#deded6] px-4 py-2 text-sm text-[#686b62] hover:bg-[#f0f1eb]">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#252820] px-5 py-2 text-sm font-semibold text-white hover:bg-[#181a15] transition">Register Driver</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openDriverModal() {
            document.getElementById('driver-modal').classList.remove('hidden');
        }

        function closeDriverModal() {
            document.getElementById('driver-modal').classList.add('hidden');
        }
    </script>
</body>
</html>
