(function () {
    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function markRevealElements() {
        const selectors = [
            '.store-section', '.shop-section',
            '.category-strip', '.shop-category-section', '.catalogue',
            '.editorial-section', '.reviews-section', '.mobile-app-banner',
            '.info-hero', '.info-grid', '.admin-panel'
        ];
        document.querySelectorAll(selectors.join(',')).forEach((element, index) => {
            if (element.dataset.motionReady) return;
            element.dataset.motionReady = 'true';
            const type = index % 2 === 0 ? 'slide-up' : 'slide-right';
            element.classList.add('motion-reveal', 'motion-' + type);
        });
    }

    function observeReveals() {
        if (reduceMotion || !('IntersectionObserver' in window)) {
            document.querySelectorAll('.motion-reveal').forEach((element) => element.classList.add('is-visible'));
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' });
        document.querySelectorAll('.motion-reveal').forEach((element) => observer.observe(element));
    }

    function setupSearchAssist() {
        const searchInputs = document.querySelectorAll('input[name="search"]');
        if (!searchInputs.length) return;
        const productTerms = Array.from(document.querySelectorAll('.store-card a[href*="/store/product/"]'))
            .map((link) => link.textContent.trim())
            .filter(Boolean);
        const categoryTerms = Array.from(document.querySelectorAll('.category-list a span,.shop-category-grid strong,.shop-collection-card strong'))
            .map((item) => item.textContent.trim())
            .filter(Boolean);
        const recent = JSON.parse(localStorage.getItem('noirRecentSearches') || '[]').filter(Boolean);
        const terms = Array.from(new Set([...recent, ...productTerms, ...categoryTerms])).slice(0, 80);

        searchInputs.forEach((input) => {
            if (input.dataset.searchAssist) return;
            input.dataset.searchAssist = 'true';
            const listId = 'noir-search-' + Math.random().toString(36).slice(2);
            const datalist = document.createElement('datalist');
            datalist.id = listId;
            document.body.appendChild(datalist);
            input.setAttribute('list', listId);
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('aria-label', input.getAttribute('aria-label') || 'Search products');
            const update = () => {
                const value = input.value.toLowerCase().trim();
                const matches = terms.filter((term) => term.toLowerCase().includes(value) || fuzzyMatch(term, value)).slice(0, 8);
                datalist.innerHTML = matches.map((term) => '<option value="' + escapeHtml(term) + '"></option>').join('');
            };
            input.addEventListener('input', update);
            input.form && input.form.addEventListener('submit', () => {
                const query = input.value.trim();
                if (!query) return;
                const nextRecent = [query, ...recent.filter((item) => item.toLowerCase() !== query.toLowerCase())].slice(0, 6);
                localStorage.setItem('noirRecentSearches', JSON.stringify(nextRecent));
                emitAnalytics('search_submitted', { query: query });
            });
            update();
        });
        document.querySelectorAll('[data-recent-searches]').forEach((host) => {
            const saved = JSON.parse(localStorage.getItem('noirRecentSearches') || '[]').filter(Boolean).slice(0, 6);
            host.innerHTML = saved.map((term) => '<a href="' + escapeHtml(host.dataset.searchBase || location.pathname) + '?search=' + encodeURIComponent(term) + '#catalogue">' + escapeHtml(term) + '</a>').join('');
            host.hidden = saved.length === 0;
        });
    }

    function fuzzyMatch(term, query) {
        if (query.length < 3) return false;
        let offset = 0;
        return query.split('').every((character) => {
            const found = term.toLowerCase().indexOf(character, offset);
            offset = found + 1;
            return found !== -1;
        });
    }

    function setupSkeletonNavigation() {
        document.querySelectorAll('form[method="get"], .shop-filter-button, .filter-button').forEach((element) => {
            const form = element.tagName === 'FORM' ? element : element.closest('form');
            if (!form || form.dataset.skeletonReady) return;
            form.dataset.skeletonReady = 'true';
            form.addEventListener('submit', () => {
                document.body.classList.add('is-loading-results');
            });
        });
        window.addEventListener('pageshow', () => document.body.classList.remove('is-loading-results'));
    }

    function setupProductTelemetry() {
        if (document.body.classList.contains('product-page')) {
            emitAnalytics('product_viewed', { title: document.querySelector('h1')?.textContent.trim() || '' });
        }
        if (location.pathname.includes('/checkout')) {
            const success = new URLSearchParams(location.search).get('success');
            const error = new URLSearchParams(location.search).get('error');
            emitAnalytics(success ? 'checkout_success' : error ? 'checkout_failure' : 'checkout_started', { success: success || '', error: error || '' });
        }
        document.addEventListener('click', (event) => {
            const productLink = event.target.closest && event.target.closest('a[href*="/store/product/"]');
            if (productLink) emitAnalytics('product_clicked', { title: productLink.textContent.trim() });
            const addButton = event.target.closest && event.target.closest('button');
            if (addButton && /add to cart/i.test(addButton.textContent)) emitAnalytics('cart_intent', {});
            if (addButton && /retry payment/i.test(addButton.textContent)) emitAnalytics('payment_retry_clicked', {});
        });
    }

    window.storeWishlist = window.storeWishlist || async function (button, id, url, token) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: new URLSearchParams({_csrf: token, id: id})
        });
        const data = await response.json();
        if (response.ok && data.success) {
            button.classList.add('saved');
            button.setAttribute('aria-label', 'Saved');
            if (window.storeToast) storeToast('Added to your wishlist.');
        } else if (window.storeToast) storeToast(data.message || 'Unable to save this item.');
    };

    window.storeCart = window.storeCart || async function (button, id, url, token) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: new URLSearchParams({_csrf: token, id: id, quantity: 1})
        });
        const data = await response.json();
        if (response.ok && data.success) {
            button.textContent = 'Added to cart';
            button.classList.add('saved');
            document.querySelectorAll('[data-cart-count]').forEach((counter) => counter.textContent = data.count);
            if (window.storeToast) storeToast('Added to cart.');
        } else if (window.storeToast) storeToast(data.message || 'Unable to add this item to cart.');
    };

    function setupPerformanceTelemetry() {
        if (!('PerformanceObserver' in window)) return;
        let cls = 0;
        try {
            new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (!entry.hadRecentInput) cls += entry.value || 0;
                });
            }).observe({ type: 'layout-shift', buffered: true });
        } catch (error) {}
        try {
            new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lcp = entries[entries.length - 1];
                if (lcp) emitAnalytics('performance_metric', { metric: 'lcp', value: Math.round(lcp.startTime), cls: cls.toFixed(4) });
            }).observe({ type: 'largest-contentful-paint', buffered: true });
        } catch (error) {}
        try {
            new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    const duration = Math.round(entry.duration || 0);
                    if (duration > 120) emitAnalytics('performance_metric', { metric: 'slow_interaction', value: duration, name: entry.name || 'interaction' });
                });
            }).observe({ type: 'event', buffered: true, durationThreshold: 120 });
        } catch (error) {}
        window.addEventListener('load', () => {
            const nav = performance.getEntriesByType('navigation')[0];
            emitAnalytics('performance_metric', { metric: 'page_load', value: Math.round(nav ? nav.loadEventEnd : performance.now()), cls: cls.toFixed(4), connection: navigator.connection ? navigator.connection.effectiveType : '' });
        });
    }

    function emitAnalytics(event, payload) {
        window.noirAnalytics = window.noirAnalytics || [];
        const entry = { event: event, payload: payload, at: new Date().toISOString(), path: location.pathname };
        window.noirAnalytics.push(entry);
        const script = document.querySelector('script[data-analytics-endpoint]');
        const endpoint = script && script.dataset.analyticsEndpoint;
        const csrf = document.querySelector('meta[name="store-csrf-token"]')?.content || '';
        if (!endpoint || !csrf) return;
        const body = new URLSearchParams({_csrf: csrf, event: event, payload: JSON.stringify(payload || {}), path: location.pathname}).toString();
        if (navigator.sendBeacon) {
            navigator.sendBeacon(endpoint, new Blob([body], {type: 'application/x-www-form-urlencoded'}));
            return;
        }
        fetch(endpoint, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body, keepalive: true}).catch(() => {});
    }

    function escapeHtml(value) {
        return value.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    }

    function init() {
        document.body.classList.add('preloader-ready');
        if ('requestAnimationFrame' in window) {
            requestAnimationFrame(() => {
                markRevealElements();
                observeReveals();
            });
        } else {
            markRevealElements();
            observeReveals();
        }
        const runDeferred = window.requestIdleCallback || function (cb) { setTimeout(cb, 50); };
        runDeferred(() => {
            setupSearchAssist();
            setupSkeletonNavigation();
            setupProductTelemetry();
            setupPerformanceTelemetry();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
