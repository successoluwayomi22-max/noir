<?php
declare(strict_types=1);

/**
 * Generates a full HTML meta block for SEO and Open Graph, including
 * schema.org JSON-LD structured data and optional Google Analytics snippet.
 */
function store_seo_meta(
    string $title,
    string $description = '',
    string $image = '',
    array $breadcrumbs = [],
): string {
    $base        = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $site        = store_setting('store_name', 'Noir & Co.');
    $description = $description !== ''
        ? $description
        : 'Shop considered everyday pieces from ' . $site . ' with secure checkout, clear delivery, and responsive support.';
    $image       = $image !== '' ? $image : '/uploads/store/hero-editorial.jpg';
    $origin      = rtrim((string) config('url', 'http://localhost:8000'), '/');
    $path        = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $absoluteUrl = $origin . $path;
    $absoluteImage = preg_match('#^https?://#i', $image)
        ? $image
        : $origin . $base . '/' . ltrim($image, '/');

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'LocalBusiness',
        'name'     => $site,
        'url'      => $absoluteUrl,
        'image'    => $absoluteImage,
        'telephone' => store_setting('store_phone', ''),
        'email'     => store_setting('store_email', ''),
        'address'   => [
            '@type'         => 'PostalAddress',
            'streetAddress' => store_setting('store_address', ''),
            'addressCountry' => 'NG',
        ],
        'openingHoursSpecification' => [[
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'opens'     => '09:00',
            'closes'    => '17:00',
        ]],
    ];

    if ($breadcrumbs) {
        $schema = [
            $schema,
            [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => array_map(
                    static fn(array $item, int $index): array => [
                        '@type'    => 'ListItem',
                        'position' => $index + 1,
                        'name'     => (string) $item['name'],
                        'item'     => $origin . (string) $item['url'],
                    ],
                    $breadcrumbs,
                    array_keys($breadcrumbs),
                ),
            ],
        ];
    }

    $ga        = trim((string) config('analytics.google_measurement_id', ''));
    $analytics = preg_match('/^G-[A-Z0-9]+$/', $ga)
        ? '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($ga) . '"></script>'
          . '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}'
          . 'gtag("js",new Date());gtag("config","' . e($ga) . '");</script>'
        : '';

    return '<title>' . e($title) . '</title>'
        . '<link rel="canonical" href="' . e($absoluteUrl) . '">'
        . '<meta name="description" content="' . e($description) . '">'
        . '<meta property="og:type" content="website">'
        . '<meta property="og:site_name" content="' . e($site) . '">'
        . '<meta property="og:title" content="' . e($title) . '">'
        . '<meta property="og:description" content="' . e($description) . '">'
        . '<meta property="og:image" content="' . e($absoluteImage) . '">'
        . '<meta property="og:url" content="' . e($absoluteUrl) . '">'
        . '<meta name="twitter:card" content="summary_large_image">'
        . '<meta name="twitter:title" content="' . e($title) . '">'
        . '<meta name="twitter:description" content="' . e($description) . '">'
        . '<meta name="twitter:image" content="' . e($absoluteImage) . '">'
        . '<link rel="icon" type="image/svg+xml" href="' . e($base . '/assets/icons/noir.svg') . '">'
        . '<link rel="icon" type="image/png" sizes="32x32" href="' . e($base . '/favicon-32x32.png') . '">'
        . '<link rel="apple-touch-icon" sizes="180x180" href="' . e($base . '/apple-touch-icon.png') . '">'
        . '<script type="application/ld+json">'
        . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>'
        . $analytics;
}

/**
 * Renders an accessible breadcrumb navigation element.
 */
function store_breadcrumbs(array $items): string
{
    if (!$items) {
        return '';
    }

    $html = '<nav class="store-breadcrumbs" aria-label="Breadcrumb">';

    foreach ($items as $index => $item) {
        $name = e($item['name'] ?? '');
        $url  = (string) ($item['url'] ?? '');
        $html .= $index > 0 ? '<span aria-hidden="true">/</span>' : '';
        $html .= $url !== '' && $index < count($items) - 1
            ? '<a href="' . e($url) . '">' . $name . '</a>'
            : '<span>' . $name . '</span>';
    }

    return $html . '</nav>';
}
