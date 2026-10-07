<?php
declare(strict_types=1);

/**
 * Noir & Co. Asset Minifier
 * Compiles and minifies CSS and JS files in the public/assets directory.
 */

$assetsDir = __DIR__ . '/public/assets';

function minifyCss(string $css): string {
    // 1. Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    // 2. Collapse all whitespace sequences into single space
    $css = preg_replace('/\s+/', ' ', $css);
    // 3. Remove space around delimiters
    $css = str_replace(
        ['; ', ' ;', ': ', ' :', ' {', '{ ', ' }', '} ', ' ,', ', ', ' > ', '> ', ' >'],
        [';',  ';',  ':',  ':',  '{',  '{',  '}',  '}',  ',',  ',',  '>',   '>',  '>'],
        $css
    );
    // 4. Clean trailing semicolons in blocks
    $css = str_replace(';}', '}', $css);
    return trim($css);
}

function processCssDirectory(string $dir) {
    if (!is_dir($dir)) return;
    $files = glob($dir . '/*.css');
    foreach ($files as $file) {
        if (str_ends_with($file, '.min.css')) continue;
        $content = file_get_contents($file);
        $minified = minifyCss($content);
        $minFile = str_replace('.css', '.min.css', $file);
        file_put_contents($minFile, $minified);
        
        $originalSize = filesize($file);
        $newSize = strlen($minified);
        $saved = $originalSize - $newSize;
        $percent = $originalSize > 0 ? round(($saved / $originalSize) * 100) : 0;
        echo "Minified CSS: " . basename($file) . " -> " . basename($minFile) . " (Saved $saved bytes, $percent%)\n";
    }
}

function processJsDirectory(string $dir) {
    if (!is_dir($dir)) return;
    $files = glob($dir . '/*.js');
    foreach ($files as $file) {
        if (str_ends_with($file, '.min.js') || str_contains($file, 'bundle.min')) continue;
        $minFile = str_replace('.js', '.min.js', $file);
        
        // Use Terser for maximum compression and mangling
        $escapedSource = escapeshellarg($file);
        $escapedTarget = escapeshellarg($minFile);
        $cmd = "npx terser $escapedSource --compress --mangle -o $escapedTarget 2>&1";
        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($minFile)) {
            $originalSize = filesize($file);
            $newSize = filesize($minFile);
            $saved = $originalSize - $newSize;
            $percent = $originalSize > 0 ? round(($saved / $originalSize) * 100) : 0;
            echo "Minified JS (Terser): " . basename($file) . " -> " . basename($minFile) . " (Saved $saved bytes, $percent%)\n";
        } else {
            // Regex fallback
            $content = file_get_contents($file);
            $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
            $content = preg_replace('/^\s*\/\/.*$/m', '', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            file_put_contents($minFile, trim($content));
            echo "Minified JS (Regex fallback): " . basename($file) . "\n";
        }
    }
}

echo "Starting asset minification pipeline...\n";
processCssDirectory($assetsDir . '/css');
processJsDirectory($assetsDir . '/js');
echo "Minification pipeline complete.\n";
