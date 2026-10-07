<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Careers — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta('Careers — ' . $storeName, 'Join the team at ' . $storeName . '.') ?>
    <?= store_brand_style() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>

<main class="careers-page">
    <?= store_breadcrumbs([['name' => 'Home', 'url' => $publicPath . '/'], ['name' => 'Careers', 'url' => '']]) ?>

    <header class="page-header">
        <h1>Join Noir</h1>
        <p>We are always looking for passionate individuals to join our growing team. See our open roles below.</p>
    </header>

    <div class="job-listings">
        <div class="job-card">
            <div class="job-meta">Design · London (Hybrid)</div>
            <h3>Senior Menswear Designer</h3>
            <p>Lead the creative direction for our seasonal menswear collections, focusing on sustainable sourcing and modern tailoring.</p>
            <a href="mailto:careers@noir.com" class="button">Apply now</a>
        </div>
        
        <div class="job-card">
            <div class="job-meta">Engineering · Remote</div>
            <h3>Frontend Developer (React/Flutter)</h3>
            <p>Join our digital product team to build beautiful, highly-performant shopping experiences across web and mobile.</p>
            <a href="mailto:careers@noir.com" class="button">Apply now</a>
        </div>
        
        <div class="job-card">
            <div class="job-meta">Retail · New York Flagship</div>
            <h3>Store Manager</h3>
            <p>Oversee daily operations, client relations, and visual merchandising at our flagship Soho location.</p>
            <a href="mailto:careers@noir.com" class="button">Apply now</a>
        </div>
    </div>
</main>

<?php require __DIR__ . '/components/footer.php'; ?>

<style>
.careers-page { padding: clamp(40px,6vw,80px) clamp(16px,5vw,80px); background: var(--brand-paper); color: var(--brand-dark); min-height: 70vh; }
.page-header { max-width: 600px; margin: 40px 0 60px; }
.page-header h1 { font-size: clamp(36px, 5vw, 64px); font-weight: 500; letter-spacing: -0.04em; margin-bottom: 12px; }
.page-header p { font-size: 16px; color: var(--brand-muted); line-height: 1.6; }

.job-listings { max-width: 800px; display: flex; flex-direction: column; gap: 24px; }
.job-card { background: var(--brand-surface); border: 1px solid var(--brand-border); padding: 32px; border-radius: 12px; }
.job-meta { font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--brand-muted); margin-bottom: 12px; font-weight: 600; }
.job-card h3 { font-size: 24px; font-weight: 500; margin-bottom: 12px; }
.job-card p { font-size: 15px; line-height: 1.6; color: var(--brand-muted); margin-bottom: 24px; max-width: 600px; }
</style>
</body>
</html>
