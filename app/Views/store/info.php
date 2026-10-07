<?php
declare(strict_types=1);
$page = $activePage ?? basename(trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'));
$page = in_array($page, ['about', 'contact', 'faq', 'journal', 'returns', 'shipping'], true) ? $page : 'journal';
$contactSent = $page === 'contact' && isset($_GET['sent']);
$contactError = $page === 'contact' && isset($_GET['error']);
$titles = [
    'about' => 'Our story',
    'contact' => 'Contact us',
    'faq' => 'Frequently asked questions',
    'journal' => 'Journal',
    'returns' => 'Returns & refunds',
    'shipping' => 'Shipping & delivery policy'
];
$descriptions = [
    'about' => 'The people, standards, and slow decisions behind ' . store_setting('store_name', 'Noir & Co.') . '.',
    'contact' => 'Product advice, order help, returns support, and partnership enquiries.',
    'faq' => 'Helpful answers about orders, delivery, returns, and account support.',
    'journal' => 'Notes on materials, rituals, styling, and the considered life.',
    'returns' => 'A clear, considerate process if something is not quite right.',
    'shipping' => 'Transparent shipping rates, courier partners, dispatch timelines, and delivery guarantees.',
];
$publicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?= store_seo_meta(title: $titles[$page] . ' - ' . $storeName, description: $descriptions[$page], image: store_setting('store_logo')) ?>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/store.min.css') ?>">
    <script src="<?= e($publicPath . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= store_brand_style() ?>
    <?= ai_assistant_assets($publicPath) ?>
    <?= recaptcha_head() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>

<main class="info-page enriched-info">
    <section class="info-hero">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e($publicPath.'/') ?>">Home</a> <span>/</span> <span aria-current="page"><?= e($titles[$page]) ?></span>
        </nav>
        <div class="hero-content">
            <p class="eyebrow"><?= e($page === 'journal' ? 'The journal' : $storeName) ?></p>
            <h1><?= e($titles[$page]) ?></h1>
            <p class="hero-desc"><?= e($descriptions[$page]) ?></p>
        </div>
    </section>

    <section class="info-content-wrapper">
        <?php if ($page === 'about'): ?>
            <div class="info-layout">
                <div class="info-sticky-header">
                    <h2>We make fewer decisions, more carefully.</h2>
                </div>
                <div class="info-body">
                    <p class="lead"><?= e($storeName) ?> began as a response to disposable shopping: too many options, too little care, and products that ask for attention before they earn trust.</p>
                    <p>Our work is built around everyday usefulness. A piece should feel easy to reach for, hold its shape through repeated wear, and make the rest of your wardrobe quieter, not louder.</p>
                    
                    <div class="premium-grid">
                        <article>
                            <div class="icon-wrap"><i data-lucide="scissors"></i></div>
                            <strong>Small edits</strong>
                            <span>Limited drops keep selection focused and quality consistently high.</span>
                        </article>
                        <article>
                            <div class="icon-wrap"><i data-lucide="feather"></i></div>
                            <strong>Material first</strong>
                            <span>Fabric, finish, and fit lead every product decision we make.</span>
                        </article>
                        <article>
                            <div class="icon-wrap"><i data-lucide="wrench"></i></div>
                            <strong>Repair mindset</strong>
                            <span>We design for continued use, not fast replacement or seasonal trends.</span>
                        </article>
                    </div>

                    <div class="text-sections">
                        <h3>Our philosophy</h3>
                        <p>Every item starts with a practical question: what does this help someone do, wear, carry, or repeat with more ease? We believe in subtraction over addition.</p>
                        
                        <h3>Our materials</h3>
                        <p>We prioritize natural textures, durable cottons, structured knits, responsible trims, and finishes that age with character rather than deteriorating.</p>
                        
                        <h3>Our process</h3>
                        <p>We review fit, care, stock, packaging, and after-sale support before a product enters the catalogue. That slower rhythm helps us reduce waste and protect the customer experience.</p>
                    </div>

                    <div class="glass-callout">
                        <div class="callout-inner">
                            <strong>The Launch Standard</strong>
                            <p>For every release, we rigorously check product photography, size information, delivery readiness, returns handling, and support notes before any promotion begins.</p>
                        </div>
                    </div>
                    
                    <a href="<?= e($publicPath . '/store') ?>" class="premium-button">Explore the collection</a>
                </div>
            </div>

        <?php elseif ($page === 'contact'): ?>
            <div class="info-layout">
                <div class="info-sticky-header">
                    <h2>Talk to the right person, faster.</h2>
                </div>
                <div class="info-body">
                    <?php if ($contactSent): ?>
                        <div class="form-alert success" role="status">
                            <i data-lucide="check-circle-2"></i>
                            <div>
                                <strong>Message received</strong>
                                <p>We will reply as soon as possible.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($contactError): ?>
                        <div class="form-alert error" role="alert">
                            <i data-lucide="alert-circle"></i>
                            <div>
                                <strong><?= (isset($_GET['error']) && $_GET['error'] === 'recaptcha') ? 'Security check failed' : 'Delivery failed' ?></strong>
                                <p><?= (isset($_GET['error']) && $_GET['error'] === 'recaptcha') ? 'Please complete the reCAPTCHA verification and try again.' : 'Check the required fields and try again.' ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <p class="lead">Use the form for order support, sizing advice, returns, press, partnerships, or product questions. Include your order number when the message relates to a purchase.</p>
                    
                    <div class="premium-grid contact-options">
                        <article>
                            <div class="icon-wrap"><i data-lucide="package-check"></i></div>
                            <strong>Orders</strong>
                            <span>Payment, delivery, tracking, exchanges, and returns.</span>
                        </article>
                        <article>
                            <div class="icon-wrap"><i data-lucide="shirt"></i></div>
                            <strong>Product advice</strong>
                            <span>Fit, materials, care, and styling help from our team.</span>
                        </article>
                        <article>
                            <div class="icon-wrap"><i data-lucide="handshake"></i></div>
                            <strong>Partnerships</strong>
                            <span>Wholesale, creative work, collaborations, and press.</span>
                        </article>
                    </div>

                    <div class="contact-details-glass">
                        <div class="detail-item">
                            <i data-lucide="mail"></i>
                            <div>
                                <span>Email</span>
                                <strong><?= e(store_setting('store_email', 'support@noirandco.com')) ?></strong>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i data-lucide="phone"></i>
                            <div>
                                <span>Phone</span>
                                <strong><?= e(store_setting('store_phone', '+234 901 234 5678')) ?></strong>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i data-lucide="clock"></i>
                            <div>
                                <span>Hours</span>
                                <strong>Monday - Friday, 9am - 5pm</strong>
                            </div>
                        </div>
                    </div>

                    <div class="glass-callout success-tint">
                        <div class="callout-inner">
                            <strong>Response Time Promise</strong>
                            <p>We respect your time. Every inquiry receives a thoughtful, human response within 1 business day.</p>
                        </div>
                    </div>

                    <form method="post" action="<?= e($publicPath . '/contact') ?>" class="premium-form" novalidate>
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        
                        <div class="form-grid">
                            <div class="input-group">
                                <label>Your name</label>
                                <input type="text" name="name" required autocomplete="name" placeholder="Jane Doe">
                            </div>
                            <div class="input-group">
                                <label>Email address</label>
                                <input type="email" name="email" required autocomplete="email" placeholder="jane@example.com">
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="input-group">
                                <label>Subject</label>
                                <select name="subject" required>
                                    <option value="" disabled selected>Choose a topic</option>
                                    <option value="order">Order support</option>
                                    <option value="product">Product question</option>
                                    <option value="returns">Returns and refunds</option>
                                    <option value="partnership">Partnership inquiry</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Order number <span class="optional">(Optional)</span></label>
                                <input type="text" name="order_number" placeholder="ORD-XXXXX">
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label>Message</label>
                            <textarea name="message" rows="6" required placeholder="How can we help you today?"></textarea>
                        </div>
                        
                        <?= recaptcha_field() ?>

                        <button type="submit" class="premium-button">Send message <i data-lucide="send" class="icon-small"></i></button>
                    </form>
                </div>
            </div>

        <?php elseif ($page === 'faq'): ?>
            <div class="info-layout">
                <div class="info-sticky-header">
                    <h2>Everything you need to know.</h2>
                </div>
                <div class="info-body">
                    <div class="premium-faq-list">
                        <?php foreach ([
                            'How do I track my order?' => 'Once your order ships, you will receive a tracking update via email. You can also check your customer dashboard when signed in.',
                            'What is your return policy?' => 'Returns are accepted within 7 days for unused items in their original condition. Contact support with your order number to begin the simple process.',
                            'Do you ship internationally?' => 'The current launch flow focuses on local delivery. International delivery can be reviewed manually through our support team.',
                            'How can I change my order?' => 'Contact us quickly before fulfilment begins. Once an order has shipped, changes may no longer be possible.',
                            'How long does delivery take?' => 'Most orders are estimated at 2-5 business days after payment confirmation.',
                        ] as $question => $answer): ?>
                            <details class="premium-details">
                                <summary>
                                    <?= e($question) ?>
                                    <i data-lucide="chevron-down"></i>
                                </summary>
                                <div class="details-content">
                                    <p><?= e($answer) ?></p>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($page === 'journal'): ?>
            <div class="journal-masonry">
                <?php foreach ([
                    ['Materials', 'The quiet intelligence of cotton', 'How weight, weave, and recovery shape the pieces you reach for most.', 'leaf'],
                    ['Rituals', 'A wardrobe that lowers the noise', 'A practical note on repeating outfits, caring for favourites, and choosing with intent.', 'coffee'],
                    ['Design', 'Why restraint is not absence', 'The small choices in proportion, hardware, and finish that create lasting character.', 'pen-tool'],
                    ['Care', 'Make your pieces last longer', 'Cold wash habits, storage, drying, and small repair decisions that protect shape and texture.', 'droplets'],
                ] as $idx => $article): ?>
                    <article class="journal-card <?= $idx === 0 ? 'featured' : '' ?>">
                        <div class="journal-card-inner">
                            <div class="journal-icon"><i data-lucide="<?= e($article[3]) ?>"></i></div>
                            <div class="journal-content">
                                <span class="journal-tag"><?= e($article[0]) ?></span>
                                <h2><?= e($article[1]) ?></h2>
                                <p><?= e($article[2]) ?></p>
                                <a href="<?= e($publicPath . '/store') ?>" class="read-more">Shop the edit <i data-lucide="arrow-right"></i></a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php elseif ($page === 'returns'): ?>
            <div class="info-layout">
                <div class="info-sticky-header">
                    <h2>A clear, considered return path.</h2>
                </div>
                <div class="info-body">
                    <p class="lead">If something is not right, contact support with your order number and reason for return. We will confirm eligibility and outline the next steps.</p>
                    
                    <div class="premium-grid returns-policy">
                        <article>
                            <div class="icon-wrap"><i data-lucide="calendar"></i></div>
                            <strong>Return window</strong>
                            <span>7 days from the date of delivery.</span>
                        </article>
                        <article>
                            <div class="icon-wrap"><i data-lucide="tag"></i></div>
                            <strong>Condition</strong>
                            <span>Unused, unwashed, and in original packaging.</span>
                        </article>
                        <article>
                            <div class="icon-wrap"><i data-lucide="credit-card"></i></div>
                            <strong>Refund timing</strong>
                            <span>Reviewed after receipt and processed to original payment.</span>
                        </article>
                    </div>

                    <div class="text-sections">
                        <h3>Return steps</h3>
                        <ol class="premium-steps">
                            <li><strong>Initiate:</strong> Send your order number through the contact page.</li>
                            <li><strong>Review:</strong> Wait for return approval and courier instructions.</li>
                            <li><strong>Pack:</strong> Pack the item securely with original tags attached.</li>
                            <li><strong>Ship:</strong> Track the return until it is received at our facility.</li>
                            <li><strong>Resolve:</strong> Finance confirms your refund status after review.</li>
                        </ol>
                    </div>

                    <a href="<?= e($publicPath . '/contact') ?>" class="premium-button">Contact support</a>
                </div>
            </div>

        <?php elseif ($page === 'shipping'): ?>
            <div class="info-layout">
                <div class="info-sticky-header">
                    <h2>Transparent shipping & delivery standards.</h2>
                </div>
                <div class="info-body">
                    <p class="lead">Every order is hand-inspected, carefully packed in plastic-neutral sustainable packaging, and shipped with end-to-end tracking.</p>
                    
                    <div class="premium-grid returns-policy">
                        <article>
                            <div class="icon-wrap"><i data-lucide="package-check"></i></div>
                            <strong>Processing time</strong>
                            <span>Dispatched within 24 to 48 business hours.</span>
                        </article>
                        <article>
                            <div class="icon-wrap"><i data-lucide="truck"></i></div>
                            <strong>Standard delivery</strong>
                            <span>2 to 5 business days nationwide.</span>
                        </article>
                        <article>
                            <div class="icon-wrap"><i data-lucide="globe"></i></div>
                            <strong>Courier tracking</strong>
                            <span>Real-time SMS & email tracking updates provided.</span>
                        </article>
                    </div>

                    <div class="text-sections">
                        <h3>Delivery partners & rates</h3>
                        <p>We work exclusively with vetted premium couriers to guarantee package security. Shipping charges are calculated dynamically at checkout based on weight and destination before you enter any payment information.</p>

                        <h3>Tracking your package</h3>
                        <p>Once dispatched, you will receive an automated dispatch email with a direct tracking link. You can also monitor real-time shipment milestones on our <a href="<?= e($publicPath . '/track-order') ?>" style="color: var(--brand-primary); font-weight: 600; text-decoration: underline;">Track Order</a> page at any time.</p>

                        <h3>Damaged or lost shipments</h3>
                        <p>All shipments are insured in transit. If an item arrives damaged or fails to reach you within the estimated delivery window, notify us within 48 hours via <a href="<?= e($publicPath . '/contact') ?>" style="color: var(--brand-primary); font-weight: 600; text-decoration: underline;">Client Concierge</a> so our logistics team can issue an immediate replacement or full refund.</p>
                    </div>

                    <a href="<?= e($publicPath . '/track-order') ?>" class="premium-button">Track an existing order</a>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/components/footer.php'; ?>

<style>
/* Dynamic Brand Info Styles */
.info-hero {
    background: linear-gradient(135deg, var(--brand-primary, #687158), color-mix(in srgb, var(--brand-primary, #687158), var(--brand-dark, #252820) 40%));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    color: var(--brand-on-primary, #fff);
    padding: 0 5vw 80px;
    border-radius: 0 0 40px 40px;
    margin-bottom: 60px;
    box-shadow: 0 20px 40px color-mix(in srgb, var(--brand-primary, #687158) 25%, transparent), inset 0 -1px 0 rgba(255, 255, 255, 0.3);
}

.breadcrumbs {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 24px 0;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
}

.breadcrumbs a {
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.2s;
}

.breadcrumbs a:hover {
    opacity: 0.8;
}

.hero-content {
    max-width: 800px;
    margin: 40px auto 0;
    text-align: center;
}

.hero-content .eyebrow {
    color: #c9cebe;
    font-size: 12px;
    letter-spacing: 0.2em;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 16px;
}

.hero-content h1 {
    font-size: clamp(40px, 6vw, 72px);
    font-weight: 500;
    letter-spacing: -0.04em;
    margin: 0 0 24px;
    line-height: 1.1;
}

.hero-desc {
    font-size: 18px;
    color: #e4e6df;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.info-content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 5vw 100px;
}

.info-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 80px;
}

.info-sticky-header {
    position: sticky;
    top: 100px;
    align-self: start;
}

.info-sticky-header h2 {
    font-size: clamp(32px, 4vw, 48px);
    font-weight: 500;
    letter-spacing: -0.04em;
    line-height: 1.1;
    color: var(--brand-dark);
    margin: 0;
}

.info-body {
    color: var(--brand-dark);
}

.lead {
    font-size: 20px;
    line-height: 1.6;
    color: var(--brand-dark);
    margin-bottom: 40px;
    font-weight: 500;
}

p {
    line-height: 1.7;
    color: var(--brand-muted);
    margin-bottom: 24px;
}

/* Premium Grid (Features/Stats/Options) */
.premium-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
    margin: 48px 0;
}

.premium-grid article {
    background: #fff;
    border: 1px solid rgba(104, 113, 88, 0.1);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.premium-grid article:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(104, 113, 88, 0.08);
}

.icon-wrap {
    width: 48px;
    height: 48px;
    background: var(--brand-primary-light);
    color: var(--brand-primary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.premium-grid strong {
    display: block;
    font-size: 18px;
    color: var(--brand-dark);
    margin-bottom: 12px;
    font-weight: 600;
}

.premium-grid span {
    font-size: 14px;
    color: var(--brand-muted);
    line-height: 1.6;
    display: block;
}

/* Glass Callout */
.glass-callout {
    background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.4));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(104, 113, 88, 0.15);
    border-radius: 24px;
    padding: 40px;
    margin: 48px 0;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.03);
}

.glass-callout.success-tint {
    background: linear-gradient(135deg, rgba(104, 113, 88, 0.05), rgba(104, 113, 88, 0.02));
    border-color: rgba(104, 113, 88, 0.2);
}

.callout-inner strong {
    display: block;
    font-size: 18px;
    color: var(--brand-primary);
    margin-bottom: 12px;
    font-weight: 600;
}

.callout-inner p {
    margin: 0;
    font-size: 15px;
    color: var(--brand-dark);
}

/* Premium Button */
.premium-button {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: var(--brand-primary);
    color: #fff;
    border: none;
    padding: 16px 32px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: 0 10px 20px rgba(104, 113, 88, 0.2);
}

.premium-button:hover {
    background: var(--brand-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(104, 113, 88, 0.3);
}

/* Contact Specific */
.contact-details-glass {
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #fff;
    border: 1px solid rgba(104, 113, 88, 0.1);
    border-radius: 24px;
    padding: 32px;
    margin-top: 48px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 20px;
}

.detail-item i {
    color: var(--brand-primary);
    width: 24px;
    height: 24px;
}

.detail-item span {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--brand-muted);
    margin-bottom: 4px;
}

.detail-item strong {
    font-size: 16px;
    color: var(--brand-dark);
    font-weight: 500;
}

/* Form Styles */
.premium-form {
    background: #fff;
    padding: 40px;
    border-radius: 24px;
    border: 1px solid rgba(104, 113, 88, 0.1);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.02);
    margin-top: 48px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.input-group {
    margin-bottom: 24px;
}

.input-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--brand-dark);
    margin-bottom: 10px;
}

.input-group .optional {
    color: var(--brand-muted);
    font-weight: 400;
}

.input-group input,
.input-group select,
.input-group textarea {
    width: 100%;
    background: #fbfbf9;
    border: 1px solid rgba(104, 113, 88, 0.2);
    border-radius: 12px;
    padding: 16px;
    font-size: 15px;
    color: var(--brand-dark);
    transition: all 0.2s ease;
    font-family: inherit;
}

.input-group input:focus,
.input-group select:focus,
.input-group textarea:focus {
    outline: none;
    border-color: var(--brand-primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(104, 113, 88, 0.1);
}

.form-alert {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 32px;
}

.form-alert.success {
    background: #f0f4ec;
    border: 1px solid #d1dfc1;
    color: #3f4e30;
}

.form-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd6d6;
    color: #a83232;
}

/* FAQ Styles */
.premium-faq-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.premium-details {
    background: #fff;
    border: 1px solid rgba(104, 113, 88, 0.1);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.premium-details[open] {
    border-color: var(--brand-primary);
    box-shadow: 0 10px 30px rgba(104, 113, 88, 0.08);
}

.premium-details summary {
    padding: 24px;
    font-size: 18px;
    font-weight: 500;
    color: var(--brand-dark);
    cursor: pointer;
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.premium-details summary::-webkit-details-marker {
    display: none;
}

.premium-details summary i {
    color: var(--brand-primary);
    transition: transform 0.3s ease;
}

.premium-details[open] summary i {
    transform: rotate(180deg);
}

.details-content {
    padding: 0 24px 24px;
    color: var(--brand-muted);
    line-height: 1.7;
}

/* Text Sections */
.text-sections h3 {
    font-size: 24px;
    font-weight: 500;
    color: var(--brand-dark);
    margin: 40px 0 16px;
}

/* Premium Steps (Returns) */
.premium-steps {
    list-style: none;
    padding: 0;
    margin: 32px 0 48px;
    counter-reset: steps;
}

.premium-steps li {
    position: relative;
    padding-left: 48px;
    margin-bottom: 24px;
    font-size: 16px;
    line-height: 1.6;
    color: var(--brand-muted);
}

.premium-steps li::before {
    counter-increment: steps;
    content: counter(steps);
    position: absolute;
    left: 0;
    top: -2px;
    width: 32px;
    height: 32px;
    background: var(--brand-primary-light);
    color: var(--brand-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.premium-steps strong {
    color: var(--brand-dark);
    font-weight: 600;
}

/* Journal Masonry */
.journal-masonry {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
}

.journal-card {
    background: #fff;
    border-radius: 24px;
    border: 1px solid rgba(104, 113, 88, 0.1);
    overflow: hidden;
    transition: all 0.4s ease;
}

.journal-card.featured {
    grid-column: 1 / -1;
    display: grid;
    background: linear-gradient(to right, #fff, #fbfbf9);
}

.journal-card-inner {
    padding: 40px;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.journal-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(104, 113, 88, 0.1);
    border-color: rgba(104, 113, 88, 0.3);
}

.journal-icon {
    width: 56px;
    height: 56px;
    background: var(--brand-primary);
    color: #fff;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 32px;
    box-shadow: 0 10px 20px rgba(104, 113, 88, 0.2);
}

.journal-tag {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: var(--brand-primary);
    font-weight: 700;
    display: block;
    margin-bottom: 12px;
}

.journal-content h2 {
    font-size: clamp(28px, 3vw, 36px);
    font-weight: 500;
    line-height: 1.2;
    letter-spacing: -0.04em;
    color: var(--brand-dark);
    margin: 0 0 16px;
}

.journal-content p {
    font-size: 16px;
    margin-bottom: 32px;
    flex-grow: 1;
}

.read-more {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--brand-primary);
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: gap 0.3s ease;
}

.read-more:hover {
    gap: 12px;
}

/* Responsive */
@media(max-width: 950px) {
    .info-layout {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .info-sticky-header {
        position: static;
    }
}

@media(max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .journal-masonry {
        grid-template-columns: 1fr;
    }
    
    .premium-form {
        padding: 24px;
    }
    
    .glass-callout {
        padding: 24px;
    }
}
</style>

<script>
    lucide.createIcons();
</script>
</body>
</html>
