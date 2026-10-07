<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$user = \Noir\Core\Auth::user(); 
$storeName = store_setting('store_name', 'Noir & Co.');

$totalPosts = count($posts);
$activePosts = 0;
$totalLikes = 0;
$totalProductsTagged = 0;

foreach ($posts as $p) {
    if (!empty($p['is_active'])) {
        $activePosts++;
    }
    $totalLikes += (int)($p['likes_count'] ?? 0);
    $totalProductsTagged += (int)($p['products_count'] ?? 0);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Community Feed — <?= e($storeName) ?></title>
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
    .feed-card-glow:hover {
      box-shadow: 0 20px 35px -10px rgba(0,0,0,0.14), 0 0 0 1px rgba(104, 113, 88, 0.25);
    }
  </style>
</head>
<body class="admin-page bg-[#f6f6f1] text-[#252820]">
  <?php $activeNav = 'community-feed'; include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="admin-main">
    <!-- Topbar Component -->
    <?php 
    $eyebrow = 'Social Showcase & Community';
    $title = 'Community Feed Curation';
    $subtitle = 'Curate customer showcases, runway styles, and tagged product highlights for the mobile social discovery stream.';
    include __DIR__ . '/partials/topbar.php'; 
    ?>

    <div class="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      
      <!-- Top Metrics Grid -->
      <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm transition hover:shadow-md">
          <div class="flex items-center justify-between text-[#8e9187]">
            <span class="text-[11px] font-bold uppercase tracking-wider">Total Feed Posts</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f1eb] text-[#687158]">
              <i data-lucide="layout-grid" class="h-4 w-4"></i>
            </div>
          </div>
          <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-[#252820]"><?= number_format($totalPosts) ?></strong>
          <small class="text-xs text-[#8e9187]">Published showcases</small>
        </div>

        <div class="rounded-2xl border border-[#ddead5] bg-[#fafdfa] p-5 shadow-sm transition hover:shadow-md">
          <div class="flex items-center justify-between text-[#497042]">
            <span class="text-[11px] font-bold uppercase tracking-wider">Live on App</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#dfeedc] text-[#497042]">
              <i data-lucide="eye" class="h-4 w-4"></i>
            </div>
          </div>
          <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-[#385c32]"><?= number_format($activePosts) ?></strong>
          <small class="text-xs text-[#497042]/80">Visible to customers</small>
        </div>

        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm transition hover:shadow-md">
          <div class="flex items-center justify-between text-[#8e9187]">
            <span class="text-[11px] font-bold uppercase tracking-wider">Community Likes</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#fbe7e6] text-[#c53030]">
              <i data-lucide="heart" class="h-4 w-4"></i>
            </div>
          </div>
          <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-[#252820]"><?= number_format($totalLikes) ?></strong>
          <small class="text-xs text-[#8e9187]">Audience engagements</small>
        </div>

        <div class="rounded-2xl border border-[#e5e5dd] bg-white p-5 shadow-sm transition hover:shadow-md">
          <div class="flex items-center justify-between text-[#8e9187]">
            <span class="text-[11px] font-bold uppercase tracking-wider">Tagged Garments</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f1eb] text-[#687158]">
              <i data-lucide="shopping-bag" class="h-4 w-4"></i>
            </div>
          </div>
          <strong class="mt-3 block text-2xl lg:text-3xl font-extrabold text-[#252820]"><?= number_format($totalProductsTagged) ?></strong>
          <small class="text-xs text-[#8e9187]">Shoppable attachments</small>
        </div>
      </section>

      <!-- Search, Filter & Action Toolbar -->
      <section class="flex flex-col gap-4 rounded-2xl border border-[#e5e5dd] bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <!-- Search Bar -->
          <div class="relative min-w-[260px] flex-1 max-w-md">
            <i data-lucide="search" class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#92948a]"></i>
            <input 
              type="search" 
              id="post-search" 
              placeholder="Search by creator handle or caption keyword…" 
              class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] py-2.5 pl-10 pr-4 text-xs font-medium outline-none transition focus:border-[#687158] focus:bg-white focus:ring-2 focus:ring-[#687158]/10"
              oninput="filterPosts()"
            >
          </div>

          <!-- Status Filter Tabs -->
          <div class="flex items-center rounded-xl border border-[#deded6] bg-[#fbfbf8] p-1 text-xs">
            <button type="button" onclick="setStatusFilter('all')" class="status-pill active rounded-lg px-3 py-1.5 font-bold transition" data-status="all">All Posts</button>
            <button type="button" onclick="setStatusFilter('active')" class="status-pill rounded-lg px-3 py-1.5 font-medium text-[#77796f] transition" data-status="active">Live on App</button>
            <button type="button" onclick="setStatusFilter('hidden')" class="status-pill rounded-lg px-3 py-1.5 font-medium text-[#77796f] transition" data-status="hidden">Hidden</button>
          </div>
        </div>

        <button 
          type="button" 
          onclick="openForm()" 
          class="brand-btn-primary inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold shadow-md"
        >
          <i data-lucide="plus-circle" class="h-4 w-4"></i>
          <span>Create Community Post</span>
        </button>
      </section>

      <!-- Feed Grid (Luxury Lookbook Cards) -->
      <?php if (empty($posts)): ?>
        <div class="rounded-3xl border border-dashed border-[#dcdcd4] bg-white p-16 text-center shadow-sm">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#eef0e9] text-[#687158] shadow-inner">
            <i data-lucide="image-off" class="h-8 w-8"></i>
          </div>
          <h3 class="mt-5 text-lg font-extrabold text-[#252820]">No Community Posts Yet</h3>
          <p class="mt-2 text-xs text-[#77796f] max-w-md mx-auto leading-relaxed">
            Curate customer looks, influencer editorial runway styles, and tagged product features to launch your mobile social discovery stream.
          </p>
          <button 
            type="button" 
            onclick="openForm()" 
            class="brand-btn-primary mt-6 inline-flex items-center gap-2 rounded-xl px-6 py-3 text-xs font-bold shadow-md"
          >
            <i data-lucide="plus-circle" class="h-4 w-4"></i> Create First Look
          </button>
        </div>
      <?php else: ?>
        <div id="posts-grid" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($posts as $post): 
            $resolvedImg = media_url($base, (string)$post['image_url']);
            $isActive = (bool)$post['is_active'];
            $searchKey = strtolower(($post['user_handle'] ?? '') . ' ' . ($post['caption'] ?? ''));
          ?>
            <div 
              class="post-card feed-card-glow group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-[#e5e5dd] bg-white shadow-sm transition-all duration-300"
              data-search="<?= e($searchKey) ?>"
              data-status="<?= $isActive ? 'active' : 'hidden' ?>"
            >
              <!-- Media Visual Container (1:1 Ratio) -->
              <div class="relative aspect-square w-full overflow-hidden bg-[#1e201b]">
                <img 
                  src="<?= e($resolvedImg) ?>" 
                  alt="<?= e($post['user_handle']) ?>"
                  class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                  onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=800&auto=format&fit=crop'"
                >

                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/40"></div>

                <!-- Top Badges & Actions -->
                <div class="absolute inset-x-3.5 top-3.5 flex items-center justify-between z-10">
                  <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider backdrop-blur-md shadow-md <?= $isActive ? 'bg-emerald-500/90 text-white' : 'bg-black/70 text-gray-300' ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?= $isActive ? 'bg-white' : 'bg-gray-400' ?>"></span>
                    <?= $isActive ? 'Live on App' : 'Hidden Draft' ?>
                  </span>

                  <div class="flex items-center gap-1.5">
                    <button 
                      type="button" 
                      onclick="toggleItem(<?= (int)$post['id'] ?>)" 
                      class="flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-md transition hover:bg-white hover:text-[#252820] shadow"
                      title="<?= $isActive ? 'Hide look' : 'Make live' ?>"
                    >
                      <i data-lucide="<?= $isActive ? 'eye-off' : 'eye' ?>" class="h-3.5 w-3.5"></i>
                    </button>
                    <button 
                      type="button" 
                      onclick="deleteItem(<?= (int)$post['id'] ?>)" 
                      class="flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-rose-400 backdrop-blur-md transition hover:bg-rose-600 hover:text-white shadow"
                      title="Delete Post"
                    >
                      <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                    </button>
                  </div>
                </div>

                <!-- Floating Handle Overlay on Image -->
                <div class="absolute inset-x-3.5 bottom-3.5 z-10 flex items-center justify-between text-white">
                  <div class="flex items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-full bg-white/20 text-xs font-extrabold text-white backdrop-blur-md">
                      <?= e(strtoupper(substr($post['user_handle'] ?? '@', 1, 1))) ?>
                    </span>
                    <strong class="text-xs font-bold drop-shadow"><?= e($post['user_handle']) ?></strong>
                  </div>
                  <span class="inline-flex items-center gap-1 rounded-full bg-black/40 px-2.5 py-1 text-xs font-bold text-rose-300 backdrop-blur-md">
                    <i data-lucide="heart" class="h-3.5 w-3.5 fill-current text-rose-400"></i>
                    <?= number_format((int)$post['likes_count']) ?>
                  </span>
                </div>
              </div>

              <!-- Post Details Body -->
              <div class="flex flex-1 flex-col justify-between p-5">
                <div>
                  <p class="text-xs font-medium leading-relaxed text-[#55584f] line-clamp-3" title="<?= e($post['caption']) ?>">
                    <?= e($post['caption']) ?>
                  </p>
                </div>

                <!-- Footer Meta & Attached Product Counts -->
                <div class="mt-4 flex items-center justify-between border-t border-[#f0f1ec] pt-3 text-xs text-[#8e9187]">
                  <div class="flex items-center gap-1.5 font-bold text-[#687158]">
                    <i data-lucide="tag" class="h-3.5 w-3.5"></i>
                    <span><?= number_format((int)$post['products_count']) ?> tagged <?= ((int)$post['products_count'] === 1) ? 'item' : 'items' ?></span>
                  </div>
                  <span class="text-[11px] font-semibold text-[#a0a397]">Post #<?= (int)$post['id'] ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div id="no-post-matches" class="hidden py-16 text-center text-[#8e9187]">
          <i data-lucide="search-x" class="mx-auto h-10 w-10 text-[#b3b6aa]"></i>
          <p class="mt-3 text-sm font-bold text-[#252820]">No community looks match your search</p>
          <p class="mt-1 text-xs text-[#77796f]">Try adjusting your search query or reset the filter to All.</p>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <!-- Add Community Post Modal -->
  <div id="catalog-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm overflow-y-auto">
    <form id="catalog-form" class="my-auto w-full max-w-lg rounded-3xl bg-white p-6 lg:p-8 shadow-2xl flex flex-col max-h-[92vh]">
      <div class="flex items-start justify-between border-b border-[#e8e8e0] pb-4 shrink-0">
        <div>
          <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Social Pipeline</p>
          <h2 class="text-xl font-extrabold text-[#252820]">Add Community Lookbook Post</h2>
        </div>
        <button type="button" onclick="closeForm()" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
          <i data-lucide="x" class="h-5 w-5"></i>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto py-5 space-y-4" style="overscroll-behavior: contain;">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
          <label class="block text-xs font-bold text-[#252820] mb-1.5">Creator / Influencer Handle *</label>
          <input 
            name="user_handle" 
            required 
            value="@noir.atelier" 
            placeholder="e.g. @amara.couture" 
            class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
          >
        </div>

        <div>
          <label class="block text-xs font-bold text-[#252820] mb-1.5">Lookbook Photo Image URL *</label>
          <input 
            type="text" 
            name="image_url" 
            id="image-url-input" 
            required 
            placeholder="https://images.unsplash.com/... or /uploads/..." 
            class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white" 
            oninput="previewImage(this.value)"
          >
          <div id="modal-img-preview" class="mt-2.5 hidden h-44 w-full rounded-2xl bg-cover bg-center border border-[#deded6] shadow-sm"></div>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#252820] mb-1.5">Caption &amp; Styling Story *</label>
          <textarea 
            name="caption" 
            required 
            rows="3" 
            placeholder="Describe the styling notes, runway inspiration, or customer showcase…" 
            class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-[#252820] mb-1.5">Tagged Products Count</label>
            <input 
              type="number" 
              name="products_count" 
              value="1" 
              min="0" 
              class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
            >
          </div>
          <div>
            <label class="block text-xs font-bold text-[#252820] mb-1.5">Initial Likes Count</label>
            <input 
              type="number" 
              name="likes_count" 
              value="85" 
              min="0" 
              class="w-full rounded-xl border border-[#deded6] bg-[#fbfbf8] p-3 text-xs font-medium outline-none focus:border-[#687158] focus:bg-white"
            >
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 border-t border-[#e8e8e0] pt-4 shrink-0">
        <button type="button" onclick="closeForm()" class="rounded-xl border border-[#deded6] px-5 py-2.5 text-xs font-semibold text-[#55584f] hover:bg-[#f5f5f0]">Cancel</button>
        <button type="submit" class="brand-btn-primary rounded-xl px-6 py-2.5 text-xs font-bold shadow-md">Publish Look</button>
      </div>
    </form>
  </div>

  <div id="catalog-toast" class="fixed bottom-6 right-6 z-[60] hidden rounded-2xl bg-[#252820] px-5 py-3 text-xs font-bold text-white shadow-2xl transition-all"></div>

  <script>
    lucide.createIcons();
    const endpoint = '<?= e(($base ?: '') . '/admin/community-feed/action') ?>';
    const csrf = '<?= e(csrf_token()) ?>';
    let currentStatusFilter = 'all';

    function toast(m) {
      const t = document.getElementById('catalog-toast');
      if (!t) return;
      t.textContent = m;
      t.classList.remove('hidden');
      setTimeout(() => t.classList.add('hidden'), 2800);
    }

    function openForm() {
      const m = document.getElementById('catalog-modal');
      m.classList.remove('hidden');
      m.classList.add('flex');
    }

    function closeForm() {
      const m = document.getElementById('catalog-modal');
      m.classList.add('hidden');
      m.classList.remove('flex');
    }

    function previewImage(url) {
      const p = document.getElementById('modal-img-preview');
      if (url && (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/'))) {
        p.style.backgroundImage = `url('${url}')`;
        p.classList.remove('hidden');
      } else {
        p.classList.add('hidden');
      }
    }

    // Keybindings: Esc closes modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
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
      filterPosts();
    }

    function filterPosts() {
      const q = (document.getElementById('post-search').value || '').trim().toLowerCase();
      const cards = document.querySelectorAll('.post-card');
      let visible = 0;

      cards.forEach(c => {
        const search = c.dataset.search || '';
        const status = c.dataset.status || '';
        const matchesQuery = !q || search.includes(q);
        const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;

        if (matchesQuery && matchesStatus) {
          c.style.display = '';
          visible++;
        } else {
          c.style.display = 'none';
        }
      });

      const no = document.getElementById('no-post-matches');
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
          body: new URLSearchParams({ ...body, _csrf: csrf })
        });
        const d = await r.json();
        toast(d.message || 'Updated');
        if (d.success) {
          setTimeout(() => location.reload(), 450);
        }
      } catch (err) {
        toast('Failed to update feed. Please check network connection.');
      }
    }

    function toggleItem(id) {
      send({ id, action: 'toggle_status' });
    }

    function deleteItem(id) {
      if (confirm('Are you sure you want to remove this community post?')) {
        send({ id, action: 'delete' });
      }
    }

    document.getElementById('catalog-form').onsubmit = function(e) {
      e.preventDefault();
      send(Object.fromEntries(new FormData(this)));
      closeForm();
    };

    document.addEventListener('DOMContentLoaded', () => {
      setStatusFilter('all');
    });
  </script>
</body>
</html>
