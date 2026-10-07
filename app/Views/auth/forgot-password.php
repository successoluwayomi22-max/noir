<?php declare(strict_types=1); $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); $sent = isset($_GET['sent']); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot password — <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/password-toggle.min.js') ?>" defer></script><?= store_brand_style() ?>
</head>
<body class="auth-page">
<main class="auth-shell">
    <a class="brand" href="<?= e(($base ?: '') . '/') ?>"><?= e(store_setting('store_name', 'Noir & Co.')) ?></a>
    <section class="auth-card">
        <p class="eyebrow">Account recovery</p>
        <h1>Find your way<br><em>back in.</em></h1>
        <?php if ($sent): ?>
            <p class="form-message success">If an account exists for that email, we sent a six-digit verification code. Enter it below to choose a new password.</p>
            <form method="post" action="reset-password">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label>Verification code<input type="text" name="token" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456"></label>
                <label>New password<input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters"></label>
                <label>Confirm new password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="Repeat your password"></label>
                <button class="button full" type="submit">Update password <span>↗</span></button>
            </form>
        <?php else: ?>
            <p>Enter your account email and we’ll send a secure verification code.</p>
            <form method="post" action="forgot-password">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label>Email address<input type="email" name="email" required autocomplete="email" placeholder="you@example.com"></label>
                <button class="button full" type="submit">Send verification code <span>↗</span></button>
            </form>
        <?php endif; ?>
        <p class="auth-switch"><a href="login">← Back to sign in</a></p>
    </section>
</main>
</body>
</html>
