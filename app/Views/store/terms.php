<?php
declare(strict_types=1);
$page = 'terms';
$publicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
$storeEmail = store_setting('store_email', 'legal@noirandco.com');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?= store_seo_meta(title: 'Terms of Service - ' . $storeName, description: 'Terms of service, sales policy, payment security, and customer agreement for ' . $storeName, image: store_setting('store_logo')) ?>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/store.min.css') ?>">
    <script src="<?= e($publicPath . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= store_brand_style() ?>
    <style>
        .terms-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }
        .terms-hero {
            text-align: center;
            padding: 60px 20px 48px;
            background: radial-gradient(circle at 50% 0%, rgba(104, 113, 88, 0.08) 0%, transparent 70%);
            border-bottom: 1px solid var(--brand-border, #e8e8e0);
            margin-bottom: 48px;
            border-radius: 24px;
        }
        .terms-hero .eyebrow {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.25em;
            color: #687158;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .terms-hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 400;
            letter-spacing: -0.02em;
            color: var(--brand-dark, #1a1a1a);
            margin-bottom: 16px;
        }
        .terms-hero p {
            font-size: 1.05rem;
            color: var(--brand-muted, #70706a);
            max-width: 680px;
            margin: 0 auto;
            line-height: 1.6;
        }
        .trust-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
            margin-top: 32px;
        }
        .trust-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid var(--brand-border, #e5e5dd);
            font-size: 12px;
            font-weight: 600;
            color: #2c2f26;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }
        .trust-badge i {
            color: #687158;
            width: 16px;
            height: 16px;
        }
        .terms-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 56px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .terms-layout {
                grid-template-columns: 1fr;
                gap: 32px;
            }
        }
        .terms-nav {
            position: sticky;
            top: 100px;
            background: #fbfbf8;
            border: 1px solid var(--brand-border, #e5e5dd);
            border-radius: 20px;
            padding: 24px;
        }
        .terms-nav h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #687158;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .terms-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .terms-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--brand-muted, #666);
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .terms-nav a:hover {
            color: #1a1a1a;
            background: rgba(104, 113, 88, 0.1);
        }
        .terms-section {
            background: #ffffff;
            border: 1px solid var(--brand-border, #e5e5dd);
            border-radius: 24px;
            padding: 36px 40px;
            margin-bottom: 32px;
            scroll-margin-top: 100px;
        }
        @media (max-width: 600px) {
            .terms-section {
                padding: 24px 20px;
            }
        }
        .terms-section .sec-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0eb;
        }
        .terms-section .sec-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #f4f5ee;
            color: #687158;
            flex-shrink: 0;
        }
        .terms-section h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }
        .terms-section p {
            font-size: 0.95rem;
            line-height: 1.75;
            color: #4a4d44;
            margin-bottom: 16px;
        }
        .terms-section ul {
            margin: 12px 0 16px 20px;
            color: #4a4d44;
            line-height: 1.7;
            font-size: 0.95rem;
        }
        .terms-section li {
            margin-bottom: 8px;
        }
        .highlight-box {
            background: #fcfcfa;
            border-left: 3px solid #687158;
            padding: 16px 20px;
            border-radius: 0 12px 12px 0;
            margin: 20px 0;
            font-size: 0.9rem;
            color: #3b3d36;
        }
        .legal-timestamp {
            font-size: 12px;
            color: #8c8f85;
            margin-top: 24px;
        }
    </style>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>

<main class="terms-container">
    <div class="terms-hero">
        <p class="eyebrow">Legal &amp; Merchant Governance</p>
        <h1>Terms of Service</h1>
        <p>Please read these Terms of Service carefully before purchasing or interacting with <?= e($storeName) ?>. By accessing our platform, catalogue, or submitting an order, you agree to be bound by these terms.</p>
        <div class="trust-badges">
            <div class="trust-badge"><i data-lucide="shield-check"></i> PCI-DSS Level 1 Encrypted</div>
            <div class="trust-badge"><i data-lucide="scale"></i> Consumer Protection Adherent</div>
            <div class="trust-badge"><i data-lucide="truck"></i> Insured Delivery Guarantee</div>
            <div class="trust-badge"><i data-lucide="refresh-cw"></i> 7-Day Inspection Window</div>
        </div>
    </div>

    <div class="terms-layout">
        <!-- Sidebar Navigation -->
        <aside class="terms-nav">
            <h4>Quick Navigation</h4>
            <ul>
                <li><a href="#acceptance"><i data-lucide="file-check"></i> 1. Acceptance &amp; Entity</a></li>
                <li><a href="#eligibility"><i data-lucide="user-check"></i> 2. Eligibility &amp; Accounts</a></li>
                <li><a href="#products-pricing"><i data-lucide="tag"></i> 3. Products &amp; Pricing</a></li>
                <li><a href="#orders-payments"><i data-lucide="credit-card"></i> 4. Orders &amp; Payments</a></li>
                <li><a href="#shipping-risk"><i data-lucide="truck"></i> 5. Shipping &amp; Title</a></li>
                <li><a href="#returns-refunds"><i data-lucide="rotate-ccw"></i> 6. Returns &amp; Refunds</a></li>
                <li><a href="#intellectual-prop"><i data-lucide="sparkles"></i> 7. Intellectual Property</a></li>
                <li><a href="#liability"><i data-lucide="shield-alert"></i> 8. Limitation of Liability</a></li>
                <li><a href="#governing-law"><i data-lucide="landmark"></i> 9. Governing Law</a></li>
                <li><a href="#contact"><i data-lucide="mail"></i> 10. Contact &amp; Notices</a></li>
            </ul>
        </aside>

        <!-- Main Content Column -->
        <div class="terms-content">
            <!-- 1. Acceptance -->
            <section id="acceptance" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="file-check"></i></div>
                    <h2>1. Acceptance of Terms &amp; Merchant Identity</h2>
                </div>
                <p>These Terms of Service (&ldquo;Terms&rdquo;) constitute a legally binding agreement between you (&ldquo;Customer&rdquo;, &ldquo;User&rdquo;, or &ldquo;you&rdquo;) and <strong><?= e($storeName) ?></strong> (&ldquo;we&rdquo;, &ldquo;us&rdquo;, or &ldquo;our&rdquo;), the operator and Merchant of Record for all transactions conducted on this storefront.</p>
                <p>By accessing our digital shop, browsing collections, creating an account, or placing an order, you confirm that you have read, understood, and agreed to these Terms, along with our <a href="<?= e($publicPath . '/privacy-policy') ?>" style="color: #687158; text-decoration: underline; font-weight: 500;">Privacy Policy</a> and <a href="<?= e($publicPath . '/shipping') ?>" style="color: #687158; text-decoration: underline; font-weight: 500;">Shipping Policy</a>.</p>
                <div class="highlight-box">
                    If you do not agree with any provision of these Terms, you must immediately discontinue using the website and refrain from placing transactions.
                </div>
            </section>

            <!-- 2. Eligibility & Accounts -->
            <section id="eligibility" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="user-check"></i></div>
                    <h2>2. Customer Eligibility &amp; Account Responsibility</h2>
                </div>
                <p>To place an order or create a customer profile with <?= e($storeName) ?>, you must be at least 18 years of age (or the age of legal majority in your jurisdiction) and possess full legal capacity to enter into binding contracts.</p>
                <ul>
                    <li><strong>Account Security:</strong> You are responsible for safeguarding your login credentials, password, and session security. Any actions taken through your account are your sole responsibility.</li>
                    <li><strong>Accurate Information:</strong> You agree to provide true, current, and complete shipping, contact, and billing details during checkout. Inaccurate addresses or fraudulent details may result in order cancellation or delayed delivery at the buyer&rsquo;s expense.</li>
                    <li><strong>Account Termination:</strong> We reserve the right to suspend or terminate accounts that engage in fraudulent chargebacks, automated scraping, abusive conduct, or policy violations.</li>
                </ul>
            </section>

            <!-- 3. Products & Pricing -->
            <section id="products-pricing" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="tag"></i></div>
                    <h2>3. Product Details, Accuracy &amp; Pricing</h2>
                </div>
                <p>We take meticulous care in presenting our capsules, fabrics, colors, and cuts as accurately as possible. However, monitor displays and ambient lighting may cause slight optical variations in natural fibers and leather patinas.</p>
                <ul>
                    <li><strong>Editions &amp; Stock:</strong> Items are produced in curated small drops. Placement of an item in your shopping cart does not reserve inventory until full payment is authorized.</li>
                    <li><strong>Pricing &amp; Currency:</strong> Prices are displayed in your selected local currency or default store currency. Applicable taxes, VAT, and shipping fees are calculated transparently during the checkout phase before you authorize payment.</li>
                    <li><strong>Pricing Errors:</strong> In the rare event an item is erroneously listed at an incorrect price due to a system glitch, we reserve the right to decline or cancel orders placed for the affected item, with an immediate 100% refund.</li>
                </ul>
            </section>

            <!-- 4. Orders & Payments -->
            <section id="orders-payments" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="credit-card"></i></div>
                    <h2>4. Order Processing, Payment Security &amp; Fraud Prevention</h2>
                </div>
                <p>When you place an order on <?= e($storeName) ?>, your submission constitutes an offer to purchase. An order is formally accepted once our automated fraud and payment verification verifies full settlement and generates an Order Number.</p>
                <ul>
                    <li><strong>Payment Gateways:</strong> We process credit/debit cards, bank transfers, and local payment rails through certified PCI-DSS Level 1 compliant gateway partners (including Paystack and licensed acquiring partners).</li>
                    <li><strong>Cardholder Authentication:</strong> All transactions utilize 3D-Secure (3DS) two-factor authentication and tokenized encryption. <?= e($storeName) ?> never stores, sees, or retains your raw credit card numbers or CVV codes on our servers.</li>
                    <li><strong>Fraud Screening:</strong> To protect genuine buyers, suspicious transactions or mismatched billing details may undergo automated anti-fraud security holds or require manual proof of payment authorization.</li>
                </ul>
                <div class="highlight-box">
                    <strong>Payment Confirmation:</strong> Upon successful settlement, an automated digital receipt and confirmation email are sent to your specified email address containing your unique Order Number.
                </div>
            </section>

            <!-- 5. Shipping & Risk -->
            <section id="shipping-risk" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="truck"></i></div>
                    <h2>5. Fulfilment, Delivery &amp; Transfer of Risk</h2>
                </div>
                <p>Orders are dispatched by our logistics team following quality inspection. Standard processing takes 24 to 48 business hours.</p>
                <ul>
                    <li><strong>Tracking:</strong> Each parcel is assigned a trackable tracking code verifiable through our <a href="<?= e($publicPath . '/track-order') ?>" style="color: #687158; text-decoration: underline;">Track Order</a> portal.</li>
                    <li><strong>Delivery Timelines:</strong> Standard domestic delivery ranges from 2 to 5 business days, while regional express options arrive in 1 to 2 business days. International transit times depend on customs clearance.</li>
                    <li><strong>Transfer of Risk &amp; Title:</strong> Title and risk of loss pass to the customer upon verified delivery confirmation by the courier service to the designated shipping address.</li>
                </ul>
            </section>

            <!-- 6. Returns & Refunds -->
            <section id="returns-refunds" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="rotate-ccw"></i></div>
                    <h2>6. Statutory Returns, Cancellations &amp; Refund Policy</h2>
                </div>
                <p>We stand behind the craft of every piece. If your purchase does not meet your expectations, we provide a structured, transparent return process.</p>
                <ul>
                    <li><strong>7-Day Inspection Period:</strong> You have seven (7) calendar days from receipt of your shipment to inspect and initiate an exchange or return request via our <a href="<?= e($publicPath . '/returns') ?>" style="color: #687158; text-decoration: underline;">Returns Portal</a> or Client Concierge.</li>
                    <li><strong>Eligible Condition:</strong> Items must be returned in their original, pristine condition &mdash; unworn, unwashed, unaltered, with all original tags, security seals, and dust bags intact.</li>
                    <li><strong>Refund Processing:</strong> Approved refunds are credited directly to the original payment method used during checkout within 3 to 7 business days following physical receipt and atelier inspection.</li>
                    <li><strong>Defective or Damaged Merchandise:</strong> If an item arrives damaged or with a manufacturing defect, report it to our team within 48 hours of delivery with photographic evidence for immediate complimentary replacement or full refund.</li>
                </ul>
            </section>

            <!-- 7. Intellectual Property -->
            <section id="intellectual-prop" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="sparkles"></i></div>
                    <h2>7. Intellectual Property Rights</h2>
                </div>
                <p>All content available on this storefront &mdash; including brand marks, logos, typography, editorial photography, garment patterns, audio, software source code, user interfaces, and digital artwork &mdash; is the exclusive property of <?= e($storeName) ?> or its licensors and is protected by copyright, trademark, and international intellectual property treaties.</p>
                <p>You may not reproduce, modify, distribute, sell, mirror, or extract our design assets or catalogue data without express written permission from our executive office.</p>
            </section>

            <!-- 8. Limitation of Liability -->
            <section id="liability" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="shield-alert"></i></div>
                    <h2>8. Warranties &amp; Limitation of Liability</h2>
                </div>
                <p>Our website, products, and services are provided on an &ldquo;as is&rdquo; and &ldquo;as available&rdquo; basis. To the maximum extent permitted by applicable law, <?= e($storeName) ?> disclaims all warranties, express or implied, including merchantability and fitness for a particular purpose.</p>
                <p>In no event shall <?= e($storeName) ?>, its directors, employees, or logistics suppliers be liable for indirect, incidental, punitive, or consequential damages resulting from the use of our services or delayed shipments caused by force majeure events (strikes, weather emergencies, customs delays, or network outages).</p>
                <p>Our aggregate liability for any claim arising out of your purchase is strictly capped at the purchase price paid by you for the specific order in question.</p>
            </section>

            <!-- 9. Governing Law -->
            <section id="governing-law" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="landmark"></i></div>
                    <h2>9. Governing Law &amp; Dispute Resolution</h2>
                </div>
                <p>These Terms and any non-contractual obligations arising out of or in connection with them shall be governed by and construed in accordance with the laws of the jurisdiction where <?= e($storeName) ?> maintains its principal commercial registration, without regard to conflicts of law principles.</p>
                <p>In the event of any dispute or disagreement, we encourage customers to first reach out to our Client Concierge to achieve an amicable resolution. If a resolution cannot be reached within 30 days, disputes will be submitted to the competent commercial courts having jurisdiction.</p>
            </section>

            <!-- 10. Contact -->
            <section id="contact" class="terms-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="mail"></i></div>
                    <h2>10. Contact, Legal Notices &amp; Updates</h2>
                </div>
                <p>We may periodically update these Terms to reflect operational improvements, statutory mandates, or evolving merchant compliance standards. The latest version will always be maintained at this URL.</p>
                <div class="highlight-box">
                    <strong>Corporate &amp; Legal Inquiries:</strong><br>
                    <strong>Maison:</strong> <?= e($storeName) ?><br>
                    <strong>Client Concierge:</strong> <a href="<?= e($publicPath . '/contact') ?>" style="color: #687158; text-decoration: underline;">Contact Concierge Page</a><br>
                    <strong>Direct Legal Email:</strong> <a href="mailto:<?= e($storeEmail) ?>" style="color: #687158; text-decoration: underline;"><?= e($storeEmail) ?></a><br>
                    <strong>Response Timeline:</strong> Formal inquiries receive written acknowledgement within 24 business hours.
                </div>
                <p class="legal-timestamp">Last modified: <?= date('F j, Y') ?> &bull; Version 2.4</p>
            </section>
        </div>
    </div>
</main>

<?php require __DIR__ . '/components/footer.php'; ?>
<script>
    if (window.lucide) {
        window.lucide.createIcons();
    }
</script>
</body>
</html>
