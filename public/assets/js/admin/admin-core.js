/**
 * Noir & Co. — Enterprise Admin Core Client Library
 * Handles:
 * - Dynamic brand color real-time synchronization
 * - Luxury custom file dropzones & previewers
 * - Global toast alerts
 * - Modal drawers & accessible keybindings
 */

// 1. Dynamic Brand Color Synchronization
function updateBrandVariables(primary, dark, accent, paper) {
    if (!primary) return;
    document.documentElement.style.setProperty('--brand-primary', primary);
    if (dark) document.documentElement.style.setProperty('--brand-dark', dark);
    if (accent) document.documentElement.style.setProperty('--brand-accent', accent);
    if (paper) document.documentElement.style.setProperty('--brand-paper', paper);

    // Update active primary buttons & badges
    const saveBtn = document.getElementById('btn-save-settings');
    if (saveBtn) {
        saveBtn.style.backgroundColor = primary;
    }

    document.querySelectorAll('.dynamic-brand-primary-bg').forEach(el => el.style.backgroundColor = primary);
    document.querySelectorAll('.dynamic-brand-primary-text').forEach(el => el.style.color = primary);
    document.querySelectorAll('.dynamic-brand-primary-border').forEach(el => el.style.borderColor = primary);

    const lblP = document.getElementById('lbl-primary');
    const lblD = document.getElementById('lbl-dark');
    const lblA = document.getElementById('lbl-accent');
    const lblBg = document.getElementById('lbl-paper');
    if (lblP) lblP.textContent = primary;
    if (lblD) lblD.textContent = dark;
    if (lblA) lblA.textContent = accent;
    if (lblBg) lblBg.textContent = paper;
}

function setBrandPalette(primary, dark, accent, paper) {
    const p = document.getElementById('picker-primary');
    const d = document.getElementById('picker-dark');
    const a = document.getElementById('picker-accent');
    const bg = document.getElementById('picker-paper');
    if (p) p.value = primary;
    if (d) d.value = dark;
    if (a) a.value = accent;
    if (bg) bg.value = paper;

    updateBrandVariables(primary, dark, accent, paper);

    document.querySelectorAll('.palette-card').forEach(c => {
        const isCurrent = (c.getAttribute('data-primary') || '').toLowerCase() === primary.toLowerCase();
        c.classList.toggle('active', isCurrent);
        const icon = c.querySelector('.check-icon');
        if (icon) icon.style.opacity = isCurrent ? '1' : '0';
    });
}

// 2. Custom Luxury File Upload & Instant Live Preview
function handleFileSelected(input, imgId, labelId, fallbackId) {
    if (!input || !input.files || !input.files[0]) return;
    const file = input.files[0];

    const labelEl = document.getElementById(labelId);
    if (labelEl) {
        labelEl.textContent = file.name;
        labelEl.style.color = 'var(--brand-primary, #687158)';
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        const imgEl = document.getElementById(imgId);
        const fallbackEl = fallbackId ? document.getElementById(fallbackId) : null;
        if (imgEl) {
            imgEl.src = e.target.result;
            imgEl.classList.remove('hidden');
        }
        if (fallbackEl) {
            fallbackEl.classList.add('hidden');
        }
    };
    reader.readAsDataURL(file);
}

// 3. Global Toast Notifications
function showToast(message, type = 'info') {
    let toast = document.getElementById('toast-notification');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-notification';
        toast.className = 'fixed bottom-6 right-6 z-[9999] flex items-center gap-3 rounded-2xl bg-[#252820] px-5 py-3.5 text-xs font-bold text-white shadow-2xl transition-all duration-300 transform translate-y-2 opacity-0';
        document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.classList.remove('translate-y-2', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');

    clearTimeout(window._toastTimeout);
    window._toastTimeout = setTimeout(() => {
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('translate-y-2', 'opacity-0');
    }, 3000);
}

// 4. Global Keybindings (ESC closes modals)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-backdrop:not(.hidden)').forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }
});
