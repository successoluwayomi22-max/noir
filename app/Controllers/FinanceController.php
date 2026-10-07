<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\App\Traits\ManagesProfileImage;
use Noir\Core\Container;
use Noir\Core\Request;
use PDO;

final class FinanceController
{
    use ManagesProfileImage;

    public function dashboard(Request $request): never
    {
        $db = Container::get('db');
        [$from, $to, $paymentStatus, $search, $preset] = $this->filters($request);
        $data = $this->compileFinanceData($db, $from, $to, $paymentStatus, $search, $preset, 10);

        extract($data);
        $activePage = 'dashboard';
        require BASE_PATH . '/app/Views/finance/dashboard.php';
        exit;
    }

    public function analytics(Request $request): never
    {
        $db = Container::get('db');
        [$from, $to, $paymentStatus, $search, $preset] = $this->filters($request);
        $data = $this->compileFinanceData($db, $from, $to, $paymentStatus, $search, $preset, 5);

        extract($data);
        $activePage = 'analytics';
        require BASE_PATH . '/app/Views/finance/analytics.php';
        exit;
    }

    public function ledger(Request $request): never
    {
        $db = Container::get('db');
        [$from, $to, $paymentStatus, $search, $preset] = $this->filters($request);
        $data = $this->compileFinanceData($db, $from, $to, $paymentStatus, $search, $preset, 200);

        extract($data);
        $activePage = 'ledger';
        require BASE_PATH . '/app/Views/finance/ledger.php';
        exit;
    }

    public function operations(Request $request): never
    {
        $db = Container::get('db');
        [$from, $to, $paymentStatus, $search, $preset] = $this->filters($request);
        $data = $this->compileFinanceData($db, $from, $to, $paymentStatus, $search, $preset, 10);

        extract($data);
        $activePage = 'operations';
        require BASE_PATH . '/app/Views/finance/operations.php';
        exit;
    }

    public function settings(Request $request): never
    {
        $db = Container::get('db');
        $userId = (int)(Container::get('auth')->user()['user_id'] ?? 0);

        $nq = $db->prepare("SELECT title, body, read_at, DATE_FORMAT(created_at, '%d %b %Y %H:%i') as time FROM customer_notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8");
        $nq->execute([$userId]);
        $notifications = $nq->fetchAll(PDO::FETCH_ASSOC);

        $saved = isset($_GET['saved']);
        $activePage = 'settings';
        require BASE_PATH . '/app/Views/finance/settings.php';
        exit;
    }

    public function transactionDetail(Request $request): never
    {
        $db = Container::get('db');
        $id = (int)($request->params['id'] ?? $request->input('id', 0));

        if ($id <= 0) {
            json(['success' => false, 'message' => 'Invalid transaction or order ID.'], 400);
        }

        $orderQuery = $db->prepare("
            SELECT 
                o.id,
                o.order_number,
                o.total,
                o.currency,
                o.status,
                o.payment_status,
                o.created_at,
                COALESCE(u.name, 'Guest Customer') as customer_name,
                COALESCE(u.email, 'guest@noir.local') as customer_email,
                COALESCE(u.phone, '—') as customer_phone,
                pt.provider,
                pt.provider_reference,
                pt.status as gateway_status,
                pt.paid_at,
                pt.gateway_payload
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN (
                SELECT pt1.order_id, pt1.provider, pt1.provider_reference, pt1.status, pt1.paid_at, pt1.gateway_payload
                FROM payment_transactions pt1
                INNER JOIN (
                    SELECT order_id, MAX(id) as max_id FROM payment_transactions GROUP BY order_id
                ) pt2 ON pt1.id = pt2.max_id
            ) pt ON pt.order_id = o.id
            WHERE o.id = ?
            LIMIT 1
        ");
        $orderQuery->execute([$id]);
        $order = $orderQuery->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            json(['success' => false, 'message' => 'Transaction record not found.'], 404);
        }

        // Fetch Order Items
        $itemsQuery = $db->prepare("SELECT product_name, quantity, unit_price, image_url FROM order_items WHERE order_id = ?");
        $itemsQuery->execute([$id]);
        $items = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$it) {
            $it['product_name'] = str_ireplace('Atelier', 'Signature', (string)$it['product_name']);
        }
        unset($it);

        // Calculate Fee and Channel
        $total = (float)$order['total'];
        $isPaid = $order['payment_status'] === 'paid';
        $fee = 0.0;
        if ($isPaid) {
            $fee = ($total * 0.015) + ($total >= 2500 ? 100 : 0);
            if ($fee > 2000) $fee = 2000.0;
        }
        $net = max(0.0, $total - $fee);

        $channel = 'Paystack Checkout';
        $authCode = '—';
        $ip = '—';
        if (!empty($order['gateway_payload'])) {
            $payload = json_decode((string)$order['gateway_payload'], true);
            $ch = $payload['data']['channel'] ?? $payload['channel'] ?? '';
            if ($ch === 'bank_transfer') {
                $channel = 'Paystack Bank Transfer';
            } elseif ($ch === 'card') {
                $channel = 'Paystack Debit Card';
            } elseif ($ch !== '') {
                $channel = 'Paystack ' . ucwords(str_replace('_', ' ', $ch));
            }
            $authCode = $payload['data']['authorization']['authorization_code'] ?? '—';
            $ip = $payload['data']['ip_address'] ?? '—';
        }

        $order['estimated_fee'] = $fee;
        $order['net_payout'] = $net;
        $order['payment_channel'] = $channel;
        $order['auth_code'] = $authCode;
        $order['client_ip'] = $ip;

        json([
            'success' => true,
            'order' => $order,
            'items' => $items
        ]);
    }

    public function printerStatus(Request $request): never
    {
        $connected = false;
        $printerName = 'None';
        $isOffline = true;
        $port = 'None';

        if (stripos(PHP_OS, 'WIN') === 0) {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            $psScript = '$p = Get-CimInstance Win32_Printer | Where-Object { $_.Default -eq $true }; if ($p) { Write-Output "$($p.Name)|$($p.WorkOffline)|$($p.PortName)" } else { Write-Output "none|true|none" }';
            $proc = @proc_open(['powershell', '-NoProfile', '-NonInteractive', '-Command', $psScript], $descriptors, $pipes);
            if (is_resource($proc)) {
                fclose($pipes[0]);
                $out = trim((string)stream_get_contents($pipes[1]));
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);

                if (!empty($out) && str_contains($out, '|')) {
                    $parts = explode('|', $out);
                    $printerName = trim($parts[0] ?? '');
                    $isOffline = strtolower(trim($parts[1] ?? 'true')) === 'true';
                    $port = trim($parts[2] ?? '');

                    $isVirtual = in_array(strtoupper($port), ['PORTPROMPT:', 'NUL:', 'SHRFAX:'], true)
                        || stripos($printerName, 'PDF') !== false
                        || stripos($printerName, 'XPS') !== false
                        || stripos($printerName, 'OneNote') !== false
                        || stripos($printerName, 'Fax') !== false;

                    if (!$isOffline && !$isVirtual && $printerName !== '' && $printerName !== 'none') {
                        $connected = true;
                    }
                }
            }
        }

        json([
            'success' => true,
            'connected' => $connected,
            'printer_name' => $printerName,
            'offline' => $isOffline,
            'port' => $port,
            'message' => $connected ? 'Physical printer is connected and online.' : 'No physical printer is currently connected.'
        ]);
    }

    public function updateProfile(Request $request): never
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $this->handleProfileUpload($request, ($base ?: '') . '/finance/settings');
    }

    public function export(Request $request): never
    {
        [$from, $to, $paymentStatus, $search] = $this->filters($request);
        $db = Container::get('db');

        $where = ['o.created_at >= ?', 'o.created_at < ?'];
        $params = [
            $from . ' 00:00:00',
            date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'
        ];

        if ($paymentStatus !== '') {
            $where[] = 'o.payment_status = ?';
            $params[] = $paymentStatus;
        }

        if ($search !== '') {
            $where[] = '(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR pt.provider_reference LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql = "
            SELECT 
                o.order_number,
                COALESCE(pt.provider_reference, '—') as gateway_ref,
                COALESCE(u.name, 'Guest Customer') as customer_name,
                COALESCE(u.email, 'guest@noir.local') as customer_email,
                COALESCE(pt.provider, 'Paystack') as provider,
                o.payment_status,
                o.status as fulfillment_status,
                o.total,
                o.currency,
                o.created_at
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN (
                SELECT pt1.order_id, pt1.provider, pt1.provider_reference
                FROM payment_transactions pt1
                INNER JOIN (
                    SELECT order_id, MAX(id) as max_id FROM payment_transactions GROUP BY order_id
                ) pt2 ON pt1.id = pt2.max_id
            ) pt ON pt.order_id = o.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY o.created_at DESC
        ";

        $q = $db->prepare($sql);
        $q->execute($params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="noir-financial-ledger-' . $from . '-to-' . $to . '.csv"');

        $out = fopen('php://output', 'wb');
        fputcsv($out, [
            'Order Number',
            'Gateway Reference',
            'Customer Name',
            'Customer Email',
            'Payment Provider',
            'Payment Status',
            'Fulfillment Status',
            'Gross Amount',
            'Estimated Gateway Fee',
            'Net Settlement',
            'Currency',
            'Transaction Date'
        ]);

        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $total = (float)$row['total'];
            $isPaid = $row['payment_status'] === 'paid';
            $fee = 0.0;
            if ($isPaid) {
                $fee = ($total * 0.015) + ($total >= 2500 ? 100 : 0);
                if ($fee > 2000) $fee = 2000.0;
            }
            $net = max(0.0, $total - $fee);

            fputcsv($out, [
                $row['order_number'],
                $row['gateway_ref'],
                $row['customer_name'],
                $row['customer_email'],
                $row['provider'],
                ucfirst($row['payment_status']),
                ucfirst($row['fulfillment_status']),
                number_format($total, 2, '.', ''),
                number_format($fee, 2, '.', ''),
                number_format($net, 2, '.', ''),
                $row['currency'],
                $row['created_at']
            ]);
        }

        fclose($out);
        exit;
    }

    private function compileFinanceData(PDO $db, string $from, string $to, string $paymentStatus, string $search, string $preset, int $limit = 100): array
    {
        $where = ['o.created_at >= ?', 'o.created_at < ?'];
        $params = [
            $from . ' 00:00:00',
            date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'
        ];

        if ($paymentStatus !== '') {
            $where[] = 'o.payment_status = ?';
            $params[] = $paymentStatus;
        }

        if ($search !== '') {
            $where[] = '(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR pt.provider_reference LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // 1. Overall Aggregates for the filtered period
        $stats = [
            'revenue' => 0.0,
            'gmv' => 0.0,
            'paid' => 0,
            'pending' => 0,
            'failed' => 0,
            'refunded' => 0,
            'total_orders' => 0,
            'aov' => 0.0,
            'pending_amount' => 0.0,
            'estimated_fees' => 0.0,
            'net_payout' => 0.0,
            'conversion_rate' => 0.0,
        ];

        $baseWhere = ['o.created_at >= ?', 'o.created_at < ?'];
        $baseParams = [
            $from . ' 00:00:00',
            date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'
        ];
        $q = $db->prepare("SELECT o.payment_status, COUNT(*) as total, COALESCE(SUM(o.total), 0) as amount FROM orders o WHERE " . implode(' AND ', $baseWhere) . " GROUP BY o.payment_status");
        $q->execute($baseParams);

        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (string)$row['payment_status'];
            $totalCount = (int)$row['total'];
            $totalAmount = (float)$row['amount'];

            $stats['gmv'] += $totalAmount;
            $stats['total_orders'] += $totalCount;

            if ($key === 'paid') {
                $stats['paid'] = $totalCount;
                $stats['revenue'] = $totalAmount;
            } elseif ($key === 'pending') {
                $stats['pending'] = $totalCount;
                $stats['pending_amount'] = $totalAmount;
            } elseif ($key === 'failed') {
                $stats['failed'] = $totalCount;
            } elseif ($key === 'refunded') {
                $stats['refunded'] = $totalCount;
            }
        }

        if ($stats['paid'] > 0) {
            $stats['aov'] = $stats['revenue'] / $stats['paid'];
        }
        if ($stats['total_orders'] > 0) {
            $stats['conversion_rate'] = round(($stats['paid'] / $stats['total_orders']) * 100, 1);
        }

        // Estimated fees
        $paidOrdersQuery = $db->prepare("SELECT total FROM orders o WHERE o.created_at >= ? AND o.created_at < ? AND o.payment_status = 'paid'");
        $paidOrdersQuery->execute($baseParams);
        $totalFees = 0.0;
        foreach ($paidOrdersQuery->fetchAll(PDO::FETCH_COLUMN) as $orderAmt) {
            $amt = (float)$orderAmt;
            $fee = ($amt * 0.015) + ($amt >= 2500 ? 100 : 0);
            if ($fee > 2000) $fee = 2000.0;
            $totalFees += $fee;
        }
        $stats['estimated_fees'] = $totalFees;
        $stats['net_payout'] = max(0.0, $stats['revenue'] - $totalFees);

        // 2. Daily Timeline Trend for Revenue Chart
        $trendQuery = $db->prepare("
            SELECT 
                DATE(o.created_at) as log_date,
                DATE_FORMAT(o.created_at, '%b %d') as date_label,
                COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.total ELSE 0 END), 0) as paid_revenue,
                COUNT(CASE WHEN o.payment_status = 'paid' THEN 1 END) as paid_count,
                COALESCE(SUM(o.total), 0) as total_gmv,
                COUNT(*) as total_count
            FROM orders o
            WHERE o.created_at >= ? AND o.created_at < ?
            GROUP BY DATE(o.created_at)
            ORDER BY log_date ASC
        ");
        $trendQuery->execute($baseParams);
        $revenueTimeline = $trendQuery->fetchAll(PDO::FETCH_ASSOC);

        // 3. Payment Channels Breakdown
        $channelQuery = $db->prepare("
            SELECT 
                COALESCE(pt.provider, 'Paystack Standard') as channel_name,
                COUNT(*) as tx_count,
                COALESCE(SUM(o.total), 0) as channel_volume
            FROM orders o
            LEFT JOIN payment_transactions pt ON pt.order_id = o.id
            WHERE o.created_at >= ? AND o.created_at < ? AND o.payment_status = 'paid'
            GROUP BY channel_name
        ");
        $channelQuery->execute($baseParams);
        $channelBreakdown = $channelQuery->fetchAll(PDO::FETCH_ASSOC);

        // 4. Enriched Transaction Ledger
        $ledgerSql = "
            SELECT 
                o.id as order_id,
                o.order_number,
                o.payment_status,
                o.status as order_status,
                o.total,
                o.currency,
                o.created_at,
                COALESCE(u.name, 'Guest Customer') as customer_name,
                COALESCE(u.email, 'guest@noir.local') as customer_email,
                pt.provider,
                pt.provider_reference,
                pt.status as tx_status,
                pt.paid_at,
                pt.gateway_payload
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN (
                SELECT pt1.order_id, pt1.provider, pt1.provider_reference, pt1.status, pt1.paid_at, pt1.gateway_payload
                FROM payment_transactions pt1
                INNER JOIN (
                    SELECT order_id, MAX(id) as max_id FROM payment_transactions GROUP BY order_id
                ) pt2 ON pt1.id = pt2.max_id
            ) pt ON pt.order_id = o.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY o.created_at DESC
            LIMIT $limit
        ";
        $lq = $db->prepare($ledgerSql);
        $lq->execute($params);
        $rawOrders = $lq->fetchAll(PDO::FETCH_ASSOC);

        $orders = [];
        foreach ($rawOrders as $ord) {
            $total = (float)$ord['total'];
            $isPaid = $ord['payment_status'] === 'paid';
            $fee = 0.0;
            if ($isPaid) {
                $fee = ($total * 0.015) + ($total >= 2500 ? 100 : 0);
                if ($fee > 2000) $fee = 2000.0;
            }
            $net = max(0.0, $total - $fee);

            $channel = 'Card / Paystack';
            if (!empty($ord['gateway_payload'])) {
                $payload = json_decode((string)$ord['gateway_payload'], true);
                $ch = $payload['data']['channel'] ?? $payload['channel'] ?? '';
                if ($ch === 'bank_transfer') {
                    $channel = 'Bank Transfer';
                } elseif ($ch === 'card') {
                    $channel = 'Debit Card';
                } elseif ($ch === 'bank') {
                    $channel = 'Direct Bank Debit';
                } elseif ($ch !== '') {
                    $channel = ucwords(str_replace('_', ' ', $ch));
                }
            }

            $ord['estimated_fee'] = $fee;
            $ord['net_payout'] = $net;
            $ord['channel'] = $channel;
            $orders[] = $ord;
        }

        $operations = $this->operationsSummary($db);
        $userId = (int)(Container::get('auth')->user()['user_id'] ?? 0);

        $nq = $db->prepare("SELECT title, body, read_at, DATE_FORMAT(created_at, '%d %b %Y %H:%i') as time FROM customer_notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8");
        $nq->execute([$userId]);
        $notifications = $nq->fetchAll(PDO::FETCH_ASSOC);

        $saved = isset($_GET['saved']);

        return [
            'from' => $from,
            'to' => $to,
            'paymentStatus' => $paymentStatus,
            'search' => $search,
            'preset' => $preset,
            'stats' => $stats,
            'revenueTimeline' => $revenueTimeline,
            'channelBreakdown' => $channelBreakdown,
            'orders' => $orders,
            'operations' => $operations,
            'notifications' => $notifications,
            'saved' => $saved
        ];
    }

    private function filters(Request $request): array
    {
        $preset = (string)$request->input('preset', '30d');
        $from = (string)$request->input('from', '');
        $to = (string)$request->input('to', '');

        $today = date('Y-m-d');

        if ($preset === 'today') {
            $from = $today;
            $to = $today;
        } elseif ($preset === '7d') {
            $from = date('Y-m-d', strtotime('-6 days'));
            $to = $today;
        } elseif ($preset === '30d') {
            $from = date('Y-m-d', strtotime('-29 days'));
            $to = $today;
        } elseif ($preset === 'month') {
            $from = date('Y-m-01');
            $to = $today;
        } elseif ($preset === 'year') {
            $from = date('Y-01-01');
            $to = $today;
        } elseif ($preset === 'all') {
            $from = '2024-01-01';
            $to = $today;
        } else {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $from = date('Y-m-d', strtotime('-29 days'));
                $preset = '30d';
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $to = $today;
            }
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
        }

        $status = (string)$request->input('payment_status', '');
        $validStatus = in_array($status, ['paid', 'pending', 'failed', 'refunded'], true) ? $status : '';
        $search = trim((string)$request->input('search', ''));

        return [$from, $to, $validStatus, $search, $preset];
    }

    private function operationsSummary(PDO $db): array
    {
        $summary = [
            'returns_open' => 0,
            'refunds_completed' => 0,
            'shipments_pending' => 0,
            'shipments_in_transit' => 0,
            'reservations_active' => 0,
            'delivered_closed' => 0,
            'partial_fulfilments' => 0
        ];

        try {
            if ($this->tableExists($db, 'order_returns')) {
                $summary['returns_open'] = (int)$db->query("SELECT COUNT(*) FROM order_returns WHERE status IN ('requested','approved','received')")->fetchColumn();
                $summary['refunds_completed'] = (int)$db->query("SELECT COUNT(*) FROM order_returns WHERE status='refunded'")->fetchColumn();
            }
            if ($this->tableExists($db, 'order_shipments')) {
                $summary['shipments_pending'] = (int)$db->query("SELECT COUNT(*) FROM order_shipments WHERE status IN ('pending','packed')")->fetchColumn();
                $summary['shipments_in_transit'] = (int)$db->query("SELECT COUNT(*) FROM order_shipments WHERE status IN ('shipped','out_for_delivery')")->fetchColumn();
            }
            if ($this->tableExists($db, 'inventory_reservations')) {
                $summary['reservations_active'] = (int)$db->query("SELECT COUNT(*) FROM inventory_reservations WHERE status='active'")->fetchColumn();
            }
            if ($this->tableExists($db, 'order_fulfillments')) {
                $summary['partial_fulfilments'] = (int)$db->query("SELECT COUNT(DISTINCT order_id) FROM order_fulfillments WHERE status IN ('reserved','picked','packed')")->fetchColumn();
            }

            $orderDispatches = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('shipped','out_for_delivery')")->fetchColumn();
            if ($orderDispatches > 0 && $summary['shipments_in_transit'] === 0) {
                $summary['shipments_in_transit'] = (int)$orderDispatches;
            }

            $orderPending = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')")->fetchColumn();
            if ($orderPending > 0 && $summary['shipments_pending'] === 0) {
                $summary['shipments_pending'] = (int)$orderPending;
            }

            $summary['delivered_closed'] = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status IN ('delivered','completed')")->fetchColumn();
        } catch (\Throwable) {
        }

        return $summary;
    }

    private function tableExists(PDO $db, string $table): bool
    {
        $q = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
        $q->execute([$table]);
        return (bool)$q->fetchColumn();
    }
}
