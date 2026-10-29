<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hardened PHP session wrapper with flash messages and "old input" for forms.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = str_starts_with((string) Config::get('APP_URL', ''), 'https://');
        session_name('bnsid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        // Flash data lives for exactly one subsequent request.
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        $_SESSION['_flash_next'] = [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Issue a new session id, e.g. after login, to prevent session fixation. */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            session_destroy();
        }
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash_next']['messages'][] = ['type' => $type, 'text' => $message];
    }

    /** @return list<array{type: string, text: string}> */
    public static function messages(): array
    {
        return $_SESSION['_flash_now']['messages'] ?? [];
    }

    public static function flashInput(array $input, array $errors): void
    {
        unset($input['password'], $input['password_confirmation'], $input['_token']);
        $_SESSION['_flash_next']['old'] = $input;
        $_SESSION['_flash_next']['errors'] = $errors;
    }

    public static function old(string $key, string $default = ''): string
    {
        return (string) ($_SESSION['_flash_now']['old'][$key] ?? $default);
    }

    /** @return array<string, string> */
    public static function errors(): array
    {
        return $_SESSION['_flash_now']['errors'] ?? [];
    }
}
