<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal JSON-lines logger writing to storage/logs/app.log.
 */
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $line = json_encode([
            'time' => gmdate('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES) . PHP_EOL;

        $dir = BASE_PATH . '/storage/logs';
        if (is_dir($dir) && is_writable($dir)) {
            file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
        } else {
            error_log(trim($line));
        }
    }
}
