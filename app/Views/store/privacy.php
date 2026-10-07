<?php
declare(strict_types=1);
$page = 'privacy';
$publicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
$storeEmail = store_setting('store_email', 'privacy@noirandco.com');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?= store_seo_meta(title: 'Privacy & Security Policy - ' . $storeName, description: 'Comprehensive data protection, biometric security, and privacy governance for ' . $storeName, image: store_setting('store_logo')) ?>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/store.min.css') ?>">
    <script src="<?= e($publicPath . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= store_brand_style() ?>
    <style>
        .privacy-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }
        .privacy-hero {
            text-align: center;
            padding: 60px 20px 48px;
            background: radial-gradient(circle at 50% 0%, rgba(104, 113, 88, 0.08) 0%, transparent 70%);
            border-bottom: 1px solid var(--brand-border, #e8e8e0);
            margin-bottom: 48px;
            border-radius: 24px;
        }
        .privacy-hero .eyebrow {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.25em;
            color: #687158;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .privacy-hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 400;
            letter-spacing: -0.02em;
            color: var(--brand-dark, #1a1a1a);
            margin-bottom: 16px;
        }
        .privacy-hero p {
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
        .privacy-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 56px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .privacy-layout {
                grid-template-columns: 1fr;
                gap: 32px;
            }
        }
        .privacy-nav {
            position: sticky;
            top: 100px;
            background: #fbfbf8;
            border: 1px solid var(--brand-border, #e5e5dd);
            border-radius: 20px;
            padding: 24px;
        }
        .privacy-nav h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #687158;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .privacy-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .privacy-nav a {
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
        .privacy-nav a:hover {
            color: #1a1a1a;
            background: rgba(104, 113, 88, 0.1);
        }
        .privacy-section {
            background: #ffffff;
            border: 1px solid var(--brand-border, #e5e5dd);
            border-radius: 24px;
            padding: 36px 40px;
            margin-bottom: 32px;
            scroll-margin-top: 100px;
        }
        @media (max-width: 600px) {
            .privacy-section {
                padding: 24px 20px;
            }
        }
        .privacy-section .sec-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0eb;
        }
        .privacy-section .sec-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #f4f5ee;
            color: #687158;
            flex-shrink: 0;
        }
        .privacy-section h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }
        .privacy-section p {
            font-size: 0.95rem;
            line-height: 1.75;
            color: #4a4d44;
            margin-bottom: 16px;
        }
        .privacy-section ul {
            margin: 12px 0 16px 20px;
            color: #4a4d44;
            line-height: 1.7;
            font-size: 0.95rem;
        }
        .privacy-section li {
            margin-bottom: 8px;
        }
        .feature-box {
            background: #fbfbf8;
            border-left: 4px solid #687158;
            border-radius: 0 12px 12px 0;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 0.9rem;
            color: #383b32;
        }
        .feature-box strong {
            color: #1a1a1a;
        }
        .concierge-card {
            background: linear-gradient(135deg, #242721 0%, #171915 100%);
            color: #ffffff;
            border-radius: 24px;
            padding: 40px;
            margin-top: 48px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }
        .concierge-card h3 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .concierge-card p {
            color: #a4a99d;
            font-size: 0.95rem;
            max-width: 540px;
            margin: 0;
        }
        .concierge-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #98A682;
            color: #121412;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .concierge-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>

<main class="privacy-container">
    <!-- Hero Section -->
    <section class="privacy-hero">
        <p class="eyebrow">Trust & Transparency</p>
        <h1>Privacy & Security Policy</h1>
        <p>At <?= e($storeName) ?>, we hold your confidentiality to the highest standard of luxury. Learn how your data, identity, and biometric preferences are protected across our digital atelier.</p>
        
        <div class="trust-badges">
            <span class="trust-badge"><i data-lucide="shield-check"></i> 256-Bit TLS Encryption</span>
            <span class="trust-badge"><i data-lucide="fingerprint"></i> On-Device Biometric Enclave</span>
            <span class="trust-badge"><i data-lucide="user-x"></i> Zero Sale of Personal Data</span>
            <span class="trust-badge"><i data-lucide="credit-card"></i> PCI-DSS Level 1 Compliant</span>
        </div>
    </section>

    <!-- Layout Grid -->
    <div class="privacy-layout">
        <!-- Sidebar Navigation -->
        <aside class="privacy-nav">
            <h4>Contents</h4>
            <ul>
                <li><a href="#collection"><i data-lucide="database" class="w-4 h-4"></i> Data Collection</a></li>
                <li><a href="#biometrics"><i data-lucide="fingerprint" class="w-4 h-4"></i> Biometrics & Device Keys</a></li>
                <li><a href="#isolation"><i data-lucide="layers" class="w-4 h-4"></i> Account Isolation</a></li>
                <li><a href="#payments"><i data-lucide="lock" class="w-4 h-4"></i> Payment Tokenization</a></li>
                <li><a href="#usage"><i data-lucide="activity" class="w-4 h-4"></i> How We Use Data</a></li>
                <li><a href="#rights"><i data-lucide="scale" class="w-4 h-4"></i> Client Privacy Rights</a></li>
                <li><a href="#retention"><i data-lucide="archive" class="w-4 h-4"></i> Data Retention</a></li>
                <li><a href="#dpo"><i data-lucide="mail" class="w-4 h-4"></i> Data Protection Officer</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="privacy-content-body">
            <!-- 1. Data Collection -->
            <section id="collection" class="privacy-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="database"></i></div>
                    <h2>1. Information We Collect</h2>
                </div>
                <p>We collect only the essential information necessary to deliver bespoke craftsmanship, seamless logistics, and personalized concierge services.</p>
                <ul>
                    <li><strong>Account Profile:</strong> Name, verified email address, phone number, and optional bespoke styling notes.</li>
                    <li><strong>Delivery Destinations:</strong> Recipient names, street addresses, postal zones, and delivery instructions saved in your private customer address book.</li>
                    <li><strong>Transactional History:</strong> Curated order records, custom sizing specifications, invoices, and shipment tracking milestones.</li>
                    <li><strong>Technical Telemetry:</strong> Device operating system, browser configuration, and IP address for fraud mitigation and session security.</li>
                </ul>
            </section>

            <!-- 2. Biometric Security -->
            <section id="biometrics" class="privacy-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="fingerprint"></i></div>
                    <h2>2. Biometric & Hardware Enclave Security</h2>
                </div>
                <p>When you enable Touch ID, Face ID, or Fingerprint Unlock in our mobile application, your biometric samples never leave your physical device.</p>
                <div class="feature-box">
                    <strong>Zero Biometric Cloud Storage:</strong> All biometric verifications occur locally via your smartphone's hardware Secure Enclave (Apple iOS) or Android BiometricPrompt / KeyStore. <?= e($storeName) ?> never records, transmits, or possesses copies of your face or fingerprint data.
                </div>
                <p>You may toggle biometric unlock on or off at any moment in your mobile account settings under <em>Privacy & Security</em>.</p>
            </section>

            <!-- 3. Account Data Isolation -->
            <section id="isolation" class="privacy-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="layers"></i></div>
                    <h2>3. Multi-Account Data Isolation</h2>
                </div>
                <p>To preserve absolute discretion across shared family devices or multiple client accounts:</p>
                <ul>
                    <li><strong>Cryptographically Scoped Profiles:</strong> Address books, payment methods, and shopping bags are strictly partitioned by authenticated User ID.</li>
                    <li><strong>Zero Session Bleed:</strong> Logging into a different profile isolates all historical delivery records and billing cards, preventing cross-account data leakage.</li>
                    <li><strong>Secure Cache Wipe:</strong> Terminating a session purges active authentication tokens and private cache partitions from the device.</li>
                </ul>
            </section>

            <!-- 4. Payment Tokenization -->
            <section id="payments" class="privacy-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="lock"></i></div>
                    <h2>4. Payment Security & Tokenization</h2>
                </div>
                <p>All online financial transactions are executed via PCI-DSS Level 1 certified gateway providers (Paystack). Full credit/debit card numbers, CVVs, and banking pins are never processed or stored on our servers.</p>
                <p>We only store safe display metadata—such as the card brand and the last four digits—allowing you to identify and manage your saved payment methods with confidence.</p>
            </section>

            <!-- 5. How We Use Data -->
            <section id="usage" class="privacy-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="activity"></i></div>
                    <h2>5. How We Use Your Information</h2>
                </div>
                <p>Your personal information is used exclusively to facilitate your boutique experience:</p>
                <ul>
                    <li>Fulfilling bespoke garment orders, processing secure payments, and coordinating white-glove courier deliveries.</li>
                    <li>Notifying you of order confirmations, tracking updates, and concierge service requests.</li>
                    <li>Tailoring your AI Stylist recommendations based on your preferences.</li>
                    <li>Preventing unauthorized transactions, protecting intellectual property, and combating fraud.</li>
                </ul>
            </section>

            <!-- 6. Client Privacy Rights -->
            <section id="rights" class="privacy-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="scale"></i></div>
                    <h2>6. Your Privacy Rights (GDPR / NDPR / CCPA)</h2>
                </div>
                <p>Regardless of your geographic location, <?= e($storeName) ?> extends global privacy protections to all clients:</p>
                <ul>
                    <li><strong>Right to Access & Portability:</strong> Request an export of all personal data held in your customer file.</li>
                    <li><strong>Right to Rectification:</strong> Edit and update your personal details directly from your customer portal at any time.</li>
                    <li><strong>Right to Erasure (The Right to Be Forgotten):</strong> Request the permanent deletion of your account and personal history, subject to statutory accounting requirements.</li>
                    <li><strong>Marketing Opt-Out:</strong> Unsubscribe from marketing dispatches with one click via email footers or app settings.</li>
                </ul>
            </section>

            <!-- 7. Data Retention -->
            <section id="retention" class="privacy-section">
                <div class="sec-header">
                    <div class="sec-icon"><i data-lucide="archive"></i></div>
                    <h2>7. Data Retention & Safeguards</h2>
                </div>
                <p>We retain your profile for as long as your account remains active. Encrypted backups and purchase transaction logs are retained strictly in compliance with applicable tax, financial audit, and consumer protection laws.</p>
            </section>

            <!-- Concierge Contact -->
            <div id="dpo" class="concierge-card">
                <div>
                    <h3>Data Protection & Concierge</h3>
                    <p>Have questions regarding our security protocols, account data isolation, or wish to request data erasure? Our Privacy Officer is available to assist you.</p>
                </div>
                <a href="mailto:<?= e($storeEmail) ?>" class="concierge-btn">
                    <i data-lucide="mail"></i> Contact Privacy Officer
                </a>
            </div>
        </div>
    </div>
</main>

<footer class="site-footer">
    <span class="brand">
        <?php if (store_setting('store_logo')): ?>
            <?= store_logo_tag($publicPath, $storeName) ?>
        <?php else: ?>
            <?= e($storeName) ?>
        <?php endif; ?>
    </span>
    <span>Crafted with discretion for the considered life.</span>
</footer>

<script>
    lucide.createIcons();
    // Smooth scrolling for sidebar links
    document.querySelectorAll('.privacy-nav a').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href');
            var targetElem = document.querySelector(targetId);
            if (targetElem) {
                targetElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>
</body>
</html>
