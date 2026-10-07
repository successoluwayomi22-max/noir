<?php
declare(strict_types=1);

$b = ($base ?? '') ?: rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
$storeLogo = (string)store_setting('store_logo');
$logoUrl = ($storeLogo !== '' && file_exists(BASE_PATH . '/public' . $storeLogo))
    ? ($b . $storeLogo)
    : ($b . '/assets/icons/noir.svg');

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (!isset($activeNav) || !$activeNav) {
    if (str_ends_with($currentUri, '/admin') || str_ends_with($currentUri, '/admin/')) {
        $activeNav = 'overview';
    } elseif (str_contains($currentUri, '/admin/products')) {
        $activeNav = 'products';
    } elseif (str_contains($currentUri, '/admin/store-catalog')) {
        $activeNav = 'store-catalog';
    } elseif (str_contains($currentUri, '/admin/ai-intelligence')) {
        $activeNav = 'ai-intelligence';
    } elseif (str_contains($currentUri, '/admin/orders')) {
        $activeNav = 'orders';
    } elseif (str_contains($currentUri, '/admin/fleet')) {
        $activeNav = 'fleet';
    } elseif (str_contains($currentUri, '/admin/video-commerce')) {
        $activeNav = 'video-commerce';
    } elseif (str_contains($currentUri, '/admin/community-feed')) {
        $activeNav = 'community-feed';
    } elseif (str_contains($currentUri, '/admin/customers')) {
        $activeNav = 'customers';
    } elseif (str_contains($currentUri, '/admin/staff-applications')) {
        $activeNav = 'staff-applications';
    } elseif (str_contains($currentUri, '/admin/reports')) {
        $activeNav = 'reports';
    } elseif (str_contains($currentUri, '/admin/security')) {
        $activeNav = 'security';
    } elseif (str_contains($currentUri, '/admin/roles')) {
        $activeNav = 'roles';
    } elseif (str_contains($currentUri, '/admin/settings')) {
        $activeNav = 'settings';
    } elseif (str_contains($currentUri, '/admin/telemetry')) {
        $activeNav = 'telemetry';
    } elseif (str_contains($currentUri, '/admin/system-logs')) {
        $activeNav = 'system-logs';
    } else {
        $activeNav = '';
    }
}
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand-lockup">
        <a class="brand-link" href="<?= e($b . '/') ?>" title="View Storefront">
            <img src="<?= e($logoUrl) ?>" alt="<?= e($storeName) ?>" class="brand-logo-img">
            <span class="brand-name"><?= e($storeName) ?></span>
        </a>
    </div>
    
    <p class="sidebar-label">Workspace</p>
    <a class="side-link <?= $activeNav === 'overview' ? 'active' : '' ?>" href="<?= e($b . '/admin') ?>">
        <i data-lucide="layout-dashboard" class="side-nav-icon"></i>
        <span>Overview</span>
    </a>
    <a class="side-link <?= $activeNav === 'products' ? 'active' : '' ?>" href="<?= e($b . '/admin/products') ?>">
        <i data-lucide="package" class="side-nav-icon"></i>
        <span>Products</span>
    </a>
    <a class="side-link <?= $activeNav === 'store-catalog' ? 'active' : '' ?>" href="<?= e($b . '/admin/store-catalog') ?>">
        <i data-lucide="layers" class="side-nav-icon"></i>
        <span>Store catalogue</span>
    </a>
    <a class="side-link <?= $activeNav === 'ai-intelligence' ? 'active' : '' ?>" href="<?= e($b . '/admin/ai-intelligence') ?>">
        <i data-lucide="sparkles" class="side-nav-icon"></i>
        <span>AI Intelligence</span>
    </a>
    <a class="side-link <?= $activeNav === 'orders' ? 'active' : '' ?>" href="<?= e($b . '/admin/orders') ?>">
        <i data-lucide="shopping-bag" class="side-nav-icon"></i>
        <span>Orders</span>
    </a>
    <a class="side-link <?= $activeNav === 'fleet' ? 'active' : '' ?>" href="<?= e($b . '/admin/fleet') ?>">
        <i data-lucide="truck" class="side-nav-icon"></i>
        <span>Fleet &amp; Couriers</span>
    </a>
    <a class="side-link <?= $activeNav === 'video-commerce' ? 'active' : '' ?>" href="<?= e($b . '/admin/video-commerce') ?>">
        <i data-lucide="video" class="side-nav-icon"></i>
        <span>Video Commerce</span>
    </a>
    <a class="side-link <?= $activeNav === 'community-feed' ? 'active' : '' ?>" href="<?= e($b . '/admin/community-feed') ?>">
        <i data-lucide="message-square" class="side-nav-icon"></i>
        <span>Community Feed</span>
    </a>
    <a class="side-link <?= $activeNav === 'notifications' ? 'active' : '' ?>" href="<?= e($b . '/admin/notifications') ?>">
        <i data-lucide="bell-ring" class="side-nav-icon"></i>
        <span>Notifications</span>
    </a>
    <a class="side-link <?= $activeNav === 'customers' ? 'active' : '' ?>" href="<?= e($b . '/admin/customers') ?>">
        <i data-lucide="users" class="side-nav-icon"></i>
        <span>Customers</span>
    </a>
    <a class="side-link <?= $activeNav === 'staff-applications' ? 'active' : '' ?>" href="<?= e($b . '/admin/staff-applications') ?>">
        <i data-lucide="user-plus" class="side-nav-icon"></i>
        <span>Staff applications</span>
    </a>
    <a class="side-link <?= $activeNav === 'reports' ? 'active' : '' ?>" href="<?= e($b . '/admin/reports') ?>">
        <i data-lucide="bar-chart-3" class="side-nav-icon"></i>
        <span>Reports</span>
    </a>
    <a class="side-link <?= $activeNav === 'security' ? 'active' : '' ?>" href="<?= e($b . '/admin/security') ?>">
        <i data-lucide="shield-alert" class="side-nav-icon"></i>
        <span>Security Shield</span>
    </a>

    <p class="sidebar-label">System</p>
    <a class="side-link <?= $activeNav === 'roles' ? 'active' : '' ?>" href="<?= e($b . '/admin/roles') ?>">
        <i data-lucide="shield-check" class="side-nav-icon"></i>
        <span>Staff</span>
    </a>
    <a class="side-link <?= $activeNav === 'settings' ? 'active' : '' ?>" href="<?= e($b . '/admin/settings') ?>">
        <i data-lucide="settings" class="side-nav-icon"></i>
        <span>Settings</span>
    </a>
    <a class="side-link <?= $activeNav === 'telemetry' ? 'active' : '' ?>" href="<?= e($b . '/admin/telemetry') ?>">
        <i data-lucide="activity" class="side-nav-icon"></i>
        <span>APM Telemetry</span>
    </a>
    <a class="side-link <?= $activeNav === 'heap' ? 'active' : '' ?>" href="<?= e($b . '/admin/heap') ?>">
        <i data-lucide="cpu" class="side-nav-icon"></i>
        <span>Heap Profiler</span>
    </a>
    <a class="side-link <?= $activeNav === 'system-logs' ? 'active' : '' ?>" href="<?= e($b . '/admin/system-logs') ?>">
        <i data-lucide="terminal" class="side-nav-icon"></i>
        <span>System Logs</span>
    </a>
    <a class="side-link" href="<?= e($b . '/') ?>" target="_blank" rel="noopener">
        <i data-lucide="external-link" class="side-nav-icon"></i>
        <span>View storefront ↗</span>
    </a>
</aside>

<style>
/* Administrative Layout Foundation */
body.admin-page, body {
    margin: 0;
    padding: 0;
}
.admin-sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 250px;
    background: var(--brand-dark, #1f221a);
    color: var(--brand-on-dark, #e9e9e0);
    padding: 24px 16px;
    overflow-y: auto;
    z-index: 50;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    box-sizing: border-box;
}
.admin-main {
    margin-left: 250px !important;
    min-height: 100vh;
    box-sizing: border-box;
    display: block;
    position: relative;
}
.admin-sidebar::-webkit-scrollbar {
    width: 5px;
}
.admin-sidebar::-webkit-scrollbar-thumb {
    background: color-mix(in srgb, var(--brand-primary, #687158) 40%, rgba(255, 255, 255, 0.1));
    border-radius: 9999px;
}
.sidebar-brand-lockup {
    padding: 4px 4px 18px;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.sidebar-brand-lockup .brand-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #ffffff;
    transition: opacity 0.2s ease;
    border: none !important;
}
.sidebar-brand-lockup .brand-link:hover {
    opacity: 0.9;
}
.sidebar-brand-lockup .brand-logo-img {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: #252820;
}
.sidebar-brand-lockup .brand-name {
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.14em;
    color: var(--brand-paper, #f7f7f2);
    text-transform: uppercase;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.admin-sidebar .brand {
    border: none !important;
    text-decoration: none !important;
}
.admin-sidebar .sidebar-label {
    margin: 20px 12px 6px;
    color: var(--brand-muted, #8e9187);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .2em;
    text-transform: uppercase;
}
.admin-sidebar .side-link {
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: 10px;
    padding: 9px 12px;
    color: #b9bcb2;
    font-size: 12.5px;
    text-decoration: none;
    transition: background .18s ease, color .18s ease, transform .18s ease, box-shadow .18s ease;
    margin-bottom: 2px;
}
.admin-sidebar .side-link:hover {
    background: color-mix(in srgb, var(--brand-primary, #687158) 32%, rgba(255, 255, 255, 0.05));
    color: #ffffff;
    transform: translateX(3px);
}
.admin-sidebar .side-link.active {
    background: var(--brand-primary, #687158);
    color: var(--brand-on-primary, #ffffff);
    font-weight: 600;
    box-shadow: 0 4px 14px color-mix(in srgb, var(--brand-primary, #687158) 40%, transparent);
}
.admin-sidebar .side-nav-icon {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
    stroke-width: 2px;
    opacity: 0.85;
}
.admin-sidebar .side-link.active .side-nav-icon {
    opacity: 1;
}
@media (max-width: 900px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform .3s cubic-bezier(0.2, 0.8, 0.2, 1);
        width: min(280px, 85vw);
        z-index: 999995 !important;
    }
    .admin-sidebar.admin-mobile-open {
        transform: translateX(0) !important;
        box-shadow: 0 0 50px rgba(0,0,0,0.5) !important;
    }
    .admin-main {
        margin-left: 0 !important;
        padding-top: 60px !important;
    }
}
</style>
<script>
if (window.lucide && typeof lucide.createIcons === 'function') {
    lucide.createIcons();
} else {
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    });
}
</script>
