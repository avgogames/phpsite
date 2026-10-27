<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Converts PHP errors to exceptions, logs uncaught exceptions and shows a safe error page.
 */
final class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug): void
    {
        self::$debug = $debug;
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(Throwable $e): void
    {
        Logger::error($e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, (string) $e . PHP_EOL);
            exit(1);
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        try {
            echo View::render('errors/500', [
                'title' => 'Something went wrong',
                'detail' => self::$debug ? (string) $e : null,
            ], 'layouts/minimal');
        } catch (Throwable) {
            echo 'Internal Server Error';
        }
    }
}
