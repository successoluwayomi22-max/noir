<?php
require 'bootstrap.php';
$db = Noir\Core\Container::get('db');
$db->query("INSERT INTO orders (user_id, order_number, status, total, currency, payment_method, shipping_status, payment_status, created_at, updated_at) VALUES (1, 'ORD-12345', 'pending', 500, 'NGN', 'paystack', 'pending', 'pending', '2026-08-23 10:00:00', '2026-08-23 10:00:00')");
echo "Inserted";
