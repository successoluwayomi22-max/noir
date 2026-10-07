<?php
declare(strict_types=1);

namespace Noir\Core;

/**
 * Renders a PHP view file with an explicit, named set of variables.
 *
 * Replaces the use of `extract()` + `require` throughout controllers,
 * making every variable that flows into a view visible at the call site.
 *
 * Usage:
 *   ViewData::render(BASE_PATH . '/app/Views/store/shop.php', [
 *       'catalog'    => $catalog,
 *       'pagination' => $pagination,
 *   ]);
 */
final class ViewData
{
    /**
     * @param array<string, mixed> $data Variables to expose inside the view.
     */
    public static function render(string $viewPath, array $data = []): never
    {
        // Use a closure to create an isolated scope — the view cannot reach
        // variables declared in this method (e.g. $viewPath, $data itself).
        (static function (string $__view, array $__vars): void {
            extract($__vars, EXTR_SKIP);
            require $__view;
        })($viewPath, $data);

        exit;
    }
}
