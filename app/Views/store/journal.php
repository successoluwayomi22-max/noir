<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Journal - <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/store.min.css') ?>">
    <script src="<?= e($publicPath . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= store_brand_style() ?>
    <?= ai_assistant_assets($publicPath) ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>
<main class="info-page journal-page">
    <section class="info-hero">
        <p class="eyebrow">The journal</p>
        <h1>Notes for a<br><em>considered life.</em></h1>
        <p>Ideas, materials, styling habits, and operations notes that shape the <?= e($storeName) ?> collection.</p>
    </section>
    <section class="journal-grid">
        <?php foreach ([
            ['Materials', 'The quiet intelligence of cotton', 'How weight, weave, and recovery shape the pieces you reach for most.'],
            ['Rituals', 'A wardrobe that lowers the noise', 'A practical note on repeating outfits, caring for favourites, and choosing with intent.'],
            ['Design', 'Why restraint is not absence', 'The small choices in proportion, hardware, and finish that create lasting character.'],
            ['Care', 'Make your pieces last longer', 'Cold wash habits, storage, drying, and small repair decisions that protect shape and texture.'],
            ['Operations', 'What happens after checkout', 'Payment verification, fulfilment checks, courier handoff, and support follow-up in plain language.'],
            ['Returns', 'A better recovery path', 'How we think about exchanges, refunds, return evidence, and keeping customers informed.'],
        ] as $article): ?>
            <article>
                <p class="eyebrow"><?= e($article[0]) ?></p>
                <h2><?= e($article[1]) ?></h2>
                <p><?= e($article[2]) ?></p>
                <a href="<?= e($publicPath . '/store') ?>">Shop the edit</a>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="journal-feature">
        <div>
            <p class="eyebrow">Behind the store</p>
            <h2>Commerce should feel calm after the payment, too.</h2>
            <p>We are building the store around clear checkout recovery, shipment visibility, return handling, and support records so customers are not left guessing after they buy.</p>
        </div>
        <div class="journal-feature-list">
            <span><i data-lucide="credit-card"></i> Payment confirmation before fulfilment</span>
            <span><i data-lucide="truck"></i> Courier and tracking workflow foundation</span>
            <span><i data-lucide="refresh-cw"></i> Returns and refund status tracking</span>
            <span><i data-lucide="bar-chart-3"></i> Search, checkout, and product analytics</span>
        </div>
    </section>
    <section class="newsletter" id="journal">
        <p class="eyebrow">Keep in touch</p>
        <h2>A little more<br><em>noir.</em></h2>
        <p>New notes and considered arrivals, sent occasionally.</p>
        <form class="newsletter-form" method="post" action="<?= e($publicPath . '/newsletter/subscribe') ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="email" name="email" placeholder="your@email.com" aria-label="Your email address" required>
            <button type="submit" class="button">Subscribe</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/components/footer.php'; ?>
<style>
.journal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;padding:70px 8vw;background:var(--brand-surface)}
.journal-grid article,.journal-feature{border:1px solid var(--brand-border);border-radius:18px;background:var(--brand-paper);box-shadow:0 8px 24px rgba(37,40,32,.05)}
.journal-grid article{padding:30px}.journal-grid h2{margin:14px 0;font-size:clamp(22px,2.5vw,34px);font-weight:500;letter-spacing:-.04em}.journal-grid article p:not(.eyebrow),.journal-feature p{color:var(--brand-muted);font-size:13px;line-height:1.7}.journal-grid a{display:inline-block;margin-top:18px;color:var(--brand-primary);font-size:12px;text-decoration:underline}
.journal-feature{display:grid;grid-template-columns:.9fr 1.1fr;gap:32px;margin:0 8vw 70px;padding:36px}.journal-feature h2{margin:12px 0;font-size:clamp(28px,4vw,52px);font-weight:500;letter-spacing:-.05em}.journal-feature-list{display:grid;gap:12px}.journal-feature-list span{display:flex;align-items:center;gap:10px;border:1px solid var(--brand-border);border-radius:12px;background:var(--brand-surface);padding:14px;font-size:13px}.journal-feature-list svg{width:18px;height:18px;color:var(--brand-primary)}
@media(max-width:900px){.journal-grid{grid-template-columns:1fr 1fr;padding:50px 6vw}.journal-feature{grid-template-columns:1fr;margin:0 6vw 50px}}
@media(max-width:620px){.journal-grid{grid-template-columns:1fr}}
</style>
<script>lucide.createIcons();</script>
</body>
</html>
