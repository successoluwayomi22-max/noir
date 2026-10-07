<?php
declare(strict_types=1);

/**
 * Reusable Luxury KPI Metric Card Component
 * 
 * @var string $icon Lucide icon name
 * @var string $label Metric title
 * @var string|int|float $value Formatted metric value
 * @var string|null $trend Optional trend context
 * @var string $trendType 'up' | 'down' | 'neutral'
 */

$icon = $icon ?? 'bar-chart-2';
$label = $label ?? 'Metric';
$value = $value ?? '0';
$trend = $trend ?? null;
$trendType = $trendType ?? 'up';
?>
<div class="rounded-3xl border border-[#e5e5dd] bg-white p-5 shadow-[0_8px_30px_rgba(37,40,32,0.03)] hover:shadow-[0_12px_36px_rgba(37,40,32,0.06)] hover:border-[#deded6] transition-all flex flex-col justify-between">
    <div class="flex items-center justify-between">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-[#f5f6f1] text-[var(--brand-primary)]" style="color: var(--brand-primary, #687158);">
            <i data-lucide="<?= e($icon) ?>" class="h-5 w-5"></i>
        </span>
        <?php if (!empty($trend)): ?>
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-extrabold <?= $trendType === 'up' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($trendType === 'down' ? 'bg-rose-50 text-rose-700 border border-rose-100' : 'bg-zinc-50 text-zinc-600 border border-zinc-200') ?>">
                <i data-lucide="<?= $trendType === 'up' ? 'trending-up' : ($trendType === 'down' ? 'trending-down' : 'minus') ?>" class="h-3 w-3"></i>
                <?= e($trend) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="mt-4">
        <p class="text-xs font-bold uppercase tracking-wider text-[#8e9187]"><?= e($label) ?></p>
        <strong class="mt-1 block text-2xl lg:text-3xl font-extrabold text-[#252820] tracking-tight"><?= e((string)$value) ?></strong>
    </div>
</div>
