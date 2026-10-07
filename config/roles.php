<?php
declare(strict_types=1);

return [
    'redirects' => [
        'super-admin' => '/admin/dashboard', 'administrator' => '/admin/dashboard', 'store-manager' => '/admin/dashboard',
        'inventory-manager' => '/admin/dashboard', 'warehouse-officer' => '/admin/dashboard', 'finance-manager' => '/finance/dashboard',
        'customer-support' => '/admin/dashboard', 'marketing-manager' => '/admin/dashboard', 'delivery-manager' => '/delivery/dashboard',
        'content-manager' => '/admin/dashboard', 'vendor' => '/vendor/dashboard', 'customer' => '/',
    ],
    'default_redirect' => '/dashboard',
    'admin_roles' => ['super-admin','administrator','store-manager','inventory-manager','warehouse-officer','customer-support','marketing-manager','content-manager'],
];
