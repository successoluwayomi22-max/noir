# Noir & Co.

Enterprise e-commerce foundation built with raw PHP 8.3, PDO, MySQL, and a central public router.

## Requirements

- PHP 8.3+
- MySQL 8+
- Apache with `mod_rewrite` (or PHP's built-in server for local development)

## Quick start

1. Copy `.env.example` to `.env` and set the database credentials.
2. Create the database and run `database/migrations/001_initial_schema.sql`.
3. Point the web server document root to `public/`.
4. For local development, run `php -S localhost:8000 -t public public/index.php`.

The first seeded account is `admin@noir.test` with password `ChangeMe!123`. Change it immediately outside development.

## Regression checks

Run the focused local regression suite with:

```bash
php tests/run.php
```

The suite covers guest auth schema readiness, pending checkout/payment transaction creation, admin customer-account boundaries, and upload execution blocking.

Run the operational preflight with:

```bash
php tests/ops_preflight.php
```

The preflight checks provider configuration, operations tables, upload protection, backup readiness, and key local routes. Record completed evidence in `docs/VALIDATION_EVIDENCE.md`.

## AI shopping assistant

The storefront includes an optional AI-powered shopping assistant. It recommends from the current published catalogue, but cannot access customer accounts or make changes to carts, orders, payments, or refunds.

For OpenAI, add these values to your local `.env` and restart PHP/Apache:

```ini
AI_ASSISTANT_ENABLED=true
OPENAI_API_KEY=your_server_side_openai_key
# Optional: change this if your account uses a different supported model.
OPENAI_MODEL=gpt-4o-mini
```

For OpenRouter, use its OpenAI-compatible Chat Completions endpoint instead:

```ini
AI_ASSISTANT_ENABLED=true
AI_PROVIDER=openrouter
OPENROUTER_API_KEY=your_server_side_openrouter_key
AI_API_BASE_URL=https://openrouter.ai/api/v1
AI_MODEL=openai/gpt-4.1-mini
AI_ASSISTANT_TIMEOUT=25
```

The key stays on the server; the browser only calls the site's CSRF-protected assistant endpoint. The app rate limit is configurable through `AI_ASSISTANT_*`, but a provider's free tier can still impose its own caps.

## Architecture

`public/index.php` is the only web entry point. Application code is under `app/`, shared infrastructure is under `core/`, environment configuration is under `config/`, routes are under `routes/`, and scheduled tasks are under `crons/`.

RBAC is database-driven. Create roles, permissions, and route/action rules through the admin module or SQL without changing the authorization engine.
