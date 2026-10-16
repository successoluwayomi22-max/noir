# Noir & Co. — Gemini Assistant Knowledge Base

Noir & Co. is a luxury Parisian fashion house digital commerce platform.

### Core Architecture
- **Web Storefront & Admin**: Native PHP 8.2+ MVC (`core/`, `controllers/`, `routes/`, `public/`).
- **RESTful API**: `/api/v1/` endpoints powering mobile and headless integrations.
- **Flutter Mobile App**: Located in `mobile/`, state management via Provider, minSdk 24, compileSdk/targetSdk 36.

### Operational Standards
- **Error Handling**: Dedicated offline, 500, and 404 screens with live health probe diagnostics (`/api/v1/health`) and dynamic server IP switching.
- **Brand Aesthetic**: Minimalist monochrome noir (`#0D0D0D`, `#D4AF37` gold, `#FFFFFF` white) with editorial serif headings.
- Detailed architecture: see [.agents/rules/noir_platform_knowledge.md](file:///c:/xamp/htdocs/noir/.agents/rules/noir_platform_knowledge.md).
