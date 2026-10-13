<?php 
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user();
$storeName = store_setting('store_name', 'Noir & Co.');

$totalVideos = count($videos);
$activeVideos = 0;
$totalReactions = 0;
$linkedProductsCount = 0;

foreach ($videos as $v) {
    if (($v['status'] ?? 'active') === 'active') {
        $activeVideos++;
    }
    $totalReactions += (int)($v['likes_count'] ?? 0) + (int)($v['comments_count'] ?? 0);
    if (!empty($v['product_id'])) {
        $linkedProductsCount++;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Video Commerce &amp; Reels — <?= e($storeName) ?></title>
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
    .video-card-glow:hover {
      box-shadow: 0 20px 35px -10px rgba(0,0,0,0.18), 0 0 0 1px rgba(104, 113, 88, 0.3);
    }
  </style>
</head>
<body class="admin-page bg-[#f6f6f1] text-[#252820]">
  <?php $activeNav = 'video-commerce'; include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="admin-main">
    <!-- Topbar Component -->
    <?php 
    $eyebrow = 'Shoppable Media & Reels';
    $title = 'Video Commerce Pipeline';
    $subtitle = 'Curate TikTok & Reels-style vertical shoppable videos linked directly to boutique catalog products.';
    include __DIR__ . '/partials/topbar.php'; 
    ?>

    <div class="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      
      <!-- Top Metrics Grid -->
      <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm transition hover:shadow-md">
          <div class="flex items-center justify-between text-[#8e9187]">
            <span class="text-[11px] font-bold uppercase tracking-wider">Total Video Reels</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f1eb] text-[#687158]">
              <i data-lucide="video" class="h-4 w-4"></i>
            </div>
          </div>
          <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-[#252820]"><?= number_format($totalVideos) ?></strong>
          <small class="text-xs text-[#8e9187]">Cataloged shoppable clips</small>
        </div>

        <div class="rounded-2xl border border-[#ddead5] bg-[#fafdfa] p-5 shadow-sm transition hover:shadow-md">
          <div class="flex items-center justify-between text-[#497042]">
            <span class="text-[11px] font-bold uppercase tracking-wider">Active on App Feed</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#dfeedc] text-[#497042]">
              <i data-lucide="smartphone" class="h-4 w-4"></i>
            </div>
          </div>
          <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-[#385c32]"><?= number_format($activeVideos) ?></strong>
          <small class="text-xs text-[#497042]/80">Streaming to clientele</small>
        </div>

        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm transition hover:shadow-md">
          <div class="flex items-center justify-between text-[#8e9187]">
            <span class="text-[11px] font-bold uppercase tracking-wider">Total Reactions</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#fbe7e6] text-[#c53030]">
              <i data-lucide="heart" class="h-4 w-4"></i>
            </div>
          </div>
          <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-[#252820]"><?= number_format($totalReactions) ?></strong>
          <small class="text-xs text-[#8e9187]">Likes and comment engagements</small>
        </div>

        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm transition hover:shadow-md">
          <div class="flex items-center justify-between text-[#8e9187]">
            <span class="text-[11px] font-bold uppercase tracking-wider">Linked Products</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f1eb] text-[#687158]">
              <i data-lucide="shopping-bag" class="h-4 w-4"></i>
            </div>
          </div>
          <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-[#252820]"><?= number_format($linkedProductsCount) ?></strong>
          <small class="text-xs text-[#8e9187]">Direct checkout touchpoints</small>
        </div>
      </section>

      <!-- Search, Filter & Action Bar -->
      <section class="flex flex-col gap-4 rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <!-- Search Input -->
          <div class="relative min-w-[260px] flex-1 max-w-md">
            <i data-lucide="search" class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#92948a]"></i>
            <input 
              type="search" 
              id="video-search" 
              placeholder="Search by title, handle, or product…" 
              class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] py-2.5 pl-10 pr-4 text-xs font-medium outline-none transition focus:border-[#687158] focus:bg-white focus:ring-2 focus:ring-[#687158]/10"
              oninput="filterVideos()"
            >
          </div>

          <!-- Status Filters -->
          <div class="flex items-center rounded-xl border border-[#deded6] bg-[#fbfbf8] p-1 text-xs">
            <button type="button" onclick="setStatusFilter('all')" class="status-pill active rounded-lg px-3 py-1.5 font-bold transition" data-status="all">All Videos</button>
            <button type="button" onclick="setStatusFilter('active')" class="status-pill rounded-lg px-3 py-1.5 font-medium text-[#77796f] transition" data-status="active">Active</button>
            <button type="button" onclick="setStatusFilter('draft')" class="status-pill rounded-lg px-3 py-1.5 font-medium text-[#77796f] transition" data-status="draft">Draft / Hidden</button>
          </div>
        </div>

        <button 
          type="button" 
          onclick="openAddVideoModal()" 
          class="brand-btn-primary inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold shadow-md"
        >
          <i data-lucide="plus-circle" class="h-4 w-4"></i>
          <span>Add Shoppable Reel</span>
        </button>
      </section>

      <!-- Video Feed Grid (9:16 Smartphone Ratio Luxury Cards) -->
      <?php if (empty($videos)): ?>
        <div class="rounded-3xl border border-dashed border-[#dcdcd4] bg-white p-16 text-center shadow-sm">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#eef0e9] text-[#687158] shadow-inner">
            <i data-lucide="video" class="h-8 w-8"></i>
          </div>
          <h3 class="mt-5 text-lg font-extrabold text-[#252820]">No Videos in Content Pipeline</h3>
          <p class="mt-2 text-xs text-[#77796f] max-w-md mx-auto leading-relaxed">
            Populate your mobile app and storefront video reels by adding high-converting vertical MP4 clips tagged directly to boutique garments.
          </p>
          <button 
            type="button" 
            onclick="openAddVideoModal()" 
            class="brand-btn-primary mt-6 inline-flex items-center gap-2 rounded-xl px-6 py-3 text-xs font-bold shadow-md"
          >
            <i data-lucide="plus-circle" class="h-4 w-4"></i> Add First Shoppable Video
          </button>
        </div>
      <?php else: ?>
        <div id="video-grid" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <?php foreach ($videos as $video): 
            $isActive = ($video['status'] ?? 'active') === 'active';
            $resolvedThumbnail = !empty($video['thumbnail']) 
              ? media_url($base ?: '', $video['thumbnail']) 
              : (!empty($video['product_image']) ? media_url($base ?: '', $video['product_image']) : '');
            $videoUrl = (string)($video['url'] ?? '');
            $searchKey = strtolower(($video['title'] ?? '') . ' ' . ($video['user'] ?? '') . ' ' . ($video['product_name'] ?? ''));
          ?>
            <div 
              class="video-card video-card-glow group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-[#e5e5dd] bg-white shadow-sm transition-all duration-300"
              data-search="<?= e($searchKey) ?>"
              data-status="<?= $isActive ? 'active' : 'draft' ?>"
            >
              <!-- 9:16 Media Aspect Container -->
              <div class="relative aspect-[9/14] w-full overflow-hidden bg-[#181916]">
                <?php if (!empty($resolvedThumbnail)): ?>
                  <img 
                    src="<?= e($resolvedThumbnail) ?>" 
                    alt="<?= e($video['title']) ?>" 
                    class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105 opacity-90"
                    onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800'"
                  >
                <?php else: ?>
                  <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-white/40">
                    <i data-lucide="video" class="h-12 w-12 stroke-[1.5]"></i>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-white/60">Shoppable Reel</span>
                  </div>
                <?php endif; ?>

                <!-- Cinema Dark Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/25 to-black/60"></div>

                <!-- Top Header: Status & Live Controls -->
                <div class="absolute inset-x-3.5 top-3.5 flex items-center justify-between z-10">
                  <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider backdrop-blur-md shadow-md <?= $isActive ? 'bg-emerald-500/90 text-white' : 'bg-black/70 text-gray-300' ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?= $isActive ? 'bg-white' : 'bg-gray-400' ?>"></span>
                    <?= $isActive ? 'Active in App' : 'Hidden Draft' ?>
                  </span>

                  <!-- Quick Action Buttons -->
                  <div class="flex items-center gap-1.5">
                    <button 
                      type="button" 
                      onclick="toggleItem(<?= (int)$video['id'] ?>)" 
                      class="flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-md transition hover:bg-white hover:text-[#252820] shadow"
                      title="<?= $isActive ? 'Hide from feed' : 'Activate in feed' ?>"
                    >
                      <i data-lucide="<?= $isActive ? 'eye-off' : 'eye' ?>" class="h-3.5 w-3.5"></i>
                    </button>
                    <button 
                      type="button" 
                      onclick="deleteItem(<?= (int)$video['id'] ?>)" 
                      class="flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-rose-400 backdrop-blur-md transition hover:bg-rose-600 hover:text-white shadow"
                      title="Delete Reel"
                    >
                      <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                    </button>
                  </div>
                </div>

                <!-- Center Play Badge Button (Triggers Live Player Modal) -->
                <button 
                  type="button" 
                  onclick="playVideoPreview('<?= e(addslashes($videoUrl)) ?>', '<?= e(addslashes($video['title'])) ?>', '<?= e(addslashes($video['user'] ?? '@noir.atelier')) ?>', '<?= e(addslashes($video['product_name'] ?? '')) ?>', '<?= !empty($video['product_price']) ? number_format((float)$video['product_price']) : '' ?>', '<?= e(addslashes($resolvedThumbnail)) ?>')"
                  class="absolute inset-0 m-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-md transition-all duration-300 group-hover:scale-110 group-hover:bg-white group-hover:text-[#252820] shadow-2xl"
                  title="Watch Video Preview"
                >
                  <i data-lucide="play" class="h-6 w-6 fill-current translate-x-0.5"></i>
                </button>

                <!-- Bottom Information & Attached Product -->
                <div class="absolute inset-x-3.5 bottom-3.5 z-10 space-y-3">
                  <!-- User Handle & Caption -->
                  <div>
                    <div class="flex items-center gap-1.5 text-white/80">
                      <span class="grid h-5 w-5 place-items-center rounded-full bg-white/20 text-[10px] font-bold text-white">
                        <?= e(strtoupper(substr($video['user'] ?? '@', 1, 1))) ?>
                      </span>
                      <strong class="text-xs font-semibold text-white"><?= e($video['user'] ?? '@noir.atelier') ?></strong>
                    </div>
                    <h3 class="mt-1 line-clamp-2 text-xs font-bold leading-snug text-white" title="<?= e($video['title']) ?>">
                      <?= e($video['title'] ?: 'Untitled Video Reel') ?>
                    </h3>
                  </div>

                  <!-- Linked Product Chip -->
                  <?php if (!empty($video['product_id'])): ?>
                    <div class="flex items-center gap-2.5 rounded-2xl border border-white/20 bg-black/60 p-2 backdrop-blur-md transition hover:bg-black/80">
                      <?php if (!empty($video['product_image'])): ?>
                        <img 
                          src="<?= e(media_url($base ?: '', $video['product_image'])) ?>" 
                          class="h-9 w-9 shrink-0 rounded-xl object-cover"
                          onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=400'"
                        >
                      <?php else: ?>
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                          <i data-lucide="tag" class="h-4 w-4"></i>
                        </div>
                      <?php endif; ?>
                      <div class="min-w-0 flex-1">
                        <p class="truncate text-[11px] font-bold text-white"><?= e($video['product_name'] ?? 'Linked Product') ?></p>
                        <?php if (!empty($video['product_price'])): ?>
                          <p class="text-[11px] font-black text-[#d4dbbe]">₦<?= number_format((float)$video['product_price']) ?></p>
                        <?php endif; ?>
                      </div>
                      <a 
                        href="<?= e(($base ?: '') . '/product?id=' . (int)$video['product_id']) ?>" 
                        target="_blank" 
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/20 text-white transition hover:bg-white hover:text-black"
                        title="View Product in Boutique"
                      >
                        <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                      </a>
                    </div>
                  <?php else: ?>
                    <div class="flex items-center gap-2 rounded-xl bg-white/10 px-2.5 py-1.5 backdrop-blur-sm text-[10px] text-white/70">
                      <i data-lucide="info" class="h-3.5 w-3.5"></i>
                      <span>No product linked to this reel</span>
                    </div>
                  <?php endif; ?>

                  <!-- Engagement Stats Footer -->
                  <div class="flex items-center justify-between border-t border-white/15 pt-2 text-white/80 text-[11px]">
                    <div class="flex items-center gap-3">
                      <span class="inline-flex items-center gap-1 font-semibold text-rose-400">
                        <i data-lucide="heart" class="h-3.5 w-3.5 fill-current"></i> <?= number_format((int)$video['likes_count']) ?>
                      </span>
                      <span class="inline-flex items-center gap-1 font-semibold text-sky-400">
                        <i data-lucide="message-circle" class="h-3.5 w-3.5 fill-current"></i> <?= number_format((int)$video['comments_count']) ?>
                      </span>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-white/50">Reel #<?= (int)$video['id'] ?></span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div id="no-video-matches" class="hidden py-16 text-center text-[#8e9187]">
          <i data-lucide="search-x" class="mx-auto h-10 w-10 text-[#b3b6aa]"></i>
          <p class="mt-3 text-sm font-bold text-[#252820]">No shoppable videos match your search</p>
          <p class="mt-1 text-xs text-[#77796f]">Try searching for another keyword or switch the filter back to All.</p>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <!-- 1. Live In-Page Video Preview Player Modal -->
  <div id="video-player-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90 p-4 backdrop-blur-md">
    <div class="relative flex flex-col items-center max-w-sm w-full">
      <!-- Top Dismiss Button -->
      <button 
        type="button" 
        onclick="closeVideoPreview()" 
        class="absolute -top-12 right-0 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white hover:text-black"
      >
        <i data-lucide="x" class="h-5 w-5"></i>
      </button>

      <!-- Smartphone Frame Container -->
      <div class="relative w-full aspect-[9/16] overflow-hidden rounded-3xl border-2 border-white/20 bg-black shadow-2xl">
        <video id="preview-video-element" class="h-full w-full object-cover" controls autoplay loop playsinline></video>
        
        <!-- Live Fallback Poster if Video URL is empty or broken -->
        <div id="video-fallback-msg" class="absolute inset-0 hidden flex-col items-center justify-center p-6 text-center bg-black/80 text-white">
          <i data-lucide="alert-circle" class="h-10 w-10 text-amber-400 mb-3"></i>
          <h4 class="text-sm font-bold">Video URL Not Streamable</h4>
          <p class="text-xs text-gray-300 mt-1">This reel does not currently have a direct MP4 link configured.</p>
        </div>
      </div>

      <!-- Preview Context Footer -->
      <div class="mt-3 w-full rounded-2xl bg-white/10 p-3.5 text-white backdrop-blur-md">
        <strong id="preview-creator" class="text-xs font-bold block text-white/90">@creator</strong>
        <p id="preview-title" class="text-xs text-white/70 line-clamp-1 mt-0.5">Video Title</p>
        <div id="preview-product-bar" class="mt-2 hidden items-center justify-between border-t border-white/10 pt-2 text-xs">
          <span id="preview-prod-name" class="font-bold text-white truncate max-w-[180px]">Product</span>
          <span id="preview-prod-price" class="font-black text-[#d4dbbe]">₦0</span>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Add Shoppable Video Modal -->
  <div id="catalog-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm overflow-y-auto">
    <form id="catalog-form" class="my-auto w-full max-w-lg rounded-3xl bg-white p-6 lg:p-8 shadow-2xl flex flex-col max-h-[92vh]">
      <div class="flex items-start justify-between border-b border-[#f1f2eb] pb-4 shrink-0">
        <div>
          <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Content Management</p>
          <h2 class="text-xl font-extrabold text-[#252820]">Add Shoppable Video Reel</h2>
        </div>
        <button type="button" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700" onclick="closeForm()">
          <i data-lucide="x" class="h-5 w-5"></i>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto py-5 space-y-4" style="overscroll-behavior: contain;">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
          <label class="block text-xs font-bold text-[#252820] mb-1.5">Video Caption / Look Title *</label>
          <input 
            name="title" 
            required 
            placeholder="e.g. Minimalist Linen Blazer Styling Session — Atelier 2026" 
            class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
          >
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-[#252820] mb-1.5">Creator Handle</label>
            <input 
              name="user" 
              value="@noir.atelier" 
              class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
            >
          </div>

          <div>
            <label class="block text-xs font-bold text-[#252820] mb-1.5">Tag Boutique Product</label>
            <select 
              name="product_id" 
              class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
            >
              <option value="">-- Optional (Select Product) --</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= (int)$p['id'] ?>">
                  <?= e($p['name']) ?> (₦<?= number_format((float)$p['price']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#252820] mb-1.5">Direct MP4 Video Stream URL *</label>
          <input 
            type="url" 
            name="url" 
            id="video-url-input"
            required 
            placeholder="https://cdn.coverr.co/.../1080p.mp4" 
            class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
          >
          <small class="text-[11px] text-[#8c9183] mt-1 block">Enter a direct HTTPS link to an MP4 video or CDN asset.</small>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#252820] mb-1.5">Thumbnail Poster Image (Optional)</label>
          <input 
            type="url" 
            name="thumbnail" 
            placeholder="https://images.unsplash.com/... (optional)" 
            class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
          >
          <small class="text-[11px] text-[#8c9183] mt-1 block">Leaves empty to automatically inherit the tagged garment's high-res image.</small>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 border-t border-[#f1f2eb] pt-4 shrink-0">
        <button type="button" class="rounded-xl border border-[#deded6] px-5 py-2.5 text-xs font-semibold text-[#555] hover:bg-gray-50" onclick="closeForm()">Cancel</button>
        <button type="submit" class="brand-btn-primary rounded-xl px-6 py-2.5 text-xs font-bold shadow-md">Publish to Mobile Feed</button>
      </div>
    </form>
  </div>

  <div id="catalog-toast" class="fixed bottom-6 right-6 z-[60] hidden rounded-2xl bg-[#252820] px-5 py-3 text-xs font-bold text-white shadow-2xl transition-all"></div>

  <script>
    lucide.createIcons();
    const endpoint = '<?= e(($base ?: '') . '/admin/video-commerce/action') ?>';
    const csrf = '<?= e(csrf_token()) ?>';
    let currentStatusFilter = 'all';

    function toast(m) {
      const t = document.getElementById('catalog-toast');
      if (!t) return;
      t.textContent = m;
      t.classList.remove('hidden');
      setTimeout(() => t.classList.add('hidden'), 2800);
    }

    function openAddVideoModal() {
      const modal = document.getElementById('catalog-modal');
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function closeForm() {
      const modal = document.getElementById('catalog-modal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    function playVideoPreview(url, title, creator, prodName, prodPrice, poster) {
      const modal = document.getElementById('video-player-modal');
      const videoEl = document.getElementById('preview-video-element');
      const fallback = document.getElementById('video-fallback-msg');
      
      document.getElementById('preview-creator').textContent = creator;
      document.getElementById('preview-title').textContent = title;
      
      const prodBar = document.getElementById('preview-product-bar');
      if (prodName) {
        document.getElementById('preview-prod-name').textContent = prodName;
        document.getElementById('preview-prod-price').textContent = prodPrice ? '₦' + prodPrice : '';
        prodBar.classList.remove('hidden');
        prodBar.classList.add('flex');
      } else {
        prodBar.classList.add('hidden');
        prodBar.classList.remove('flex');
      }

      if (url && url.trim().length > 0) {
        videoEl.src = url;
        videoEl.poster = poster || '';
        videoEl.classList.remove('hidden');
        fallback.classList.add('hidden');
        videoEl.play().catch(e => console.log('Autoplay handled:', e));
      } else {
        videoEl.src = '';
        videoEl.classList.add('hidden');
        fallback.classList.remove('hidden');
      }

      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function closeVideoPreview() {
      const modal = document.getElementById('video-player-modal');
      const videoEl = document.getElementById('preview-video-element');
      if (videoEl) {
        videoEl.pause();
        videoEl.src = '';
      }
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    // Keybindings: Esc closes player or modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeVideoPreview();
        closeForm();
      }
    });

    function setStatusFilter(status) {
      currentStatusFilter = status;
      document.querySelectorAll('.status-pill').forEach(btn => {
        const isMatch = btn.dataset.status === status;
        btn.classList.toggle('active', isMatch);
        btn.classList.toggle('bg-white', isMatch);
        btn.classList.toggle('shadow-sm', isMatch);
        btn.classList.toggle('text-[#252820]', isMatch);
        btn.classList.toggle('text-[#77796f]', !isMatch);
      });
      filterVideos();
    }

    function filterVideos() {
      const q = (document.getElementById('video-search').value || '').trim().toLowerCase();
      const cards = document.querySelectorAll('.video-card');
      let visible = 0;

      cards.forEach(card => {
        const search = card.dataset.search || '';
        const status = card.dataset.status || '';
        const matchesQuery = !q || search.includes(q);
        const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;

        if (matchesQuery && matchesStatus) {
          card.style.display = '';
          visible++;
        } else {
          card.style.display = 'none';
        }
      });

      const no = document.getElementById('no-video-matches');
      if (no) no.classList.toggle('hidden', visible > 0 || cards.length === 0);
    }

    async function send(body) {
      try {
        const r = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'application/json'
          },
          body: new URLSearchParams({...body, _csrf: csrf})
        });
        const d = await r.json();
        toast(d.message || 'Operation successful');
        if (d.success) setTimeout(() => location.reload(), 450);
      } catch (err) {
        toast('Operation failed. Please check network connection.');
      }
    }

    function toggleItem(id) {
      send({ id, action: 'toggle_status' });
    }

    function deleteItem(id) {
      if (confirm('Are you sure you want to remove this video from the mobile feed?')) {
        send({ id, action: 'delete' });
      }
    }

    document.getElementById('catalog-form').onsubmit = function(e) {
      e.preventDefault();
      send(Object.fromEntries(new FormData(this)));
      closeForm();
    };

    // Initialize filter pill styles on load
    document.addEventListener('DOMContentLoaded', () => {
      setStatusFilter('all');
    });
  </script>
</body>
</html>
