<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
$db = \Noir\Core\Container::get('db');

echo "=== Setting up AI Intelligence and Knowledge Base Tables ===" . PHP_EOL;

// 1. ai_demand_signals table
$db->exec("
CREATE TABLE IF NOT EXISTS `ai_demand_signals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `style` varchar(80) DEFAULT NULL,
  `color` varchar(80) DEFAULT NULL,
  `size` varchar(40) DEFAULT NULL,
  `budget` decimal(14,2) DEFAULT NULL,
  `occasion` varchar(80) DEFAULT NULL,
  `raw_query` text NOT NULL,
  `had_catalog_match` tinyint(1) NOT NULL DEFAULT 1,
  `unmet_demand_flag` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('detected','under_review','in_production','dropped') NOT NULL DEFAULT 'detected',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_style` (`style`),
  KEY `idx_color` (`color`),
  KEY `idx_unmet` (`unmet_demand_flag`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "✓ ai_demand_signals table verified/created." . PHP_EOL;

// 2. ai_knowledge_base table
$db->exec("
CREATE TABLE IF NOT EXISTS `ai_knowledge_base` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(60) NOT NULL DEFAULT 'general',
  `title` varchar(200) NOT NULL,
  `keywords` text NOT NULL,
  `content` text NOT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `action_destination` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "✓ ai_knowledge_base table verified/created." . PHP_EOL;

// 3. Pre-seed Knowledge Base if empty
$kbCount = (int)$db->query("SELECT COUNT(*) FROM ai_knowledge_base")->fetchColumn();
if ($kbCount === 0) {
    $knowledgeItems = [
        [
            'category' => 'platform',
            'title' => 'About Noir Atelier',
            'keywords' => 'what is noir, about us, brand, who are you, company, store, maison',
            'content' => 'Noir & Co. is a contemporary luxury fashion maison offering bespoke tailoring, runway capsules, minimalist silhouettes, and elevated streetwear crafted from noble materials.',
            'action_type' => 'navigate',
            'action_destination' => '/collections',
        ],
        [
            'category' => 'shipping',
            'title' => 'White-Glove Courier & Shipping Policy',
            'keywords' => 'shipping, delivery, dispatch, courier, how long does delivery take, transit, tracking, lagos, abuja',
            'content' => 'We offer insured white-glove courier dispatch. Metropolitan delivery takes 24–48 hours, while domestic and regional orders arrive within 2–4 business days with real-time GPS tracking.',
            'action_type' => 'navigate',
            'action_destination' => '/track-order',
        ],
        [
            'category' => 'returns',
            'title' => 'Concierge Returns & Exchanges',
            'keywords' => 'return, returns, exchange, refund, policy, swap, size exchange, money back',
            'content' => 'Items in pristine, unworn condition with original seals intact may be returned or exchanged within 14 days of delivery. Our courier will pick up from your doorstep.',
            'action_type' => 'navigate',
            'action_destination' => '/privacy-policy',
        ],
        [
            'category' => 'orders',
            'title' => 'Order Tracking & Status',
            'keywords' => 'order status, where is my order, track order, tracking number, package, dispatch status',
            'content' => 'You can view your active order timeline, courier details, and proof-of-delivery receipts anytime in your account dashboard or via our dedicated Track Order portal.',
            'action_type' => 'navigate',
            'action_destination' => '/track-order',
        ],
        [
            'category' => 'payments',
            'title' => 'Accepted Payment Methods',
            'keywords' => 'payment, how to pay, cards, paystack, flutterwave, apple pay, google pay, wallet, bank transfer',
            'content' => 'We accept all major debit/credit cards (Visa, Mastercard, Verve), Apple Pay, Google Pay, direct bank transfer, and Noir In-App Customer Wallet credits.',
            'action_type' => 'navigate',
            'action_destination' => '/customer/wallet',
        ],
        [
            'category' => 'sizing',
            'title' => 'Atelier Size Guide & Fit Advice',
            'keywords' => 'size, sizing, size guide, fit, measurements, chest, waist, small, medium, large, xl',
            'content' => 'Our garments feature tailored and relaxed luxury silhouettes. Consult our comprehensive Size Guide for exact chest, waist, and length measurements.',
            'action_type' => 'navigate',
            'action_destination' => '/size-guide',
        ],
        [
            'category' => 'support',
            'title' => 'Customer Care & Concierge Support',
            'keywords' => 'contact, support, customer service, email, phone, help desk, human, speak to agent',
            'content' => 'Our Concierge Support team is available 24/7. You can submit a support ticket in your dashboard, initiate a live chat, or email concierge@noir.test.',
            'action_type' => 'navigate',
            'action_destination' => '/customer/support',
        ],
        [
            'category' => 'wishlist',
            'title' => 'Wishlist & Saved Outfits',
            'keywords' => 'wishlist, saved items, save outfit, my favorites, bookmark',
            'content' => 'Save your favorite pieces and custom AI stylist outfits to your Wishlist to revisit anytime or receive price drop notifications.',
            'action_type' => 'navigate',
            'action_destination' => '/customer/wishlist',
        ],
    ];

    $stmt = $db->prepare("
        INSERT INTO ai_knowledge_base (category, title, keywords, content, action_type, action_destination, is_active, sort_order, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
    ");

    foreach ($knowledgeItems as $idx => $item) {
        $stmt->execute([
            $item['category'],
            $item['title'],
            $item['keywords'],
            $item['content'],
            $item['action_type'],
            $item['action_destination'],
            $idx
        ]);
    }
    echo "✓ Seeded " . count($knowledgeItems) . " standard knowledge base topics." . PHP_EOL;
}

echo "=== AI Intelligence Tables Ready! ===" . PHP_EOL;
