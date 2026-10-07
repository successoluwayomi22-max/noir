<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Account</p>
        <h1 class="mt-2 text-3xl font-medium">Payment methods</h1>
        <p class="mt-2 text-sm text-[#77796f]">Manage your saved ways to pay.</p>
    </div>
    <button id="open-payment-form" type="button" class="rounded-xl bg-[#687158] px-4 py-3 text-xs font-semibold text-white">+ Add payment method</button>
</div>

<div class="grid gap-5 md:grid-cols-2">
    <?php if (!$payments): ?>
        <div class="rounded-2xl border border-dashed border-[#c9cdbd] p-8 text-sm text-[#77796f]">No payment methods saved yet.</div>
    <?php endif; ?>

    <?php foreach ($payments as $payment): $isDefault = !empty($payment['is_default'] ?? $payment['default'] ?? false); ?>
        <article class="rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7] p-6">
            <div class="flex items-start justify-between">
                <div class="grid h-10 w-14 place-items-center rounded-lg bg-[#252820] text-xs text-white"><?= e($payment['type']) ?></div>
                <i data-lucide="more-horizontal" class="h-5 w-5 text-[#92948a]"></i>
            </div>
            <p class="mt-8 text-lg tracking-[.2em]">&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; <?= e($payment['last4']) ?></p>
            <div class="mt-5 flex justify-between text-xs text-[#77796f]">
                <span><?= e($payment['expires']) ?></span>
                <?php if ($isDefault): ?>
                    <span class="text-[#687158]">Default method</span>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<section class="mt-6 rounded-2xl border border-dashed border-[#c9cdbd] bg-[#f0f2e9] p-6">
    <div class="flex items-start gap-4">
        <i data-lucide="shield-check" class="h-5 w-5 text-[#687158]"></i>
        <div>
            <h2 class="font-medium">Your payments are protected</h2>
            <p class="mt-1 text-xs leading-5 text-[#77796f]">Only the card type, last four digits, and expiry date are stored for display.</p>
        </div>
    </div>
</section>

<div id="payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#252820]/40 p-5">
    <form id="payment-form" class="w-full max-w-md rounded-2xl bg-[#fbfbf7] p-6 shadow-2xl">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]">Secure payment</p>
                <h2 class="mt-2 text-xl font-medium">Add a payment method</h2>
            </div>
            <button type="button" id="close-payment-form" class="text-xl text-[#77796f]" aria-label="Close payment form">&times;</button>
        </div>
        <div id="payment-error" class="mt-5 hidden rounded-xl bg-[#f1dddd] p-3 text-xs text-[#a75b52]"></div>
        <div class="mt-6 grid gap-4">
            <label class="grid gap-2 text-xs text-[#77796f]">Card type
                <select name="type" data-searchable class="rounded-xl border border-[#deded6] bg-white p-3">
                    <option>Visa</option>
                    <option>Mastercard</option>
                    <option>Verve</option>
                    <option>American Express</option>
                </select>
            </label>
            <label class="grid gap-2 text-xs text-[#77796f]">Last four digits
                <input name="last_four" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" required class="rounded-xl border border-[#deded6] bg-white p-3" placeholder="4242">
            </label>
            <label class="grid gap-2 text-xs text-[#77796f]">Expiry date
                <input name="expires" required class="rounded-xl border border-[#deded6] bg-white p-3" placeholder="09/28">
            </label>
        </div>
        <button class="mt-6 w-full rounded-xl bg-[#687158] px-4 py-3 text-sm font-semibold text-white">Save payment method</button>
    </form>
</div>

<script>
document.getElementById('open-payment-form').onclick = () => document.getElementById('payment-modal').classList.replace('hidden', 'flex');
document.getElementById('close-payment-form').onclick = () => document.getElementById('payment-modal').classList.replace('flex', 'hidden');
document.getElementById('payment-form').onsubmit = async function (event) {
    event.preventDefault();
    const error = document.getElementById('payment-error');
    error.classList.add('hidden');
    const formData = new FormData(this);
    formData.append('_csrf', '<?= e(csrf_token()) ?>');
    const response = await fetch('<?= e(($base ?: '') . '/customer/ajax/payment-add') ?>', {
        method: 'POST',
        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        body: formData
    });
    const data = await response.json();
    if (!response.ok || !data.success) {
        error.textContent = data.message || 'Unable to add payment method.';
        error.classList.remove('hidden');
        return;
    }
    location.reload();
};
</script>
