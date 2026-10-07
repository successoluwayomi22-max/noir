<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$error = (string)($_GET['error'] ?? '');
$googleEnabled = trim((string)config('google.client_id', '')) !== '';
$redirect = isset($_GET['redirect']) ? urldecode((string)$_GET['redirect']) : '';
$currentUser = \Noir\Core\Container::get('auth')->user();
$isGuest = !empty($currentUser['is_guest']);
$errorMessages = [
    'name' => 'Please enter your full name.',
    'email' => 'Please enter a valid email address.',
    'password' => 'Your password must be 8-20 characters long and include an uppercase letter, lowercase letter, number, and special character.',
    'confirmation' => 'Your passwords do not match.',
    'exists' => 'An account with that email or username already exists.',
    'username' => 'We could not create a username for this account. Please try again.',
    'recaptcha' => 'Security check failed. Please complete the reCAPTCHA verification and try again.',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create your account — <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= recaptcha_head() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/password-toggle.min.js') ?>" defer></script><?= store_brand_style() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/password-strength.min.js') ?>" defer></script>
</head>
<body class="auth-page">
<main class="auth-shell">
    <a class="brand" href="<?= e(($base ?: '') . '/') ?>"><?= e(store_setting('store_name', 'Noir & Co.')) ?></a>
    <section class="auth-card google-auth-card">
        <p class="eyebrow">Join the collection</p>
        <h1>Make room for<br><em>something good.</em></h1>
        <p class="auth-intro">Create your account with Google or use your email and a password. Your details are kept securely in your customer account.</p>
        <?php if ($error !== ''): ?>
            <p class="form-message error"><?= e($errorMessages[$error] ?? urldecode($error)) ?></p>
        <?php endif; ?>
        <?php if ($isGuest): ?>
            <div class="guest-auth-banner"><strong>You are exploring as a guest.</strong><span>Register now to transfer your saved items, addresses, orders, reviews, tickets, and preferences.</span></div>
        <?php else: ?>
            <form class="guest-signin-form" method="post" action="<?= e(($base ?: '') . '/guest') ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="redirect" value="<?= e($redirect !== '' ? $redirect : '/store') ?>">
                <button class="guest-auth-button" type="submit">Continue as Guest</button>
            </form>
        <?php endif; ?>
        <?php if ($googleEnabled): ?>
            <a class="google-auth-button" href="<?= e(($base ?: '') . '/auth/google' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : '')) ?>">
                <svg class="google-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Continue with Google
            </a>
            <p class="google-auth-note">No password or manual signup form is needed.</p>
        <?php else: ?>
            <p class="form-message error">Google sign-in is not configured yet. Please contact the store administrator.</p>
        <?php endif; ?>
        <div class="signup-divider"><span>or sign up with email</span></div>
        <form class="signup-form" method="post" action="<?= e(($base ?: '') . '/register') ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <?php if ($redirect !== ''): ?><input type="hidden" name="redirect" value="<?= e($redirect) ?>"><?php endif; ?>
            <label>Full name<input type="text" name="name" required autocomplete="name" placeholder="Your full name"></label>
            <label>Email address<input type="email" name="email" required autocomplete="email" placeholder="you@example.com"></label>
            <label>Password<input type="password" name="password" data-password-strength required minlength="8" maxlength="20" autocomplete="new-password" placeholder="8-20 characters"></label>
            <label>Confirm password<input type="password" name="password_confirmation" required minlength="8" maxlength="20" autocomplete="new-password" placeholder="Repeat your password"></label>
            <?= recaptcha_field() ?>
            <button class="button full signup-submit" type="submit">Create account <span aria-hidden="true">↗</span></button>
        </form>
        <p class="auth-switch">Already have an account? <a href="<?= e(($base ?: '') . '/login' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : '')) ?>">Sign in</a></p>
    </section>
</main>
<style>
.auth-intro{max-width:430px;margin:14px 0 24px;color:#77796f;font-size:13px;line-height:1.7}
.google-auth-button{display:flex;align-items:center;justify-content:center;gap:12px;width:100%;border:1px solid #dcdcd3;border-radius:12px;background:#fff;padding:14px 18px;color:#252820;font-size:14px;font-weight:600;text-decoration:none;transition:box-shadow .2s ease,border-color .2s ease,transform .2s ease}
.google-auth-button:hover{border-color:#687158;box-shadow:0 8px 22px rgba(37,40,32,.08);transform:translateY(-1px)}
.google-auth-button:active{transform:translateY(0)}
.google-auth-button:focus-visible{outline:3px solid #68715833;outline-offset:2px}
.google-icon{width:20px;height:20px;flex:0 0 auto}
.google-auth-note{margin:14px 0 0;text-align:center;color:#92948a;font-size:12px;line-height:1.6}
.guest-signin-form{margin:0 0 16px}
.guest-auth-button{display:flex;align-items:center;justify-content:center;width:100%;border:1px solid #cfd4c4;border-radius:12px;background:#eef0e7;padding:14px 18px;color:#252820;font-size:14px;font-weight:700;cursor:pointer;transition:box-shadow .2s ease,border-color .2s ease,transform .2s ease}
.guest-auth-button:hover{border-color:#687158;box-shadow:0 8px 22px rgba(37,40,32,.08);transform:translateY(-1px)}
.guest-auth-banner{display:grid;gap:4px;margin-bottom:18px;border:1px solid #d7ddc9;border-radius:12px;background:#eef0e7;padding:13px 15px;color:#4f5b43;font-size:12px;line-height:1.55}
.guest-auth-banner span{color:#687158}
.signup-divider{display:flex;align-items:center;gap:14px;margin:30px 0 22px;color:#92948a;font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
.signup-divider:before,.signup-divider:after{content:"";height:1px;flex:1;background:#e3e3da}
.signup-form{display:grid;gap:16px}
.signup-form label{display:grid;gap:7px;color:#77796f;font-size:12px}
.auth-card .signup-form input:not([type=checkbox]){width:100%;border:1px solid #d8d8d0;border-radius:14px;background:#fff;padding:14px 16px;color:#252820;font:inherit;font-size:14px;outline:0;transition:border-color .2s ease,box-shadow .2s ease}
.auth-card .signup-form input:not([type=checkbox]):focus{border-color:#687158;box-shadow:0 0 0 3px #6871581a}
.auth-card .signup-form input:not([type=checkbox])::placeholder{color:#aaaCA3}
.auth-card .signup-form .signup-submit{margin-top:20px;justify-content:space-between;border:0!important;border-radius:10px;background:#687158!important;color:#fff!important;padding:14px 18px!important;cursor:pointer;transition:background .2s ease,transform .2s ease}
.signup-submit:hover{background:#687158!important;transform:translateY(-1px)}
.signup-submit:active{transform:translateY(0)}
.signup-submit span{margin-left:auto}
.signup-form + .auth-switch{margin-top:24px}
</style>
</body>
</html>
