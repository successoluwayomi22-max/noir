<?php
declare(strict_types=1);

/**
 * Returns the URL to a media asset, resolving local paths through the public
 * directory. Falls back to a catalogue placeholder if the file does not exist.
 */
function media_url(string $base, ?string $path, int $maxWidth = 480): string
{
    if (!$path || trim($path) === '') {
        return 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=' . $maxWidth . '&q=70&fm=webp';
    }

    if (str_contains($path, 'images.unsplash.com')) {
        // Dynamically optimize Unsplash image bandwidth according to requested display width and modern WebP format
        $optimized = preg_replace('/[?&]w=\d+/', '', $path);
        $optimized = preg_replace('/[?&]q=\d+/', '', $optimized);
        $optimized = preg_replace('/[?&]fm=\w+/', '', $optimized);
        $optimized = preg_replace('/[?&]auto=[^&]+/', '', $optimized);
        $optimized = preg_replace('/[?&]fit=[^&]+/', '', $optimized);
        // Clean up dangling ? or && artifacts
        $optimized = preg_replace('/\?&/', '?', $optimized);
        $optimized = preg_replace('/&&+/', '&', $optimized);
        $optimized = rtrim($optimized, '?&');
        $delimiter = str_contains($optimized, '?') ? '&' : '?';
        return $optimized . $delimiter . 'auto=format&fit=crop&w=' . $maxWidth . '&q=70&fm=webp';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $base = trim(str_replace('\\', '/', $base));
    if ($base === '' || $base === '/') {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($scriptName));
        if ($dir !== '/' && $dir !== '.' && $dir !== '') {
            $base = rtrim($dir, '/');
        } else {
            $base = '';
        }
    }

    $localPath = '/' . ltrim(str_replace('\\', '/', $path), '/');
    $file      = defined('BASE_PATH') ? BASE_PATH . '/public' . $localPath : '';

    if ($file && !is_file($file)) {
        $webpFile = defined('BASE_PATH') ? preg_replace('/\.(jpe?g|png)$/i', '.webp', $file) : '';
        if ($webpFile && is_file($webpFile)) {
            $localPath = (string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $localPath);
            $file = $webpFile;
        } else {
            $tshirtWebp = defined('BASE_PATH') ? BASE_PATH . '/public/uploads/catalogue/tshirt.webp' : '';
            if (is_file($tshirtWebp)) {
                return ($base !== '' ? rtrim($base, '/') : '') . '/uploads/catalogue/tshirt.webp';
            }
            $tshirtFile = defined('BASE_PATH') ? BASE_PATH . '/public/uploads/catalogue/tshirt.jpg' : '';
            if (is_file($tshirtFile)) {
                return ($base !== '' ? rtrim($base, '/') : '') . '/uploads/catalogue/tshirt.jpg';
            }
            return 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80';
        }
    }

    // Auto-upgrade existing JPG/PNG catalogue images to high-performance WebP
    if (defined('BASE_PATH') && preg_match('/\.(jpe?g|png)$/i', $localPath)) {
        $candidateWebp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $localPath);
        if ($candidateWebp && is_file(BASE_PATH . '/public' . $candidateWebp)) {
            $localPath = $candidateWebp;
        }
    }

    return ($base !== '' ? rtrim($base, '/') : '') . $localPath;
}

/**
 * Returns a versioned asset URL, preferring the minified variant when it exists.
 */
function asset_url(string $publicBase, string $path): string
{
    $path      = '/' . ltrim($path, '/');
    $extension = pathinfo($path, PATHINFO_EXTENSION);

    if (in_array($extension, ['css', 'js'], true) && !str_ends_with($path, '.min.' . $extension)) {
        $minPath = substr($path, 0, -(strlen($extension) + 1)) . '.min.' . $extension;
        if (is_file(BASE_PATH . '/public' . $minPath)) {
            $path = $minPath;
        }
    }

    return rtrim($publicBase, '/') . $path;
}

/**
 * Returns true if the given path is one of the built-in catalogue placeholder images
 * rather than a real product image.
 */
function is_legacy_catalogue_image(?string $path): bool
{
    if ($path === null || trim($path) === '') {
        return false;
    }

    $parsedPath = parse_url($path, PHP_URL_PATH);
    $normalized = '/' . ltrim(strtolower((string) ($parsedPath ?: $path)), '/');

    $placeholders = [
        '/uploads/catalogue/accessories.jpg',
        '/uploads/catalogue/bags.jpg',
        '/uploads/catalogue/hoodie.jpg',
        '/uploads/catalogue/jacket.jpg',
        '/uploads/catalogue/jeans.jpg',
        '/uploads/catalogue/shirt.jpg',
        '/uploads/catalogue/shorts.jpg',
        '/uploads/catalogue/sneakers.jpg',
        '/uploads/catalogue/trousers.jpg',
        '/uploads/catalogue/tshirt.jpg',
        '/uploads/store/product-01.jpg',
        '/uploads/store/product-02.jpg',
        '/uploads/store/product-03.jpg',
        '/uploads/store/product-04.jpg',
    ];

    return str_contains($normalized, 'de0b224c304be0f556090189.png')
        || str_contains($normalized, '098a7d0e542fe1ff393a121a.png')
        || in_array($normalized, $placeholders, true);
}

/**
 * Returns an inline CSS filter string to visually tint a product placeholder image
 * to roughly match the product colour, improving catalogue aesthetics.
 */
function product_image_tint(array $product): string
{
    // Never tint custom uploaded or external photography images
    $feat = (string)($product['featured_image'] ?? '');
    if ($feat !== '' && !is_legacy_catalogue_image($feat)) {
        return '';
    }

    $name = strtolower((string) ($product['name'] ?? ''));

    if (str_contains($name, 'tee') || str_contains($name, 'hoodie') || str_contains($name, 'sweatshirt')) {
        return '';
    }

    $nameTints = [
        'essential heavyweight tee' => 'filter:sepia(.16) saturate(.62) brightness(.94);',
        'studio rib tee'            => 'filter:sepia(.34) saturate(.72) brightness(.88);',
        'meridian cotton tee'       => 'filter:brightness(.38) saturate(.72);',
        'contour jersey tee'        => 'filter:sepia(.38) hue-rotate(35deg) saturate(.72) brightness(.82);',
        'quiet logo tee'            => 'filter:sepia(.34) hue-rotate(315deg) saturate(.58) brightness(.93);',
        'layering tee'              => 'filter:grayscale(.62) brightness(.72);',
        'core slub tee'             => 'filter:sepia(.18) hue-rotate(165deg) saturate(.62) brightness(.9);',
        'alto pima tee'             => 'filter:sepia(.12) saturate(.5) brightness(1.02);',
        'everyday mercer tee'       => 'filter:sepia(.5) hue-rotate(5deg) saturate(.62) brightness(.86);',
        'frame relaxed tee'         => 'filter:grayscale(.72) brightness(.76);',
    ];

    foreach ($nameTints as $needle => $tint) {
        if (str_contains($name, $needle)) {
            return $tint;
        }
    }

    // Only tint from an explicit product colour; descriptions and tags often
    // mention styling colours that are not the colour of the item itself.
    $text = strtolower((string) ($product['colors'] ?? $product['color'] ?? ''));

    if (str_contains($text, 'obsidian') || str_contains($text, 'ink')) {
        return 'filter:brightness(.32) saturate(.8);';
    }
    if (str_contains($text, 'olive'))   return 'filter:sepia(.45) hue-rotate(35deg) saturate(.62) brightness(.86);';
    if (str_contains($text, 'tobacco')) return 'filter:sepia(.62) hue-rotate(330deg) saturate(.88) brightness(.88);';
    if (str_contains($text, 'mushroom')) return 'filter:sepia(.24) saturate(.52) brightness(.9);';
    if (str_contains($text, 'stone'))   return 'filter:grayscale(.4) sepia(.12) brightness(.94);';
    if (str_contains($text, 'ash'))     return 'filter:grayscale(.72) brightness(.92);';
    if (str_contains($text, 'ecru'))    return 'filter:sepia(.12) saturate(.72) brightness(1.05);';

    return '';
}

/**
 * Selects the most appropriate catalogue image path for a product based on its
 * name and category keywords, falling back through progressively broader pools.
 */
function catalogue_image_for(array $product): string
{
    $name     = strtolower((string) ($product['name'] ?? ''));
    $category = strtolower((string) ($product['category'] ?? $product['category_name'] ?? '') . ' ' . $name);
    $hash     = abs(crc32($name));

    $fromPool = static function (array $pool, string $seed) use ($hash): string {
        $offset = abs(crc32($seed));
        return $pool[($hash + $offset) % count($pool)];
    };

    $teePool = [
        '/uploads/catalogue/tee-black.jpg',      '/uploads/catalogue/tee-blue.jpg',
        '/uploads/catalogue/tee-brown.jpg',       '/uploads/catalogue/tee-charcoal.jpg',
        '/uploads/catalogue/tee-cream.jpg',       '/uploads/catalogue/tee-gray.jpg',
        '/uploads/catalogue/tee-olive.jpg',       '/uploads/catalogue/tee-pink.jpg',
        '/uploads/catalogue/tee-real-blue.jpg',   '/uploads/catalogue/tee-real-colours.jpg',
        '/uploads/catalogue/tee-real-pink.jpg',   '/uploads/catalogue/tee-real-stack.jpg',
        '/uploads/catalogue/tshirt.jpg',          '/uploads/catalogue/shirt.jpg',
    ];
    $outerwearPool = [
        '/uploads/catalogue/jacket.jpg',
        '/uploads/catalogue/hoodie.jpg',
        '/uploads/catalogue/hoodie-real-olive.jpg',
        '/uploads/catalogue/hoodie-real-pack.jpg',
    ];
    $bottomPool   = ['/uploads/catalogue/trousers.jpg', '/uploads/catalogue/jeans.jpg', '/uploads/catalogue/shorts.jpg'];
    $footwearPool = [
        '/uploads/catalogue/sneakers.jpg',
        '/uploads/products/unsplash-fashion-footwear.jpg',
        '/uploads/products/unsplash-sneaker-editorial.jpg',
    ];
    $carryPool     = ['/uploads/catalogue/bags.jpg', '/uploads/catalogue/accessories.jpg', '/uploads/store/product-03.jpg'];
    $accessoryPool = [
        '/uploads/catalogue/accessories.jpg', '/uploads/catalogue/bags.jpg',
        '/uploads/store/product-01.jpg',      '/uploads/store/product-02.jpg',
        '/uploads/store/product-04.jpg',
    ];
    $generalPool = array_merge(
        $teePool, $outerwearPool, $bottomPool, $footwearPool, $carryPool,
        ['/uploads/store/product-01.jpg', '/uploads/store/product-02.jpg',
         '/uploads/store/product-03.jpg', '/uploads/store/product-04.jpg'],
    );

    $specificNameImages = [
        'key case'    => '/uploads/store/product-04.jpg',
        'glove'       => '/uploads/store/product-02.jpg',
        'tie'         => '/uploads/catalogue/tee-real-blue.jpg',
        'cap'         => '/uploads/catalogue/tee-real-pink.jpg',
        'card holder' => '/uploads/store/product-01.jpg',
        'sunglasses'  => '/uploads/catalogue/accessories.jpg',
        'scarf'       => '/uploads/catalogue/tee-real-colours.jpg',
        'beanie'      => '/uploads/catalogue/hoodie-real-pack.jpg',
        'belt'        => '/uploads/catalogue/bags.jpg',
        'watch'       => '/uploads/store/product-03.jpg',
        'holdall'     => '/uploads/store/collection-01.jpg',
        'shoulder bag' => '/uploads/store/collection-02.jpg',
    ];

    foreach ($specificNameImages as $keyword => $image) {
        if (str_contains($name, $keyword)) {
            return $image;
        }
    }

    if (str_contains($category, 'tee') || str_contains($category, 't-shirt') || str_contains($category, 'shirt')) {
        return $fromPool($teePool, 'tops');
    }

    if (str_contains($category, 'hoodie') || str_contains($category, 'sweatshirt')) {
        return $fromPool($outerwearPool, 'hoodie');
    }

    $namePools = [
        'essentials'  => $teePool,
        'new arrivals' => $generalPool,
        'premium'     => $teePool,
        'streetwear'  => $outerwearPool,
        'limited'     => $accessoryPool,
        'summer'      => $bottomPool,
        'winter'      => $outerwearPool,
        'best sellers' => $generalPool,
        'watch'       => $accessoryPool,
        'sunglasses'  => $accessoryPool,
        'beanie'      => $accessoryPool,
        'scarf'       => $accessoryPool,
        'cap'         => $accessoryPool,
        'glove'       => $accessoryPool,
        'card holder' => $accessoryPool,
        'key case'    => $accessoryPool,
        'belt'        => $accessoryPool,
        'tie'         => $accessoryPool,
    ];

    foreach ($namePools as $keyword => $pool) {
        if (str_contains($name, $keyword)) {
            return $fromPool($pool, $keyword);
        }
    }

    $imagePools = [
        't-shirt'   => $teePool,
        'tee'       => $teePool,
        'shirt'     => $teePool,
        'hoodie'    => $outerwearPool,
        'sweatshirt' => $outerwearPool,
        'jacket'    => $outerwearPool,
        'coat'      => $outerwearPool,
        'jean'      => $bottomPool,
        'trouser'   => $bottomPool,
        'short'     => $bottomPool,
        'sneaker'   => $footwearPool,
        'trainer'   => $footwearPool,
        'runner'    => $footwearPool,
        'court'     => $footwearPool,
        'bag'       => $carryPool,
        'tote'      => $carryPool,
        'backpack'  => $carryPool,
        'crossbody' => $carryPool,
        'accessor'  => $accessoryPool,
    ];

    foreach ($imagePools as $keyword => $pool) {
        if (str_contains($category, $keyword)) {
            return $fromPool($pool, $keyword);
        }
    }

    return $fromPool($generalPool, 'general');
}

/**
 * Returns a representative image path for a collection based on its name keywords.
 */
function collection_image_for(string $name): string
{
    $name   = strtolower($name);
    $images = [
        'best'      => '/uploads/catalogue/sneakers.jpg',
        'winter'    => '/uploads/catalogue/jacket.jpg',
        'summer'    => '/uploads/catalogue/shorts.jpg',
        'limited'   => '/uploads/catalogue/accessories.jpg',
        'street'    => '/uploads/catalogue/hoodie.jpg',
        'premium'   => '/uploads/catalogue/shirt.jpg',
        'essential' => '/uploads/catalogue/tshirt.jpg',
        'new'       => '/uploads/catalogue/shirt.jpg',
    ];

    foreach ($images as $keyword => $image) {
        if (str_contains($name, $keyword)) {
            return $image;
        }
    }

    return '/uploads/catalogue/shirt.jpg';
}
