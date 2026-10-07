<?php 
declare(strict_types=1); 
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); 
$ref = trim((string)($_GET['ref'] ?? ''));
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Application Submitted — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?= store_brand_style() ?>
    <style>
        .status-box {
            display: none;
            margin-top: 20px;
            padding: 16px;
            border-radius: 12px;
            text-align: left;
            font-size: 13px;
            line-height: 1.5;
        }
        .status-box.pending {
            background: #fffbf0;
            border: 1px solid #f6dfa9;
            color: #7d5912;
        }
        .status-box.approved {
            background: #f2f9f1;
            border: 1px solid #c9e6c5;
            color: #2b5724;
        }
        .status-box.rejected {
            background: #fdf5f4;
            border: 1px solid #f2cfcb;
            color: #8c362d;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 11px;
            margin-bottom: 6px;
        }
    </style>
</head>
<body class="auth-page">
<main class="auth-shell">
    <a class="brand" href="<?= e(($base ?: '') . '/') ?>"><?= e($storeName) ?></a>
    <section class="auth-card" style="max-width: 480px;">
        <p class="eyebrow">Application received</p>
        <h1>Your application<br><em>was submitted.</em></h1>
        
        <div class="form-message success" style="margin-bottom: 16px;">
            Thank you for applying to <?= e($storeName) ?>. Our hiring committee reviews every submission carefully.
        </div>

        <?php if ($ref !== ''): ?>
            <div style="background: #f7f7f2; border: 1px solid #e8e9e0; border-radius: 10px; padding: 14px; margin-bottom: 20px; text-align: left;">
                <span style="display: block; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.05em;">Your Application Reference</span>
                <strong id="ref-display" style="display: block; font-size: 16px; font-family: monospace; color: #252820; margin-top: 2px;"><?= e($ref) ?></strong>
                <small style="display: block; color: #777; font-size: 11px; margin-top: 4px;">Please keep this reference safe to check your application status.</small>
            </div>
        <?php endif; ?>

        <p class="hero-text" style="font-size: 13px; color: #666; margin-bottom: 20px;">
            Whenever a decision is reached, a notification message is automatically delivered to your email. You can also check your live status below.
        </p>

        <!-- Live Status Checker Section -->
        <div style="border-top: 1px solid #e8e9e0; padding-top: 20px; text-align: left;">
            <label style="display: block; font-size: 12px; font-weight: 700; color: #252820; margin-bottom: 6px;">Track Application Status</label>
            <div style="display: flex; gap: 8px;">
                <input 
                    type="text" 
                    id="status-ref-input" 
                    placeholder="Enter reference (e.g. STAFF-...) or email" 
                    value="<?= e($ref) ?>" 
                    style="flex: 1; padding: 10px 14px; border: 1px solid #deded6; border-radius: 8px; font-size: 13px; outline: none;"
                >
                <button 
                    type="button" 
                    id="check-status-btn" 
                    onclick="checkStatus()" 
                    class="button" 
                    style="padding: 10px 18px; font-size: 13px; white-space: nowrap;"
                >
                    Check
                </button>
            </div>

            <!-- Dynamic Status Results Box -->
            <div id="status-result" class="status-box">
                <div id="status-badge" class="status-badge"></div>
                <div id="status-title" style="font-weight: 700; margin-bottom: 4px;"></div>
                <div id="status-desc" style="font-size: 12px;"></div>
                <div id="status-action" style="margin-top: 12px;"></div>
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px;">
            <a class="button full" href="<?= e(($base ?: '') . '/') ?>" style="flex: 1; text-align: center;">Return to home</a>
            <a class="button outline" href="<?= e(($base ?: '') . '/login') ?>" style="flex: 1; text-align: center;">Sign In</a>
        </div>
    </section>
</main>

<script>
async function checkStatus() {
    const input = document.getElementById('status-ref-input');
    const btn = document.getElementById('check-status-btn');
    const resultBox = document.getElementById('status-result');
    const badge = document.getElementById('status-badge');
    const title = document.getElementById('status-title');
    const desc = document.getElementById('status-desc');
    const action = document.getElementById('status-action');

    const query = (input.value || '').trim();
    if (!query) {
        input.focus();
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Checking…';

    try {
        const res = await fetch('<?= e(($base ?: "") . "/careers/status") ?>?ref=' + encodeURIComponent(query), {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        resultBox.style.display = 'block';
        resultBox.className = 'status-box';
        action.innerHTML = '';

        if (!data.success || !data.application) {
            resultBox.classList.add('rejected');
            badge.innerHTML = '<i data-lucide="help-circle" style="width:14px;height:14px;"></i> Not Found';
            title.textContent = 'No record found';
            desc.textContent = data.message || 'We could not find an application matching that reference or email.';
        } else {
            const app = data.application;
            const st = (app.status || 'pending').toLowerCase();

            if (st === 'approved') {
                resultBox.classList.add('approved');
                badge.innerHTML = '<i data-lucide="check-circle" style="width:14px;height:14px;"></i> Approved';
                title.textContent = `Application Approved — Role: ${app.role || app.position || 'Staff'}`;
                desc.textContent = `Congratulations ${app.name}! Your staff account is active. You can now sign in using the password you set during registration.`;
                action.innerHTML = '<a href="<?= e(($base ?: "") . "/login") ?>" class="button" style="display:inline-block;padding:8px 18px;font-size:12px;">Sign In to Staff Portal &rarr;</a>';
            } else if (st === 'rejected') {
                resultBox.classList.add('rejected');
                badge.innerHTML = '<i data-lucide="x-circle" style="width:14px;height:14px;"></i> Not Selected';
                title.textContent = 'Application Update';
                desc.textContent = app.reason ? `Feedback from hiring team: "${app.reason}"` : 'Thank you for your application. We regret that we are unable to offer you a position at this time.';
            } else {
                resultBox.classList.add('pending');
                badge.innerHTML = '<i data-lucide="clock" style="width:14px;height:14px;"></i> Under Review';
                title.textContent = 'Application Pending Evaluation';
                desc.textContent = `Candidate: ${app.name} (${app.position}). Your profile and submitted credentials are currently queued for administrator evaluation. You will receive an email once a decision is made.`;
            }
        }
        lucide.createIcons();
    } catch (err) {
        resultBox.style.display = 'block';
        resultBox.className = 'status-box rejected';
        badge.innerHTML = '<i data-lucide="alert-circle" style="width:14px;height:14px;"></i> Error';
        title.textContent = 'Network or system error';
        desc.textContent = 'Could not retrieve application status at this time. Please try again in a few moments.';
        lucide.createIcons();
    } finally {
        btn.disabled = false;
        btn.textContent = 'Check';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    const query = (document.getElementById('status-ref-input').value || '').trim();
    if (query) {
        checkStatus();
    }
});
</script>
</body>
</html>
