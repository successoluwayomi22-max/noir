<?php
/** @var array $cards */
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital VIP Gift Cards & Vouchers - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0d0f0c; color: #f7f7f2; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; }
        .gc-hero { padding: 60px 20px 40px; text-align: center; max-width: 900px; margin: 0 auto; }
        .gc-hero h1 { font-size: 36px; font-weight: 800; letter-spacing: 2px; color: #d4af37; margin-bottom: 12px; }
        .gc-hero p { font-size: 16px; color: #a1a19a; line-height: 1.6; }
        
        .gc-container { max-width: 1200px; margin: 0 auto; padding: 20px 20px 80px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; }
        @media (max-width: 900px) { .gc-container { grid-template-columns: 1fr; } }

        /* Card Preview */
        .card-preview-wrapper { perspective: 1000px; }
        .luxury-card { width: 100%; aspect-ratio: 1.586; border-radius: 20px; padding: 28px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.8), inset 0 0 0 1px rgba(212,175,55,0.3); transition: transform 0.4s ease, box-shadow 0.4s ease; }
        .luxury-card:hover { transform: translateY(-6px) rotateX(4deg); box-shadow: 0 28px 50px rgba(212,175,55,0.25); }
        
        .theme-gold_foil { background: linear-gradient(135deg, #1c1c1a 0%, #2b281b 40%, #161814 100%); }
        .theme-velvet_obsidian { background: linear-gradient(135deg, #09090b 0%, #18181b 50%, #050505 100%); }
        .theme-emerald_silk { background: linear-gradient(135deg, #061a14 0%, #0f382a 50%, #04100c 100%); }
        .theme-rose_gold { background: linear-gradient(135deg, #2b1d1f 0%, #4a282c 50%, #1a1012 100%); }

        .card-chip { width: 44px; height: 32px; background: linear-gradient(135deg, #d4af37, #aa820a); border-radius: 6px; box-shadow: inset 0 0 4px rgba(0,0,0,0.4); }
        .card-brand { font-size: 16px; font-weight: 900; letter-spacing: 3px; color: #d4af37; text-transform: uppercase; }
        .card-amount { font-size: 32px; font-weight: 800; color: #fff; letter-spacing: 1px; }
        .card-code { font-family: monospace; font-size: 15px; letter-spacing: 2px; color: #d4af37; opacity: 0.9; }

        .gc-form-box { background: #161814; border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 20px; padding: 32px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #8c9082; margin-bottom: 8px; }
        .form-input { width: 100%; box-sizing: border-box; background: #0e100d; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 12px 14px; color: #fff; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-input:focus { border-color: #d4af37; }

        .tier-btn-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .tier-btn { background: #0e100d; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 10px 4px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 13px; text-align: center; transition: all 0.2s; }
        .tier-btn.active, .tier-btn:hover { background: rgba(212, 175, 55, 0.15); border-color: #d4af37; color: #d4af37; }

        .btn-purchase-gc { width: 100%; background: #d4af37; color: #111; font-weight: 800; font-size: 15px; padding: 16px; border-radius: 12px; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 1.5px; transition: background 0.2s; }
        .btn-purchase-gc:hover { background: #e5c158; }

        .balance-checker-box { background: #11130f; border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 24px; margin-top: 30px; }
    </style>
</head>
<body>

<header style="padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05);">
    <a href="<?= $base ?>/" style="color: #d4af37; font-weight: 900; letter-spacing: 2px; text-decoration: none; font-size: 18px;">
        <?= htmlspecialchars($storeName) ?>
    </a>
    <a href="<?= $base ?>/store" style="color: #a1a19a; text-decoration: none; font-size: 14px; font-weight: 600;">
        <i class="fa-solid fa-arrow-left"></i> Return to Boutique
    </a>
</header>

<div class="gc-hero">
    <h1><i class="fa-solid fa-gift" style="margin-right: 12px;"></i> DIGITAL LUXURY GIFT CARDS</h1>
    <p>Present the gift of refined bespoke choice. Instantly delivered with gold-embossed digital vouchers, personalized styling messages, and seamless 1-tap checkout redemption.</p>
</div>

<div class="gc-container">
    <!-- Visual Preview -->
    <div class="card-preview-wrapper">
        <div id="cardPreview" class="luxury-card theme-gold_foil">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="card-brand"><?= htmlspecialchars($storeName) ?></div>
                <div class="card-chip"></div>
            </div>

            <div>
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #a1a19a;">VIP Digital Voucher</div>
                <div id="previewAmount" class="card-amount"><?= format_currency(50000) ?></div>
                <div id="previewMessage" style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 6px; max-height: 40px; overflow: hidden;">
                    "A gesture of timeless distinction."
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <div style="font-size: 10px; color: #8c9082; text-transform: uppercase;">Recipient</div>
                    <div id="previewRecipient" style="font-size: 13px; font-weight: 700; color: #fff;">Distinguished Guest</div>
                </div>
                <div class="card-code">NOIR-VIP-XXXX-XXXX</div>
            </div>
        </div>

        <!-- Balance Checker -->
        <div class="balance-checker-box">
            <h4 style="margin: 0 0 12px; color: #d4af37; font-size: 15px;"><i class="fa-solid fa-credit-card"></i> Check Gift Card Balance</h4>
            <div style="display: flex; gap: 8px;">
                <input type="text" id="checkCodeInput" class="form-input" placeholder="e.g. NOIR-VIP-8800" style="text-transform: uppercase;">
                <button type="button" onclick="checkBalance()" style="background: #252820; color: #d4af37; border: 1px solid #d4af37; padding: 0 18px; border-radius: 10px; font-weight: 700; cursor: pointer;">
                    Check
                </button>
            </div>
            <div id="balanceResult" style="margin-top: 12px; font-size: 13px; display: none;"></div>
        </div>
    </div>

    <!-- Purchase Form -->
    <div class="gc-form-box">
        <h2 style="margin: 0 0 20px; font-size: 20px; color: #fff;">Customize Your Luxury Gift</h2>

        <div class="form-group">
            <label>Select Denomination</label>
            <div class="tier-btn-grid">
                <button type="button" class="tier-btn" onclick="setAmount(25000, this)"><?= format_currency(25000) ?></button>
                <button type="button" class="tier-btn active" onclick="setAmount(50000, this)"><?= format_currency(50000) ?></button>
                <button type="button" class="tier-btn" onclick="setAmount(100000, this)"><?= format_currency(100000) ?></button>
                <button type="button" class="tier-btn" onclick="setAmount(250000, this)"><?= format_currency(250000) ?></button>
            </div>
            <input type="number" id="customAmount" class="form-input" placeholder="Or enter custom amount in NGN" value="50000" oninput="updateCustomAmount(this.value)">
        </div>

        <div class="form-group">
            <label>Voucher Aesthetic Theme</label>
            <select id="cardTheme" class="form-input" onchange="setTheme(this.value)">
                <option value="theme-gold_foil">Gold Foil Luxe</option>
                <option value="theme-velvet_obsidian">Velvet Obsidian</option>
                <option value="theme-emerald_silk">Emerald Silk</option>
                <option value="theme-rose_gold">Rose Gold Velvet</option>
            </select>
        </div>

        <div class="form-group">
            <label>Recipient Name</label>
            <input type="text" id="recipientName" class="form-input" placeholder="e.g. Lady Vivienne" oninput="document.getElementById('previewRecipient').innerText = this.value || 'Distinguished Guest'">
        </div>

        <div class="form-group">
            <label>Recipient Email Address</label>
            <input type="email" id="recipientEmail" class="form-input" placeholder="recipient@domain.com" required>
        </div>

        <div class="form-group">
            <label>Personal Bespoke Note</label>
            <textarea id="personalMessage" class="form-input" rows="3" placeholder="Add a personal greeting..." oninput="document.getElementById('previewMessage').innerText = '\"' + (this.value || 'A gesture of timeless distinction.') + '\"'"></textarea>
        </div>

        <button type="button" class="btn-purchase-gc" onclick="purchaseGiftCard()">
            <i class="fa-solid fa-shield-halved"></i> Purchase & Dispatch Gift Card
        </button>
        <div id="purchaseStatus" style="margin-top: 14px; font-size: 13px; text-align: center; display: none;"></div>
    </div>
</div>

<script>
const activeCurrencySymbol = '<?= e(currency_symbol()) ?>';
const activeCurrencyRate = <?= (float)get_exchange_rate() ?>;
const activeCurrencyCode = '<?= e(active_currency()) ?>';
let currentAmount = 50000;

function formatGiftCardPrice(ngnAmount) {
    const converted = ngnAmount * activeCurrencyRate;
    if (activeCurrencyCode === 'NGN') {
        return activeCurrencySymbol + Math.round(converted).toLocaleString();
    }
    return activeCurrencySymbol + converted.toFixed(2);
}

function setAmount(val, btn) {
    document.querySelectorAll('.tier-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    currentAmount = val;
    document.getElementById('customAmount').value = val;
    document.getElementById('previewAmount').innerText = formatGiftCardPrice(val);
}

function updateCustomAmount(val) {
    document.querySelectorAll('.tier-btn').forEach(b => b.classList.remove('active'));
    currentAmount = Number(val) || 0;
    document.getElementById('previewAmount').innerText = formatGiftCardPrice(currentAmount);
}

function setTheme(themeClass) {
    const card = document.getElementById('cardPreview');
    card.className = 'luxury-card ' + themeClass;
}

async function checkBalance() {
    const code = document.getElementById('checkCodeInput').value.trim();
    const resultBox = document.getElementById('balanceResult');
    if (!code) return;

    resultBox.style.display = 'block';
    resultBox.innerHTML = '<span style="color: #8c9082;">Verifying voucher...</span>';

    try {
        const res = await fetch('<?= $base ?>/api/v1/gift-cards/balance?code=' + encodeURIComponent(code));
        const data = await res.json();
        if (data.success && data.gift_card) {
            const gc = data.gift_card;
            resultBox.innerHTML = `<div style="background: rgba(46, 213, 115, 0.1); border: 1px solid #2ed573; border-radius: 8px; padding: 10px; color: #2ed573;">
                <strong>Valid Gift Card (${gc.code})</strong><br>
                Available Balance: <strong>₦${Number(gc.balance).toLocaleString()}</strong><br>
                <small style="color: #a1a19a;">Status: ${gc.status.toUpperCase()}</small>
            </div>`;
        } else {
            resultBox.innerHTML = `<div style="background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; border-radius: 8px; padding: 10px; color: #ff6b6b;">
                ${data.message || 'Invalid gift card code.'}
            </div>`;
        }
    } catch (e) {
        resultBox.innerHTML = '<span style="color: #ff6b6b;">Error connecting to verification server.</span>';
    }
}

async function purchaseGiftCard() {
    const recipientEmail = document.getElementById('recipientEmail').value.trim();
    const recipientName = document.getElementById('recipientName').value.trim();
    const personalMessage = document.getElementById('personalMessage').value.trim();
    const statusBox = document.getElementById('purchaseStatus');

    if (!recipientEmail) {
        alert('Please enter recipient email address.');
        return;
    }
    if (currentAmount < 5000) {
        alert('Minimum voucher amount is ₦5,000.');
        return;
    }

    statusBox.style.display = 'block';
    statusBox.innerHTML = '<span style="color: #d4af37;"><i class="fa-solid fa-spinner fa-spin"></i> Generating luxury voucher...</span>';

    try {
        const res = await fetch('<?= $base ?>/api/v1/gift-cards/purchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                amount: currentAmount,
                recipient_name: recipientName,
                recipient_email: recipientEmail,
                message: personalMessage,
                theme: document.getElementById('cardTheme').value.replace('theme-', '')
            })
        });
        const data = await res.json();
        if (data.success && data.gift_card) {
            statusBox.innerHTML = `<div style="background: rgba(46, 213, 115, 0.15); border: 1px solid #2ed573; border-radius: 8px; padding: 14px; color: #2ed573;">
                <strong>Voucher Created Successfully</strong><br>
                Code: <strong style="font-family: monospace; font-size: 15px; color: #fff;">${data.gift_card.code}</strong><br>
                Balance: ₦${Number(data.gift_card.balance).toLocaleString()} Dispatched to ${recipientEmail}.
            </div>`;
        } else {
            statusBox.innerHTML = `<span style="color: #ff6b6b;">${data.message || 'Failed to create voucher.'}</span>`;
        }
    } catch (e) {
        statusBox.innerHTML = '<span style="color: #ff6b6b;">Network error while purchasing voucher.</span>';
    }
}
</script>

</body>
</html>
