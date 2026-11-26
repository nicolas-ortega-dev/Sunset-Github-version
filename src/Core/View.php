<?php
declare(strict_types=1);

namespace Sunset\Core;

final class View
{
    /** Renderiza views/{$view}.php dentro de views/layouts/{$layout}.php */
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $root = ROOT_PATH . '/views';
        extract($data, EXTR_SKIP);

        ob_start();
        require $root . '/' . $view . '.php';
        $content = ob_get_clean();

        require $root . '/layouts/' . $layout . '.php';
    }

    /** Devuelve el HTML de una vista parcial (sin layout), para respuestas AJAX. */
    public static function partial(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require ROOT_PATH . '/views/' . $view . '.php';
        return (string) ob_get_clean();
    }
}
