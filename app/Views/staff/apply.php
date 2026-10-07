<?php declare(strict_types=1); $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Join <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="<?= e(($base ?: '') . '/assets/js/password-toggle.min.js') ?>" defer></script><?= store_brand_style() ?>
    <script src="<?= e(($base ?: '') . '/assets/js/password-strength.min.js') ?>" defer></script>
</head>
<body class="auth-page">
<main class="auth-shell">
    <a class="brand" href="<?= e(($base ?: '') . '/') ?>"><?= e(store_setting('store_name', 'Noir & Co.')) ?></a>
    <section class="auth-card">
        <p class="eyebrow">Careers at <?= e(store_setting('store_name', 'Noir & Co.')) ?></p>
        <h1>Join the<br><em>team.</em></h1>
        <p class="hero-text">Submit your application. Your account will only be created after an administrator reviews and approves it.</p>
        <?php if (isset($_GET['submitted'])): ?>
            <div class="form-message success">Application successful. Our team will review your application and get back to you.</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="form-message error"><?php if ($_GET['error'] === 'database'): ?>Your application could not be saved yet. Please ask the administrator to complete the database setup and try again.<?php elseif ($_GET['error'] === 'resume' && ($_GET['reason'] ?? '') === 'large'): ?>Your CV is too large. Please upload a file under 5 MB.<?php elseif ($_GET['error'] === 'resume' && ($_GET['reason'] ?? '') === 'missing'): ?>Please select a CV or resume before submitting.<?php elseif ($_GET['error'] === 'resume'): ?>Please upload your CV as a PDF, DOC, or DOCX file.<?php else: ?><?php $fieldNames = ['name' => 'full name', 'email' => 'email address', 'username' => 'username (start with a letter; use lowercase letters, numbers, dots, dashes, or underscores)', 'password' => 'password (at least 8 characters)', 'position' => 'position']; $fields = array_filter(array_map('trim', explode(',', (string)($_GET['fields'] ?? '')))); ?>Please correct: <?= e(implode('; ', array_map(static fn(string $field): string => $fieldNames[$field] ?? $field, $fields)) ?: 'your application details') ?>.<?php endif; ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" action="<?= e(($base ?: '') . '/careers/apply') ?>" class="compact-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label>Full name<input name="name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Phone<input name="phone"></label>
            <label>Username<input name="username" pattern="[a-z][a-z0-9._-]{2,79}" required><small>Start with a letter; use lowercase letters, numbers, dots, dashes, or underscores.</small></label>
            <label>Password<input type="password" name="password" data-password-strength minlength="8" required><small>Must contain at least 8 characters.</small></label>
            <label>Position applied for
                <select name="position" required>
                    <option value="">Select a position</option>
                    <option>Administrator</option><option>Store Manager</option><option>Inventory Manager</option>
                    <option>Warehouse Officer</option><option>Finance Manager</option><option>Customer Support</option>
                    <option>Marketing Manager</option><option>Delivery Manager</option><option>Content Manager</option>
                </select>
            </label>
            <label>CV / Resume
                <input type="file" name="resume" accept=".pdf,.doc,.docx" required>
                <small>PDF, DOC, or DOCX · Maximum 5 MB</small>
            </label>
            <label>Message<textarea name="message" rows="4" placeholder="Tell us briefly about your experience"></textarea></label>
            <button class="button full" type="submit">Submit application <span>↗</span></button>
        </form>
    </section>
</main>
</body>
</html>
