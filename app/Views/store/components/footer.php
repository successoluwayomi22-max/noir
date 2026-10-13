<?php
$publicPath = $publicPath ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$downloadHost = !empty($_SERVER['HTTP_HOST']) 
    ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']) 
    : rtrim((string)config('url', 'http://localhost:8000'), '/');
$appDownloadUrl = $downloadHost . ($publicPath ?: '') . '/download/app';
?>
<footer class="site-footer shop-footer" id="contact">
    <div class="footer-ambient-aura" aria-hidden="true"></div>
    <div class="footer-container">
        <!-- Editorial Atelier Pillars -->
        <div class="footer-pillars-strip">
            <div class="footer-pillar-cell">
                <div class="footer-pillar-icon-box">
                    <i data-lucide="gem"></i>
                </div>
                <div class="footer-pillar-text">
                    <span class="footer-pillar-title"><?= __('Master Craftsmanship') ?></span>
                    <span class="footer-pillar-sub"><?= __('Hand-finished by heritage ateliers') ?></span>
                </div>
            </div>
            <div class="footer-pillar-cell">
                <div class="footer-pillar-icon-box">
                    <i data-lucide="leaf"></i>
                </div>
                <div class="footer-pillar-text">
                    <span class="footer-pillar-title"><?= __('Conscious Sourcing') ?></span>
                    <span class="footer-pillar-sub"><?= __('Ethical, traceable organic fibers') ?></span>
                </div>
            </div>
            <div class="footer-pillar-cell">
                <div class="footer-pillar-icon-box">
                    <i data-lucide="shield-check"></i>
                </div>
                <div class="footer-pillar-text">
                    <span class="footer-pillar-title"><?= __('Bespoke Client Care') ?></span>
                    <span class="footer-pillar-sub"><?= __('Worldwide insured tracked dispatch') ?></span>
                </div>
            </div>
        </div>

        <!-- Main 5-Column Grid -->
        <div class="footer-main-grid">
            <!-- Brand Column -->
            <div class="footer-brand-col">
                <a href="<?= e($publicPath . '/') ?>" class="footer-brand-lockup" aria-label="<?= e(store_setting('store_name', 'Noir & Co.')) ?>">
                    <?php if(store_setting('store_logo')): ?>
                        <span class="footer-brand-logo-mark"><?= store_logo_tag($publicPath, store_setting('store_name', 'Noir & Co.')) ?></span>
                    <?php else: ?>
                        <span class="footer-brand-logo-mark footer-fallback-mark">NC</span>
                    <?php endif; ?>
                    <div class="footer-brand-title-wrap">
                        <span class="footer-brand-title"><?= e(store_setting('store_name', 'Noir & Co.')) ?></span>
                        <span class="footer-brand-tagline">WORLDWIDE</span>
                    </div>
                </a>
                <p class="footer-brand-desc">
                    <?= __('Considered design for the everyday. Curating modern wardrobe capsules with quiet, architectural character.') ?>
                </p>
                <div class="footer-cert-badge">
                    <span class="footer-cert-dot"></span>
                    <span><?= __('DESIGN STUDIO &bull; WORLDWIDE') ?></span>
                </div>
            </div>

            <!-- Column 2: Shop -->
            <div class="footer-nav-col">
                <span class="footer-col-heading"><?= __('Shop') ?></span>
                <ul class="footer-nav-list">
                    <li><a href="<?= e($publicPath . '/store') ?>"><?= __('All Products') ?></a></li>
                    <li><a href="<?= e($publicPath . '/store#collections') ?>"><?= __('Collections') ?></a></li>
                    <li><a href="<?= e($publicPath . '/search') ?>"><?= __('Search Catalog') ?></a></li>
                    <li><a href="<?= e($publicPath . '/size-guide') ?>"><?= __('Size Guide') ?></a></li>
                </ul>
            </div>

            <!-- Column 3: Client Care -->
            <div class="footer-nav-col">
                <span class="footer-col-heading"><?= __('Client Care') ?></span>
                <ul class="footer-nav-list">
                    <li><a href="<?= e($publicPath . '/track-order') ?>"><?= __('Track Order') ?></a></li>
                    <li><a href="<?= e($publicPath . '/returns') ?>"><?= __('Returns & Exchanges') ?></a></li>
                    <li><a href="<?= e($publicPath . '/contact') ?>"><?= __('Client Concierge') ?></a></li>
                    <li><a href="<?= e($publicPath . '/faq') ?>"><?= __('FAQ') ?></a></li>
                </ul>
            </div>

            <!-- Column 4: Company & Mobile App -->
            <div class="footer-nav-col footer-company-col">
                <span class="footer-col-heading"><?= __('Company') ?></span>
                <ul class="footer-nav-list">
                    <li><a href="<?= e($publicPath . '/about') ?>"><?= __('About The Maison') ?></a></li>
                    <li><a href="<?= e($publicPath . '/journal') ?>"><?= __('Journal & Stories') ?></a></li>
                    <li><a href="<?= e($publicPath . '/privacy-policy') ?>"><?= __('Privacy Policy') ?></a></li>
                </ul>

                <!-- Small QR Code Card for App Download (Android & iOS) -->
                <div class="footer-app-qr-box">
                    <a href="<?= e($publicPath . '/download/app?view=1') ?>" class="footer-app-qr-card" title="<?= __('Scan or click to download Noir App for iOS & Android') ?>">
                        <div class="footer-qr-img-frame">
                            <img src="<?= e(($publicPath?:'').'/assets/icons/app-qr.svg') ?>" 
                                 alt="<?= __('Scan to Download Noir App') ?>" 
                                 width="66" 
                                 height="66" 
                                 loading="eager" 
                                 class="footer-qr-code-img">
                        </div>
                        <div class="footer-app-qr-content">
                            <span class="footer-app-qr-title"><?= __('Noir App') ?></span>
                            <span class="footer-app-qr-sub"><?= __('Scan to download') ?></span>
                            <div class="footer-app-pills">
                                <span class="footer-app-pill" title="Apple iOS"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="display:inline-block;vertical-align:-1px;"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> iOS</span>
                                <span class="footer-app-pill" title="Android" style="color:#3ddc84;"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="display:inline-block;vertical-align:-1px;"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C18.0741 13.3414 18.5227 13.79 18.5227 14.3414c0 .5514-.4486 1-.9997 1zm-11.046 0c-.5511 0-.9993-.4486-.9993-1 0-.5514.4482-1 .9993-1C6.0278 13.3414 6.4764 13.79 6.4764 14.3414c0 .5514-.4486 1-.9994 1zm11.4045-6.02l1.996-3.456c.145-.2513.06-.5727-.1913-.7177-.251-.145-.5724-.06-.7174.1913l-2.0232 3.5042C15.4858 8.3503 13.792 8.005 12 8.005c-1.792 0-3.4858.3453-4.9456.8432L5.0312 5.344c-.145-.2513-.4664-.3363-.7174-.1913-.2513.145-.3363.4664-.1913.7177l1.996 3.456C2.6877 11.2384.3435 15.1118.005 19.645h23.99c-.3385-4.5332-2.6827-8.4066-6.1135-10.3236z"/></svg> Android</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Column 5: Preferences -->
            <div class="footer-nav-col footer-pref-col">
                <span class="footer-col-heading"><?= __('Preferences') ?></span>
                <div class="footer-pref-stack">
                    <div class="footer-select-box">
                        <label for="footer-currency-select" class="footer-select-label"><?= __('Currency') ?></label>
                        <div class="footer-select-control">
                            <?= currency_switcher_html($publicPath) ?>
                        </div>
                    </div>
                    <div class="footer-select-box">
                        <label for="footer-language-select" class="footer-select-label"><?= __('Language') ?></label>
                        <div class="footer-select-control">
                            <?= language_switcher_html($publicPath) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hairline Divider & Bottom Bar -->
        <div class="footer-bottom-row">
            <div class="footer-bottom-left">
                &copy; <?= date('Y') ?> <strong><?= e(store_setting('store_name', 'Noir & Co.')) ?></strong>. <?= __('All rights reserved.') ?>
            </div>
            <div class="footer-bottom-right">
                <a href="<?= e($publicPath . '/privacy-policy') ?>"><?= __('Privacy') ?></a>
                <span class="footer-sep">&bull;</span>
                <a href="<?= e($publicPath . '/terms') ?>"><?= __('Terms') ?></a>
                <span class="footer-sep">&bull;</span>
                <span><?= __('Timeless craft. Made to endure.') ?></span>
            </div>
        </div>
    </div>
</footer>

<?php require __DIR__ . '/cookie-banner.php'; ?>

<style>
/* Luxury Maison Footer — Dynamic Brand-Aware Design */
.shop-footer {
    position: relative;
    background: linear-gradient(
        172deg,
        color-mix(in srgb, var(--brand-primary, #687158) 90%, #fff 10%) 0%,
        var(--brand-primary, #687158) 50%,
        color-mix(in srgb, var(--brand-primary, #687158) 90%, #000 10%) 100%
    ) !important;
    color: var(--brand-on-primary, #f7f7f2) !important;
    padding: 60px 7vw 36px !important;
    border-top: 1px solid rgba(255, 255, 255, 0.18) !important;
    overflow: hidden;
    isolation: isolate;
    font-family: inherit;
}

.footer-ambient-aura {
    position: absolute;
    top: -100px;
    left: 50%;
    transform: translateX(-50%);
    width: 750px;
    height: 200px;
    background: radial-gradient(circle, color-mix(in srgb, var(--brand-primary, #687158) 22%, transparent) 0%, color-mix(in srgb, var(--brand-primary, #687158) 0%, transparent) 70%);
    filter: blur(40px);
    pointer-events: none;
    z-index: 0;
}

.footer-container {
    position: relative;
    z-index: 1;
    max-width: 1320px;
    margin: 0 auto;
    width: 100%;
}

/* Value Strip */
.footer-pillars-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    padding-bottom: 40px;
    margin-bottom: 48px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.footer-pillar-cell {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 20px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.footer-pillar-cell:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

.footer-pillar-icon-box {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    flex: none;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    transition: transform 0.3s ease, background 0.3s ease;
}

.footer-pillar-cell:hover .footer-pillar-icon-box {
    transform: scale(1.06);
    background: #ffffff;
    color: var(--brand-primary, #687158);
}

.footer-pillar-icon-box svg {
    width: 18px;
    height: 18px;
    stroke-width: 1.8;
}

.footer-pillar-title {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -0.01em;
}

.footer-pillar-sub {
    display: block;
    font-size: 11.5px;
    color: #ffffff;
    opacity: 0.92;
    margin-top: 2px;
    font-weight: 400;
}

/* Main Grid */
.footer-main-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr 1fr 1.3fr;
    gap: 48px;
    margin-bottom: 50px;
    align-items: start;
}

/* Brand Column */
.footer-brand-col {
    max-width: 320px;
}

.footer-brand-lockup {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.footer-brand-lockup:hover {
    transform: translateY(-1px);
}

.footer-brand-logo-mark {
    display: inline-grid;
    place-items: center;
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    border-radius: 10px;
    overflow: hidden;
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    padding: 0;
}

.footer-brand-logo-mark .store-brand-with-logo {
    display: block;
    width: 100%;
    height: 100%;
}

.footer-brand-logo-mark .store-logo-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.footer-fallback-mark {
    color: #ffffff;
    font-size: 17px;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.footer-brand-title-wrap {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.footer-brand-title {
    color: #ffffff !important;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    line-height: 1.1;
}

.footer-brand-tagline {
    font-size: 10px;
    color: #ffffff;
    opacity: 0.95;
    letter-spacing: 0.22em;
    font-weight: 800;
    text-transform: uppercase;
}

.footer-brand-desc {
    margin: 16px 0 0;
    font-size: 13px;
    color: #ffffff;
    opacity: 0.92;
    line-height: 1.65;
    font-weight: 400;
}

.footer-cert-badge {
    margin-top: 18px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.15);
    font-size: 10px;
    color: #eef3ea;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    font-weight: 700;
}

.footer-cert-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #9fe283;
    box-shadow: 0 0 6px #9fe283;
    animation: certDotPulse 2s infinite;
    will-change: transform, opacity;
    contain: paint;
    transform: translateZ(0);
}

@keyframes certDotPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
}

/* Nav Columns */
.footer-nav-col {
    display: flex;
    flex-direction: column;
}

.footer-col-heading {
    display: block;
    color: #ffffff !important;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    font-weight: 800;
    margin-bottom: 18px;
}

.footer-nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 11px;
}

.footer-nav-list li a {
    position: relative;
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    padding: 6px 0;
    font-size: 13.5px;
    color: #ffffff !important;
    opacity: 0.92;
    text-decoration: none;
    font-weight: 400;
    transition: color 0.2s ease, transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.footer-nav-list li a:hover {
    color: #ffffff !important;
    opacity: 1;
    transform: translateX(5px);
    font-weight: 600;
}

/* App Download QR Code Card */
.footer-app-qr-box {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-app-qr-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 10px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.13);
    text-decoration: none !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    max-width: 220px;
}

.footer-app-qr-card:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.28);
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
}

.footer-qr-img-frame {
    width: 66px;
    height: 66px;
    flex: 0 0 66px;
    border-radius: 8px;
    background: #ffffff;
    padding: 3px;
    display: grid;
    place-items: center;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.14);
    overflow: hidden;
}

.footer-qr-code-img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: contain;
    border-radius: 5px;
}

.footer-app-qr-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.footer-app-qr-title {
    font-size: 12.5px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -0.01em;
    line-height: 1.2;
}

.footer-app-qr-sub {
    font-size: 10px;
    color: #ffffff;
    opacity: 0.92;
    margin-bottom: 4px;
    letter-spacing: 0.02em;
}

.footer-app-pills {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}

.footer-app-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.18);
    font-size: 9px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.03em;
    line-height: 1.3;
}

.footer-app-pill svg {
    width: 10px;
    height: 10px;
    stroke-width: 2.2;
}

/* Preferences Column & Luxury Select Styling */
.footer-pref-col {
    max-width: 250px;
}

.footer-pref-stack {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.footer-select-box {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.footer-select-label {
    display: block;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: #ffffff;
    opacity: 0.95;
    font-weight: 800;
}

.footer-select-control form {
    display: block !important;
    width: 100%;
}

.footer-select-control select {
    display: block !important;
    width: 100% !important;
    min-height: 42px !important;
    height: 42px !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background-color: #ffffff !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23252820' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 14px center !important;
    background-size: 14px 14px !important;
    border: 1px solid rgba(255, 255, 255, 0.9) !important;
    color: var(--brand-dark, #1a2216) !important;
    border-radius: 10px !important;
    padding: 0 38px 0 14px !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    outline: none !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
    white-space: nowrap !important;
    text-overflow: ellipsis !important;
    overflow: hidden !important;
}

.footer-select-control select:hover {
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18), 0 0 0 2px rgba(255, 255, 255, 0.4) !important;
    transform: translateY(-2px) !important;
}

.footer-select-control select:focus {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.22), 0 0 0 3px rgba(255, 255, 255, 0.6) !important;
    transform: translateY(-2px) !important;
}

.footer-select-control select option {
    background: #ffffff !important;
    color: #1a2216 !important;
    padding: 8px 12px !important;
    font-size: 13px !important;
    font-weight: 500 !important;
}

/* Bottom Row */
.footer-bottom-row {
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    padding-top: 24px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    font-size: 12px;
    color: #ffffff;
    opacity: 0.92;
}

.footer-bottom-left strong {
    color: #ffffff;
    font-weight: 700;
}

.footer-bottom-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.footer-bottom-right a {
    color: #ffffff;
    opacity: 0.95;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    padding: 6px 12px;
    transition: color 0.2s ease;
}

.footer-bottom-right a:hover {
    color: #ffffff;
    text-decoration: underline;
}

.footer-sep {
    opacity: 0.4;
}

/* Responsive */
@media (max-width: 1024px) {
    .footer-pillars-strip {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .footer-main-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 36px !important;
    }
    .footer-brand-col {
        grid-column: 1 / -1;
        max-width: 100%;
    }
}

@media (max-width: 580px) {
    .shop-footer {
        padding: 44px 18px 28px !important;
    }
    .footer-main-grid {
        grid-template-columns: 1fr !important;
        gap: 28px !important;
    }
    .footer-bottom-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>


<!-- Offcanvas Cart -->
<dialog id="offcanvas-cart" class="offcanvas-cart" aria-labelledby="offcanvas-cart-title" aria-describedby="offcanvas-cart-status" onclick="if(event.target===this) closeOffcanvasCart()">
    <div class="offcanvas-cart-content">
        <header>
            <h2 id="offcanvas-cart-title">Your bag</h2>
            <button type="button" aria-label="Close cart" onclick="closeOffcanvasCart()">
                <i data-lucide="x"></i>
            </button>
        </header>
        <p id="offcanvas-cart-status" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></p>
        <div id="offcanvas-cart-body" class="offcanvas-cart-body" aria-live="polite" aria-busy="false">
            <!-- Fetched via AJAX -->
            <div style="text-align: center; padding: 40px; color: #85877d;">Loading your selection...</div>
        </div>
        <footer class="offcanvas-footer">
            <a href="<?= e($publicPath.'/cart') ?>" class="button button-secondary">View full cart</a>
            <a href="<?= e($publicPath.'/checkout') ?>" class="button button-primary">Proceed to checkout</a>
        </footer>
    </div>
</dialog>

<style>
.offcanvas-cart {
    margin: 0;
    margin-left: auto;
    height: 100vh;
    height: 100dvh;
    max-height: none;
    width: 440px;
    max-width: 100vw;
    border: none;
    padding: 0;
    background: rgba(247, 247, 242, 0.85);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    box-shadow: -15px 0 60px rgba(0,0,0,0.08);
    color: var(--brand-dark, #252820);
}
.offcanvas-cart::backdrop {
    background: rgba(20, 22, 18, 0.4);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
.offcanvas-cart-content {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.offcanvas-cart-content header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 35px 40px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
.offcanvas-cart-content h2 {
    font-size: 26px;
    font-weight: 400;
    margin: 0;
    letter-spacing: -0.04em;
}
.offcanvas-cart-content header button {
    background: #e8e9e0;
    border: none;
    cursor: pointer;
    color: #252820;
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    transition: transform 0.25s ease, background 0.25s ease;
}
.offcanvas-cart-content header button:hover {
    background: #d4d6c8;
    transform: scale(1.05);
}
.offcanvas-cart-content header button svg {
    width: 18px;
    height: 18px;
}
.offcanvas-cart-body {
    flex: 1;
    overflow-y: auto;
    padding: 10px 40px;
}
.offcanvas-cart-body::-webkit-scrollbar {
    width: 6px;
}
.offcanvas-cart-body::-webkit-scrollbar-track {
    background: transparent;
}
.offcanvas-cart-body::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 4px;
}
.offcanvas-footer {
    padding: 30px 40px;
    border-top: 1px solid rgba(0,0,0,0.06);
    background: rgba(247, 247, 242, 0.6);
    display: grid;
    gap: 12px;
}
.offcanvas-footer .button {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 18px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.25s ease, filter 0.25s ease;
}
.offcanvas-footer .button:hover {
    transform: translateY(-2px);
    filter: brightness(1.1);
}
.offcanvas-footer .button-secondary {
    background: #e8e9e0;
    color: #252820;
}
.offcanvas-footer .button-primary {
    background: #252820;
    color: #fff;
    box-shadow: 0 8px 20px rgba(37,40,32,0.2);
}
.offcanvas-cart .sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
.offcanvas-cart-body .cart-item {
    display: grid;
    grid-template-columns: 90px 1fr;
    gap: 20px;
    padding: 30px 0;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
.offcanvas-cart-body .cart-item:last-child {
    border-bottom: none;
}
.offcanvas-cart-body .cart-image {
    width: 90px;
    height: 110px;
    border-radius: 10px;
    overflow: hidden;
    background: #eef0e7;
}
.offcanvas-cart-body .cart-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.offcanvas-cart-body .cart-item-copy {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
}
.offcanvas-cart-body .cart-item-copy h2 {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 4px;
    color: #1a1a1a;
}
.offcanvas-cart-body .cart-item-copy p {
    font-size: 13px;
    color: #85877d;
    margin-bottom: 12px;
}
.offcanvas-cart-body .cart-item-copy strong {
    font-size: 15px;
    font-weight: 600;
    color: #252820;
}
.offcanvas-cart-body .cart-item-copy form {
    margin-top: auto;
}
.offcanvas-cart-body .cart-remove {
    background: transparent;
    border: none;
    padding: 0;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
    color: #a75b52;
    cursor: pointer;
    border-bottom: 1px solid transparent;
    transition: color 0.2s, border-color 0.2s;
    margin-top: 10px;
}
.offcanvas-cart-body .cart-remove:hover {
    color: #7a3d35;
    border-color: #7a3d35;
}
.offcanvas-cart-body .cart-summary {
    display: none;
}
</style>

<script nonce="<?= csp_nonce() ?>">
window.storeToast = window.storeToast || function(message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.style.cssText = 'position:fixed;right:24px;bottom:24px;z-index:9999;border-radius:12px;padding:13px 18px;background:#252820;color:#fff;font-size:12px;box-shadow:0 10px 30px rgba(37,40,32,0.3)';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2600);
};

let offcanvasCartFocus = null;

function announceOffcanvasCart(message) {
    const status = document.getElementById('offcanvas-cart-status');
    if (!status) return;
    status.textContent = '';
    setTimeout(() => { status.textContent = message; }, 20);
}

function closeOffcanvasCart() {
    const dialog = document.getElementById('offcanvas-cart');
    if (!dialog || !dialog.open) return;
    dialog.close();
}

function syncOffcanvasCartClosed() {
    document.querySelectorAll('[aria-controls="offcanvas-cart"]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
    announceOffcanvasCart('Cart closed.');
    if (offcanvasCartFocus && document.contains(offcanvasCartFocus)) {
        offcanvasCartFocus.focus();
    }
}

function openOffcanvasCart() {
    if (window.matchMedia('(max-width: 768px)').matches) {
        window.location.href = '<?= e($publicPath.'/cart') ?>';
        return;
    }
    const dialog = document.getElementById('offcanvas-cart');
    const body = document.getElementById('offcanvas-cart-body');
    offcanvasCartFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    body.setAttribute('aria-busy', 'true');
    body.innerHTML = '<div style="text-align: center; padding: 40px; color: #85877d;">Loading your selection...</div>';
    announceOffcanvasCart('Loading your cart.');
    dialog.showModal();
    document.querySelectorAll('[aria-controls="offcanvas-cart"]').forEach((button) => button.setAttribute('aria-expanded', 'true'));
    
    fetch('<?= e($publicPath.'/cart') ?>')
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const cartContent = doc.querySelector('.cart-content');
            if (cartContent) {
                body.innerHTML = cartContent.innerHTML;
                body.setAttribute('aria-busy', 'false');
                announceOffcanvasCart('Cart loaded.');
                if(window.lucide) lucide.createIcons();
            } else {
                body.setAttribute('aria-busy', 'false');
                announceOffcanvasCart('Cart content is unavailable.');
            }
        })
        .catch(err => {
            body.setAttribute('aria-busy', 'false');
            body.innerHTML = '<div role="alert" style="text-align: center; padding: 40px; color: #a75b52;">Failed to load cart.</div>';
            announceOffcanvasCart('Failed to load cart.');
        });
}

document.getElementById('offcanvas-cart')?.addEventListener('close', syncOffcanvasCartClosed);

if(window.lucide && typeof window.lucide.createIcons === 'function') {
    lucide.createIcons();
}
</script>

<?php $klaviyoKey = (string)store_setting('klaviyo_public_key', ''); if (!empty($klaviyoKey) && $klaviyoKey !== 'Vkdz3G'): ?>
<script async type="text/javascript" src="https://static.klaviyo.com/onsite/js/klaviyo.js?company_id=<?= urlencode($klaviyoKey) ?>" crossorigin="anonymous"></script>
<?php endif; ?>

<?php $firebaseKey = store_setting('firebase_api_key', ''); if (!empty($firebaseKey) && $firebaseKey !== 'API_KEY'): ?>
<!-- Firebase JS SDK -->
<script defer src="https://www.gstatic.com/firebasejs/10.13.1/firebase-app-compat.js"></script>
<script defer src="https://www.gstatic.com/firebasejs/10.13.1/firebase-messaging-compat.js"></script>
<script nonce="<?= csp_nonce() ?>">
  if (typeof firebase !== 'undefined' && firebase.messaging && firebase.messaging.isSupported()) {
      firebase.initializeApp({
        apiKey: "<?= e($firebaseKey) ?>",
        authDomain: "<?= e(store_setting('firebase_project_id', '')) ?>.firebaseapp.com",
        projectId: "<?= e(store_setting('firebase_project_id', '')) ?>",
        storageBucket: "<?= e(store_setting('firebase_project_id', '')) ?>.appspot.com",
        messagingSenderId: "<?= e(store_setting('firebase_sender_id', '')) ?>",
        appId: "<?= e(store_setting('firebase_app_id', '')) ?>"
      });
      const messaging = firebase.messaging();
      
      Notification.requestPermission().then((permission) => {
        if (permission === 'granted') {
          messaging.getToken({ vapidKey: "<?= e(store_setting('firebase_vapid_key', '')) ?>" }).then((currentToken) => {
            if (currentToken) {
              fetch('<?= e($publicPath ?? "") ?>/customer/fcm-token', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/x-www-form-urlencoded',
                  },
                  body: '_csrf=<?= e(csrf_token()) ?>&fcm_token=' + encodeURIComponent(currentToken)
              }).catch(console.error);
            }
          }).catch(console.error);
        }
      });
      
      messaging.onMessage((payload) => {
        console.log('Push notification received: ', payload);
      });
  }
</script>
<?php endif; ?>
