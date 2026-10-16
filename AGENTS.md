# Noir & Co. — Workspace Knowledge & Operational Guidelines

## Maison Identity & Platform Architecture
**Noir & Co.** is a luxury fashion maison and bespoke tailoring e-commerce ecosystem consisting of:
1. **PHP Web Application & Admin Atelier**: Custom MVC architecture in root (`app/`, `core/`, `controllers/`, `routes/web.php`, `public/`).
2. **RESTful API Backend**: Headless endpoints in `routes/api.php` under `/api/v1/` serving catalogue, auth, cart, orders, and health checks.
3. **Flutter Mobile Application**: High-end mobile client in `mobile/` with full offline resilience, biometric security, Google auth, and bespoke luxury UI.

## Key Design & Aesthetic Rules
- Deep luxury dark aesthetic: `#0D0D0D` Obsidian, `#1A1A1A` Card Background, `#D4AF37` / `#C5A059` Gold accents, `#FAFAFA` Alabaster.
- Typography: Editorial serifs (Bodoni / Playfair) paired with clean geometric sans (Inter).
- Zero low-effort placeholders or generic designs; always uphold haute couture luxury standards.

## Codebase Conventions
- **PHP**: Strictly typed PHP 8.2+, prepared PDO queries, clean controller dispatch, LAN IP redirect protection in `bootstrap.php`.
- **Flutter / Dart**: Provider state management, zero static analysis warnings (`dart analyze lib`), responsive widgets, reusable luxury error states (`ErrorStateView`).
- **Release APK**: Built via `flutter build apk --release` with compileSdk & targetSdk set to 36.

For full architectural details, see [.agents/rules/noir_platform_knowledge.md](file:///c:/xamp/htdocs/noir/.agents/rules/noir_platform_knowledge.md).
