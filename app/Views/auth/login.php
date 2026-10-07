<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$error = isset($_GET['error']) ? urldecode((string)$_GET['error']) : '';
$registered = ($_GET['registered'] ?? '') === '1';
$googleEnabled = trim((string)config('google.client_id', '')) !== '';
$redirect = isset($_GET['redirect']) ? urldecode((string)$_GET['redirect']) : '';
$currentUser = \Noir\Core\Container::get('auth')->user();
$isGuest = !empty($currentUser['is_guest']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign in - <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="<?= e(($base ?: '') . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= recaptcha_head() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/password-toggle.min.js') ?>" defer></script><?= store_brand_style() ?>
</head>
<body class="auth-page">
<main class="auth-shell">
    <a class="brand" href="<?= e(($base ?: '') . '/') ?>"><?= e(store_setting('store_name', 'Noir & Co.')) ?></a>
    <section class="auth-card google-auth-card">
        <p class="eyebrow">Welcome back</p>
        <h1>Good to see<br><em>you again.</em></h1>
        <p class="auth-intro">Sign in securely with the Google account connected to your customer profile.</p>

        <?php if ($registered): ?>
            <div class="form-message success"><strong>Your account is ready.</strong> Continue with Google to see your welcome notification.</div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="form-message error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($isGuest): ?>
            <div class="guest-auth-banner"><strong>You are exploring as a guest.</strong><span>Create an account or sign in to keep your progress permanently.</span></div>
        <?php endif; ?>

        <?php if ($googleEnabled): ?>
            <a class="google-auth-button" href="<?= e(($base ?: '') . '/auth/google' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : '')) ?>">
                <svg class="google-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Continue with Google
            </a>
            <p class="google-auth-note">New here? Google will create your customer account automatically.</p>
        <?php else: ?>
            <div class="form-message error">Google sign-in is not configured yet. Please contact the store administrator.</div>
        <?php endif; ?>

        <?php if (!$isGuest): ?>
            <form class="guest-signin-form" method="post" action="<?= e(($base ?: '') . '/guest') ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="redirect" value="<?= e($redirect !== '' ? $redirect : '/store') ?>">
                <button class="guest-auth-button" type="submit">Continue as Guest</button>
            </form>
            <p class="google-auth-note">Guest data stays isolated in this browser session and can be transferred when you register.</p>
        <?php endif; ?>

        <div class="alternate-login">
            <div class="login-divider"><span>or sign in with email</span></div>
            <form id="email-login-form" method="post" action="<?= e(($base ?: '') . '/login') ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <?php if ($redirect !== ''): ?><input type="hidden" name="redirect" value="<?= e($redirect) ?>"><?php endif; ?>
                <div class="input-group-stack">
                    <label class="input-field-label">Email or username
                        <div class="input-field-wrapper">
                            <svg class="input-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <input id="login-email" type="text" name="email" required autocomplete="username" placeholder="you@example.com">
                        </div>
                    </label>
                    <label class="input-field-label" style="margin-top: 14px;">Password
                        <div class="input-field-wrapper">
                            <svg class="input-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <input id="login-password" type="password" name="password" required autocomplete="current-password" placeholder="Your password">
                            <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </label>
                    <label class="remember-row"><input type="checkbox" name="remember" value="1"> Remember me</label>
                    <?= recaptcha_field() ?>
                    <button class="button full" type="submit">Sign in</button>
                </div>
            </form>
            <a class="forgot-link" href="<?= e(($base ?: '') . '/forgot-password') ?>">Forgot your password?</a>
        </div>

        <p class="auth-switch">New customer? <a href="<?= e(($base ?: '') . '/register' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : '')) ?>">Create an account with Google</a></p>
    </section>
</main>
<style>
.auth-intro{max-width:440px;margin:16px 0 28px;color:#77796f;font-size:14px;line-height:1.65}
.google-auth-button{display:flex;align-items:center;justify-content:center;gap:14px;width:100%;border:1px solid #d5d5cc;border-radius:14px;background:#fff;padding:15px 20px;color:#252820;font-size:15px;font-weight:600;text-decoration:none;transition:all .25s cubic-bezier(.4,0,.2,1);box-shadow:0 1px 3px rgba(37,40,32,.06)}
.google-auth-button:hover{border-color:#687158;box-shadow:0 8px 28px rgba(37,40,32,.14);transform:translateY(-2px)}
.google-auth-button:active{transform:translateY(0)}
.google-auth-button:focus-visible{outline:3px solid #68715833;outline-offset:2px}
.google-icon{width:22px;height:22px}
.google-auth-note{margin:16px 0 0;text-align:center;color:#92948a;font-size:13px;line-height:1.55}
.guest-signin-form{margin-top:14px}
.guest-auth-button{display:flex;align-items:center;justify-content:center;width:100%;border:1px solid #cfd4c4;border-radius:14px;background:#eef0e7;padding:15px 20px;color:#252820;font-size:15px;font-weight:700;cursor:pointer;transition:all .2s ease}
.guest-auth-button:hover{border-color:#687158;box-shadow:0 8px 22px rgba(37,40,32,.1);transform:translateY(-1px)}
.guest-auth-banner{display:grid;gap:4px;margin-bottom:20px;border:1px solid #d7ddc9;border-radius:14px;background:#eef0e7;padding:14px 16px;color:#4f5b43;font-size:13px;line-height:1.5}
.guest-auth-banner span{color:#687158}
.alternate-login{margin-top:32px;padding-top:28px;border-top:1px solid #e8e8e0}
.login-divider{display:flex;align-items:center;gap:16px;margin:0 0 22px;color:#9a9c92;font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase}
.login-divider:before,.login-divider:after{content:"";height:1px;flex:1;background:#e3e3da}
.input-field-label{display:block;color:#77796f;font-size:13px;font-weight:500;margin-bottom:8px}
.input-field-wrapper{position:relative;display:flex;align-items:center}
.auth-card .input-field-wrapper input:not([type=checkbox]){width:100%;border:1px solid #d8d8d0;border-radius:12px;background:#fff;padding:13px 52px 13px 48px;color:#252820;font:inherit;font-size:15px;outline:0;transition:border-color .2s ease,box-shadow .2s ease,background .2s ease}
.input-field-wrapper input:focus{border-color:#687158;box-shadow:0 0 0 3px #6871581a}
.input-field-wrapper input:disabled{background:#f8f8f4;color:#9a9c92;cursor:not-allowed}
.input-field-wrapper input:not(:disabled):hover{border-color:#c8c8c0}
.input-field-icon{position:absolute;top:50%;left:14px;width:18px;height:18px;transform:translateY(-50%);color:#9a9c92;pointer-events:none;transition:color .2s ease}
.input-field-wrapper:focus-within .input-field-icon{color:#687158}
.password-heading{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;color:#77796f;font-size:13px;font-weight:500}
.password-heading button{border:0;background:transparent;padding:0;color:#687158;font:inherit;font-size:13px;text-decoration:underline;cursor:pointer;transition:color .2s ease}
.password-heading button:hover{color:#576047}
.password-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:transparent;padding:10px;color:#9a9c92;cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:8px;transition:color .2s ease,background .2s ease}
.password-toggle:hover{color:#687158;background:#f5f4ef}
.password-toggle:focus-visible{outline:2px solid #687158;outline-offset:2px}
.password-toggle svg{display:block}
.remember-row{display:flex!important;align-items:center;gap:10px;color:#77796f!important;font-size:13px!important;font-weight:500;margin:16px 0}
.remember-row input{width:auto!important;accent-color:#687158;cursor:pointer}
.remember-row input:focus-visible{outline:2px solid #687158;outline-offset:2px}
.forgot-link{display:block;margin-top:18px;text-align:center;color:#687158;font-size:13px;text-decoration:underline;font-weight:500;transition:color .2s ease}
.forgot-link:hover{color:#576047}
.form-message{padding:14px 16px;border-radius:12px;margin-bottom:20px;font-size:13px;line-height:1.5}
.form-message.success{background:#e8f0e3;color:#5a7a4f;border:1px solid #c8e0c0}
.form-message.error{background:#f5e8e8;color:#9b5e5e;border:1px solid #e8d0d0}
.form-message.error strong,.form-message.success strong{font-weight:600}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
.input-error{animation:shake .3s ease-in-out;border-color:#e8d0d0!important}
@media(max-width:480px){
    .auth-intro{font-size:13px;line-height:1.6}
    .google-auth-button{padding:14px 18px;font-size:14px}
    .auth-card .input-field-wrapper input:not([type=checkbox]){padding:12px 48px 12px 44px;font-size:14px}
    .alternate-login{margin-top:28px;padding-top:24px}
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Password visibility toggle
    const passwordToggle = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('login-password');
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', () => {
            const eyeOpen = passwordToggle.querySelector('.eye-open');
            const eyeClosed = passwordToggle.querySelector('.eye-closed');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                if (eyeOpen) eyeOpen.style.display = 'none';
                if (eyeClosed) eyeClosed.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                if (eyeOpen) eyeOpen.style.display = 'block';
                if (eyeClosed) eyeClosed.style.display = 'none';
            }
        });
    }
});
</script>
</body>
</html>
