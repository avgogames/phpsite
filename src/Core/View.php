<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Renders plain-PHP templates from /templates inside a layout.
 * Templates must escape output with e(); data is extracted into local variables.
 */
final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            return $content;
        }

        return self::capture($layout, $data + ['content' => $content]);
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::capture('partials/' . $template, $data);
    }

    private static function capture(string $template, array $data): string
    {
        $file = BASE_PATH . '/templates/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Template not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
