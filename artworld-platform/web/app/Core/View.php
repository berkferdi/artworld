<?php

declare(strict_types=1);

namespace Web\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        $viewFile = WEB_VIEWS . '/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean() ?: '';

        if ($layout === '') {
            echo $content;
            return;
        }

        $layoutFile = WEB_VIEWS . '/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Layout not found: ' . $layout);
        }
        require $layoutFile;
    }

    /** @param array<string, mixed> $data */
    public static function partial(string $partial, array $data = []): void
    {
        $file = WEB_VIEWS . '/' . $partial . '.php';
        if (!is_file($file)) {
            return;
        }
        extract($data, EXTR_SKIP);
        require $file;
    }
}
