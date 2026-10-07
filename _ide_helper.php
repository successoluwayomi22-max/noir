<?php
/**
 * IDE Helper Stubs – auto-generated for Intelephense / PhpStorm
 *
 * This file is never included at runtime; it exists solely so that the
 * language server can resolve global helper functions, superglobals, and
 * the router variable used across route files.
 *
 * @noinspection ALL
 */

// ── Router instance (used in routes/*.php) ────────────────────────────────────
/** @var \Noir\Core\Router $router */
$router = new \Noir\Core\Router();

// ── core/helpers.php ──────────────────────────────────────────────────────────

/**
 * Retrieve a configuration value by dot-key.
 */
function config(?string $key = null, mixed $default = null): mixed {}

/**
 * Retrieve a store setting from the database.
 */
function store_setting(string $key, string $default = ''): string {}

/**
 * Send a JSON response and terminate.
 * @return never
 */
function json(array $payload, int $status = 200): never { exit; }

/**
 * Get the current CSRF token.
 */
function csrf_token(): string {}

/**
 * HTML-escape a value.
 */
function e(mixed $value): string {}

/**
 * Generate a URL-safe slug from a string.
 */
function catalogue_slug(string $value): string {}

/**
 * Get the currently active currency code (e.g. 'NGN', 'USD').
 */
function active_currency(): string {}

/**
 * Get the symbol for a given currency code.
 */
function currency_symbol(?string $currency = null): string {}

/**
 * Format a monetary amount in the active (or specified) currency.
 */
function format_currency(mixed $amount, ?string $currency = null): string {}

/**
 * Fetch real-time exchange rate for a currency code.
 */
function get_exchange_rate(?string $currency = null): float {}

/**
 * Render the currency-switcher HTML dropdown.
 */
function currency_switcher_html(string $publicPath = ''): string {}

/**
 * Normalize legacy encodings to UTF-8.
 */
function normalize_legacy_encoding(string $output): string {}

// ── core/i18n.php ─────────────────────────────────────────────────────────────

/**
 * Get the current locale code (e.g. 'en', 'fr').
 */
function current_locale(?string $override = null): string {}

/**
 * Translate a key using the active locale.
 */
function __(string $key, mixed ...$args): string {}

/**
 * Render the language-switcher HTML dropdown.
 */
function language_switcher_html(string $publicPath = ''): string {}

// ── core/media.php ────────────────────────────────────────────────────────────

/**
 * Resolve a media URL.
 */
function media_url(string $base, ?string $path): string {}

/**
 * Resolve an asset URL with cache busting.
 */
function asset_url(string $publicBase, string $path): string {}

/**
 * Check whether an image path points to a legacy catalogue placeholder.
 */
function is_legacy_catalogue_image(?string $path): bool {}

/**
 * Get a CSS tint filter string for a product image.
 */
function product_image_tint(array $product): string {}

/**
 * Get the best available catalogue image for a product.
 */
function catalogue_image_for(array $product): string {}

/**
 * Get a representative image for a collection by name.
 */
function collection_image_for(string $name): string {}

// ── core/branding.php ─────────────────────────────────────────────────────────

/**
 * Write the PWA web manifest to disk.
 */
function sync_store_manifest(string $name, string $paper, string $dark): void {}

/**
 * Render the store logo <img> tag.
 */
function store_logo_tag(string $base, string $alt = ''): string {}

/**
 * Render the user avatar for the account dropdown.
 */
function store_account_avatar(string $base, array $user): string {}

/**
 * Choose a contrasting text color for a given background hex.
 */
function brand_contrast(string $hex, string $light = '#fff', string $dark = '#252820'): string {}

/**
 * Output the inline <style> block with brand CSS custom properties.
 */
function store_brand_style(): string {}

/**
 * Output the AI assistant <link> + <script> tags.
 */
function ai_assistant_assets(string $publicBase): string {}

// ── Constants ─────────────────────────────────────────────────────────────────

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}
