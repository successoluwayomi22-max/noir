<?php
declare(strict_types=1);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName = store_setting('store_name', 'Noir & Co.');
?>
<!-- Receipt / Invoice View Modal -->
<div id="receipt-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#252820]/60 p-4 backdrop-blur-sm overflow-y-auto">
    <div class="my-auto w-full max-w-xl rounded-2xl border border-[#e5e5dd] bg-white p-6 sm:p-8 shadow-2xl">
        
        <div class="flex items-start justify-between border-b border-[#e8e8e0] pb-4">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#687158]">Maison Financial Receipt</span>
                <h2 id="receipt-modal-title" class="text-xl font-extrabold text-[#252820] mt-0.5">Commercial Slip</h2>
            </div>
            <button type="button" onclick="closeReceiptModal()" class="rounded-lg p-1 text-[#92948a] hover:bg-[#f0f1ec]">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <!-- Dynamic Feedback Notification Banner -->
        <div id="receipt-feedback-banner" class="hidden my-3 rounded-xl p-3 text-xs font-semibold flex items-center gap-2"></div>

        <!-- Receipt Content Body -->
        <div id="receipt-modal-body" class="py-4 space-y-6">
            <p class="text-center text-sm text-[#8e9187]">Loading receipt data&hellip;</p>
        </div>

        <!-- Modal Footer with Printer Hardware Detection & PDF Auto-Download Fallback -->
        <div class="border-t border-[#e8e8e0] pt-4 flex flex-wrap items-center justify-between gap-3 no-print">
            <!-- Hardware status pill -->
            <div id="printer-status-pill" class="flex items-center gap-1.5 text-[11px] font-medium text-[#8e9187]">
                <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Checking printer connection&hellip;</span>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeReceiptModal()" class="rounded-xl border border-[#deded6] px-3.5 py-2 text-xs font-bold text-[#55584f] hover:bg-[#f5f5f0] transition-colors">
                    Close
                </button>

                <!-- Dedicated PDF Download Button -->
                <button type="button" id="download-pdf-btn" onclick="downloadReceiptPdf()" class="rounded-xl border border-[#deded6] bg-[#fafaf7] px-3.5 py-2 text-xs font-bold text-[#252820] hover:bg-white hover:border-[#687158] transition-all shadow-sm flex items-center gap-1.5" title="Download official PDF copy">
                    <i data-lucide="file-down" class="h-3.5 w-3.5 text-[#687158]"></i>
                    <span>Download PDF</span>
                </button>

                <!-- Smart Print / Auto PDF Button -->
                <button type="button" id="print-receipt-btn" onclick="smartPrintOrPdf()" class="rounded-xl bg-[#687158] px-4 py-2 text-xs font-bold text-white hover:bg-[#596249] shadow-sm flex items-center gap-1.5 transition-all">
                    <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                    <span id="print-btn-label">Print Slip</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Standalone Client PDF Generation Library -->
<script src="<?= e(($base ?: '') . '/assets/js/html2pdf.bundle.min.js') ?>"></script>

<script>
let currentReceiptData = null;
let printerStatusCache = null;

// Check printer hardware status on demand
async function checkPrinterStatus(forceRefresh = false) {
    if (printerStatusCache && !forceRefresh) {
        return printerStatusCache;
    }
    try {
        const res = await fetch(base + '/finance/printer-status', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        printerStatusCache = data;
        updatePrinterPill(data);
        return data;
    } catch (e) {
        const fallback = { success: true, connected: false, printer_name: 'Unavailable', offline: true };
        printerStatusCache = fallback;
        updatePrinterPill(fallback);
        return fallback;
    }
}

function updatePrinterPill(data) {
    const pill = document.getElementById('printer-status-pill');
    if (!pill) return;
    if (data && data.connected) {
        pill.innerHTML = `
            <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
            <span class="text-emerald-800 font-semibold" title="${esc(data.printer_name)} (${esc(data.port)})">
                Printer Ready: ${esc(data.printer_name || 'Connected')}
            </span>
        `;
    } else {
        const name = (data && data.printer_name && data.printer_name !== 'None' && data.printer_name !== 'none') ? data.printer_name : 'No printer';
        pill.innerHTML = `
            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
            <span class="text-[#77796f]" title="${esc(name)} (Offline)">
                Printer Offline &bull; Auto-PDF Active
            </span>
        `;
    }
}

function showReceiptBanner(msg, type = 'info') {
    const b = document.getElementById('receipt-feedback-banner');
    if (!b) return;
    b.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-900', 'border-emerald-200', 'bg-amber-50', 'text-amber-900', 'border-amber-200', 'bg-sky-50', 'text-sky-900', 'border-sky-200');
    
    let icon = 'info';
    if (type === 'success') {
        b.classList.add('bg-emerald-50', 'text-emerald-900', 'border', 'border-emerald-200');
        icon = 'check-circle-2';
    } else if (type === 'warning') {
        b.classList.add('bg-amber-50', 'text-amber-900', 'border', 'border-amber-200');
        icon = 'alert-triangle';
    } else {
        b.classList.add('bg-sky-50', 'text-sky-900', 'border', 'border-sky-200');
        icon = 'info';
    }
    b.innerHTML = `<i data-lucide="${icon}" class="h-4 w-4 shrink-0"></i><span>${msg}</span>`;
    b.classList.remove('hidden');
    lucide.createIcons();
    setTimeout(() => { b.classList.add('hidden'); }, 6000);
}

function viewReceipt(orderId) {
    const modal = document.getElementById('receipt-modal');
    const body = document.getElementById('receipt-modal-body');
    const b = document.getElementById('receipt-feedback-banner');
    if (b) b.classList.add('hidden');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    body.innerHTML = '<p class="text-center text-sm text-[#8e9187] py-8">Retrieving transaction credentials&hellip;</p>';

    // Trigger asynchronous printer status check
    checkPrinterStatus(true);

    fetch(base + '/finance/transaction/' + orderId, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success || !data.order) {
            body.innerHTML = '<p class="text-center text-sm text-rose-600 py-8">Could not retrieve receipt for this order.</p>';
            return;
        }
        currentReceiptData = data;
        const o = data.order;
        const items = data.items || [];

        let itemsHtml = '';
        items.forEach((it) => {
            const sub = Number(it.quantity) * Number(it.unit_price);
            itemsHtml += `
                <tr class="border-b border-[#f0f0ea] text-xs">
                    <td class="py-2.5 font-medium text-[#252820]">${esc(it.product_name)}</td>
                    <td class="py-2.5 text-center text-[#77796f]">${it.quantity}</td>
                    <td class="py-2.5 text-right font-mono">${Number(it.unit_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="py-2.5 text-right font-mono font-bold text-[#252820]">${sub.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                </tr>
            `;
        });

        const statusClass = o.payment_status === 'paid' ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-amber-700 bg-amber-50 border-amber-200';

        body.innerHTML = `
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div class="rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187] block">Transaction ID</span>
                    <strong class="font-mono text-xs text-[#252820] block mt-0.5">${esc(o.order_number)}</strong>
                    <small class="text-[10px] text-[#8e9187]">Gateway Ref: ${esc(o.provider_reference || 'Direct / Offline')}</small>
                </div>
                <div class="rounded-xl border border-[#e5e5dd] bg-[#fafaf7] p-3.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8e9187] block">Settlement State</span>
                    <span class="inline-block mt-1 font-bold text-[11px] uppercase tracking-wide px-2 py-0.5 rounded border ${statusClass}">
                        ${esc(o.payment_status)}
                    </span>
                    <small class="block text-[10px] text-[#8e9187] mt-1">${esc(o.payment_channel)}</small>
                </div>
            </div>

            <div class="rounded-xl border border-[#e5e5dd] p-3.5 text-xs space-y-1">
                <div class="flex justify-between"><span class="text-[#8e9187]">Customer:</span> <strong class="text-[#252820]">${esc(o.customer_name)}</strong></div>
                <div class="flex justify-between"><span class="text-[#8e9187]">Email Address:</span> <span>${esc(o.customer_email)}</span></div>
                <div class="flex justify-between"><span class="text-[#8e9187]">Phone Contact:</span> <span>${esc(o.customer_phone)}</span></div>
                <div class="flex justify-between"><span class="text-[#8e9187]">Captured Timestamp:</span> <span>${esc(o.created_at)}</span></div>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#8e9187] mb-2">Acquired Items</h4>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-[#e8e8e0] text-[10px] font-bold uppercase text-[#8e9187]">
                            <th class="py-1 text-left">Item</th>
                            <th class="py-1 text-center">Qty</th>
                            <th class="py-1 text-right">Price</th>
                            <th class="py-1 text-right">Subtotal (${esc(o.currency)})</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>

            <div class="rounded-xl bg-[#252820] text-white p-4 space-y-1.5 text-xs">
                <div class="flex justify-between text-[#a3a89b]"><span>Gross Transaction:</span> <span>${esc(o.currency)} ${Number(o.total).toLocaleString(undefined, {minimumFractionDigits: 2})}</span></div>
                <div class="flex justify-between text-[#a3a89b]"><span>Est. Gateway Fee:</span> <span>-₦${Number(o.estimated_fee).toLocaleString(undefined, {minimumFractionDigits: 2})}</span></div>
                <div class="flex justify-between text-sm font-bold pt-2 border-t border-white/15 text-white">
                    <span>Net Settled Amount:</span>
                    <span class="text-[#c3cfb2]">₦${Number(o.net_payout).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                </div>
            </div>
        `;
        lucide.createIcons();
    })
    .catch(() => {
        body.innerHTML = '<p class="text-center text-sm text-rose-600 py-8">Failed to connect to finance server.</p>';
    });
}

function closeReceiptModal() {
    const modal = document.getElementById('receipt-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Build printable/PDF DOM element
function buildReceiptPrintableElement(o, items) {
    const container = document.createElement('div');
    container.style.padding = '35px';
    container.style.color = '#252820';
    container.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    container.style.lineHeight = '1.5';
    container.style.fontSize = '12px';
    container.style.backgroundColor = '#ffffff';

    let itemRows = '';
    items.forEach(it => {
        const sub = Number(it.quantity) * Number(it.unit_price);
        itemRows += `
            <tr style="border-bottom: 1px solid #e8e8e0;">
                <td style="padding: 9px 4px;">${esc(it.product_name)}</td>
                <td style="padding: 9px 4px; text-align: center;">${it.quantity}</td>
                <td style="padding: 9px 4px; text-align: right;">${Number(it.unit_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                <td style="padding: 9px 4px; text-align: right; font-weight: bold;">${sub.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            </tr>
        `;
    });

    container.innerHTML = `
        <div style="border-bottom: 2px solid #252820; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-size: 22px; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase;"><?= e($storeName) ?></div>
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #687158; font-weight: bold;">Treasury Receipt &amp; Commercial Voucher</div>
            </div>
            <div style="text-align: right; font-size: 11px;">
                <strong style="font-size: 13px;">Order #${esc(o.order_number)}</strong><br>
                <span style="color: #666;">Gateway Ref: ${esc(o.provider_reference || 'Direct / Offline')}</span><br>
                <span style="color: #666;">Date: ${esc(o.created_at)}</span>
            </div>
        </div>

        <div style="background: #fafaf7; border: 1px solid #e5e5dd; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 11px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span><strong>Client:</strong> ${esc(o.customer_name)} (${esc(o.customer_email)})</span>
                <span><strong>Channel:</strong> ${esc(o.payment_channel)}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span><strong>Contact Phone:</strong> ${esc(o.customer_phone)}</span>
                <span><strong>Status:</strong> <span style="font-weight: bold; text-transform: uppercase; color: #2e7d32;">${esc(o.payment_status)}</span></span>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="border-bottom: 2px solid #252820; font-size: 10px; text-transform: uppercase; color: #666;">
                    <th style="padding: 6px 4px; text-align: left;">Item Description</th>
                    <th style="padding: 6px 4px; text-align: center;">Qty</th>
                    <th style="padding: 6px 4px; text-align: right;">Unit Price (${esc(o.currency)})</th>
                    <th style="padding: 6px 4px; text-align: right;">Subtotal (${esc(o.currency)})</th>
                </tr>
            </thead>
            <tbody>
                ${itemRows}
            </tbody>
        </table>

        <div style="border-top: 2px solid #252820; padding-top: 12px; margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; color: #555;">
                <span>Gross Commercial Value:</span>
                <strong>${esc(o.currency)} ${Number(o.total).toLocaleString(undefined, {minimumFractionDigits: 2})}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; color: #555;">
                <span>Estimated Payment Processing Fee:</span>
                <span>-₦${Number(o.estimated_fee).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px 0 0 0; margin-top: 6px; border-top: 1px solid #e0e0e0; font-size: 15px; font-weight: 800; color: #2e7d32;">
                <span>Net Treasury Settlement:</span>
                <span>₦${Number(o.net_payout).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>
        </div>

        <div style="margin-top: 35px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #e8e8e0; padding-top: 12px;">
            This voucher confirms genuine luxury asset acquisition managed under <?= e($storeName) ?> Treasury Protocol.
        </div>
    `;
    return container;
}

// 1. Direct PDF Download Generator
function downloadReceiptPdf() {
    if (!currentReceiptData || !currentReceiptData.order) {
        showReceiptBanner('Please open a transaction receipt first.', 'warning');
        return;
    }
    const o = currentReceiptData.order;
    const items = currentReceiptData.items || [];
    const btn = document.getElementById('download-pdf-btn');
    if (btn) btn.innerHTML = '<span class="inline-block animate-spin mr-1">&bull;</span> Generating PDF...';

    const element = buildReceiptPrintableElement(o, items);
    const filename = `Noir-Receipt-${o.order_number}.pdf`;

    const opt = {
        margin:       [10, 10, 10, 10],
        filename:     filename,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        if (btn) {
            btn.innerHTML = '<i data-lucide="file-down" class="h-3.5 w-3.5 text-[#687158]"></i><span>Download PDF</span>';
            lucide.createIcons();
        }
        showReceiptBanner(`Receipt PDF downloaded successfully (${filename})`, 'success');
    }).catch(err => {
        if (btn) {
            btn.innerHTML = '<i data-lucide="file-down" class="h-3.5 w-3.5 text-[#687158]"></i><span>Download PDF</span>';
            lucide.createIcons();
        }
        showReceiptBanner('Could not compile PDF. Triggering print dialog instead...', 'warning');
        printReceiptContent();
    });
}

// 2. Direct Print Window
function printReceiptContent() {
    if (!currentReceiptData || !currentReceiptData.order) return;
    const o = currentReceiptData.order;
    const items = currentReceiptData.items || [];

    let rows = '';
    items.forEach(it => {
        const sub = Number(it.quantity) * Number(it.unit_price);
        rows += `<tr><td>${esc(it.product_name)}</td><td style="text-align:center;">${it.quantity}</td><td style="text-align:right;">${Number(it.unit_price).toLocaleString(undefined, {minimumFractionDigits:2})}</td><td style="text-align:right;">${sub.toLocaleString(undefined, {minimumFractionDigits:2})}</td></tr>`;
    });

    const printHtml = `
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Receipt - ${esc(o.order_number)}</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 25px; color: #252820; font-size: 13px; line-height: 1.5; }
                .header { border-bottom: 2px solid #252820; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
                .brand { font-size: 20px; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { border-bottom: 1px solid #252820; padding: 8px 4px; text-align: left; font-size: 11px; text-transform: uppercase; }
                td { border-bottom: 1px solid #e8e8e0; padding: 8px 4px; }
                .totals { margin-top: 15px; border-top: 2px solid #252820; padding-top: 10px; }
                .totals-row { display: flex; justify-content: space-between; padding: 3px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <div>
                    <div class="brand"><?= e($storeName) ?></div>
                    <small>Treasury Receipt &amp; Commercial Voucher</small>
                </div>
                <div style="text-align:right;">
                    <strong>Order #${esc(o.order_number)}</strong><br>
                    <small>Ref: ${esc(o.provider_reference || 'N/A')}</small><br>
                    <small>${esc(o.created_at)}</small>
                </div>
            </div>
            <div>
                <strong>Customer:</strong> ${esc(o.customer_name)} (${esc(o.customer_email)})<br>
                <strong>Channel:</strong> ${esc(o.payment_channel)} &bull; <strong>Status:</strong> ${esc(o.payment_status).toUpperCase()}
            </div>
            <table>
                <thead>
                    <tr><th>Product</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Subtotal</th></tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            <div class="totals">
                <div class="totals-row"><span>Gross Total:</span> <strong>${esc(o.currency)} ${Number(o.total).toLocaleString(undefined, {minimumFractionDigits:2})}</strong></div>
                <div class="totals-row"><span>Est. Gateway Fee:</span> <span>-₦${Number(o.estimated_fee).toLocaleString(undefined, {minimumFractionDigits:2})}</span></div>
                <div class="totals-row" style="font-size: 15px; font-weight: 800; margin-top: 5px; color: #687158;"><span>Net Settlement:</span> <span>₦${Number(o.net_payout).toLocaleString(undefined, {minimumFractionDigits:2})}</span></div>
            </div>
            <p style="font-size:10px; color:#888; text-align:center; margin-top: 30px;">This voucher verifies an authentic acquisition processed through <?= e($storeName) ?>.</p>
            <script>window.onload=function(){window.print();};<\/script>
        </body>
        </html>
    `;

    const win = window.open('', '_blank', 'width=700,height=800');
    if (win) {
        win.document.open();
        win.document.write(printHtml);
        win.document.close();
    }
}

// 3. Smart Intelligent Print Routine:
// - If physical printer is connected: sends to printer via window.print()
// - If physical printer is NOT connected: automatically generates & downloads PDF form!
async function smartPrintOrPdf() {
    if (!currentReceiptData || !currentReceiptData.order) return;
    const btn = document.getElementById('print-receipt-btn');
    const label = document.getElementById('print-btn-label');
    if (label) label.textContent = 'Checking printer...';

    const status = await checkPrinterStatus(true);

    if (status && status.connected) {
        // Physical printer is connected and online
        if (label) label.textContent = 'Printing...';
        showReceiptBanner(`Printer online (${status.printer_name}). Sending voucher to printer...`, 'success');
        printReceiptContent();
        setTimeout(() => { if (label) label.textContent = 'Print Slip'; }, 2000);
    } else {
        // No physical printer connected -> Automatically download PDF form!
        if (label) label.textContent = 'Downloading PDF...';
        showReceiptBanner('No active printer connected. Voucher automatically converted & downloaded as PDF.', 'warning');
        downloadReceiptPdf();
        setTimeout(() => { if (label) label.textContent = 'Print Slip'; }, 2000);
    }
}

function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
