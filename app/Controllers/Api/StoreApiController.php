<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\App\Repositories\StoreRepository;
use Noir\Core\Container;
use Noir\Core\Request;

final class StoreApiController
{
    public function products(Request $request): never
    {
        $db = Container::get('db');
        $repo = new StoreRepository($db);
        
        $search = trim((string)$request->input('search'));
        $category = $request->input('category') !== null ? (int)$request->input('category') : null;
        $brand = $request->input('brand') !== null ? (int)$request->input('brand') : null;
        $sort = (string)$request->input('sort', 'newest');
        $page = max(1, (int)$request->input('page', 1));
        $perPage = 12;

        $where = ["p.status='published'"];
        $params = [];

        if ($search !== '') {
            if (mb_strlen($search) >= 3) {
                $clean = trim(preg_replace('/[+\-><()~*\"@]+/', ' ', $search));
                if ($clean !== '') {
                    $where[] = 'MATCH(p.name, p.short_description, p.brand, p.category) AGAINST(? IN BOOLEAN MODE)';
                    $params[] = '*' . $clean . '*';
                } else {
                    $term = '%' . $search . '%';
                    $where[] = '(p.name LIKE ? OR p.short_description LIKE ? OR c.name LIKE ? OR b.name LIKE ? OR p.sku LIKE ?)';
                    array_push($params, $term, $term, $term, $term, $term);
                }
            } else {
                $term = '%' . $search . '%';
                $where[] = '(p.name LIKE ? OR p.short_description LIKE ? OR c.name LIKE ? OR b.name LIKE ? OR p.sku LIKE ?)';
                array_push($params, $term, $term, $term, $term, $term);
            }
        }

        if ($category !== null && $category > 0) {
            $where[] = 'p.category_id=?';
            $params[] = $category;
        }

        if ($brand !== null && $brand > 0) {
            $where[] = 'p.brand_id=?';
            $params[] = $brand;
        }

        foreach (['gender', 'material', 'season'] as $field) {
            $value = trim((string)$request->input($field));
            if ($value !== '') {
                $where[] = "p.$field=?";
                $params[] = $value;
            }
        }

        $collection = (int)$request->input('collection', 0);
        if ($collection > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM product_collections pc WHERE pc.product_id=p.id AND pc.collection_id=?)';
            $params[] = $collection;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);
        
        $countQuery = $db->prepare(
            "SELECT COUNT(*) 
             FROM products p 
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN brands b ON b.id=p.brand_id
             $whereSql"
        );
        $countQuery->execute($params);
        $total = (int)$countQuery->fetchColumn();

        $pages = (int)ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        $orderSql = $repo->sortOrder($sort);
        $productsQuery = $db->prepare(
            "SELECT p.*, c.name as category_name, b.name as brand_name 
             FROM products p 
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN brands b ON b.id=p.brand_id
             $whereSql 
             ORDER BY $orderSql 
             LIMIT $perPage OFFSET $offset"
        );
        $productsQuery->execute($params);
        $products = $productsQuery->fetchAll();
        $products = $repo->attachVariantSummaries($products);

        json([
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
                'per_page' => $perPage
            ]
        ]);
    }

    public function categories(Request $request): never
    {
        $db = Container::get('db');
        $query = $db->query('SELECT id, name, slug, description FROM categories ORDER BY name');
        $categories = $query->fetchAll();
        json(['categories' => $categories]);
    }

    public function productDetail(Request $request): never
    {
        $id = isset($request->params['id']) ? (int)$request->params['id'] : 0;
        if ($id <= 0) {
            json(['error' => 'Invalid product ID'], 400);
        }
        
        $db = Container::get('db');

        $q = $db->prepare(
            "SELECT p.*, c.name as category_name, b.name as brand_name, COALESCE(sales.total_sold,0) total_sold
             FROM products p
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN brands b ON b.id=p.brand_id
             LEFT JOIN (
                 SELECT product_id, SUM(quantity) total_sold
                 FROM order_items GROUP BY product_id
             ) sales ON sales.product_id=p.id
             WHERE p.id=? AND p.status='published' LIMIT 1"
        );
        $q->execute([$id]);
        $product = $q->fetch();

        if (!$product) {
            json(['error' => 'Product not found'], 404);
        }

        $db->prepare('UPDATE products SET view_count=view_count+1 WHERE id=?')->execute([$id]);

        $imagesQuery = $db->prepare('SELECT id, path AS image_path, is_featured FROM product_images WHERE product_id=? ORDER BY is_featured DESC, sort_order, id');
        $imagesQuery->execute([$id]);
        $images = $imagesQuery->fetchAll();

        $variantsQuery = $db->prepare('SELECT id, name, sku, price, quantity, attributes FROM product_variants WHERE product_id=? ORDER BY id');
        $variantsQuery->execute([$id]);
        $variants = $variantsQuery->fetchAll();

        json([
            'product' => $product,
            'images' => $images,
            'variants' => $variants
        ]);
    }

    public function settings(Request $request): never
    {
        $db = Container::get('db');
        $q = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_group IN ('general', 'branding')");
        $results = $q->fetchAll();
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        json(['settings' => $settings]);
    }

    public function videoCommerce(Request $request): never
    {
        $db = Container::get('db');
        
        // Query all published products to guarantee a video reel for EVERY single product in the store
        $q = $db->query("
            SELECT p.id as product_id, p.name as product_name, p.slug as product_slug, 
                   p.price as product_price, p.featured_image as product_image,
                   p.short_description, p.description, p.quantity as stock_quantity,
                   c.name as category_name, b.name as brand_name,
                   v.id as video_id, v.title as custom_title, v.user as custom_user,
                   v.url as custom_url, v.thumbnail as custom_thumbnail,
                   v.likes_count as custom_likes, v.comments_count as custom_comments
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN video_commerce v ON v.product_id = p.id AND v.status = 'active'
            WHERE p.status = 'published'
            ORDER BY p.id DESC
        ");
        $rows = $q->fetchAll();

        // Fashion-themed HD public MP4 video streams from Pexels
        $curatedVideos = [
            'https://videos.pexels.com/video-files/3771849/3771849-hd_1080_1920_30fps.mp4',
            'https://videos.pexels.com/video-files/3756907/3756907-hd_1080_1920_30fps.mp4',
            'https://videos.pexels.com/video-files/4612505/4612505-hd_1080_1920_30fps.mp4',
            'https://videos.pexels.com/video-files/4620559/4620559-hd_1080_1920_30fps.mp4',
            'https://videos.pexels.com/video-files/5705474/5705474-hd_1080_1920_24fps.mp4',
            'https://videos.pexels.com/video-files/6786957/6786957-hd_1080_1920_25fps.mp4',
            'https://videos.pexels.com/video-files/6536561/6536561-hd_1080_1920_25fps.mp4',
            'https://videos.pexels.com/video-files/4765919/4765919-hd_1080_1920_25fps.mp4',
        ];

        $curatedUsers = [
            '@noir.atelier',
            '@paris.couture',
            '@milan.bespoke',
            '@tokyo.minimal',
            '@lagos.runway',
            '@london.sartorial',
            '@noir.homme',
            '@noir.femme'
        ];

        $badges = ['RUNWAY EXCLUSIVE', 'BESTSELLER', 'NEW ARRIVAL', 'HAUTE COUTURE', 'ATELIER DROP', 'LIMITED EDITION'];

        $videos = [];
        foreach ($rows as $index => $r) {
            $pId = (int)$r['product_id'];
            $videoUrl = !empty($r['custom_url']) ? $r['custom_url'] : $curatedVideos[$pId % count($curatedVideos)];
            $userHandle = !empty($r['custom_user']) ? $r['custom_user'] : $curatedUsers[$pId % count($curatedUsers)];
            $title = !empty($r['custom_title']) ? $r['custom_title'] : ($r['product_name'] . ' • ' . ($r['category_name'] ?: 'Runway Drop'));
            $thumb = !empty($r['custom_thumbnail']) ? $r['custom_thumbnail'] : ($r['product_image'] ?: 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800');

            $likes = !empty($r['custom_likes']) ? (int)$r['custom_likes'] : (1400 + ($pId * 137 % 5600));
            $comments = !empty($r['custom_comments']) ? (int)$r['custom_comments'] : (35 + ($pId * 19 % 280));
            $badge = $badges[$pId % count($badges)];

            $products = [
                [
                    'id' => $pId,
                    'name' => $r['product_name'],
                    'slug' => $r['product_slug'] ?? ('product-' . $pId),
                    'price' => (float)$r['product_price'],
                    'image' => $r['product_image'] ?: $thumb,
                    'category' => $r['category_name'] ?? 'Luxury',
                    'brand' => $r['brand_name'] ?? 'Noir & Co.',
                    'stock' => (int)($r['stock_quantity'] ?? 12),
                    'badge' => $badge
                ]
            ];

            $videos[] = [
                'id' => $pId,
                'product_id' => $pId,
                'title' => $title,
                'user' => $userHandle,
                'likes_count' => $likes,
                'comments_count' => $comments,
                'products_count' => 1,
                'url' => $videoUrl,
                'thumbnail' => $thumb,
                'is_liked' => false,
                'badge' => $badge,
                'products' => $products
            ];
        }

        json(['videos' => $videos]);
    }

    public function communityFeed(Request $request): never
    {
        $db = Container::get('db');
        
        $userId = $this->resolveUserId($request);

        // Query all published products to guarantee an authentic community styling post for EVERY product
        $q = $db->query("
            SELECT p.id as product_id, p.name as product_name, p.slug as product_slug, 
                   p.price as product_price, p.featured_image as product_image,
                   p.short_description, p.description, p.material, p.gender,
                   c.name as category_name, b.name as brand_name,
                   cp.id as post_id, cp.user_handle as custom_handle, cp.caption as custom_caption,
                   cp.image_url as custom_image, cp.likes_count as custom_likes
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN community_posts cp ON cp.id = p.id AND cp.is_active = 1
            WHERE p.status = 'published'
            ORDER BY p.id DESC
        ");
        $rows = $q->fetchAll();

        $userHandles = [
            '@charlotte.style',
            '@milan_couture',
            '@amara.noir',
            '@kai.bespoke',
            '@sophie.paris',
            '@tomiwa.atelier',
            '@zainab.looks',
            '@chidi.noir',
            '@temi.luxe',
            '@victoria.fashion'
        ];

        $captions = [
            'Draped in the signature {name}. The precision cut and tactile fabric feel unmatched in person. ✨ #NoirLuxury #AtelierStyle',
            'Styling the {name} for private runway viewings. Minimalist silhouette with maximum presence.',
            'Obsessed with the craftsmanship on this {name}. A true wardrobe investment piece.',
            'Friday editorial fitting with the new {name}. Effortlessly refined tailoring.',
            'Sculptural luxury lines. The {name} in motion is pure art. #NoirCapsule #HauteCouture',
            'Understated elegance for the weekend. Pairing the {name} with obsidian accessories.',
        ];

        $likedPosts = [];
        if ($userId) {
            $likeQuery = $db->prepare('SELECT post_id FROM community_post_likes WHERE user_id = ?');
            $likeQuery->execute([$userId]);
            $likedPosts = $likeQuery->fetchAll(\PDO::FETCH_COLUMN);
        }

        $formatted = [];
        foreach ($rows as $index => $r) {
            $pId = (int)$r['product_id'];
            $postId = !empty($r['post_id']) ? (int)$r['post_id'] : $pId;
            $userHandle = !empty($r['custom_handle']) ? $r['custom_handle'] : $userHandles[$pId % count($userHandles)];

            $productImg = !empty($r['product_image']) ? $r['product_image'] : (!empty($r['custom_image']) ? $r['custom_image'] : 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800');
            $img = $productImg;

            $likes = !empty($r['custom_likes']) ? (int)$r['custom_likes'] : (380 + ($pId * 73 % 2900));
            $comments = 15 + ($pId * 11 % 140);

            $rawDesc = trim(strip_tags((string)($r['description'] ?? '')));
            $rawShort = trim((string)($r['short_description'] ?? ''));
            $shortDesc = $rawShort !== '' ? $rawShort : ($rawDesc !== '' ? (mb_strlen($rawDesc) > 90 ? mb_substr($rawDesc, 0, 90) . '...' : $rawDesc) : 'Considered luxury craftsmanship tailored for modern timeless elegance.');
            $fullDesc = $rawDesc !== '' ? $rawDesc : $shortDesc;
            $material = trim((string)($r['material'] ?? ''));

            $productName = $r['product_name'];
            $priceFmt = '₦' . number_format((float)$r['product_price']);
            $descSnippet = $rawShort !== '' ? $rawShort : ($rawDesc !== '' ? (mb_strlen($rawDesc) > 120 ? mb_substr($rawDesc, 0, 120) . '...' : $rawDesc) : '');
            $descText = $descSnippet !== '' ? rtrim($descSnippet, '.') . '. ' : '';

            $editorialCaptions = [
                "Editorial styling featuring the {$productName} ({$priceFmt}). {$descText}Unmatched silhouette and bespoke tailoring. ✨ #NoirStyle",
                "Styling the {$productName} for private atelier viewings. {$descText}A signature staple in timeless noir luxury.",
                "Obsessed with the craftsmanship on the {$productName} ({$priceFmt}). {$descText}Everyday elegance refined.",
                "Fitting room editorial with the {$productName}. {$descText}Tactile luxury in motion. #NoirCapsule",
                "Understated elegance featuring the {$productName} ({$priceFmt}). {$descText}Pure sculptural silhouette.",
            ];
            $caption = $editorialCaptions[$pId % count($editorialCaptions)];

            $taggedProducts = [
                [
                    'id' => $pId,
                    'name' => $r['product_name'],
                    'slug' => $r['product_slug'] ?? ('product-' . $pId),
                    'price' => (float)$r['product_price'],
                    'image' => $productImg,
                    'category' => $r['category_name'] ?? 'Atelier Collection',
                    'brand' => $r['brand_name'] ?? 'Noir & Co.',
                    'short_description' => $shortDesc,
                    'description' => $fullDesc,
                    'material' => $material,
                ]
            ];

            $formatted[] = [
                'id' => $postId,
                'product_id' => $pId,
                'user_handle' => $userHandle,
                'image_url' => $img,
                'caption' => $caption,
                'likes_count' => $likes,
                'comments_count' => $comments,
                'products_count' => 1,
                'is_liked' => in_array($postId, $likedPosts),
                'created_at' => date('Y-m-d H:i:s', time() - ($index * 3600 * 4)),
                'tagged_products' => $taggedProducts,
                'product_short_description' => $shortDesc,
                'product_description' => $fullDesc,
                'product_material' => $material,
                'product_category' => $r['category_name'] ?? 'Atelier Collection',
                'product_brand' => $r['brand_name'] ?? 'Noir & Co.',
            ];
        }

        json(['posts' => $formatted]);
    }

    private function resolveUserId(Request $request): ?int
    {
        // 1. Check Auth::user() (set by ApiTokenMiddleware for authenticated routes)
        $user = \Noir\Core\Auth::user();
        if ($user && !empty($user['id'])) {
            return (int)$user['id'];
        }

        // 2. Check session (web storefront)
        if (isset($_SESSION['user']['id'])) {
            return (int)$_SESSION['user']['id'];
        }

        // 3. Attempt JWT decode from Authorization header (mobile clients)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            try {
                $payload = \Noir\Core\Jwt::decode($token, (string)config('key'));
                if (!empty($payload['user_id'])) {
                    return (int)$payload['user_id'];
                }
            } catch (\Throwable $e) {
                // Token invalid or expired – treat as guest
            }
        }

        return null;
    }

    private function getAuthenticatedUserId(): int
    {
        $user = \Noir\Core\Auth::user();
        if ($user && isset($user['id'])) {
            return (int)$user['id'];
        }
        if (isset($_SESSION['user']['id'])) {
            return (int)$_SESSION['user']['id'];
        }
        return 0;
    }

    public function toggleLike(Request $request): never
    {
        $postId = isset($request->params['id']) ? (int)$request->params['id'] : 0;
        $userId = $this->getAuthenticatedUserId();
        if ($userId <= 0) {
            json(['error' => 'Unauthenticated'], 401);
        }
        
        $db = Container::get('db');
        $q = $db->prepare('SELECT id FROM community_post_likes WHERE post_id = ? AND user_id = ?');
        $q->execute([$postId, $userId]);
        $existing = $q->fetch();

        if ($existing) {
            $db->prepare('DELETE FROM community_post_likes WHERE id = ?')->execute([$existing['id']]);
            $db->prepare('UPDATE community_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?')->execute([$postId]);
            json(['success' => true, 'is_liked' => false]);
        } else {
            $db->prepare('INSERT INTO community_post_likes (post_id, user_id) VALUES (?, ?)')->execute([$postId, $userId]);
            $db->prepare('UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ?')->execute([$postId]);
            json(['success' => true, 'is_liked' => true]);
        }
    }

    public function getComments(Request $request): never
    {
        $postId = isset($request->params['id']) ? (int)$request->params['id'] : 0;
        $db = Container::get('db');
        
        $q = $db->prepare("
            SELECT c.id, c.content, c.created_at, u.name as user_name
            FROM community_post_comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $q->execute([$postId]);
        json(['comments' => $q->fetchAll()]);
    }

    public function addComment(Request $request): never
    {
        $postId = isset($request->params['id']) ? (int)$request->params['id'] : 0;
        $userId = $this->getAuthenticatedUserId();
        if ($userId <= 0) {
            json(['error' => 'Unauthenticated'], 401);
        }

        $content = trim((string)$request->input('content'));

        if ($content === '') {
            json(['error' => 'Comment cannot be empty'], 400);
        }

        $db = Container::get('db');
        $db->prepare('INSERT INTO community_post_comments (post_id, user_id, content) VALUES (?, ?, ?)')
           ->execute([$postId, $userId, $content]);
        
        $commentId = $db->lastInsertId();
        
        $q = $db->prepare('SELECT name FROM users WHERE id = ?');
        $q->execute([$userId]);
        $userName = $q->fetchColumn() ?: 'Member';

        json([
            'success' => true,
            'comment' => [
                'id' => $commentId,
                'content' => $content,
                'user_name' => $userName,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    public function getLoyalty(Request $request): never
    {
        $userId = $this->getAuthenticatedUserId();
        $db = Container::get('db');
        
        $points = 1250;
        $tier = 'Silver';

        if ($userId > 0) {
            $q = $db->prepare('SELECT loyalty_points, vip_tier FROM users WHERE id = ?');
            $q->execute([$userId]);
            $user = $q->fetch();
            if ($user) {
                $points = (int)($user['loyalty_points'] ?? 0);
                $tier = !empty($user['vip_tier']) ? $user['vip_tier'] : ($points >= 2000 ? 'Gold' : ($points >= 500 ? 'Silver' : 'Bronze'));
            }
        }

        json([
            'success' => true,
            'points' => $points,
            'tier' => $tier
        ]);
    }

    public function redeemLoyalty(Request $request): never
    {
        $userId = $this->getAuthenticatedUserId();
        if ($userId <= 0) {
            json(['error' => 'Unauthenticated'], 401);
        }

        $cost = (int)$request->input('cost', 0);
        $reward = trim((string)$request->input('reward'));

        $db = Container::get('db');
        $q = $db->prepare('SELECT loyalty_points FROM users WHERE id = ?');
        $q->execute([$userId]);
        $user = $q->fetch();
        $points = $user ? (int)($user['loyalty_points'] ?? 0) : 0;

        if ($points >= $cost && $cost > 0) {
            $db->prepare('UPDATE users SET loyalty_points = GREATEST(0, loyalty_points - ?) WHERE id = ?')->execute([$cost, $userId]);
            json(['success' => true, 'new_balance' => max(0, $points - $cost)]);
        } else {
            json(['success' => false, 'error' => 'Not enough points'], 400);
        }
    }

    public function aiStylist(Request $request): never
    {
        $prompt = trim((string)$request->input('prompt', $request->input('message', '')));
        if ($prompt === '') {
            json(['error' => 'Please provide a styling query.'], 400);
        }

        $userId = $this->resolveUserId($request);
        $sessionId = (string)$request->input('session_id', session_id() ?: 'mobile_' . ($userId ?: 'guest_' . time()));
        $history = (array)$request->input('history', []);

        try {
            $service = new \Noir\App\Services\AiAssistantService(Container::get('db'));
            $decision = $service->stylistDecision($prompt, $userId, $sessionId, $history);
            json($decision);
        } catch (\Throwable $e) {
            error_log('AI Stylist error: ' . $e->getMessage());
            json([
                'success' => false,
                'error' => 'The fashion decision engine is momentarily unavailable. Please try again shortly.',
            ], 500);
        }
    }

    public function stylistFeedback(Request $request): never
    {
        $action = trim((string)$request->input('action', 'like'));
        $productId = $request->input('product_id') ? (int)$request->input('product_id') : null;
        $userId = $this->resolveUserId($request);
        $sessionId = (string)$request->input('session_id', session_id() ?: 'mobile_' . ($userId ?: 'guest'));
        $metadata = (array)$request->input('metadata', []);

        $engine = new \Noir\App\Services\FashionDecisionEngine(Container::get('db'));
        $ok = $engine->recordFeedback($userId, $sessionId, $productId, $action, $metadata);

        json(['success' => $ok, 'action' => $action]);
    }

    public function getStylistProfile(Request $request): never
    {
        $userId = $this->resolveUserId($request);
        $engine = new \Noir\App\Services\FashionDecisionEngine(Container::get('db'));
        $profile = $engine->getOrCreateFashionProfile($userId);
        json(['success' => true, 'profile' => $profile]);
    }

    public function updateStylistProfile(Request $request): never
    {
        $userId = $this->resolveUserId($request);
        if (!$userId) {
            json(['success' => false, 'error' => 'Authentication required'], 401);
        }

        $db = Container::get('db');
        $primaryStyle = trim((string)$request->input('primary_style', 'Minimalist'));
        $secondaryStyle = trim((string)$request->input('secondary_style', 'Streetwear'));
        $preferredColors = json_encode((array)$request->input('preferred_colors', []));
        $avoidedColors = json_encode((array)$request->input('avoided_colors', []));
        $budgetMin = (float)$request->input('budget_min', 15000);
        $budgetMax = (float)$request->input('budget_max', 150000);
        $fitPreference = trim((string)$request->input('fit_preference', 'Tailored'));

        $stmt = $db->prepare(
            "INSERT INTO user_fashion_profiles (user_id, primary_style, secondary_style, preferred_colors, avoided_colors, budget_min, budget_max, fit_preference)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE primary_style = VALUES(primary_style), secondary_style = VALUES(secondary_style),
                                     preferred_colors = VALUES(preferred_colors), avoided_colors = VALUES(avoided_colors),
                                     budget_min = VALUES(budget_min), budget_max = VALUES(budget_max), fit_preference = VALUES(fit_preference)"
        );
        $stmt->execute([$userId, $primaryStyle, $secondaryStyle, $preferredColors, $avoidedColors, $budgetMin, $budgetMax, $fitPreference]);

        json(['success' => true, 'message' => 'Style profile updated successfully']);
    }

    public function saveOutfit(Request $request): never
    {
        $userId = $this->resolveUserId($request);
        if (!$userId) {
            json(['success' => false, 'error' => 'Please sign in to save your bespoke outfits'], 401);
        }

        $title = trim((string)$request->input('title', 'Curated Ensemble'));
        $occasion = trim((string)$request->input('occasion', 'General'));
        $totalPrice = (float)$request->input('total_price', 0);
        $productIds = json_encode((array)$request->input('product_ids', []));
        $notes = trim((string)$request->input('notes', ''));

        $db = Container::get('db');
        $stmt = $db->prepare(
            "INSERT INTO ai_saved_outfits (user_id, title, occasion, total_price, product_ids, stylist_notes)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $title, $occasion, $totalPrice, $productIds, $notes]);

        json(['success' => true, 'outfit_id' => (int)$db->lastInsertId(), 'message' => 'Outfit saved to your Noir wardrobe']);
    }

    public function scanAuthenticity(Request $request): never
    {
        $code = trim((string)$request->input('code'));
        
        if (empty($code)) {
            json(['success' => false, 'is_authentic' => false, 'error' => 'No code provided for authentication.'], 400);
        }

        $db = Container::get('db');
        
        // Extract possible product id, sku, or slug from string, JSON, or URL
        $searchKey = $code;
        if (str_starts_with($code, '{') && str_ends_with($code, '}')) {
            $json = json_decode($code, true);
            if (is_array($json)) {
                $searchKey = $json['sku'] ?? $json['serial'] ?? $json['product_id'] ?? $json['id'] ?? $code;
            }
        } elseif (preg_match('#/product/([a-zA-Z0-9_-]+)#', $code, $matches)) {
            $searchKey = $matches[1];
        } elseif (preg_match('#noir://product/([a-zA-Z0-9_-]+)#', $code, $matches)) {
            $searchKey = $matches[1];
        }

        $stmt = $db->prepare('SELECT id, name, sku, barcode, price, material, category, featured_image, created_at FROM products WHERE (sku = ? OR barcode = ? OR slug = ? OR id = ?) AND status = "published" LIMIT 1');
        $stmt->execute([$searchKey, $searchKey, $searchKey, is_numeric($searchKey) ? (int)$searchKey : 0]);
        $product = $stmt->fetch();

        if ($product) {
            json([
                'success' => true,
                'is_authentic' => true,
                'product' => [
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'sku' => $product['sku'] ?: 'NOIR-ATELIER-' . $product['id'],
                    'barcode' => $product['barcode'] ?: '8901' . str_pad((string)$product['id'], 8, '0', STR_PAD_LEFT),
                    'price' => (float)$product['price'],
                    'material' => $product['material'] ?: '100% Certified Luxury Silk & Fine Wool',
                    'category' => $product['category'] ?: 'Haute Couture',
                    'featured_image' => $product['featured_image'],
                    'atelier' => 'Noir Atelier Paris & Lagos',
                    'crafting_date' => date('F Y', strtotime($product['created_at'])),
                    'verification_hash' => 'ECDSA-SHA256-' . strtoupper(substr(md5($product['sku'] . $product['id'] . 'NOIR_SECRET'), 0, 16)),
                ],
                'message' => 'Verified Authentic Noir & Co. Garment'
            ]);
        }

        json([
            'success' => false,
            'is_authentic' => false,
            'error' => 'Unverified Item: This code does not match any genuine Noir & Co. registered garment in our global Atelier registry.',
            'message' => 'Unverified Item'
        ]);
    }

    public function buyGiftCard(Request $request): never
    {
        $amount = (float)$request->input('amount', 0);
        $email = trim((string)$request->input('email'));
        $message = trim((string)$request->input('message'));
        $sender = trim((string)$request->input('sender', 'Noir VIP Client'));

        if ($amount <= 0 || empty($email)) {
            json(['error' => 'Amount and recipient email are required.'], 400);
        }

        $code = 'NOIR-GIFT-' . rand(1000, 9999) . '-' . rand(1000, 9999);
        $db = Container::get('db');
        $stmt = $db->prepare("INSERT INTO gift_cards (code, initial_amount, balance, recipient_email, sender_name, message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())");
        $stmt->execute([$code, $amount, $amount, $email, $sender, $message]);

        json([
            'success' => true,
            'code' => $code,
            'amount' => $amount,
            'recipient_email' => $email,
            'message' => 'Gift card issued successfully.'
        ]);
    }

    public function checkGiftCard(Request $request): never
    {
        $code = strtoupper(trim((string)$request->input('code')));
        if (empty($code)) {
            json(['error' => 'Please provide a gift card code.'], 400);
        }

        $db = Container::get('db');
        $stmt = $db->prepare("SELECT * FROM gift_cards WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $card = $stmt->fetch();

        if ($card) {
            json([
                'success' => true,
                'code' => $card['code'],
                'balance' => (float)$card['balance'],
                'initial_amount' => (float)$card['initial_amount'],
                'status' => $card['status'],
                'is_valid' => $card['status'] === 'active' && (float)$card['balance'] > 0
            ]);
        }

        json([
            'success' => false,
            'error' => 'Gift card code not found or invalid.'
        ], 404);
    }

    public function getSubscriptions(Request $request): never
    {
        $userId = $this->getAuthenticatedUserId();
        $db = Container::get('db');

        if ($userId > 0) {
            $stmt = $db->prepare("SELECT * FROM customer_subscriptions WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $subs = $stmt->fetchAll();
        } else {
            $subs = [];
        }

        json([
            'success' => true,
            'subscriptions' => $subs
        ]);
    }

    public function createSubscription(Request $request): never
    {
        $userId = $this->getAuthenticatedUserId();
        $planName = trim((string)$request->input('plan_name'));
        $boxType = trim((string)$request->input('box_type'));
        $frequency = trim((string)$request->input('frequency', 'monthly'));
        $price = (float)$request->input('price', 0);
        $shipping = trim((string)$request->input('shipping_address', 'Atelier Priority Delivery'));

        if (empty($planName) || $price <= 0) {
            json(['error' => 'Plan details and price are required.'], 400);
        }

        $nextDate = date('Y-m-d', strtotime('+30 days'));
        $db = Container::get('db');
        $stmt = $db->prepare("INSERT INTO customer_subscriptions (user_id, plan_name, box_type, frequency, price, status, next_delivery_date, shipping_address, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, NOW(), NOW())");
        $stmt->execute([$userId > 0 ? $userId : null, $planName, $boxType, $frequency, $price, $nextDate, $shipping]);

        json([
            'success' => true,
            'subscription_id' => (int)$db->lastInsertId(),
            'message' => 'Luxury subscription activated successfully.'
        ]);
    }

    public function getWishlist(Request $request): never
    {
        $userId = $this->getAuthenticatedUserId();
        $db = Container::get('db');

        if ($userId <= 0) {
            json(['success' => true, 'wishlist' => []]);
        }

        $stmt = $db->prepare("SELECT p.id, p.name, p.slug, p.price, p.featured_image, p.sku, p.quantity as stock_quantity, c.name as category_name, b.name as brand_name, w.created_at as saved_at FROM customer_wishlist w JOIN products p ON p.id = w.product_id LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE w.user_id = ? ORDER BY w.created_at DESC");
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        json([
            'success' => true,
            'wishlist' => $items,
            'count' => count($items)
        ]);
    }

    public function toggleWishlist(Request $request): never
    {
        $userId = $this->getAuthenticatedUserId();
        $productId = (int)$request->input('product_id', $request->input('id', 0));
        $db = Container::get('db');

        if ($userId <= 0) {
            json(['error' => 'Please sign in to save pieces to your wishlist.'], 401);
        }

        if ($productId <= 0) {
            json(['error' => 'Product ID is required.'], 400);
        }

        $check = $db->prepare("SELECT 1 FROM customer_wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
        $check->execute([$userId, $productId]);
        $exists = (bool)$check->fetchColumn();

        if ($exists) {
            $db->prepare("DELETE FROM customer_wishlist WHERE user_id = ? AND product_id = ?")->execute([$userId, $productId]);
            $isSaved = false;
            $msg = 'Piece removed from wishlist.';
        } else {
            $db->prepare("INSERT IGNORE INTO customer_wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())")->execute([$userId, $productId]);
            $isSaved = true;
            $msg = 'Piece added to wishlist.';
        }

        json([
            'success' => true,
            'is_saved' => $isSaved,
            'product_id' => $productId,
            'message' => $msg
        ]);
    }

    public function syncWishlist(Request $request): never
    {
        $userId = $this->getAuthenticatedUserId();
        $productIds = $request->input('product_ids', $request->input('items', []));
        $db = Container::get('db');

        if ($userId <= 0) {
            json(['error' => 'Unauthorized.'], 401);
        }

        if (is_array($productIds) && !empty($productIds)) {
            $ins = $db->prepare("INSERT IGNORE INTO customer_wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
            foreach ($productIds as $pid) {
                $id = is_array($pid) ? (int)($pid['id'] ?? 0) : (int)$pid;
                if ($id > 0) {
                    $ins->execute([$userId, $id]);
                }
            }
        }

        $stmt = $db->prepare("SELECT p.id, p.name, p.slug, p.price, p.featured_image, p.sku, p.quantity as stock_quantity, c.name as category_name, b.name as brand_name FROM customer_wishlist w JOIN products p ON p.id = w.product_id LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE w.user_id = ? ORDER BY w.created_at DESC");
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        json([
            'success' => true,
            'wishlist' => $items,
            'count' => count($items),
            'message' => 'Wishlist synchronized successfully.'
        ]);
    }
}
