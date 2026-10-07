(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.querySelector('.admin-sidebar');
        var main = document.querySelector('.admin-main');
        if (!sidebar || !main) return;
        if (document.querySelector('.admin-mobile-toggle')) return;

        // Inject responsive admin sidebar & hamburger toggle styles
        var style = document.createElement('style');
        style.id = 'admin-mobile-nav-styles';
        style.textContent = `
            @media (max-width: 900px) {
                .admin-mobile-toggle {
                    display: flex !important;
                    flex-direction: column;
                    justify-content: space-around;
                    position: fixed;
                    top: 14px;
                    left: 14px;
                    z-index: 999999;
                    width: 42px;
                    height: 42px;
                    padding: 9px;
                    border-radius: 10px;
                    background: #252820;
                    border: 1px solid rgba(197, 168, 128, 0.4);
                    box-shadow: 0 4px 15px rgba(0,0,0,0.35);
                    cursor: pointer;
                    transition: all 0.25s ease;
                }
                .admin-mobile-toggle span {
                    display: block;
                    width: 100%;
                    height: 2.5px;
                    background: #f7f7f2;
                    border-radius: 2px;
                    transition: all 0.3s ease;
                }
                .admin-mobile-toggle[aria-expanded="true"] span:nth-child(1) {
                    transform: translateY(7px) rotate(45deg);
                }
                .admin-mobile-toggle[aria-expanded="true"] span:nth-child(2) {
                    opacity: 0;
                }
                .admin-mobile-toggle[aria-expanded="true"] span:nth-child(3) {
                    transform: translateY(-7px) rotate(-45deg);
                }
                .admin-mobile-backdrop {
                    display: none;
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.65);
                    backdrop-filter: blur(4px);
                    -webkit-backdrop-filter: blur(4px);
                    z-index: 999990;
                    border: none;
                    cursor: pointer;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                .admin-mobile-backdrop.is-open {
                    display: block;
                    opacity: 1;
                }
                .admin-sidebar {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    bottom: 0 !important;
                    width: 280px !important;
                    max-width: 85vw !important;
                    height: 100vh !important;
                    z-index: 999995 !important;
                    background: #252820 !important;
                    transform: translateX(-100%) !important;
                    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
                    box-shadow: 10px 0 35px rgba(0,0,0,0.4) !important;
                    overflow-y: auto !important;
                    padding-top: 60px !important;
                }
                .admin-sidebar.admin-mobile-open {
                    transform: translateX(0) !important;
                }
                .admin-main {
                    padding-top: 50px !important;
                }
            }
            @media (min-width: 901px) {
                .admin-mobile-toggle, .admin-mobile-backdrop {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(style);

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'admin-mobile-toggle';
        toggle.setAttribute('aria-label', 'Open admin navigation');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<span></span><span></span><span></span>';

        var backdrop = document.createElement('button');
        backdrop.type = 'button';
        backdrop.className = 'admin-mobile-backdrop';
        backdrop.setAttribute('aria-label', 'Close admin navigation');

        function closeMenu() {
            sidebar.classList.remove('admin-mobile-open');
            backdrop.classList.remove('is-open');
            document.body.classList.remove('admin-menu-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Open admin navigation');
        }

        toggle.addEventListener('click', function () {
            var open = !sidebar.classList.contains('admin-mobile-open');
            sidebar.classList.toggle('admin-mobile-open', open);
            backdrop.classList.toggle('is-open', open);
            document.body.classList.toggle('admin-menu-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close admin navigation' : 'Open admin navigation');
        });

        backdrop.addEventListener('click', closeMenu);
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMenu);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeMenu();
        });

        document.body.appendChild(backdrop);
        document.body.appendChild(toggle);
    });
}());
