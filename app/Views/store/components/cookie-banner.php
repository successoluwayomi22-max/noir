<!-- Noir & Co. Cookie Consent Banner (GDPR / ePrivacy Compliant) -->
<div id="noir-cookie-consent" class="noir-cookie-consent" style="display: none;" role="dialog" aria-live="polite" aria-label="Cookie consent" aria-labelledby="noir-cookie-title">
    <div class="noir-cookie-content">
        <div class="noir-cookie-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                <path d="M8.5 8.5v.01"></path>
                <path d="M16 15.5v.01"></path>
                <path d="M12 12v.01"></path>
                <path d="M11 17v.01"></path>
                <path d="M7 13v.01"></path>
            </svg>
        </div>
        <div class="noir-cookie-text">
            <h3 id="noir-cookie-title"><?= __('Cookie Preferences') ?></h3>
            <p>
                <?= __('We use essential cookies to ensure our store operates securely and reliably, and optional analytics cookies to enhance your experience.') ?>
                <a href="<?= e(($publicPath ?? '') . '/privacy-policy') ?>"><?= __('Learn more in our Privacy Policy.') ?></a>
            </p>
        </div>
        <div class="noir-cookie-actions">
            <button type="button" id="noir-cookie-reject" class="noir-cookie-btn noir-cookie-btn-secondary"><?= __('Essential Only') ?></button>
            <button type="button" id="noir-cookie-accept" class="noir-cookie-btn noir-cookie-btn-primary"><?= __('Accept All') ?></button>
        </div>
    </div>
</div>

<style>
.noir-cookie-consent {
    position: fixed;
    bottom: 24px;
    left: 24px;
    right: 24px;
    max-width: 820px;
    margin: 0 auto;
    background: rgba(22, 24, 20, 0.96);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 20px 48px rgba(0, 0, 0, 0.35);
    z-index: 99999;
    font-family: inherit;
    animation: noirCookieSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes noirCookieSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.noir-cookie-content {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.noir-cookie-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.08);
    color: #e4e7db;
    flex-shrink: 0;
}

.noir-cookie-text {
    flex: 1;
    min-width: 260px;
}

.noir-cookie-text h3 {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: -0.01em;
}

.noir-cookie-text p {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.72);
}

.noir-cookie-text a {
    color: #c9d2b5;
    text-decoration: underline;
    text-underline-offset: 3px;
    transition: color 0.2s ease;
}

.noir-cookie-text a:hover {
    color: #ffffff;
}

.noir-cookie-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.noir-cookie-btn {
    padding: 10px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    outline: none;
    white-space: nowrap;
}

.noir-cookie-btn-primary {
    background: #e4e7db;
    color: #171a14;
}

.noir-cookie-btn-primary:hover {
    background: #ffffff;
    transform: translateY(-1px);
}

.noir-cookie-btn-secondary {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.16);
}

.noir-cookie-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.14);
}

@media (max-width: 640px) {
    .noir-cookie-consent {
        bottom: 12px;
        left: 12px;
        right: 12px;
        padding: 16px;
    }
    .noir-cookie-actions {
        width: 100%;
        justify-content: flex-end;
    }
    .noir-cookie-btn {
        flex: 1;
        text-align: center;
    }
}
</style>

<script nonce="<?= csp_nonce() ?>">
(function() {
    var consentKey = 'noir_cookie_consent';
    var banner = document.getElementById('noir-cookie-consent');
    if (!banner) return;

    var existingConsent = localStorage.getItem(consentKey);
    if (!existingConsent) {
        banner.style.display = 'block';
    }

    var acceptBtn = document.getElementById('noir-cookie-accept');
    var rejectBtn = document.getElementById('noir-cookie-reject');

    function saveConsent(value) {
        localStorage.setItem(consentKey, JSON.stringify({
            consent: value,
            timestamp: new Date().toISOString()
        }));
        banner.style.display = 'none';
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', function() {
            saveConsent('all');
        });
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', function() {
            saveConsent('essential');
        });
    }
})();
</script>
