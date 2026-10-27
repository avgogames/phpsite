<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Reads configuration from a .env file, with real environment variables taking precedence.
 */
final class Config
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $envFile): void
    {
        self::$values = [];
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                self::$values[$key] = self::clean($value);
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $env = getenv($key);
        $value = $env !== false ? $env : (self::$values[$key] ?? null);
        if ($value === null) {
            return $default;
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null', '' => $default,
            default => $value,
        };
    }

    public static function int(string $key, int $default): int
    {
        return (int) self::get($key, $default);
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'production') === 'production';
    }

    private static function clean(string $value): string
    {
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            $end = strpos($value, $quote, 1);
            return $end === false ? substr($value, 1) : substr($value, 1, $end - 1);
        }
        // Strip trailing inline comments for unquoted values.
        $hash = strpos($value, ' #');
        return $hash === false ? $value : rtrim(substr($value, 0, $hash));
    }
}
