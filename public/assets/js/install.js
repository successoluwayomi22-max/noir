(function () {
    // Some older page templates still contain an inline install listener.
    // Ignore that legacy listener so only this data-driven prompt can render.
    var nativeAddEventListener = window.addEventListener.bind(window);
    window.addEventListener = function (type, listener, options) {
        if (type === 'beforeinstallprompt' && String(listener).indexOf('Install Noir') !== -1) return;
        return nativeAddEventListener(type, listener, options);
    };
    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }
    function isPublicStorefront() {
        return document.body && document.body.classList.contains('store-page') &&
            !document.querySelector('.info-page, .checkout-page');
    }
    function updateCustomerInstallButton() {
        var button = document.querySelector('.customer-install-app');
        if (!button) return;
        button.hidden = isStandalone() || !window.noirInstallEvent;
    }
    function promptInstall() {
        var event = window.noirInstallEvent;
        if (!event) return;
        event.prompt();
        event.userChoice.finally(function () {
            window.noirInstallEvent = null;
            updateCustomerInstallButton();
            var banner = document.querySelector('.noir-install-banner');
            if (banner) banner.remove();
        });
    }
    function showInstallBanner(nativeEvent) {
        if (!isPublicStorefront() || isStandalone() || document.querySelector('.noir-install-banner')) return;
        var storeNameMeta = document.querySelector('meta[name="store-name"]');
        var storeName = storeNameMeta && storeNameMeta.content ? storeNameMeta.content : 'Noir & Co.';
        var banner = document.createElement('div');
        banner.className = 'noir-install-banner';
        banner.innerHTML = '<strong>Install ' + storeName.replace(/[<>"']/g, '') + '</strong><span>Save the store to your home screen for a faster app-like experience.</span><button type="button">Install</button><button type="button" aria-label="Dismiss">&times;</button>';
        document.body.appendChild(banner);
        var dismissTimer = window.setTimeout(function () {
            if (banner && banner.parentNode) banner.remove();
        }, 3000);
        banner.querySelector('button').onclick = function () {
            window.clearTimeout(dismissTimer);
            if (nativeEvent) {
                nativeEvent.prompt();
                nativeEvent.userChoice.finally(function () {
                    window.noirInstallEvent = null;
                    banner.remove();
                    updateCustomerInstallButton();
                });
            } else alert('Open your browser menu and choose “Install app” or “Add to Home screen.”');
        };
        banner.querySelectorAll('button')[1].onclick = function () {
            window.clearTimeout(dismissTimer);
            banner.remove();
        };
    }
    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        window.noirInstallEvent = event;
        showInstallBanner(event);
        updateCustomerInstallButton();
    });
    window.addEventListener('appinstalled', function () {
        var banner = document.querySelector('.noir-install-banner');
        if (banner) banner.remove();
        window.noirInstallEvent = null;
        updateCustomerInstallButton();
    });
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.newsletter form, form.newsletter-form').forEach(function (form) {
            var input = form.querySelector('input[type="email"]');
            if (!input) return;
            input.name = 'email';
            form.method = 'post';
            form.action = (document.querySelector('meta[name="app-base"]') || {}).content || '/newsletter/subscribe';
            form.addEventListener('submit', function (event) {
                if (form.querySelector('input[name="_csrf"]')) return;
                event.preventDefault();
                fetch('/api/v1/auth/csrf').then(function (response) { return response.json(); }).then(function (data) {
                    var csrf = document.createElement('input'); csrf.type = 'hidden'; csrf.name = '_csrf'; csrf.value = data.csrf_token || '';
                    form.appendChild(csrf); form.submit();
                });
            });
        });
        if (document.querySelector('.customer-shell')) {
            var customerActions = document.querySelector('.customer-shell header > div:last-child');
            if (customerActions && !customerActions.querySelector('.customer-install-app')) {
                var installButton = document.createElement('button');
                installButton.type = 'button';
                installButton.className = 'customer-install-app';
                installButton.textContent = 'Install app';
                installButton.hidden = true;
                installButton.addEventListener('click', promptInstall);
                customerActions.insertBefore(installButton, customerActions.firstChild);
            }
            updateCustomerInstallButton();
        }
        var storeName = document.querySelector('meta[name="store-name"]');
        if (storeName && storeName.content) {
            document.querySelectorAll('.brand').forEach(function (brand) {
                if (!brand.querySelector('img')) brand.textContent = storeName.content;
            });
            document.title = document.title.replace(/Noir & Co\.?/gi, storeName.content);
            document.querySelectorAll('.hero-text').forEach(function (item) { item.textContent = item.textContent.replace(/Noir & Co\.?/gi, storeName.content); });
        }
        var established = document.querySelector('meta[name="store-established"]');
        if (established && established.content) document.querySelectorAll('.hero-art p').forEach(function (item) { item.textContent = 'EST. ' + established.content; });
        document.querySelectorAll('.store-page .site-header, .shop-page .shop-header, .product-page .product-header').forEach(function (header) {
            if (header.querySelector('.mobile-menu-toggle')) return;
            var source = header.querySelector('.main-nav, .shop-header nav, .product-header nav');
            if (!source) return;
            var toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'mobile-menu-toggle';
            toggle.setAttribute('aria-label', 'Open navigation');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.innerHTML = '<span></span><span></span><span></span>';
            var panel = document.createElement('div');
            panel.className = 'mobile-menu-panel';
            panel.setAttribute('aria-hidden', 'true');
            Array.prototype.forEach.call(source.querySelectorAll('a'), function (link) {
                var item = link.cloneNode(true);
                item.addEventListener('click', function () {
                    panel.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    panel.setAttribute('aria-hidden', 'true');
                });
                panel.appendChild(item);
            });
            toggle.addEventListener('click', function () {
                var open = panel.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
                panel.setAttribute('aria-hidden', open ? 'false' : 'true');
            });
            header.appendChild(toggle);
            header.appendChild(panel);
        });
        setTimeout(function () { showInstallBanner(window.noirInstallEvent || null); }, 1200);
    });
}());
