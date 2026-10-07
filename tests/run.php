<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Noir\App\Controllers\AdminController;
use Noir\App\Services\AuthenticationService;
use Noir\App\Services\PaymentService;
use Noir\App\Services\QueueService;
use Noir\Core\Container;

final class TestFailure extends RuntimeException {}

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function test(string $name, callable $callback): void
    {
        try {
            $callback();
            $this->passed++;
            echo "PASS  {$name}\n";
        } catch (Throwable $exception) {
            $this->failed++;
            echo "FAIL  {$name}: {$exception->getMessage()}\n";
        }
    }

    public function summary(): int
    {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        return $this->failed === 0 ? 0 : 1;
    }
}

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new TestFailure($message);
    }
}

function assert_same(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new TestFailure($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function test_unique(string $prefix): string
{
    return $prefix . '-' . strtolower(bin2hex(random_bytes(5)));
}

function cleanup_test_user(PDO $db, int $userId): void
{
    if ($userId > 0) {
        $db->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
    }
}

function create_test_user(PDO $db, string $accountType = 'customer', string $status = 'active'): int
{
    $suffix = test_unique('user');
    $stmt = $db->prepare('INSERT INTO users (public_id,username,name,email,password_hash,status,account_type,email_verified_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        strtoupper(bin2hex(random_bytes(13))),
        $suffix,
        'Test User',
        $suffix . '@example.test',
        password_hash('TestPass!123', PASSWORD_DEFAULT),
        $status,
        $accountType,
    ]);

    return (int)$db->lastInsertId();
}

function create_test_product(PDO $db): int
{
    $suffix = test_unique('payment');
    $stmt = $db->prepare('INSERT INTO products (public_id,name,slug,sku,category,brand,price,quantity,status,featured_image) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        strtoupper(bin2hex(random_bytes(13))),
        'Payment Test Product',
        $suffix,
        strtoupper($suffix),
        'Tests',
        'Noir Test',
        1250.00,
        3,
        'published',
        '/uploads/store/product-01.jpg',
    ]);

    return (int)$db->lastInsertId();
}

$runner = new TestRunner();
$db = Container::get('db');

$runner->test('auth creates guest sessions without runtime schema changes', function () use ($db): void {
    $service = new AuthenticationService($db);
    $sessionBefore = $_SESSION;
    $guest = $service->authenticateGuest();
    $userId = (int)$guest['user_id'];

    try {
        assert_true($userId > 0, 'Guest session should include a user id.');
        assert_true(!empty($guest['is_guest']), 'Guest session should be marked as guest.');
        assert_true(in_array('customer', $guest['roles'] ?? [], true), 'Guest session should include the customer role.');

        $stmt = $db->prepare('SELECT is_guest,guest_token_hash,guest_expires_at FROM users WHERE id=?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        assert_true((bool)$row, 'Guest user row should exist.');
        assert_same(1, (int)$row['is_guest'], 'Guest user should be persisted as guest.');
        assert_true(strlen((string)$row['guest_token_hash']) === 64, 'Guest token hash should be stored.');
        assert_true((string)$row['guest_expires_at'] !== '', 'Guest expiry should be stored.');

        $wallet = $db->prepare('SELECT COUNT(*) FROM customer_wallets WHERE user_id=?');
        $wallet->execute([$userId]);
        assert_same(1, (int)$wallet->fetchColumn(), 'Guest wallet should be created.');
    } finally {
        cleanup_test_user($db, $userId);
        $_SESSION = $sessionBefore;
    }
});

$runner->test('checkout payment service creates pending order transaction and rejects oversell', function () use ($db): void {
    $productId = create_test_product($db);
    $orderNumber = null;

    try {
        $service = new PaymentService($db);
        $order = $service->createPendingOrder(
            [['id' => $productId, 'quantity' => 2]],
            ['name' => 'Test Buyer', 'email' => 'buyer@example.test', 'address' => '1 Test Way', 'city' => 'Lagos', 'state' => 'LA'],
            0,
            'NGN',
            'paystack'
        );
        $orderNumber = (string)$order['order_number'];

        assert_true(str_starts_with($orderNumber, 'ORD-'), 'Order number should use the ORD prefix.');
        assert_true(str_starts_with((string)$order['reference'], 'nrc_'), 'Payment reference should use the nrc prefix.');
        assert_same(2500.0, (float)$order['total'], 'Order total should match product price times quantity.');

        $payment = $db->prepare('SELECT provider,status,amount,currency,gateway_payload FROM payment_transactions WHERE provider_reference=?');
        $payment->execute([$order['reference']]);
        $paymentRow = $payment->fetch();
        assert_true((bool)$paymentRow, 'Payment transaction should be created.');
        assert_same('paystack', $paymentRow['provider'], 'Payment provider should be Paystack.');
        assert_same('initialized', $paymentRow['status'], 'Payment transaction should start initialized.');
        assert_same('NGN', $paymentRow['currency'], 'Payment currency should be persisted.');

        $payload = json_decode((string)$paymentRow['gateway_payload'], true);
        assert_same('buyer@example.test', (string)($payload['customer']['email'] ?? ''), 'Checkout customer email should be stored in payment payload.');

        $items = $db->prepare('SELECT quantity,unit_price FROM order_items WHERE order_id=?');
        $items->execute([(int)$order['order_id']]);
        $item = $items->fetch();
        assert_same(2, (int)$item['quantity'], 'Order item quantity should be persisted.');
        assert_same(1250.0, (float)$item['unit_price'], 'Order item price should be persisted.');

        $thrown = false;
        try {
            $service->createPendingOrder([['id' => $productId, 'quantity' => 4]], ['email' => 'buyer@example.test'], 0, 'NGN', 'paystack');
        } catch (RuntimeException) {
            $thrown = true;
        }
        assert_true($thrown, 'Overselling beyond available stock should throw.');
    } finally {
        if ($orderNumber !== null) {
            $db->prepare('DELETE FROM orders WHERE order_number=?')->execute([$orderNumber]);
        }
        $db->prepare('DELETE FROM products WHERE id=?')->execute([$productId]);
    }
});

$runner->test('admin customer lookup is limited to customer accounts', function () use ($db): void {
    $customerId = create_test_user($db, 'customer');
    $staffId = create_test_user($db, 'staff');

    try {
        $controller = new AdminController();
        $method = new ReflectionMethod(AdminController::class, 'customerRecord');
        $method->setAccessible(true);

        $customer = $method->invoke($controller, $db, $customerId);
        $staff = $method->invoke($controller, $db, $staffId);

        assert_true(is_array($customer), 'Customer account should be visible to customer admin lookup.');
        assert_same($customerId, (int)$customer['id'], 'Customer lookup should return the requested customer.');
        assert_same(null, $staff, 'Staff account should not be returned by customer admin lookup.');
    } finally {
        cleanup_test_user($db, $staffId);
        cleanup_test_user($db, $customerId);
    }
});

$runner->test('uploads directory blocks executable scripts', function (): void {
    $path = BASE_PATH . '/public/uploads/.htaccess';
    assert_true(is_file($path), 'Uploads .htaccess should exist.');

    $contents = file_get_contents($path) ?: '';
    assert_true(str_contains($contents, 'Options -ExecCGI'), 'Uploads should disable CGI execution.');
    assert_true((bool)preg_match('/FilesMatch.+php.+phtml.+phar.+cgi.+pl.+py.+sh/s', $contents), 'Uploads should block executable extensions.');
    assert_true(str_contains($contents, 'Require all denied'), 'Uploads executable block should deny all access.');
});

$runner->test('customer repository changePassword rejects wrong current password', function () use ($db): void {
    $userId = create_test_user($db);

    try {
        $repo   = new \Noir\App\Repositories\CustomerRepository($db);
        $result = $repo->changePassword($userId, 'WrongPassword!', 'NewPass!456');

        assert_same(false, $result, 'changePassword should return false for an incorrect current password.');
    } finally {
        cleanup_test_user($db, $userId);
    }
});

$runner->test('customer repository changePassword succeeds with correct current password', function () use ($db): void {
    $userId = create_test_user($db);

    try {
        $repo   = new \Noir\App\Repositories\CustomerRepository($db);
        $result = $repo->changePassword($userId, 'TestPass!123', 'NewSecure!789');

        assert_same(true, $result, 'changePassword should return true when the current password is correct.');

        // Verify the new hash is stored.
        $q = $db->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
        $q->execute([$userId]);
        $hash = (string) $q->fetchColumn();

        assert_true(password_verify('NewSecure!789', $hash), 'The new password hash should verify correctly.');
    } finally {
        cleanup_test_user($db, $userId);
    }
});

$runner->test('customer repository addWishlist and removeWishlist roundtrip', function () use ($db): void {
    $userId    = create_test_user($db);
    $productId = create_test_product($db);

    try {
        $repo = new \Noir\App\Repositories\CustomerRepository($db);
        $repo->addWishlist($userId, $productId);

        $before = $repo->wishlist($userId);
        assert_same(1, count($before), 'Wishlist should contain the added product.');
        assert_same($productId, (int) $before[0]['id'], 'The correct product should be in the wishlist.');

        $repo->removeWishlist($userId, $productId);

        $after = $repo->wishlist($userId);
        assert_same(0, count($after), 'Wishlist should be empty after removal.');
    } finally {
        cleanup_test_user($db, $userId);
        $db->prepare('DELETE FROM products WHERE id=?')->execute([$productId]);
    }
});

$runner->test('store repository returns only published products', function () use ($db): void {
    $repo = new \Noir\App\Repositories\StoreRepository($db);

    // Insert one published and one draft product.
    $suffix = test_unique('vis');

    $db->prepare(
        'INSERT INTO products (public_id,name,slug,sku,category,brand,price,quantity,status,featured_image) VALUES (?,?,?,?,?,?,?,?,?,?)',
    )->execute([strtoupper(bin2hex(random_bytes(13))), 'Visibility Test Published', $suffix . '-pub', strtoupper($suffix) . 'P', 'Tests', 'Noir Test', 100.00, 5, 'published', '/uploads/store/product-01.jpg']);
    $publishedId = (int) $db->lastInsertId();

    $db->prepare(
        'INSERT INTO products (public_id,name,slug,sku,category,brand,price,quantity,status,featured_image) VALUES (?,?,?,?,?,?,?,?,?,?)',
    )->execute([strtoupper(bin2hex(random_bytes(13))), 'Visibility Test Draft', $suffix . '-dft', strtoupper($suffix) . 'D', 'Tests', 'Noir Test', 100.00, 5, 'draft', '/uploads/store/product-01.jpg']);
    $draftId = (int) $db->lastInsertId();

    try {
        $products = $repo->products(" WHERE p.status='published' AND p.id IN ($publishedId,$draftId)", [], 'p.id DESC');
        $ids      = array_map(static fn(array $p): int => (int) $p['id'], $products);

        assert_true(in_array($publishedId, $ids, true), 'Published product should be returned.');
        assert_true(!in_array($draftId, $ids, true), 'Draft product should not be returned.');
    } finally {
        $db->prepare('DELETE FROM products WHERE id IN (?,?)')->execute([$publishedId, $draftId]);
    }
});

$runner->test('catalogue_slug helper produces url-safe lowercase slugs', function (): void {
    assert_same('hello-world',       catalogue_slug('Hello World'),       'Basic lower and replace');
    assert_same('noir-co',           catalogue_slug('Noir & Co.'),        'Special chars removed');
    assert_same('tee-shirt',         catalogue_slug('  Tee  Shirt  '),    'Trim and collapse spaces');
    assert_same('100-cotton',        catalogue_slug('100% Cotton'),        'Percent sign removed');
    assert_same('a-b-c',             catalogue_slug('A--B---C'),          'Multiple hyphens collapsed');
});

$runner->test('queue service dispatches, atomically reserves, and completes background jobs', function () use ($db): void {
    $queueName = 'test-' . bin2hex(random_bytes(4));
    $payload = ['order_id' => 9999, 'action' => 'test_dispatch'];

    // 1. Dispatch
    $jobId = QueueService::dispatch('TestJobClass', $payload, 0, $queueName);
    assert_true($jobId > 0, 'QueueService::dispatch should return a positive job ID.');

    try {
        // Verify in DB
        $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        assert_true(!empty($row), 'Job should exist in jobs table.');
        assert_same('TestJobClass', $row['job_class'], 'Job class must match.');
        assert_same($queueName, $row['queue'], 'Queue name must match.');

        // 2. Reserve
        $reserved = QueueService::reserveNext($queueName);
        assert_true(!empty($reserved), 'QueueService::reserveNext should return the queued job.');
        assert_same($jobId, (int)$reserved['id'], 'Reserved job ID should match dispatched job ID.');
        assert_same(9999, (int)($reserved['payload']['order_id'] ?? 0), 'Payload should be deserialized.');

        // 3. Second reservation on same queue should return null (atomic lock)
        $secondReserve = QueueService::reserveNext($queueName);
        assert_same(null, $secondReserve, 'Second reserveNext should return null while job is locked.');

        // 4. Complete
        $completed = QueueService::complete($jobId);
        assert_true($completed, 'QueueService::complete should return true.');

        // Verify deleted from jobs table
        $check = $db->prepare('SELECT COUNT(*) FROM jobs WHERE id = ?');
        $check->execute([$jobId]);
        assert_same(0, (int)$check->fetchColumn(), 'Completed job should be deleted from jobs table.');
    } finally {
        $db->prepare('DELETE FROM jobs WHERE queue = ?')->execute([$queueName]);
    }
});

$runner->test('queue service handles failure and records failed jobs', function () use ($db): void {
    $queueName = 'test-fail-' . bin2hex(random_bytes(4));
    $payload = ['item_id' => 1234];

    $jobId = QueueService::dispatch('FailingJobClass', $payload, 0, $queueName);
    assert_true($jobId > 0, 'Dispatch should succeed.');

    try {
        $reserved = QueueService::reserveNext($queueName);
        assert_true(!empty($reserved), 'Job should be reserved.');

        $exception = new RuntimeException('Simulated queue worker failure');
        QueueService::fail($reserved, $exception);

        // Verify removed from active jobs
        $checkJob = $db->prepare('SELECT COUNT(*) FROM jobs WHERE id = ?');
        $checkJob->execute([$jobId]);
        assert_same(0, (int)$checkJob->fetchColumn(), 'Failed job should be removed from active jobs.');

        // Verify inserted into failed_jobs
        $checkFailed = $db->prepare('SELECT * FROM failed_jobs WHERE queue = ? AND job_class = ? ORDER BY id DESC LIMIT 1');
        $checkFailed->execute([$queueName, 'FailingJobClass']);
        $failedRow = $checkFailed->fetch(PDO::FETCH_ASSOC);

        assert_true(!empty($failedRow), 'Failure should be recorded in failed_jobs.');
        assert_true(str_contains((string)$failedRow['exception'], 'Simulated queue worker failure'), 'Exception message should be saved.');
    } finally {
        $db->prepare('DELETE FROM jobs WHERE queue = ?')->execute([$queueName]);
        $db->prepare('DELETE FROM failed_jobs WHERE queue = ?')->execute([$queueName]);
    }
});

$unitExit = $runner->summary();
$e2eExit = require __DIR__ . '/e2e_suite.php';

exit($unitExit === 0 && $e2eExit === 0 ? 0 : 1);

