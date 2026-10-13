<?php 
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = ($base === '/' || $base === '\\') ? '' : $base;
$user = \Noir\Core\Auth::user();
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Customer Notifications &amp; Push Center — <?= e($storeName) ?></title>
  <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
  <script src="<?= e(($base ?: '') . '/assets/js/tailwind.min.js') ?>"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <?= store_brand_style() ?>
  <script src="<?= e(($base ?: '') . '/assets/js/admin/admin-core.js') ?>" defer></script>
  <style>
    body { font-family: 'Urbanist', ui-sans-serif, system-ui, sans-serif; }
    .brand-btn-primary {
      background-color: var(--brand-primary, #687158);
      color: var(--brand-on-primary, #ffffff);
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .brand-btn-primary:hover {
      filter: brightness(0.92);
      transform: translateY(-1px);
    }
    .phone-frame {
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 8px #1f231b;
    }
  </style>
</head>
<body class="admin-page bg-[#f6f6f1] text-[#252820]">
  <?php $activeNav = 'notifications'; include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="admin-main">
    <!-- Topbar Component -->
    <?php 
    $eyebrow = 'Omnichannel Concierge';
    $title = 'Customer Notifications & Push Center';
    $subtitle = 'Broadcast real-time announcements, exclusive luxury drops, and privileges directly to customers on the website and mobile app.';
    include __DIR__ . '/partials/topbar.php'; 
    ?>

    <div class="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      
      <!-- Top Metrics Ribbon -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187]">Active Customers</span>
            <div class="grid h-8 w-8 place-items-center rounded-xl bg-[#687158]/10 text-[#687158]">
              <i data-lucide="users" class="h-4 w-4"></i>
            </div>
          </div>
          <p class="mt-3 text-2xl font-black text-[#252820]"><?= number_format($totalCustomers) ?></p>
          <span class="text-[11px] text-[#77796f]">Registered web clients</span>
        </div>

        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187]">Mobile App Devices</span>
            <div class="grid h-8 w-8 place-items-center rounded-xl bg-emerald-500/10 text-emerald-700">
              <i data-lucide="smartphone" class="h-4 w-4"></i>
            </div>
          </div>
          <p class="mt-3 text-2xl font-black text-[#252820]"><?= number_format($appUsers) ?></p>
          <span class="text-[11px] text-emerald-700 font-medium">FCM Push token active</span>
        </div>

        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187]">Total Broadcasts</span>
            <div class="grid h-8 w-8 place-items-center rounded-xl bg-sky-500/10 text-sky-700">
              <i data-lucide="radio" class="h-4 w-4"></i>
            </div>
          </div>
          <p class="mt-3 text-2xl font-black text-[#252820]"><?= number_format($totalBroadcasts) ?></p>
          <span class="text-[11px] text-[#77796f]">Campaigns dispatched</span>
        </div>

        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8e9187]">Web Notifications</span>
            <div class="grid h-8 w-8 place-items-center rounded-xl bg-purple-500/10 text-purple-700">
              <i data-lucide="bell" class="h-4 w-4"></i>
            </div>
          </div>
          <p class="mt-3 text-2xl font-black text-[#252820]"><?= number_format($webNotifsCount) ?></p>
          <span class="text-[11px] text-[#77796f]">In-app concierge alerts</span>
        </div>
      </div>

      <!-- Main Workspace: Composer & Live Device Preview -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: Notification Composer Form (7 cols) -->
        <div class="lg:col-span-7 rounded-2xl border border-[#e5e5dd] bg-white p-6 sm:p-8 shadow-sm">
          <div class="flex items-center gap-3 border-b border-[#f0f0ea] pb-4 mb-6">
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-[#687158] text-white">
              <i data-lucide="send" class="h-5 w-5"></i>
            </div>
            <div>
              <h2 class="text-lg font-bold text-[#252820]">Create New Broadcast</h2>
              <p class="text-xs text-[#8e9187]">Send direct alerts to customers on web browsers &amp; mobile smartphones.</p>
            </div>
          </div>

          <form id="notification-form" class="space-y-6">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <!-- Target Audience Selector -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-2">Target Audience</label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <label class="flex items-center gap-2.5 rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3 text-xs cursor-pointer hover:border-[#687158] transition-colors">
                  <input type="radio" name="target_audience" value="all_customers" checked class="accent-[#687158]" onchange="toggleAudience(this.value)">
                  <div>
                    <strong class="block font-bold text-[#252820]">All Customers</strong>
                    <small class="text-[10px] text-[#8e9187]"><?= number_format($totalCustomers) ?> recipients</small>
                  </div>
                </label>

                <label class="flex items-center gap-2.5 rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3 text-xs cursor-pointer hover:border-[#687158] transition-colors">
                  <input type="radio" name="target_audience" value="app_users_only" class="accent-[#687158]" onchange="toggleAudience(this.value)">
                  <div>
                    <strong class="block font-bold text-[#252820]">Mobile App Only</strong>
                    <small class="text-[10px] text-emerald-700"><?= number_format($appUsers) ?> FCM devices</small>
                  </div>
                </label>

                <label class="flex items-center gap-2.5 rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3 text-xs cursor-pointer hover:border-[#687158] transition-colors">
                  <input type="radio" name="target_audience" value="specific_customer" class="accent-[#687158]" onchange="toggleAudience(this.value)">
                  <div>
                    <strong class="block font-bold text-[#252820]">Specific Client</strong>
                    <small class="text-[10px] text-[#8e9187]">Individual delivery</small>
                  </div>
                </label>
              </div>
            </div>

            <!-- Specific Customer Dropdown (Conditional) -->
            <div id="specific-customer-wrap" class="hidden">
              <label for="specific_customer_id" class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-1.5">Select Client Recipient</label>
              <select name="specific_customer_id" id="specific_customer_id" class="w-full rounded-xl border border-[#d8d8cf] bg-[#fdfdfb] px-3.5 py-2.5 text-xs text-[#252820] focus:border-[#687158] focus:ring-1 focus:ring-[#687158]">
                <option value="">-- Choose Customer --</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= (int)$c['id'] ?>">
                    <?= e($c['name']) ?> (<?= e($c['email']) ?>) <?= $c['has_fcm'] ? '📱 [App]' : '🌐 [Web]' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Delivery Channels -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-2">Delivery Channels</label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <label class="flex items-center gap-2 rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3 text-xs cursor-pointer hover:border-[#687158]">
                  <input type="radio" name="channels" value="both" checked class="accent-[#687158]">
                  <div>
                    <strong class="block font-bold text-[#252820]">Both (Web &amp; App)</strong>
                    <small class="text-[10px] text-[#8e9187]">Full coverage</small>
                  </div>
                </label>

                <label class="flex items-center gap-2 rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3 text-xs cursor-pointer hover:border-[#687158]">
                  <input type="radio" name="channels" value="web_only" class="accent-[#687158]">
                  <div>
                    <strong class="block font-bold text-[#252820]">Web Concierge Only</strong>
                    <small class="text-[10px] text-[#8e9187]">In-portal bell alert</small>
                  </div>
                </label>

                <label class="flex items-center gap-2 rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3 text-xs cursor-pointer hover:border-[#687158]">
                  <input type="radio" name="channels" value="push_only" class="accent-[#687158]">
                  <div>
                    <strong class="block font-bold text-[#252820]">Mobile Push Only</strong>
                    <small class="text-[10px] text-[#8e9187]">Phone lockscreen</small>
                  </div>
                </label>
              </div>
            </div>

            <!-- Category & Type Selector -->
            <div>
              <label for="notif-type" class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-1.5">Notification Category</label>
              <select name="type" id="notif-type" onchange="updatePreview()" class="w-full rounded-xl border border-[#d8d8cf] bg-[#fdfdfb] px-3.5 py-2.5 text-xs text-[#252820] focus:border-[#687158] focus:ring-1 focus:ring-[#687158]">
                <option value="announcement">Announcement / Maison Editorial</option>
                <option value="drop">Exclusive Luxury Drop / New Arrival</option>
                <option value="privilege">Private Privilege / Member Promotion</option>
                <option value="alert">Order &amp; Concierge Alert</option>
                <option value="system">Security &amp; System Protocol</option>
              </select>
            </div>

            <!-- Notification Title -->
            <div>
              <label for="notif-title" class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-1.5">
                Headline / Title <span class="text-rose-500">*</span>
              </label>
              <input type="text" name="title" id="notif-title" required placeholder="e.g. Private Drop: The Autumn Leather Collection"
                     oninput="updatePreview()"
                     class="w-full rounded-xl border border-[#d8d8cf] bg-[#fdfdfb] px-3.5 py-2.5 text-sm text-[#252820] font-semibold focus:border-[#687158] focus:ring-1 focus:ring-[#687158]">
            </div>

            <!-- Notification Body -->
            <div>
              <label for="notif-body" class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-1.5">
                Message Body <span class="text-rose-500">*</span>
              </label>
              <textarea name="body" id="notif-body" rows="4" required placeholder="Type the notification details for customers..."
                        oninput="updatePreview()"
                        class="w-full rounded-xl border border-[#d8d8cf] bg-[#fdfdfb] px-3.5 py-2.5 text-xs text-[#252820] focus:border-[#687158] focus:ring-1 focus:ring-[#687158]"></textarea>
            </div>

            <!-- Action Destination URL -->
            <div>
              <label for="notif-url" class="block text-xs font-bold uppercase tracking-wider text-[#77796f] mb-1.5">Action Destination Link</label>
              <input type="text" name="action_url" id="notif-url" value="/store" placeholder="/store or /customer/orders"
                     class="w-full rounded-xl border border-[#d8d8cf] bg-[#fdfdfb] px-3.5 py-2 text-xs text-[#252820] font-mono focus:border-[#687158] focus:ring-1 focus:ring-[#687158]">
              <small class="text-[10px] text-[#8e9187] mt-1 block">Tapping the notification opens this route on the website or mobile app.</small>
            </div>

            <!-- Submit Button & Feedback -->
            <div class="pt-2">
              <button type="submit" id="submit-broadcast-btn" class="w-full brand-btn-primary rounded-xl py-3 text-xs font-bold tracking-wide uppercase flex items-center justify-center gap-2 shadow-sm">
                <i data-lucide="send" class="h-4 w-4"></i>
                <span id="submit-btn-text">Dispatch Omnichannel Notification</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Right: Real-Time Device Mockups Preview (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
          
          <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#f0f0ea] pb-3 mb-5">
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#252820]">Live Device Preview</h3>
                <p class="text-[11px] text-[#8e9187]">Simulated customer experience</p>
              </div>
              <div class="flex gap-1.5 bg-[#f0f1ec] p-1 rounded-xl text-[11px] font-semibold">
                <button type="button" onclick="switchPreviewTab('mobile')" id="tab-mobile-btn" class="px-2.5 py-1 rounded-lg bg-white shadow-sm text-[#252820]">Mobile Lockscreen</button>
                <button type="button" onclick="switchPreviewTab('web')" id="tab-web-btn" class="px-2.5 py-1 rounded-lg text-[#77796f] hover:text-[#252820]">Web Portal</button>
              </div>
            </div>

            <!-- 1. Mobile Phone Lockscreen Mockup -->
            <div id="preview-mobile-wrap" class="flex justify-center py-2">
              <div class="phone-frame w-72 rounded-[36px] bg-[#1a1c17] p-3.5 border-4 border-[#2f3328] relative overflow-hidden">
                <!-- Top Notch / Island -->
                <div class="w-24 h-4 bg-black rounded-full mx-auto mb-5"></div>
                
                <!-- Phone Clock -->
                <div class="text-center text-white/90 mb-6">
                  <span class="text-3xl font-extrabold tracking-tight">09:41</span>
                  <small class="block text-[10px] text-white/60 font-medium">Thursday, September 3</small>
                </div>

                <!-- Realistic Lockscreen Push Notification Card -->
                <div class="rounded-2xl bg-white/90 backdrop-blur-md p-3.5 shadow-lg border border-white/40 text-[#252820]">
                  <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-1.5">
                      <div class="h-4 w-4 rounded-md bg-[#687158] grid place-items-center text-white text-[8px] font-bold">N</div>
                      <span class="text-[10px] font-bold tracking-wider uppercase text-[#687158]"><?= e($storeName) ?></span>
                    </div>
                    <span class="text-[9px] text-[#8e9187] font-medium">now</span>
                  </div>
                  <strong id="preview-mobile-title" class="block text-xs font-bold text-[#1f231b] leading-snug">
                    Private Drop: Autumn Leather
                  </strong>
                  <p id="preview-mobile-body" class="text-[11px] text-[#55584f] mt-0.5 line-clamp-3 leading-relaxed">
                    Exclusive early acquisition is now open for registered clientele.
                  </p>
                  <div class="mt-2 flex items-center justify-between border-t border-black/5 pt-1.5">
                    <span id="preview-category-badge" class="inline-block text-[8px] font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded bg-[#687158]/15 text-[#687158]">
                      Announcement
                    </span>
                    <span class="text-[9px] font-bold text-[#687158] flex items-center gap-0.5">
                      View details <i data-lucide="chevron-right" class="h-2.5 w-2.5"></i>
                    </span>
                  </div>
                </div>

                <!-- Bottom Home Bar -->
                <div class="w-24 h-1 bg-white/40 rounded-full mx-auto mt-14 mb-1"></div>
              </div>
            </div>

            <!-- 2. Web Portal Concierge Notification Mockup -->
            <div id="preview-web-wrap" class="hidden py-4">
              <div class="rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-4 shadow-md space-y-3">
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-full bg-[#687158] text-white grid place-items-center">
                      <i data-lucide="bell" class="h-4 w-4"></i>
                    </div>
                    <div>
                      <span class="text-[10px] font-bold uppercase tracking-wider text-[#687158]">Maison Web Alert</span>
                      <h4 id="preview-web-title" class="text-xs font-bold text-[#252820]">Private Drop: Autumn Leather</h4>
                    </div>
                  </div>
                  <span class="text-[10px] text-[#8e9187]">Just now</span>
                </div>
                <p id="preview-web-body" class="text-xs text-[#55584f] bg-white p-3 rounded-lg border border-[#e8e8e0]">
                  Exclusive early acquisition is now open for registered clientele.
                </p>
                <div class="flex justify-end gap-2 text-[11px]">
                  <span class="px-2.5 py-1 rounded-lg border border-[#deded6] font-semibold text-[#77796f]">Mark as read</span>
                  <span class="px-3 py-1 rounded-lg bg-[#687158] text-white font-bold">Open link</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Anti-Ad Protection Notice -->
          <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-950 text-xs flex items-start gap-3">
            <i data-lucide="shield-check" class="h-5 w-5 text-emerald-700 shrink-0 mt-0.5"></i>
            <div>
              <strong class="block font-bold">Zero-Ad Platform Active</strong>
              <span class="text-[11px] text-emerald-900 leading-relaxed block mt-0.5">
                All external ad networks, tracking IDs, and intrusive banners are blocked across web pages and the mobile app. Notifications are strictly official concierge alerts.
              </span>
            </div>
          </div>

        </div>

      </div>

      <!-- Broadcast History Ledger Table -->
      <div class="rounded-2xl border border-[#e5e5dd] bg-white p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#f0f0ea] pb-4 mb-6">
          <div>
            <h3 class="text-base font-bold text-[#252820]">Broadcast Dispatch History</h3>
            <p class="text-xs text-[#8e9187]">Audit log of notifications sent to customers across web and mobile channels.</p>
          </div>
          <span class="text-xs font-bold text-[#687158] bg-[#687158]/10 px-3 py-1.5 rounded-xl">
            <?= count($broadcasts) ?> Logged Broadcasts
          </span>
        </div>

        <?php if (empty($broadcasts)): ?>
          <div class="py-12 text-center text-[#8e9187]">
            <i data-lucide="bell-off" class="h-8 w-8 mx-auto mb-2 text-[#b0b2a8]"></i>
            <p class="text-sm font-medium">No previous broadcast campaigns recorded.</p>
            <small class="text-xs">Dispatches sent from the composer above will appear here.</small>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-[#e8e8e0] text-[10px] font-bold uppercase tracking-wider text-[#8e9187]">
                  <th class="py-3 px-2">Campaign Title</th>
                  <th class="py-3 px-2">Category</th>
                  <th class="py-3 px-2">Audience</th>
                  <th class="py-3 px-2">Channels</th>
                  <th class="py-3 px-2 text-center">Recipients</th>
                  <th class="py-3 px-2">Dispatched At</th>
                  <th class="py-3 px-2 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#f0f0ea]">
                <?php foreach ($broadcasts as $b): ?>
                  <tr class="hover:bg-[#fafaf7] transition-colors">
                    <td class="py-3.5 px-2">
                      <strong class="font-bold text-[#252820] block"><?= e($b['title']) ?></strong>
                      <span class="text-[11px] text-[#77796f] line-clamp-1 max-w-sm"><?= e($b['body']) ?></span>
                    </td>
                    <td class="py-3.5 px-2">
                      <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-[#687158]/15 text-[#687158]">
                        <?= e($b['type']) ?>
                      </span>
                    </td>
                    <td class="py-3.5 px-2 text-[#55584f]">
                      <?php 
                      echo match ($b['target_audience']) {
                        'app_users_only' => '📱 Mobile App Only',
                        'specific_customer' => '👤 Specific Client',
                        default => '👥 All Customers'
                      };
                      ?>
                    </td>
                    <td class="py-3.5 px-2 font-medium">
                      <?php
                      echo match ($b['channels']) {
                        'web_only' => '<span class="text-sky-700">🌐 Web Portal</span>',
                        'push_only' => '<span class="text-purple-700">📱 Mobile Push</span>',
                        default => '<span class="text-emerald-700 font-semibold">🌐 Web + 📱 App</span>'
                      };
                      ?>
                    </td>
                    <td class="py-3.5 px-2 text-center font-mono font-bold text-[#252820]">
                      <?= number_format((int)$b['recipient_count']) ?>
                    </td>
                    <td class="py-3.5 px-2 text-[#8e9187] whitespace-nowrap">
                      <?= e(date('M j, Y • H:i', strtotime($b['created_at']))) ?>
                    </td>
                    <td class="py-3.5 px-2 text-right whitespace-nowrap">
                      <button type="button" onclick="deleteBroadcast(<?= (int)$b['id'] ?>)" class="text-rose-600 hover:text-rose-800 p-1.5 rounded-lg hover:bg-rose-50" title="Delete log entry">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <script>
  const base = <?= json_encode($base ?: '') ?>;
  const csrf = <?= json_encode(csrf_token()) ?>;

  function toggleAudience(val) {
    const wrap = document.getElementById('specific-customer-wrap');
    if (val === 'specific_customer') {
      wrap.classList.remove('hidden');
    } else {
      wrap.classList.add('hidden');
    }
  }

  function switchPreviewTab(tab) {
    const mobileWrap = document.getElementById('preview-mobile-wrap');
    const webWrap = document.getElementById('preview-web-wrap');
    const mBtn = document.getElementById('tab-mobile-btn');
    const wBtn = document.getElementById('tab-web-btn');

    if (tab === 'mobile') {
      mobileWrap.classList.remove('hidden');
      webWrap.classList.add('hidden');
      mBtn.classList.add('bg-white', 'shadow-sm', 'text-[#252820]');
      mBtn.classList.remove('text-[#77796f]');
      wBtn.classList.remove('bg-white', 'shadow-sm', 'text-[#252820]');
      wBtn.classList.add('text-[#77796f]');
    } else {
      mobileWrap.classList.add('hidden');
      webWrap.classList.remove('hidden');
      wBtn.classList.add('bg-white', 'shadow-sm', 'text-[#252820]');
      wBtn.classList.remove('text-[#77796f]');
      mBtn.classList.remove('bg-white', 'shadow-sm', 'text-[#252820]');
      mBtn.classList.add('text-[#77796f]');
    }
  }

  function updatePreview() {
    const titleVal = document.getElementById('notif-title').value.trim() || 'Private Drop: Autumn Leather';
    const bodyVal = document.getElementById('notif-body').value.trim() || 'Exclusive early acquisition is now open for registered clientele.';
    const typeVal = document.getElementById('notif-type').value;

    document.getElementById('preview-mobile-title').textContent = titleVal;
    document.getElementById('preview-mobile-body').textContent = bodyVal;
    document.getElementById('preview-web-title').textContent = titleVal;
    document.getElementById('preview-web-body').textContent = bodyVal;

    const badge = document.getElementById('preview-category-badge');
    if (badge) {
      badge.textContent = typeVal.toUpperCase();
    }
  }

  function showToast(msg, isSuccess = true) {
    const toast = document.getElementById('admin-toast');
    const toastMsg = document.getElementById('admin-toast-msg');
    const toastIcon = document.getElementById('admin-toast-icon');
    if (!toast || !toastMsg) return;

    toastMsg.textContent = msg;
    if (isSuccess) {
      toast.className = 'fixed bottom-8 right-8 z-50 flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-[#161a14] px-5 py-4 text-xs font-semibold text-emerald-300 shadow-2xl transition-all duration-300 transform translate-y-0 opacity-100';
      toastIcon.setAttribute('data-lucide', 'check-circle-2');
    } else {
      toast.className = 'fixed bottom-8 right-8 z-50 flex items-center gap-3 rounded-2xl border border-rose-500/30 bg-[#1f1515] px-5 py-4 text-xs font-semibold text-rose-300 shadow-2xl transition-all duration-300 transform translate-y-0 opacity-100';
      toastIcon.setAttribute('data-lucide', 'alert-circle');
    }
    lucide.createIcons();

    setTimeout(() => {
      toast.classList.add('opacity-0', 'translate-y-4');
    }, 4500);
  }

  document.getElementById('notification-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-broadcast-btn');
    const btnText = document.getElementById('submit-btn-text');
    btn.disabled = true;
    btnText.textContent = 'Dispatching broadcast...';

    const formData = new FormData(this);

    try {
      const res = await fetch(base + '/admin/notifications/send', {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        }
      });
      const data = await res.json();

      if (data.success) {
        showToast(data.message, true);
        setTimeout(() => { window.location.reload(); }, 1200);
      } else {
        showToast(data.message || 'Failed to dispatch broadcast.', false);
      }
    } catch (err) {
      showToast('Network error while dispatching broadcast.', false);
    } finally {
      btn.disabled = false;
      btnText.textContent = 'Dispatch Omnichannel Notification';
    }
  });

  async function deleteBroadcast(id) {
    if (!confirm('Are you sure you want to delete this broadcast log?')) return;

    const fd = new FormData();
    fd.append('_csrf', csrf);
    fd.append('id', id);

    try {
      const res = await fetch(base + '/admin/notifications/delete', {
        method: 'POST',
        body: fd,
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        }
      });
      const data = await res.json();
      if (data.success) {
        showToast('Broadcast record deleted.', true);
        setTimeout(() => { window.location.reload(); }, 800);
      } else {
        showToast(data.message || 'Failed to delete record.', false);
      }
    } catch (e) {
      showToast('Error deleting broadcast record.', false);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    updatePreview();
  });
  </script>

  <!-- Luxury Floating Admin Toast Banner -->
  <div id="admin-toast" class="fixed bottom-8 right-8 z-50 flex items-center gap-3 rounded-2xl border border-white/10 bg-[#1c1e17] px-5 py-4 text-xs font-semibold text-white shadow-2xl opacity-0 translate-y-4 pointer-events-none transition-all duration-300">
    <i id="admin-toast-icon" data-lucide="bell" class="h-4 w-4 shrink-0"></i>
    <span id="admin-toast-msg">Notification update</span>
  </div>
</body>
</html>
