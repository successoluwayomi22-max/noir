<?php
declare(strict_types=1);

$rate = match (strtoupper($currency)) {
    'USD' => 0.00065,
    'EUR' => 0.00060,
    'GBP' => 0.00052,
    'CAD' => 0.00088,
    'AUD' => 0.00098,
    'GHS' => 0.0098,
    'KES' => 0.084,
    'ZAR' => 0.012,
    default => 1.0,
};
$total = 0.0;
foreach ($cart as $item) {
    $total += ((float)$item['price'] * $rate) * (int)$item['quantity'];
}
$symbols = ['NGN' => '&#8358;', 'USD' => '$', 'GBP' => '&pound;', 'EUR' => '&euro;'];
$symbol = $symbols[$currency] ?? e($currency) . ' ';
$retryCurrency = !empty($retryPayment['currency']) ? (string)$retryPayment['currency'] : $currency;
$retrySymbol = $symbols[$retryCurrency] ?? e($retryCurrency) . ' ';
$shippingAmount = 0.0;
$shippingLabel = 'Free launch delivery';
$deliveryEstimate = 'Estimated delivery: 2-5 business days after payment confirmation.';
$errors = [
    'details' => __('Please complete your contact and delivery details.'),
    'invalid_address' => __('Please provide a genuine, deliverable street address with building or plot details.'),
    'currency' => __('That currency is not available for checkout yet.'),
    'payment_unavailable' => __('Online payment is not configured yet. Add your Paystack test secret key first.'),
    'payment_failed' => __('We could not confirm that payment. You can retry the saved payment below or start checkout again.'),
    'rate_limit' => __('Too many payment attempts. For your security, please wait a minute before retrying.'),
    'rejected' => __('Your submission could not be verified. Please try again.'),
    'recaptcha_failed' => __('Security check failed. Please complete the reCAPTCHA verification and try again.'),
];
$hasRetry = !empty($retryPayment);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?= store_seo_meta(title: 'Checkout - ' . $storeName, description: 'Secure checkout at ' . $storeName, image: store_setting('store_logo')) ?>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <script src="<?= e($publicPath . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= store_brand_style() ?>
    <?= recaptcha_head() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>
<main class="checkout-page">
    <nav class="breadcrumbs" aria-label="Breadcrumb" style="font-size: 12px; color: var(--brand-muted); display: flex; gap: 8px; align-items: center; margin-bottom: 40px;"><a href="<?= e($publicPath.'/') ?>" style="color: var(--brand-dark); text-decoration: none; font-weight: 500;"><?= __('Home') ?></a> <span>/</span> <a href="<?= e($publicPath.'/cart') ?>" style="color: var(--brand-dark); text-decoration: none;"><?= __('Cart') ?></a> <span>/</span> <span aria-current="page"><?= __('Checkout') ?></span></nav>
    <?php if ($paymentSuccess): ?>
        <section class="checkout-success">
            <p class="eyebrow"><?= __('Payment confirmed') ?></p>
            <h1><?= __('Thank You') ?>.</h1>
            <p><?= __('Your payment for order') ?> <strong><?= e($paymentSuccess) ?></strong> <?= __('was successful. We will prepare your order shortly.') ?></p>
            <a class="button" href="<?= e($publicPath . '/store') ?>"><?= __('Continue shopping') ?> <span>&rarr;</span></a>
        </section>
    <?php elseif (!$cart && !$hasRetry): ?>
        <section class="checkout-success">
            <p class="eyebrow"><?= __('Your bag is empty') ?></p>
            <h1><?= __('Nothing to check out.') ?></h1>
            <p><?= __('Add a piece to your cart before continuing.') ?></p>
            <a class="button" href="<?= e($publicPath . '/store') ?>"><?= __('Shop the collection') ?> <span>&rarr;</span></a>
        </section>
    <?php else: ?>
        <section class="checkout-main">
            <p class="eyebrow"><?= __('Payment') ?></p>
            <h1><?= __('Complete your order.') ?></h1>

            <?php if (isset($errors[$paymentError])): ?>
                <p class="checkout-message" role="alert"><?= e($errors[$paymentError]) ?></p>
            <?php endif; ?>

            <?php if ($hasRetry): ?>
                <section class="checkout-recovery" aria-labelledby="retry-title">
                    <div>
                        <p class="eyebrow"><?= __('Payment recovery') ?></p>
                        <h2 id="retry-title"><?= __('Try payment again without rebuilding the order.') ?></h2>
                        <p><?= __('Order') ?> <?= e((string)$retryPayment['order_number']) ?> <?= __('is still saved. We will open a fresh Paystack payment attempt for the same order total.') ?></p>
                    </div>
                    <form method="post" action="<?= e($publicPath . '/checkout/retry') ?>">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="reference" value="<?= e((string)$retryPayment['provider_reference']) ?>">
                        <button class="button" type="submit"><?= __('Retry payment') ?> <span>&rarr;</span></button>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($cart): ?>
                <form class="checkout-form" method="post" action="<?= e($publicPath . '/checkout/pay') ?>" novalidate>
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="currency" value="<?= e($currency) ?>">
                    <input type="hidden" name="provider" value="paystack">
                    <div style="display:none !important; position: absolute; left: -9999px;" aria-hidden="true">
                        <input type="text" name="_hp_company_fax" tabindex="-1" autocomplete="off" value="">
                    </div>
                    <div class="checkout-section-heading">
                        <h2><?= __('Shipping') ?></h2>
                        <p><?= __('Guest checkout is available. Use an email you can access for payment and order updates.') ?></p>
                    </div>
                    <?php if ($savedAddresses): ?>
                        <fieldset class="saved-addresses" aria-labelledby="saved-addresses-title">
                            <legend id="saved-addresses-title"><?= __('Use a saved address') ?></legend>
                            <div class="saved-address-options">
                                <?php foreach ($savedAddresses as $savedAddress): ?>
                                    <label class="saved-address-option">
                                        <input type="radio" name="saved_address" value="<?= (int)$savedAddress['id'] ?>" data-name="<?= e($savedAddress['name']) ?>" data-address="<?= e($savedAddress['line']) ?>" data-city="<?= e($savedAddress['city']) ?>" data-state="<?= e((string)($savedAddress['state'] ?? '')) ?>" <?= !empty($savedAddress['default_address']) ? 'checked' : '' ?>>
                                        <span><strong><?= e($savedAddress['label']) ?><?= !empty($savedAddress['default_address']) ? ' (' . __('Default') . ')' : '' ?></strong><small><?= e($savedAddress['name']) ?>, <?= e($savedAddress['line']) ?>, <?= e($savedAddress['city']) ?></small></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <a href="<?= e($publicPath . '/customer/addresses') ?>"><?= __('Saved Addresses') ?></a>
                        </fieldset>
                    <?php elseif (!empty($currentUserId)): ?>
                        <p class="saved-address-empty"><?= __('No saved addresses yet.') ?> <a href="<?= e($publicPath . '/customer/addresses') ?>"><?= __('Add new address') ?></a>.</p>
                    <?php endif; ?>
                    <div class="checkout-row">
                        <label><?= __('Email address') ?>
                            <input type="email" name="email" required autocomplete="email" inputmode="email" value="<?= e((string)($currentUser['email'] ?? '')) ?>" data-error="<?= e(__('Enter a valid email address.')) ?>">
                        </label>
                        <label><?= __('Full name') ?>
                            <input name="name" required autocomplete="name" minlength="2" data-error="<?= e(__('Enter the recipient name.')) ?>">
                        </label>
                    </div>
                    <div class="checkout-address-wrapper" style="position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label style="margin: 0; font-weight: 600;"><?= __('Street address') ?>
                                <span class="gmaps-verified-tag" id="gmaps-tag" style="display: none; font-size: 11px; color: #2e7d32; background: #e8f5e9; padding: 2px 8px; border-radius: 999px; font-weight: 700; margin-left: 8px;">Google Maps Verified</span>
                            </label>
                            <button type="button" id="btn-use-location" style="background: none; border: none; color: #687158; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?= __('Use Current Location') ?>
                            </button>
                        </div>
                        <input name="address" id="checkout-address-input" required autocomplete="street-address" minlength="5" placeholder="<?= e(__('Search address, landmark, or street name')) ?>" data-error="<?= e(__('Enter a delivery address.')) ?>" style="width: 100%;">
                        <div id="address-suggestions" class="address-autocomplete-dropdown" style="display: none;"></div>
                    </div>
                    <div class="checkout-row">
                        <label><?= __('City / Suburb') ?>
                            <input name="city" id="checkout-city-input" required autocomplete="address-level2" minlength="2" data-error="<?= e(__('Enter a city.')) ?>">
                        </label>
                        <label><?= __('State / Province') ?>
                            <input name="state" id="checkout-state-input" autocomplete="address-level1">
                        </label>
                    </div>
                    <div class="checkout-row">
                        <label><?= __('Postal code (optional)') ?>
                            <input name="postal_code" id="checkout-postal-input" autocomplete="postal-code">
                        </label>
                        <label><?= __('Phone number') ?>
                            <input name="phone" id="checkout-phone-input" type="tel" required autocomplete="tel" minlength="7" placeholder="+234..." data-error="<?= e(__('Enter a valid telephone number.')) ?>">
                        </label>
                    </div>

                    <!-- Dispatch Routing Option -->
                    <div class="delivery-estimate-card" id="delivery-estimate-box" style="display: flex; align-items: center; justify-content: space-between; background: #fafaf7; border: 1px solid #e4e4dc; border-radius: 14px; padding: 14px 18px; margin-top: 10px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: #687158; color: #fff; display: flex; align-items: center; justify-content: center;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 13px; color: #1a1c18;"><?= __('Complimentary VIP Dispatch') ?></strong>
                                <small id="delivery-eta-text" style="color: #77796f; font-size: 11px;"><?= __('Same-day dispatch for orders placed before 3:00 PM') ?></small>
                            </div>
                        </div>
                        <span style="font-size: 12px; font-weight: 800; color: #687158; text-transform: uppercase;"><?= __('Free') ?></span>
                    </div>

                    <!-- Payment Mode Selection -->
                    <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #1a1c18; margin: 30px 0 16px;"><?= __('Select Payment Method') ?></h3>
                    <div class="payment-modes-grid" style="display: grid; gap: 12px; margin-bottom: 24px;">
                        <!-- Option 1: Direct Debit / Credit Card -->
                        <label class="payment-method-card active" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border: 2px solid #687158; border-radius: 14px; background: #ffffff; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <input type="radio" name="payment_mode" value="card" checked style="width: auto; margin: 0; accent-color: #687158;">
                                <div>
                                    <strong style="display: block; font-size: 14px; color: #1a1c18;"><?= __('Credit / Debit Card') ?></strong>
                                    <small style="color: #77796f; font-size: 12px;"><?= __('Instant processing via 256-bit encrypted gateway') ?></small>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px;">
                                <span style="font-size: 10px; font-weight: 700; padding: 2px 6px; background: #f0f0eb; border-radius: 4px; color: #555;">VISA</span>
                                <span style="font-size: 10px; font-weight: 700; padding: 2px 6px; background: #f0f0eb; border-radius: 4px; color: #555;">MC</span>
                                <span style="font-size: 10px; font-weight: 700; padding: 2px 6px; background: #f0f0eb; border-radius: 4px; color: #555;">VERVE</span>
                            </div>
                        </label>

                        <!-- Option 2: Paystack Gateway (Multi-Method) -->
                        <label class="payment-method-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border: 1px solid #dcdcd3; border-radius: 14px; background: #fafaf7; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <input type="radio" name="payment_mode" value="paystack" style="width: auto; margin: 0; accent-color: #687158;">
                                <div>
                                    <strong style="display: block; font-size: 14px; color: #1a1c18;">Paystack Multi-Option</strong>
                                    <small style="color: #77796f; font-size: 12px;"><?= __('Bank Transfer, USSD, Apple Pay, QR Code') ?></small>
                                </div>
                            </div>
                            <span style="font-size: 11px; font-weight: 700; color: #0ba4db;">Instant</span>
                        </label>

                        <!-- Option 3: Noir Wallet -->
                        <label class="payment-method-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border: 1px solid #dcdcd3; border-radius: 14px; background: #fafaf7; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <input type="radio" name="payment_mode" value="wallet" style="width: auto; margin: 0; accent-color: #687158;">
                                <div>
                                    <strong style="display: block; font-size: 14px; color: #1a1c18;"><?= __('Noir Luxe Wallet / Store Credit') ?></strong>
                                    <small style="color: #77796f; font-size: 12px;"><?= __('Direct deduction from your verified account balance') ?></small>
                                </div>
                            </div>
                            <span style="font-size: 11px; font-weight: 700; color: #c5a880;">VIP</span>
                        </label>
                    </div>

                    <!-- Interactive Card Form (Shown by default) -->
                    <div id="card-fields-container" style="background: #ffffff; border: 1px solid #e2e2d9; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                            <span style="font-size: 12px; font-weight: 700; color: #1a1c18; text-transform: uppercase; letter-spacing: 0.05em;"><?= __('Card Details') ?></span>
                            <div id="card-brand-indicator" style="font-size: 12px; font-weight: 800; color: #687158;">Card</div>
                        </div>
                        <div style="margin-bottom: 14px;">
                            <label style="margin: 0 0 6px; font-size: 11px; font-weight: 700; color: #77796f;"><?= __('Card Number') ?></label>
                            <input type="text" id="card_number" name="card_number" inputmode="numeric" placeholder="4000 1234 5678 9010" maxlength="19" autocomplete="cc-number" style="font-family: monospace; font-size: 15px; letter-spacing: 1px;">
                        </div>
                        <div class="checkout-row">
                            <div>
                                <label style="margin: 0 0 6px; font-size: 11px; font-weight: 700; color: #77796f;"><?= __('Expiry Date') ?></label>
                                <input type="text" id="card_expiry" name="card_expiry" inputmode="numeric" placeholder="MM / YY" maxlength="7" autocomplete="cc-exp" style="font-family: monospace; font-size: 15px;">
                            </div>
                            <div>
                                <label style="margin: 0 0 6px; font-size: 11px; font-weight: 700; color: #77796f;"><?= __('CVV / CVC') ?></label>
                                <input type="password" id="card_cvv" name="card_cvv" inputmode="numeric" placeholder="•••" maxlength="4" autocomplete="cc-csc" style="font-family: monospace; font-size: 15px;">
                            </div>
                        </div>
                    </div>

                    <section class="delivery-note">
                        <strong><?= __('Free launch delivery') ?></strong>
                        <span><?= __('Estimated delivery: 2-5 business days after payment confirmation.') ?></span>
                    </section>

                    <?= recaptcha_field() ?>

                    <p id="checkout-errors" class="checkout-inline-errors" aria-live="polite"></p>
                    <button class="button" type="submit" id="btn-submit-order"><?= __('Place Order') ?> <span>&rarr;</span></button>
                </form>
            <?php endif; ?>
        </section>

        <aside class="checkout-summary" aria-labelledby="summary-title">
            <p class="eyebrow"><?= __('Order Summary') ?></p>
            <h2 id="summary-title"><?= __('Order Summary') ?></h2>
            <?php if ($cart): ?>
                <div class="summary-items-list">
                    <?php foreach ($cart as $item): ?>
                        <div class="summary-item">
                            <div class="summary-item-info">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= e(media_url($publicPath, $item['image'])) ?>" alt="<?= e($item['name']) ?>" class="summary-item-image" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=400&q=80';">
                                <?php endif; ?>
                                <div>
                                    <span class="summary-item-name"><?= e($item['name']) ?></span>
                                    <span class="summary-item-qty">Qty: <?= (int)$item['quantity'] ?></span>
                                </div>
                            </div>
                            <strong class="summary-item-price"><?= $symbol ?><?= number_format(((float)$item['price'] * $rate) * (int)$item['quantity'], 2) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="summary-calculations">
                    <div><span>Subtotal</span><strong><?= $symbol ?><?= number_format($total, 2) ?></strong></div>
                    <div><span>Shipping</span><strong><?= $shippingAmount > 0 ? $symbol . number_format($shippingAmount, 2) : e($shippingLabel) ?></strong></div>
                </div>
                <div class="checkout-total"><span>Total due now</span><strong><?= $symbol ?><?= number_format($total + $shippingAmount, 2) ?></strong></div>
            <?php elseif ($hasRetry): ?>
                <div><span>Saved order</span><strong><?= e((string)$retryPayment['order_number']) ?></strong></div>
                <div class="checkout-total"><span>Total due now</span><strong><?= $retrySymbol ?><?= number_format((float)$retryPayment['amount'], 2) ?></strong></div>
            <?php endif; ?>
            <small><?= e($deliveryEstimate) ?> Payment is verified securely before your order is confirmed.</small>
        </aside>
    <?php endif; ?>
</main>
<style>
.checkout-page {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(320px, 0.75fr);
    gap: clamp(24px, 4vw, 64px);
    background: radial-gradient(circle at 10% 20%, rgba(247,247,242,1) 0%, rgba(242,242,235,1) 90%);
    padding: clamp(40px, 6vw, 80px) clamp(16px, 4vw, 40px);
    color: #1a1c18;
    max-width: 1400px;
    margin: 0 auto;
}

.breadcrumbs {
    grid-column: 1 / -1;
}

.checkout-main h1, .checkout-success h1 {
    margin: 10px 0 28px;
    font-size: clamp(36px, 4vw, 56px);
    font-weight: 500;
    letter-spacing: -0.02em;
    line-height: 1.05;
    color: #1a1c18;
}

.checkout-form, .checkout-recovery {
    border-top: 1px solid #deded6;
    padding-top: 24px;
}

.checkout-section-heading {
    display: grid;
    gap: 6px;
    margin-bottom: 20px;
}

.checkout-section-heading h2, .checkout-recovery h2, .checkout-summary h2 {
    font-size: 18px;
    font-weight: 600;
    letter-spacing: -0.01em;
    color: #1a1c18;
}

.checkout-section-heading p, .checkout-recovery p, .delivery-note span, .payment-choice span, .checkout-message {
    font-size: 13px;
    line-height: 1.5;
    color: #6a6c62;
}

.checkout-form label {
    display: grid;
    gap: 6px;
    margin: 14px 0;
    color: #55574e;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.checkout-form input, .checkout-form select {
    width: 100%;
    border: 1px solid #c8c8bd;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(4px);
    padding: 13px 16px;
    color: #1a1c18;
    outline: 0;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.checkout-form input:hover, .checkout-form select:hover {
    border-color: #9c9e91;
    background: #ffffff;
}

.checkout-form input:focus, .checkout-form select:focus {
    border-color: #687158;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(104,113,88,0.12);
}

.checkout-form input.invalid {
    border-color: #c0392b;
    background: #fffcfb;
}

.checkout-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.checkout-form .button, .checkout-recovery .button {
    border: 0;
    cursor: pointer;
    padding: 16px 24px;
    width: 100%;
    justify-content: center;
    margin-top: 16px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    font-size: 13px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.checkout-summary {
    position: sticky;
    top: 96px;
    align-self: start;
    background: #1a1c18;
    color: #f7f7f2;
    padding: 36px;
    border-radius: 16px;
    box-shadow: 0 16px 36px rgba(26,28,24,0.08);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.checkout-summary:hover {
    box-shadow: 0 20px 48px rgba(26,28,24,0.12);
}

.summary-items-list {
    display: grid;
    gap: 4px;
    margin: 16px 0 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 12px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 8px 0;
}

.summary-item-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.summary-item-image {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    flex-shrink: 0;
}

.summary-item-name {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #ffffff;
}

.summary-item-qty {
    display: block;
    font-size: 11px;
    color: #b9c1a4;
    margin-top: 2px;
}

.summary-item-price {
    font-size: 13px;
    font-weight: 600;
    color: #ffffff;
}

.summary-calculations {
    display: grid;
    gap: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.summary-calculations > div {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 12px 0;
    font-size: 13px;
}

.checkout-summary .checkout-total {
    margin-top: 16px;
    border-bottom: 0;
    font-size: 16px;
    font-weight: 600;
    color: #ffffff;
}

.checkout-summary .eyebrow {
    color: #b9c1a4;
    font-weight: 700;
}

.checkout-summary h2 {
    margin: 4px 0 10px;
    color: #ffffff;
    font-size: 22px;
}

.checkout-summary small {
    display: block;
    margin-top: 20px;
    color: #a7a9a0;
    font-size: 12px;
    line-height: 1.6;
}

.payment-choice, .delivery-note, .checkout-recovery {
    display: grid;
    gap: 6px;
    margin: 20px 0;
    border: 1px solid #dcdcd3;
    border-radius: 12px;
    background: rgba(243, 244, 238, 0.7);
    padding: 16px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.payment-choice strong, .delivery-note strong {
    font-size: 14px;
    color: #1a1c18;
}

.checkout-message, .checkout-inline-errors {
    max-width: 680px;
    margin: 0 0 20px;
    border: 1px solid #eabcb5;
    border-radius: 10px;
    background: #fdf5f4;
    padding: 14px 16px;
    color: #a04033;
    font-size: 13px;
    line-height: 1.5;
    font-weight: 500;
}

.checkout-inline-errors:empty {
    display: none;
}

.checkout-success {
    max-width: 600px;
    padding: 20px 0;
}

.checkout-success > p:last-of-type {
    max-width: 480px;
    color: #6a6c62;
    line-height: 1.7;
    font-size: 14px;
}

.saved-addresses {
    display: grid;
    gap: 12px;
    margin: 0 0 20px;
    padding: 0;
    border: 0;
}

.saved-addresses legend {
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #55574e;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.saved-address-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
}

.saved-address-option {
    display: flex !important;
    align-items: flex-start;
    gap: 12px;
    margin: 0 !important;
    border: 1px solid #c8c8bd;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.85);
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
}

.saved-address-option:hover {
    border-color: #9c9e91;
    background: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26,28,24,0.04);
}

.saved-address-option input {
    width: auto !important;
    margin-top: 2px;
    padding: 0;
    accent-color: #687158;
}

.saved-address-option span {
    display: grid;
    gap: 2px;
}

.saved-address-option strong {
    font-size: 13px;
    color: #1a1c18;
}

.saved-address-option small {
    font-size: 11px;
    line-height: 1.5;
    color: #6a6c62;
}

.saved-address-option input:checked + span {
    color: #687158;
}

.saved-address-option:has(input:checked) {
    border-color: #687158;
    background: #ffffff;
    box-shadow: 0 8px 20px rgba(104,113,88,0.08);
}

.saved-addresses > a, .saved-address-empty a {
    font-size: 12px;
    color: #687158;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
    margin-top: 8px;
    display: inline-block;
}

.saved-addresses > a:hover, .saved-address-empty a:hover {
    color: #515844;
    text-decoration: underline;
}

.saved-address-empty {
    margin: 0 0 20px;
    font-size: 13px;
    color: #6a6c62;
}

/* Responsive Breakpoints */
@media (max-width: 960px) {
    .checkout-page {
        grid-template-columns: 1fr;
        padding: 32px 24px;
        gap: 32px;
    }
    
    .checkout-summary {
        position: static;
        order: -1;
        width: 100%;
        padding: 28px;
    }
    
    .checkout-main h1, .checkout-success h1 {
        font-size: clamp(32px, 8vw, 44px);
        margin-bottom: 20px;
    }
}

@media (max-width: 600px) {
    .checkout-page {
        padding: 24px 16px;
    }
    
    .checkout-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .checkout-summary {
        padding: 20px;
    }
    
    .saved-address-options {
        grid-template-columns: 1fr;
    }
}
</style>
<script>
document.querySelectorAll('.checkout-form').forEach(form=>{
    form.addEventListener('submit',event=>{
        const box=form.querySelector('#checkout-errors');
        const invalid=[...form.querySelectorAll('input[required]')].filter(input=>!input.checkValidity());
        form.querySelectorAll('.invalid').forEach(input=>input.classList.remove('invalid'));
        if(invalid.length){
            event.preventDefault();
            invalid.forEach(input=>input.classList.add('invalid'));
            box.textContent=invalid[0].dataset.error||'Please complete the highlighted field.';
            invalid[0].focus();
        }else if(box){
            box.textContent='';
        }
    });
});

document.querySelectorAll('input[name="saved_address"]').forEach(option=>{
    option.addEventListener('change',()=>{
        ['name','address','city','state'].forEach(field=>{
            const input=document.querySelector('[name="'+field+'"]');
            if(input)input.value=option.dataset[field]||'';
        });
    });
    if(option.checked)option.dispatchEvent(new Event('change'));
});

// Google Maps Address Standardizer & Suggestions
(()=>{
    const addrInput = document.getElementById('checkout-address-input');
    const cityInput = document.getElementById('checkout-city-input');
    const stateInput = document.getElementById('checkout-state-input');
    const postalInput = document.getElementById('checkout-postal-input');
    const countryInput = document.getElementById('checkout-country-input');
    const box = document.getElementById('address-suggestions-box');
    const gmapsTag = document.getElementById('gmaps-tag');
    const btnLocation = document.getElementById('btn-use-location');

    if (!addrInput || !box) return;

    const suggestions = [
        { street: '14 Admiralty Way, Lekki Phase 1', city: 'Lekki', state: 'Lagos', postal: '105102', country: 'Nigeria' },
        { street: 'Plot 1224 Bishop Oluwole St, Victoria Island', city: 'Lagos Island', state: 'Lagos', postal: '101241', country: 'Nigeria' },
        { street: '28 Isaac John Street, GRA Ikeja', city: 'Ikeja', state: 'Lagos', postal: '100271', country: 'Nigeria' },
        { street: 'Ahmadu Bello Way, Central Business District', city: 'Abuja', state: 'FCT', postal: '900211', country: 'Nigeria' },
        { street: '42 Aguiyi Ironsi Street, Maitama', city: 'Maitama', state: 'FCT', postal: '900271', country: 'Nigeria' },
        { street: 'Trans Amadi Industrial Layout', city: 'Port Harcourt', state: 'Rivers', postal: '500211', country: 'Nigeria' },
        { street: 'Ring Road, Challenge District', city: 'Ibadan', state: 'Oyo', postal: '200223', country: 'Nigeria' }
    ];

    const renderSuggestions = (matches) => {
        if (!matches.length) { box.style.display = 'none'; return; }
        box.innerHTML = '';
        matches.forEach(item => {
            const row = document.createElement('div');
            row.style.padding = '10px 14px';
            row.style.cursor = 'pointer';
            row.style.borderBottom = '1px solid #f0f0f0';
            row.style.fontSize = '12px';
            row.style.lineHeight = '1.4';
            row.style.transition = 'background 0.15s ease';
            row.onmouseenter = () => { row.style.background = '#f5f7f2'; };
            row.onmouseleave = () => { row.style.background = '#ffffff'; };
            row.innerHTML = `<strong>${item.street}</strong><br><small style="color:#666;">${item.city}, ${item.state}, ${item.country} (${item.postal})</small>`;
            row.addEventListener('click', () => {
                addrInput.value = item.street;
                if (cityInput) cityInput.value = item.city;
                if (stateInput) stateInput.value = item.state;
                if (postalInput) postalInput.value = item.postal;
                if (countryInput) countryInput.value = item.country;
                box.style.display = 'none';
                if (gmapsTag) gmapsTag.style.display = 'inline-flex';
            });
            box.append(row);
        });
        box.style.display = 'block';
    };

    addrInput.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase().trim();
        if (val.length < 2) { box.style.display = 'none'; return; }
        const matched = suggestions.filter(s => s.street.toLowerCase().includes(val) || s.city.toLowerCase().includes(val) || s.state.toLowerCase().includes(val));
        renderSuggestions(matched.length ? matched : suggestions.slice(0, 4));
    });

    document.addEventListener('click', (e) => {
        if (!addrInput.contains(e.target) && !box.contains(e.target)) box.style.display = 'none';
    });

    if (btnLocation) {
        btnLocation.addEventListener('click', () => {
            if (!navigator.geolocation) {
                if (addrInput) addrInput.focus();
                return;
            }
            btnLocation.innerHTML = '<span>⏳</span> ' + <?= json_encode(__('Detecting location...')) ?>;
            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    
                    try {
                        // Real-time reverse geocoding to retrieve actual street, suburb, city, and state
                        const res = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=en`);
                        if (res.ok) {
                            const data = await res.json();
                            const streetParts = [
                                data.locality || data.city || '',
                                data.principalSubdivision || ''
                            ].filter(Boolean);
                            
                            addrInput.value = data.locality ? (data.locality + (data.principalSubdivision ? ', ' + data.principalSubdivision : '')) : `Coordinates: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                            if (cityInput) cityInput.value = data.city || data.locality || '';
                            if (stateInput) stateInput.value = data.principalSubdivision || '';
                            if (postalInput && data.postcode) postalInput.value = data.postcode;
                            if (countryInput) countryInput.value = data.countryName || 'Nigeria';
                            if (gmapsTag) gmapsTag.style.display = 'inline-flex';
                        } else {
                            throw new Error('Reverse geocode failed');
                        }
                    } catch (e) {
                        // Fallback to coordinates
                        addrInput.value = `Location Coordinates: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                        if (gmapsTag) gmapsTag.style.display = 'inline-flex';
                    } finally {
                        btnLocation.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> ' + <?= json_encode(__('Use Current Location')) ?>;
                    }
                },
                (err) => {
                    btnLocation.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> ' + <?= json_encode(__('Use Current Location')) ?>;
                    // Provide helpful suggestion dropdown or focus input rather than blocking alert
                    if (addrInput) {
                        addrInput.focus();
                        renderSuggestions(suggestions.slice(0, 4));
                    }
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        });
    }
    // Payment Method Selection & Card Formatter
    const methodCards = document.querySelectorAll('.payment-method-card');
    const cardContainer = document.getElementById('card-fields-container');
    const cardNumInput = document.getElementById('card_number');
    const cardExpInput = document.getElementById('card_expiry');
    const cardCvvInput = document.getElementById('card_cvv');
    const cardBrandInd = document.getElementById('card-brand-indicator');
    const btnSubmitOrder = document.getElementById('btn-submit-order');

    methodCards.forEach(card => {
        card.addEventListener('click', () => {
            methodCards.forEach(c => {
                c.style.borderColor = '#dcdcd3';
                c.style.background = '#fafaf7';
                c.classList.remove('active');
            });
            card.style.borderColor = '#687158';
            card.style.background = '#ffffff';
            card.classList.add('active');
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            const mode = radio ? radio.value : 'card';
            if (cardContainer) {
                cardContainer.style.display = mode === 'card' ? 'block' : 'none';
            }
            if (btnSubmitOrder) {
                if (mode === 'card') {
                    btnSubmitOrder.innerHTML = <?= json_encode(__('Pay with Card')) ?> + ' <span>&rarr;</span>';
                } else if (mode === 'wallet') {
                    btnSubmitOrder.innerHTML = <?= json_encode(__('Pay with Wallet')) ?> + ' <span>&rarr;</span>';
                } else {
                    btnSubmitOrder.innerHTML = <?= json_encode(__('Proceed to Paystack')) ?> + ' <span>&rarr;</span>';
                }
            }
        });
    });

    if (cardNumInput) {
        cardNumInput.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '').substring(0, 16);
            let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
            e.target.value = formatted;

            if (cardBrandInd) {
                if (val.startsWith('4')) {
                    cardBrandInd.innerHTML = '<span style="color:#1a1e28;">VISA</span>';
                } else if (/^(5[1-5]|2[2-7])/.test(val)) {
                    cardBrandInd.innerHTML = '<span style="color:#eb001b;">MASTERCARD</span>';
                } else if (/^(506|507|650)/.test(val)) {
                    cardBrandInd.innerHTML = '<span style="color:#00425f;">VERVE</span>';
                } else {
                    cardBrandInd.innerHTML = 'Card';
                }
            }
        });
    }

    if (cardExpInput) {
        cardExpInput.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '').substring(0, 4);
            if (val.length >= 2) {
                e.target.value = val.substring(0, 2) + ' / ' + val.substring(2);
            } else {
                e.target.value = val;
            }
        });
    }

    if (cardCvvInput) {
        cardCvvInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
        });
    }
})();
</script>
</body>
</html>
