<?php

declare(strict_types=1);

/**
 * Noir & Co. — Enterprise Architecture Verification Suite
 * Tests all core subsystems, contracts, repositories, validation, and security headers.
 */

require_once __DIR__ . '/../bootstrap.php';

use Noir\Core\Container;
use Noir\Core\Database;
use Noir\Core\Validator;
use Noir\Core\Request;
use Noir\App\Middleware\SecurityHeadersMiddleware;
use Noir\App\Contracts\ProductRepositoryInterface;
use Noir\App\Contracts\StaffRepositoryInterface;
use Noir\App\Contracts\ReportRepositoryInterface;
use Noir\App\Contracts\OrderRepositoryInterface;
use Noir\App\Contracts\SettingRepositoryInterface;

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function assert(bool $condition, string $description): void
    {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m[PASS]\033[0m $description\n";
        } else {
            $this->failed++;
            echo "  \033[31m[FAIL]\033[0m $description\n";
        }
    }

    public function summary(): int
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "TEST RESULTS: {$this->passed} Passed, {$this->failed} Failed\n";
        echo str_repeat('=', 60) . "\n\n";
        return $this->failed > 0 ? 1 : 0;
    }
}

$t = new TestRunner();

echo "\n=== 1. CONTAINER & REPOSITORY CONTRACTS ===\n";
$prodRepo = Container::get('product_repo');
$t->assert($prodRepo instanceof ProductRepositoryInterface, 'ProductRepository implements ProductRepositoryInterface');

$staffRepo = Container::get('staff_repo');
$t->assert($staffRepo instanceof StaffRepositoryInterface, 'StaffRepository implements StaffRepositoryInterface');

$reportRepo = Container::get('report_repo');
$t->assert($reportRepo instanceof ReportRepositoryInterface, 'ReportRepository implements ReportRepositoryInterface');

$orderRepo = Container::get('order_repo');
$t->assert($orderRepo instanceof OrderRepositoryInterface, 'OrderRepository implements OrderRepositoryInterface');

$settingRepo = Container::get('setting_repo');
$t->assert($settingRepo instanceof SettingRepositoryInterface, 'SettingRepository implements SettingRepositoryInterface');

echo "\n=== 2. SINGLE-PASS DATABASE AGGREGATIONS ===\n";
$prodStats = $prodRepo->stats();
$t->assert(isset($prodStats['total'], $prodStats['active'], $prodStats['featured']), 'Product single-pass stats returns total, active, featured');

$staffStats = $staffRepo->stats();
$t->assert(isset($staffStats['total'], $staffStats['active'], $staffStats['roles_count']), 'Staff stats returns total, active, and roles count');

$orderStats = $orderRepo->stats();
$t->assert(isset($orderStats['total'], $orderStats['pending'], $orderStats['revenue']), 'Order stats returns total, pending, and revenue');

$reportSummary = $reportRepo->summary(date('Y-m-d', strtotime('-30 days')), date('Y-m-d', strtotime('+1 day')));
$t->assert(isset($reportSummary['orders'], $reportSummary['revenue'], $reportSummary['new_customers']), 'Report summary returns aggregated metrics');

echo "\n=== 3. DECLARATIVE VALIDATION ENGINE ===\n";
$validData = ['name' => 'Linen Blazer', 'price' => '45000', 'email' => 'client@noir.test', 'status' => 'published'];
$v1 = Validator::make($validData, [
    'name' => 'required|string|min:3|max:100',
    'price' => 'required|numeric|min:0',
    'email' => 'required|email',
    'status' => 'required|in:published,draft,archived',
]);
$t->assert($v1->passes(), 'Validator passes for valid input');

$invalidData = ['name' => 'X', 'price' => '-10', 'email' => 'not-an-email', 'status' => 'unknown'];
$v2 = Validator::make($invalidData, [
    'name' => 'required|min:3',
    'price' => 'required|numeric|min:0',
    'email' => 'required|email',
    'status' => 'required|in:published,draft,archived',
]);
$t->assert($v2->fails(), 'Validator fails for invalid input');
$t->assert(count($v2->errors()) === 4, 'Validator detects all 4 distinct validation errors');

echo "\n=== 4. DATABASE TRANSACTIONS ===\n";
$transResult = Database::transaction(function ($db) {
    return 'transaction_ok';
});
$t->assert($transResult === 'transaction_ok', 'Database::transaction successfully commits and returns result');

echo "\n=== 5. SECURITY HEADERS MIDDLEWARE ===\n";
$req = new Request('GET', '/admin/settings');
$sec = new SecurityHeadersMiddleware();
$sec->handle($req);
$t->assert(true, 'SecurityHeadersMiddleware handles request without throwing');

echo "\n=== 6. VIEW COMPONENT ARCHITECTURE ===\n";
ob_start();
$eyebrow = 'Test Category';
$title = 'Test Title';
$subtitle = 'Test Subtitle';
include BASE_PATH . '/app/Views/admin/partials/topbar.php';
$topbarHtml = ob_get_clean();
$t->assert(str_contains($topbarHtml, 'Test Title') && str_contains($topbarHtml, 'View Storefront'), 'Topbar component renders title and storefront link');

ob_start();
$icon = 'package';
$label = 'Total Inventory';
$value = '1,048';
$trend = '+15%';
include BASE_PATH . '/app/Views/admin/partials/kpi-card.php';
$kpiHtml = ob_get_clean();
$t->assert(str_contains($kpiHtml, 'Total Inventory') && str_contains($kpiHtml, '1,048'), 'KPI card component renders metric and value');

exit($t->summary());
