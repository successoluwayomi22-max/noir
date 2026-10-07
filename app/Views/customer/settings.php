<div class="mb-8">
    <div class="flex items-center gap-2 mb-2">
        <span class="h-1.5 w-1.5 rounded-full bg-[#687158]"></span>
        <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-[#687158]">Client Dossier &amp; Preferences</p>
    </div>
    <h1 class="text-3xl lg:text-4xl font-semibold tracking-tight text-[#1c1e18]">Account Settings</h1>
    <p class="mt-2 text-sm text-[#74786c]">Manage your personal credentials, digital identity, and bespoke concierge dispatch alerts.</p>
</div>

<!-- Luxury Segment Navigation Bar -->
<div class="mb-8 flex flex-wrap gap-2 border-b border-[#e5e7de] pb-4">
    <button type="button" class="settings-tab flex items-center gap-2.5 rounded-xl bg-[#687158] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:opacity-95" data-panel="personal-panel">
        <i data-lucide="user" class="h-4 w-4"></i>
        <span>Personal Information</span>
    </button>
    <button type="button" class="settings-tab flex items-center gap-2.5 rounded-xl bg-white border border-[#e1e3da] px-4 py-2.5 text-xs font-semibold text-[#666a5e] transition hover:bg-[#f2f3ec] hover:text-[#1c1e18]" data-panel="security-panel">
        <i data-lucide="shield-check" class="h-4 w-4"></i>
        <span>Security &amp; Password</span>
    </button>
    <button type="button" class="settings-tab flex items-center gap-2.5 rounded-xl bg-white border border-[#e1e3da] px-4 py-2.5 text-xs font-semibold text-[#666a5e] transition hover:bg-[#f2f3ec] hover:text-[#1c1e18]" data-panel="notifications-panel">
        <i data-lucide="bell" class="h-4 w-4"></i>
        <span>Concierge Alerts</span>
    </button>
</div>

<!-- ==========================================
     PANEL 1: PERSONAL INFORMATION & DOSSIER
     ========================================== -->
<div id="personal-panel" class="settings-panel space-y-6">
    <!-- Profile Photo Card -->
    <div class="rounded-3xl border border-[#e5e7de] bg-white p-6 lg:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div class="flex items-center gap-6">
                <!-- Avatar Preview with Ring Border -->
                <div id="customer-avatar-preview-wrap" class="relative h-24 w-24 shrink-0 overflow-hidden rounded-full ring-4 ring-[#687158]/20 shadow-lg bg-[#252820]">
                    <?php if(!empty($customer['profile_image'])): ?>
                        <img id="customer-avatar-preview-img" src="<?= e(($base?:'').$customer['profile_image']) ?>" alt="Profile photo" class="h-full w-full object-cover">
                        <span id="customer-avatar-preview-initials" class="hidden grid h-full w-full place-items-center bg-[#687158] text-2xl font-bold text-white"><?= e($customer['initials'] ?? 'CL') ?></span>
                    <?php else: ?>
                        <img id="customer-avatar-preview-img" src="" alt="Profile photo" class="hidden h-full w-full object-cover">
                        <span id="customer-avatar-preview-initials" class="grid h-full w-full place-items-center bg-[#687158] text-2xl font-bold text-white"><?= e($customer['initials'] ?? 'CL') ?></span>
                    <?php endif; ?>
                    <div id="avatar-loading-overlay" class="absolute inset-0 hidden bg-black/50 place-items-center backdrop-blur-xs">
                        <i data-lucide="loader-2" class="h-6 w-6 animate-spin text-white"></i>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-[#1c1e18]">Profile Photo</h3>
                    <p class="mt-1 text-xs text-[#74786c] max-w-md leading-relaxed">
                        Upload a square photo for your account symbol across the boutique, verified reviews, and community edits.
                    </p>
                    <span class="mt-2 inline-flex items-center gap-1.5 text-[11px] text-[#919588]">
                        <i data-lucide="info" class="h-3.5 w-3.5"></i>
                        PNG, JPG, or WEBP up to 5MB
                    </span>
                </div>
            </div>

            <!-- Upload Controls -->
            <div class="flex items-center gap-3 pt-2 sm:pt-0">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#687158] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#525a44] active:scale-95">
                    <i data-lucide="camera" class="h-4 w-4"></i>
                    <span>Upload photo</span>
                    <input type="file" id="customer-avatar-input" accept="image/jpeg,image/png,image/webp" class="hidden">
                </label>
                <button type="button" id="customer-avatar-delete-btn" class="<?= empty($customer['profile_image']) ? 'hidden' : '' ?> inline-flex items-center gap-1.5 rounded-xl border border-[#e5d0cd] bg-white px-3.5 py-2.5 text-xs font-semibold text-[#a84438] transition hover:bg-[#fdf3f2] active:scale-95">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    <span>Delete</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Personal Profile Form -->
    <div class="rounded-3xl border border-[#e5e7de] bg-white p-6 lg:p-8 shadow-sm">
        <div class="mb-6 flex items-center justify-between border-b border-[#f0f1ec] pb-5">
            <div>
                <h2 class="text-base font-semibold text-[#1c1e18]">Client Information</h2>
                <p class="mt-1 text-xs text-[#74786c]">Primary dossier contact information used for courier delivery notes and official communications.</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1 rounded-full bg-[#f1f4eb] px-3 py-1 text-[11px] font-semibold text-[#5a6a4c]">
                <i data-lucide="shield-check" class="h-3.5 w-3.5 text-[#687158]"></i>
                Verified Member
            </span>
        </div>

        <form id="customer-profile-form" class="space-y-6">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Full Name -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2 flex items-center gap-1.5">
                        <i data-lucide="user" class="h-3.5 w-3.5 text-[#888c7e]"></i>
                        Full Legal Name
                    </label>
                    <input type="text" name="name" required value="<?= e($customer['name']) ?>" class="field-input" placeholder="e.g. Olushola Moyinoluwa">
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2 flex items-center gap-1.5">
                        <i data-lucide="at-sign" class="h-3.5 w-3.5 text-[#888c7e]"></i>
                        Account Username
                    </label>
                    <input type="text" name="username" required value="<?= e($customer['username']) ?>" class="field-input" placeholder="e.g. techmama234.cbe8">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2 flex items-center gap-1.5">
                        <i data-lucide="mail" class="h-3.5 w-3.5 text-[#888c7e]"></i>
                        Primary Email Address
                    </label>
                    <div class="relative">
                        <input type="email" name="email" required value="<?= e($customer['email']) ?>" class="field-input pr-20" placeholder="e.g. client@domain.com">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 rounded bg-[#f0f4ea] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-[#526442]">Primary</span>
                    </div>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2 flex items-center gap-1.5">
                        <i data-lucide="phone" class="h-3.5 w-3.5 text-[#888c7e]"></i>
                        Phone Number
                    </label>
                    <input type="tel" name="phone" value="<?= e($customer['phone'] ?? '') ?>" class="field-input" placeholder="+234 800 000 0000">
                    <span class="mt-1 block text-[11px] text-[#93978a]">Used for instant SMS courier dispatch updates.</span>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between border-t border-[#f0f1ec] pt-5">
                <p class="text-xs text-[#8e9383] hidden sm:block">Updates will sync across your current session immediately.</p>
                <button type="submit" id="btn-save-profile" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] px-6 py-3 text-xs font-semibold text-white shadow-sm transition hover:bg-[#525a44] active:scale-95">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     PANEL 2: SECURITY & PASSWORD
     ========================================== -->
<div id="security-panel" class="settings-panel hidden space-y-6">
    <div class="rounded-3xl border border-[#e5e7de] bg-white p-6 lg:p-8 shadow-sm">
        <div class="mb-6 border-b border-[#f0f1ec] pb-5">
            <h2 class="text-base font-semibold text-[#1c1e18]">Authentication &amp; Password</h2>
            <p class="mt-1 text-xs text-[#74786c]">Update your login password or generate a one-time verification passcode via email.</p>
        </div>

        <!-- Mode Toggle Tabs -->
        <div class="mb-6 inline-flex rounded-xl border border-[#e1e3da] bg-[#f8f8f5] p-1">
            <button type="button" id="btn-mode-pwd" class="rounded-lg bg-[#252820] px-4 py-2 text-xs font-semibold text-white shadow-sm transition">
                Current Password
            </button>
            <button type="button" id="btn-mode-otp" class="rounded-lg px-4 py-2 text-xs font-semibold text-[#666a5e] transition hover:text-[#1c1e18]">
                Email OTP Code
            </button>
        </div>

        <!-- Standard Password Form -->
        <form id="password-form" class="space-y-5 max-w-xl">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2">Current Password</label>
                <div class="relative">
                    <input type="password" name="current_password" autocomplete="current-password" required class="field-input pr-10" placeholder="Enter your current password">
                    <button type="button" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-[#888c7e] hover:text-[#1c1e18]" data-target="current_password">
                        <i data-lucide="eye" class="h-4 w-4 eye-open"></i>
                        <i data-lucide="eye-off" class="h-4 w-4 eye-closed hidden"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2">New Password</label>
                <div class="relative">
                    <input type="password" name="new_password" minlength="8" autocomplete="new-password" required class="field-input pr-10" placeholder="Minimum 8 characters">
                    <button type="button" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-[#888c7e] hover:text-[#1c1e18]" data-target="new_password">
                        <i data-lucide="eye" class="h-4 w-4 eye-open"></i>
                        <i data-lucide="eye-off" class="h-4 w-4 eye-closed hidden"></i>
                    </button>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <div class="h-1 flex-1 rounded-full bg-[#e6e8df] overflow-hidden">
                        <div id="pwd-strength-bar" class="h-full w-0 transition-all duration-300 bg-[#a84438]"></div>
                    </div>
                    <span id="pwd-strength-text" class="text-[10px] text-[#8e9383]">Strength: 8+ chars required</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2">Confirm New Password</label>
                <div class="relative">
                    <input type="password" name="new_password_confirmation" minlength="8" autocomplete="new-password" required class="field-input pr-10" placeholder="Re-enter your new password">
                    <button type="button" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-[#888c7e] hover:text-[#1c1e18]" data-target="new_password_confirmation">
                        <i data-lucide="eye" class="h-4 w-4 eye-open"></i>
                        <i data-lucide="eye-off" class="h-4 w-4 eye-closed hidden"></i>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] px-6 py-3 text-xs font-semibold text-white shadow-sm transition hover:bg-[#525a44] active:scale-95">
                    <i data-lucide="key" class="h-4 w-4"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>

        <!-- Email OTP Mode Form -->
        <form id="password-otp-form" class="hidden space-y-5 max-w-xl">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            
            <div class="rounded-2xl border border-[#e5e7de] bg-[#fbfbf7] p-5">
                <div class="flex items-start gap-3.5">
                    <div class="rounded-xl bg-[#687158]/10 p-2 text-[#687158]">
                        <i data-lucide="mail-check" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#1c1e18] uppercase tracking-wider">Passwordless OTP Verification</h4>
                        <p class="mt-1 text-xs text-[#74786c]">
                            We will send a 6-digit cryptographic verification code to your verified address (<strong><?= e($customer['email']) ?></strong>).
                        </p>
                        <button type="button" id="btn-request-otp" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-[#252820] px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-[#3d4233] active:scale-95">
                            <i data-lucide="send" class="h-3.5 w-3.5"></i>
                            <span>Send 6-Digit OTP Code</span>
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2">6-Digit OTP Code</label>
                <input type="text" name="otp" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="field-input font-mono tracking-[0.3em] text-center text-base" placeholder="000000">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2">New Password</label>
                <div class="relative">
                    <input type="password" name="new_password" minlength="8" autocomplete="new-password" required class="field-input pr-10" placeholder="Minimum 8 characters">
                    <button type="button" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-[#888c7e] hover:text-[#1c1e18]" data-target="otp_new_password">
                        <i data-lucide="eye" class="h-4 w-4 eye-open"></i>
                        <i data-lucide="eye-off" class="h-4 w-4 eye-closed hidden"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[#585c4e] mb-2">Confirm New Password</label>
                <div class="relative">
                    <input type="password" name="new_password_confirmation" minlength="8" autocomplete="new-password" required class="field-input pr-10" placeholder="Re-enter your new password">
                    <button type="button" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-[#888c7e] hover:text-[#1c1e18]" data-target="otp_new_password_confirmation">
                        <i data-lucide="eye" class="h-4 w-4 eye-open"></i>
                        <i data-lucide="eye-off" class="h-4 w-4 eye-closed hidden"></i>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] px-6 py-3 text-xs font-semibold text-white shadow-sm transition hover:bg-[#525a44] active:scale-95">
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                    <span>Verify Code &amp; Update Password</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     PANEL 3: CONCIERGE NOTIFICATION PREFERENCES
     ========================================== -->
<div id="notifications-panel" class="settings-panel hidden space-y-6">
    <div class="rounded-3xl border border-[#e5e7de] bg-white p-6 lg:p-8 shadow-sm">
        <div class="mb-6 border-b border-[#f0f1ec] pb-5">
            <h2 class="text-base font-semibold text-[#1c1e18]">Concierge Notification Channels</h2>
            <p class="mt-1 text-xs text-[#74786c]">Customize how our boutique concierge and dispatch systems communicate critical milestones.</p>
        </div>

        <form id="notification-preferences-form" class="space-y-4 max-w-2xl">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            
            <?php 
            $preferences = [
                [
                    'key' => 'order_updates',
                    'icon' => 'package',
                    'title' => 'Order & Courier Dispatch Tracking',
                    'desc' => 'Receive instant status updates when your orders are confirmed, tailored, and out for courier delivery.'
                ],
                [
                    'key' => 'account_alerts',
                    'icon' => 'shield',
                    'title' => 'Security & Account Activity',
                    'desc' => 'Critical security advisories, new device logins, and password alteration verifications.'
                ],
                [
                    'key' => 'promotions',
                    'icon' => 'sparkles',
                    'title' => 'Exclusive Capsule Drops & VIP Invites',
                    'desc' => 'Exclusive early access to seasonal runway drops, archive sales, and private private-member events.'
                ]
            ];
            foreach($preferences as $pref): 
                $isChecked = !empty($notificationPreferences[$pref['key']]);
            ?>
                <label class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl border border-[#e5e7de] p-5 transition hover:border-[#687158]/50 hover:bg-[#fafaf8]">
                    <div class="flex items-start gap-3.5">
                        <div class="rounded-xl bg-[#687158]/10 p-2.5 text-[#687158] shrink-0 mt-0.5">
                            <i data-lucide="<?= e($pref['icon']) ?>" class="h-4 w-4"></i>
                        </div>
                        <div>
                            <strong class="block text-sm font-semibold text-[#1c1e18]"><?= e($pref['title']) ?></strong>
                            <p class="mt-1 text-xs leading-relaxed text-[#74786c]"><?= e($pref['desc']) ?></p>
                        </div>
                    </div>
                    <div class="relative inline-flex items-center mt-1">
                        <input type="checkbox" name="<?= e($pref['key']) ?>" value="1" <?= $isChecked ? 'checked' : '' ?> class="h-5 w-5 rounded-md border-[#d0d3c6] text-[#687158] accent-[#687158] focus:ring-[#687158]">
                    </div>
                </label>
            <?php endforeach; ?>

            <div class="pt-4">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#687158] px-6 py-3 text-xs font-semibold text-white shadow-sm transition hover:bg-[#525a44] active:scale-95">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    <span>Save Notification Preferences</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Page Specific Enhancements Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Tab Switching
    const tabs = document.querySelectorAll('.settings-tab');
    const panels = document.querySelectorAll('.settings-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            tabs.forEach(t => {
                t.classList.remove('bg-[#687158]', 'text-white', 'shadow-sm');
                t.classList.add('bg-white', 'border', 'border-[#e1e3da]', 'text-[#666a5e]');
            });
            this.classList.add('bg-[#687158]', 'text-white', 'shadow-sm');
            this.classList.remove('bg-white', 'border', 'border-[#e1e3da]', 'text-[#666a5e]');

            const targetId = this.dataset.panel;
            panels.forEach(p => {
                if (p.id === targetId) {
                    p.classList.remove('hidden');
                } else {
                    p.classList.add('hidden');
                }
            });
        });
    });

    // 2. Profile Photo Upload Handler
    const avatarInput = document.getElementById('customer-avatar-input');
    const deleteBtn = document.getElementById('customer-avatar-delete-btn');
    const imgElem = document.getElementById('customer-avatar-preview-img');
    const initialsElem = document.getElementById('customer-avatar-preview-initials');
    const loaderOverlay = document.getElementById('avatar-loading-overlay');

    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const file = this.files[0];

            if (file.size > 5 * 1024 * 1024) {
                if (typeof window.customerToast === 'function') window.customerToast('Image file size exceeds 5MB limit.');
                return;
            }

            const formData = new FormData();
            formData.append('profile', file);
            formData.append('_csrf', '<?= e(csrf_token()) ?>');

            if (loaderOverlay) loaderOverlay.classList.remove('hidden');

            fetch('<?= e(($base ?: '') . '/customer/ajax/profile-upload') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (loaderOverlay) loaderOverlay.classList.add('hidden');
                if (data.success) {
                    const fullUrl = '<?= e($base ?: '') ?>' + data.profile_image;
                    imgElem.src = fullUrl;
                    imgElem.classList.remove('hidden');
                    initialsElem.classList.add('hidden');
                    if (deleteBtn) deleteBtn.classList.remove('hidden');

                    if (typeof window.customerToast === 'function') {
                        window.customerToast('Profile photo updated successfully.');
                    }

                    // Update all profile avatars in the shell (topbar, sidebar)
                    document.querySelectorAll('.customer-avatar img, #customer-sidebar img').forEach(el => {
                        el.src = fullUrl;
                    });
                } else {
                    if (typeof window.customerToast === 'function') {
                        window.customerToast(data.message || 'Failed to upload photo.');
                    }
                }
            })
            .catch(() => {
                if (loaderOverlay) loaderOverlay.classList.add('hidden');
                if (typeof window.customerToast === 'function') {
                    window.customerToast('An error occurred during upload.');
                }
            });
        });
    }

    // 3. Profile Photo Delete Handler
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            if (!confirm('Are you sure you wish to remove your profile portrait?')) return;
            const formData = new FormData();
            formData.append('_csrf', '<?= e(csrf_token()) ?>');

            fetch('<?= e(($base ?: '') . '/customer/ajax/profile-delete') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    imgElem.src = '';
                    imgElem.classList.add('hidden');
                    initialsElem.textContent = data.initials || 'CL';
                    initialsElem.classList.remove('hidden');
                    deleteBtn.classList.add('hidden');

                    if (typeof window.customerToast === 'function') {
                        window.customerToast('Profile photo removed.');
                    }
                } else {
                    if (typeof window.customerToast === 'function') {
                        window.customerToast(data.message || 'Failed to delete photo.');
                    }
                }
            })
            .catch(() => {
                if (typeof window.customerToast === 'function') {
                    window.customerToast('An error occurred while deleting photo.');
                }
            });
        });
    }

    // 4. Functional Profile Information Save
    const profileForm = document.getElementById('customer-profile-form');
    const btnSaveProfile = document.getElementById('btn-save-profile');

    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            e.preventDefault();
            btnSaveProfile.disabled = true;
            const originalHtml = btnSaveProfile.innerHTML;
            btnSaveProfile.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i><span>Saving...</span>';
            if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();

            const formData = new FormData(profileForm);

            fetch('<?= e(($base ?: '') . '/customer/ajax/profile-update') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json().then(json => ({ status: res.status, ok: res.ok, data: json })))
            .then(({ ok, data }) => {
                btnSaveProfile.disabled = false;
                btnSaveProfile.innerHTML = originalHtml;
                if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();

                if (ok && data.success) {
                    if (typeof window.customerToast === 'function') {
                        window.customerToast(data.message || 'Profile saved successfully.');
                    }
                    if (data.customer) {
                        document.querySelectorAll('.customer-display-name').forEach(el => el.textContent = data.customer.name);
                        document.querySelectorAll('.customer-display-username').forEach(el => el.textContent = data.customer.username);
                    }
                } else {
                    if (typeof window.customerToast === 'function') {
                        window.customerToast(data.message || 'Unable to update profile.');
                    }
                }
            })
            .catch(err => {
                btnSaveProfile.disabled = false;
                btnSaveProfile.innerHTML = originalHtml;
                if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
                if (typeof window.customerToast === 'function') {
                    window.customerToast('Network error while saving profile.');
                }
            });
        });
    }

    // 5. Password Strength Meter
    const newPwdInput = document.querySelector('input[name="new_password"]');
    const strengthBar = document.getElementById('pwd-strength-bar');
    const strengthText = document.getElementById('pwd-strength-text');

    if (newPwdInput && strengthBar && strengthText) {
        newPwdInput.addEventListener('input', function () {
            const val = this.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            if (val.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'Strength: 8+ chars required';
            } else if (score <= 1) {
                strengthBar.style.width = '25%';
                strengthBar.className = 'h-full transition-all duration-300 bg-[#c0392b]';
                strengthText.textContent = 'Strength: Weak';
            } else if (score === 2) {
                strengthBar.style.width = '50%';
                strengthBar.className = 'h-full transition-all duration-300 bg-[#d35400]';
                strengthText.textContent = 'Strength: Fair';
            } else if (score === 3) {
                strengthBar.style.width = '75%';
                strengthBar.className = 'h-full transition-all duration-300 bg-[#f39c12]';
                strengthText.textContent = 'Strength: Good';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.className = 'h-full transition-all duration-300 bg-[#27ae60]';
                strengthText.textContent = 'Strength: Strong';
            }
        });
    }

    // Re-initialize Lucide Icons
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>
