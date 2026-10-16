# Noir & Co. — Luxury Maison Digital Platform Knowledge

## 1. Brand & Platform Overview
**Noir & Co.** is a prestigious luxury fashion house and bespoke tailoring atelier combining the heritage of classic Parisian haute couture with cutting-edge digital commerce. The platform encompasses a high-performance web storefront, an administrative back-office atelier suite, a headless RESTful API, and an Android/iOS mobile application.

### Aesthetic & Visual Identity
- **Palette**: Deep Obsidian (`#0D0D0D`, `#121212`), Velvet Black (`#1A1A1A`), Champagne Gold (`#D4AF37`, `#C5A059`), Soft Alabaster White (`#FAFAFA`), and Editorial Stone Grey (`#8E8E93`).
- **Typography**: Editorial serif headers (Bodoni, Playfair Display, Cormorant Garamond) paired with minimalist geometric sans-serif body typography (Inter, Outfit, Montserrat).
- **Brand Ethos**: Understated opulence, tactile digital storytelling, editorial lookbooks, bespoke private concierge, and frictionless checkout.

---

## 2. Web Architecture (`noir`)
- **Backend Core**: Custom PHP 8.2+ MVC architecture built for speed, security, and zero runtime bloat.
  - `core/`: Application kernel, Router, Database (PDO wrapper), Request/Response pipelines, Session, and Security layers.
  - `controllers/`: Domain controllers handling Store, Cart, Checkout, Auth, User Dashboard, Concierge Bookings, and Admin CMS.
  - `routes/`: Expressive route definitions for web (`routes/web.php`) and mobile API (`routes/api.php`).
  - `bootstrap.php`: Environment initialization, private LAN subnet detection (`192.168.*`, `10.*`, `172.16-31.*`) preventing unintended HTTPS redirects during local mobile device testing.
- **Database & Storage**:
  - Relational MySQL/MariaDB database with structured migrations in `database/migrations/`.
  - Rich product catalog supporting variations (sizes, fabrics, colors), inventory tracking, order lifecycles, bespoke appointment bookings, and promo coupons.
- **Frontend Layer**:
  - Vanilla HTML5, modern CSS3 (custom luxury design system, CSS variables, glassmorphism, responsive viewports), and asynchronous ES6+ JavaScript.
  - Interactive features: Lookbook carousels, quick-view modals, slide-out micro-cart drawer, bespoke measurement submission, dynamic currency converter, and real-time stock checks.
- **RESTful API**:
  - Versioned API endpoints located at `/api/v1/` serving JSON payloads for authentication, catalogue browsing, search, order management, user profiles, and system health (`/api/v1/health`).
  - Hardened with Bearer token authentication, rate limiting, and CORS handling.

---

## 3. Mobile Architecture (`mobile/`)
- **Framework**: Flutter 3.x / Dart 3.x natively compiled for Android (minSdk 24, targetSdk 36) and iOS.
- **State Management & Pattern**: Clean architecture leveraging the Provider pattern (`ChangeNotifier`), decoupled service clients, and reusable UI widget primitives.
  - `lib/providers/`: `AuthProvider`, `CartProvider`, `ProductProvider`, `OrderProvider`, `WishlistProvider`, `LocaleProvider`.
  - `lib/services/`: `ApiService` (HTTP/REST client with network interceptors, token persistence, and retry logic), `StorageService` (secure storage for credentials and preferences), `BiometricService` (fingerprint/Face ID authentication).
  - `lib/screens/`:
    - **Store & Catalogue**: `HomeScreen`, `CatalogueScreen`, `ProductDetailScreen`, `LookbookScreen`, `SearchScreen`.
    - **Cart & Checkout**: `CartScreen`, `CheckoutScreen`, `OrderSuccessScreen`.
    - **User Account**: `ProfileScreen`, `MyOrdersScreen`, `OrderDetailScreen`, `AddressBookScreen`, `SettingsScreen`.
    - **Auth**: `LoginScreen`, `RegisterScreen`, `ForgotPasswordScreen` with Google Sign-In and Biometrics.
    - **Error & Resilience**: `OfflineScreen` (live server connectivity health probe and dynamic IP reconfiguration), `ServerErrorScreen` (500), `NotFoundScreen` (404), and `ErrorStateView`.
- **Styling**: `AppTheme` encapsulating the luxury noir dark theme, gold accents, bespoke borders, custom typography, and haptic feedback.

---

## 4. Key Workflows & Operational Notes
1. **Local Mobile Testing**:
   - The API base URL in `mobile/.env` and `mobile/lib/main.dart` targets the local LAN IP (e.g. `http://192.168.164.108/noir/public`) or an active Cloudflare Tunnel (`https://*.trycloudflare.com`).
   - If Wi-Fi changes, update `API_BASE_URL` or use the in-app server IP configurator on the `/offline` screen.
2. **Authentication Flow**:
   - Web uses secure sessions + CSRF tokens.
   - Mobile uses token-based Bearer authentication (`Authorization: Bearer <token>`) stored in secure storage.
3. **Build & Release**:
   - Android release APK built via `flutter build apk --release` targeting SDK 36.
   - Output path: `mobile/build/app/outputs/flutter-apk/app-release.apk`.
