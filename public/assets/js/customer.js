(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function postForm(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            body: body
        }).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (payload) {
                if (!response.ok) {
                    var error = new Error(payload.message || 'Something went wrong.');
                    error.payload = payload;
                    throw error;
                }
                return payload;
            });
        });
    }

    function csrf() {
        var input = document.querySelector('input[name="_csrf"]');
        return input ? input.value : '';
    }

    window.customerToast = function (message) {
        var toast = document.getElementById('customer-toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('opacity-0', 'translate-y-3');
        toast.classList.add('opacity-100', 'translate-y-0');
        window.clearTimeout(toast.dataset.timer);
        toast.dataset.timer = window.setTimeout(function () {
            toast.classList.add('opacity-0', 'translate-y-3');
            toast.classList.remove('opacity-100', 'translate-y-0');
        }, 2600);
    };

    function setCustomerSidebar(open) {
        var sidebar = document.getElementById('customer-sidebar');
        var button = document.getElementById('open-customer-sidebar');
        if (sidebar) sidebar.classList.toggle('open', open);
        if (button) button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    ready(function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }

        document.getElementById('open-customer-sidebar')?.addEventListener('click', function () {
            setCustomerSidebar(true);
        });
        document.getElementById('close-customer-sidebar')?.addEventListener('click', function () {
            setCustomerSidebar(false);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setCustomerSidebar(false);
        });

        document.querySelectorAll('.customer-ajax').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var body = new FormData();
                body.append('_csrf', csrf());
                body.append('id', button.dataset.id || '');
                body.append('action', button.dataset.action || '');
                postForm(button.dataset.url, body).then(function (payload) {
                    window.customerToast(payload.message || 'Action completed.');
                    var url = button.dataset.url || '';
                    if (url.indexOf('notification-read-all') !== -1) {
                        document.querySelectorAll('.notification-item,.notification-preview').forEach(function (item) {
                            item.classList.remove('bg-[#f1f4eb]');
                            item.querySelectorAll('button').forEach(function (child) { child.remove(); });
                        });
                    } else if (url.indexOf('notification-read') !== -1) {
                        button.closest('.notification-item,.notification-preview')?.classList.remove('bg-[#f1f4eb]');
                        button.remove();
                    }
                    if (payload.unread !== undefined) {
                        document.querySelectorAll('.notification-unread-count').forEach(function (node) {
                            node.textContent = payload.unread;
                        });
                        if (Number(payload.unread) === 0) {
                            document.querySelectorAll('.notification-unread-badge').forEach(function (node) { node.remove(); });
                        }
                    }
                }).catch(function (error) {
                    window.customerToast(error.payload && error.payload.message ? error.payload.message : 'Something went wrong.');
                });
            });
        });

        document.querySelectorAll('.settings-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.settings-tab').forEach(function (item) {
                    item.classList.remove('bg-[#687158]', 'text-white');
                    item.classList.add('text-[#77796f]');
                });
                tab.classList.add('bg-[#687158]', 'text-white');
                tab.classList.remove('text-[#77796f]');
                document.querySelectorAll('.settings-panel').forEach(function (panel) {
                    panel.classList.add('hidden');
                });
                document.getElementById(tab.dataset.panel)?.classList.remove('hidden');
            });
        });

        var passwordForm = document.getElementById('password-form');
        var passwordOtpForm = document.getElementById('password-otp-form');
        var btnModePwd = document.getElementById('btn-mode-pwd');
        var btnModeOtp = document.getElementById('btn-mode-otp');
        var btnRequestOtp = document.getElementById('btn-request-otp');

        if (btnModePwd && btnModeOtp) {
            btnModePwd.addEventListener('click', function () {
                btnModePwd.classList.add('bg-[#252820]', 'text-white');
                btnModePwd.classList.remove('bg-[#f0f0ea]', 'text-[#77796f]');
                btnModeOtp.classList.add('bg-[#f0f0ea]', 'text-[#77796f]');
                btnModeOtp.classList.remove('bg-[#252820]', 'text-white');
                if (passwordForm) passwordForm.classList.remove('hidden');
                if (passwordOtpForm) passwordOtpForm.classList.add('hidden');
            });

            btnModeOtp.addEventListener('click', function () {
                btnModeOtp.classList.add('bg-[#252820]', 'text-white');
                btnModeOtp.classList.remove('bg-[#f0f0ea]', 'text-[#77796f]');
                btnModePwd.classList.add('bg-[#f0f0ea]', 'text-[#77796f]');
                btnModePwd.classList.remove('bg-[#252820]', 'text-white');
                if (passwordForm) passwordForm.classList.add('hidden');
                if (passwordOtpForm) passwordOtpForm.classList.remove('hidden');
            });
        }

        if (btnRequestOtp) {
            btnRequestOtp.addEventListener('click', function () {
                btnRequestOtp.disabled = true;
                btnRequestOtp.textContent = 'Sending OTP...';
                var formData = new FormData();
                var csrf = document.querySelector('input[name="_csrf"]');
                if (csrf) formData.append('_csrf', csrf.value);
                postForm(window.noirCustomer.passwordOtpRequestUrl, formData).then(function (payload) {
                    window.customerToast(payload.message || 'OTP code sent to your email.');
                    btnRequestOtp.textContent = 'Resend 6-digit OTP code';
                    btnRequestOtp.disabled = false;
                }).catch(function (error) {
                    window.customerToast(error.payload && error.payload.message ? error.payload.message : 'Unable to send OTP.');
                    btnRequestOtp.textContent = 'Send 6-digit OTP code';
                    btnRequestOtp.disabled = false;
                });
            });
        }

        if (passwordForm) {
            passwordForm.addEventListener('submit', function (event) {
                event.preventDefault();
                postForm(window.noirCustomer.passwordUrl, new FormData(passwordForm)).then(function (payload) {
                    window.customerToast(payload.message || 'Password updated.');
                    passwordForm.reset();
                }).catch(function (error) {
                    window.customerToast(error.payload && error.payload.message ? error.payload.message : 'Unable to update password.');
                });
            });
        }

        if (passwordOtpForm) {
            passwordOtpForm.addEventListener('submit', function (event) {
                event.preventDefault();
                postForm(window.noirCustomer.passwordOtpChangeUrl, new FormData(passwordOtpForm)).then(function (payload) {
                    window.customerToast(payload.message || 'Password updated via OTP.');
                    passwordOtpForm.reset();
                }).catch(function (error) {
                    window.customerToast(error.payload && error.payload.message ? error.payload.message : 'Unable to update password.');
                });
            });
        }

        var preferencesForm = document.getElementById('notification-preferences-form');
        if (preferencesForm) {
            preferencesForm.addEventListener('submit', function (event) {
                event.preventDefault();
                postForm(window.noirCustomer.preferencesUrl, new FormData(preferencesForm)).then(function (payload) {
                    window.customerToast(payload.message || 'Preferences saved.');
                }).catch(function (error) {
                    window.customerToast(error.payload && error.payload.message ? error.payload.message : 'Unable to save preferences.');
                });
            });
        }

        document.querySelectorAll('.password-toggle-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.querySelector('input[name="' + button.dataset.target + '"]');
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.querySelector('.eye-open')?.classList.toggle('hidden', show);
                button.querySelector('.eye-closed')?.classList.toggle('hidden', !show);
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });

        document.querySelectorAll('select[data-searchable]').forEach(function (select) {
            var input = document.createElement('input');
            input.type = 'search';
            input.placeholder = 'Search options...';
            input.className = select.className + ' mb-2';
            select.parentNode.insertBefore(input, select);
            input.addEventListener('input', function () {
                var term = input.value.toLowerCase();
                Array.from(select.options).forEach(function (option) {
                    option.hidden = !!term && !option.text.toLowerCase().includes(term);
                });
            });
        });
    });
}());
