<div class="mb-8">
    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#687158]"><?= __('Shop') ?></p>
    <h1 class="mt-2 text-3xl font-medium"><?= __('My Orders') ?></h1>
    <p class="mt-2 text-sm text-[#77796f]"><?= __('Track your purchases and view order details.') ?></p>
</div>

<?php if (!$orders): ?>
    <section class="rounded-2xl border border-dashed border-[#d6d6cd] bg-[#fbfbf7] p-12 text-center">
        <i data-lucide="shopping-bag" class="mx-auto h-8 w-8 text-[#9aa18c]"></i>
        <h2 class="mt-4 text-lg font-medium"><?= __('No orders yet.') ?></h2>
        <p class="mt-2 text-sm text-[#77796f]"><?= __('Your orders will appear here after checkout.') ?></p>
        <a href="<?= e(($base ?: '') . '/store') ?>" class="mt-6 inline-flex rounded-xl bg-[#687158] px-5 py-3 text-xs font-semibold text-white"><?= __('Shop the collection') ?></a>
    </section>
<?php else: ?>
    <section class="overflow-hidden rounded-2xl border border-[#e5e5dd] bg-[#fbfbf7]">
        <div class="overflow-x-auto">
            <table class="customer-table w-full min-w-[680px]">
                <thead><tr><th><?= __('Order number') ?></th><th><?= __('Date') ?></th><th><?= __('Status') ?></th><th><?= __('Payment') ?></th><th><?= __('Total') ?></th><th><?= __('Action') ?></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?= e($order['number']) ?></strong></td>
                        <td><?= e($order['date']) ?></td>
                        <td><?= customer_badge((string)$order['status']) ?></td>
                        <td><?= customer_badge((string)$order['payment']) ?></td>
                        <td class="whitespace-nowrap"><?= e(customer_money($order['total'], $order['currency'])) ?></td>
                        <td><a href="<?= e(($base ?: '') . '/customer/orders/' . rawurlencode($order['number'])) ?>" class="inline-flex rounded-lg border border-[#d8d8cf] px-3 py-2 text-[10px] font-semibold text-[#687158] hover:bg-[#eef0e7]"><?= __('View details') ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
