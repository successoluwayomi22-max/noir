<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// i18n — Lightweight translation helper for the web storefront.
//
// Usage:
//   __('Sign in')           → translated string
//   __('Hello, %s', $name)  → translated string with placeholder
//
// The active locale is read from $_SESSION['locale'] (default: 'en').
// Translations live in <project_root>/lang/<locale>.json.
// The language can be switched via POST /set-language { locale: 'fr' }.
// ---------------------------------------------------------------------------

/**
 * Returns the currently active locale code (e.g. 'en', 'fr', 'es').
 * Falls back to 'en' if the session locale is not set or unsupported.
 */
function current_locale(?string $override = null): string
{
    static $supported = ['en', 'fr', 'es', 'de', 'it', 'ar', 'yo', 'ha', 'ig', 'vi'];
    if ($override !== null && in_array($override, $supported, true)) {
        return $override;
    }
    $locale = (string)($_SESSION['locale'] ?? $_COOKIE['noir_locale'] ?? 'en');
    return in_array($locale, $supported, true) ? $locale : 'en';
}

/**
 * Loads and caches the translation map for the active locale.
 * Falls back to English if the locale file is not found.
 */
function _load_translations(?string $overrideLocale = null): array
{
    static $cache = [];
    $locale = $overrideLocale ?: current_locale();

    if (!isset($cache[$locale])) {
        $path = BASE_PATH . '/lang/' . $locale . '.json';
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $cache[$locale] = json_decode((string)$json, true) ?? [];
        } else {
            $cache[$locale] = [];
        }
    }

    return $cache[$locale];
}

/**
 * Translates a string into the currently active locale.
 *
 * @param string $key      The English source string (used as both key and fallback).
 * @param mixed  ...$args  Optional sprintf() arguments to interpolate into the result.
 */
function __(string $key, mixed ...$args): string
{
    $locale = current_locale();
    $map    = _load_translations($locale);
    $enMap  = $locale !== 'en' ? _load_translations('en') : $map;

    // Look up in active locale, then English fallback, then use key as-is.
    $translated = $map[$key]
        ?? ($enMap[$key] ?? null)
        ?? $key;

    // Handle sprintf substitution if args provided.
    if ($args) {
        $translated = vsprintf($translated, $args);
    }

    return $translated;
}

/**
 * Returns a <select> element for the language switcher, pre-selecting
 * the current locale. Drop this into any template.
 */
function language_switcher_html(string $publicPath = ''): string
{
    $locales = [
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'ar' => 'العربية',
        'yo' => 'Yorùbá',
        'ha' => 'Hausa',
        'ig' => 'Igbo',
        'vi' => 'Tiếng Việt',
    ];
    $current = current_locale();

    $opts = '';
    foreach ($locales as $code => $label) {
        $selected = $code === $current ? ' selected' : '';
        $opts .= '<option value="' . e($code) . '"' . $selected . '>' . e($label) . '</option>';
    }

    return '<form method="post" action="' . e($publicPath) . '/set-language" class="lang-switcher-form" style="display:inline">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<select id="footer-language-select" name="locale" onchange="this.form.submit()" aria-label="Select language">'
        . $opts
        . '</select>'
        . '</form>';
}
