<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Services\Auth;
use App\Services\Money;

/** HTML-escape a value for output in templates. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int $cents): string
{
    return Money::format($cents);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function old(string $key, string $default = ''): string
{
    return Session::old($key, $default);
}

function error_for(string $field): string
{
    $errors = Session::errors();
    return isset($errors[$field]) ? '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>' : '';
}

function current_user(): ?array
{
    return Auth::user();
}

function app_name(): string
{
    return (string) Config::get('APP_NAME', 'Bolt & Nut Hardware');
}

/** Build a URL path with an optional, cleaned query string. */
function url(string $path, array $query = []): string
{
    $query = array_filter($query, static fn ($v) => $v !== null && $v !== '');
    return $path . ($query ? '?' . http_build_query($query) : '');
}

/** Versioned asset URL so browsers refetch after a deploy. */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . $path;
    $v = is_file($file) ? substr(md5((string) filemtime($file)), 0, 8) : '0';
    return '/assets/' . $path . '?v=' . $v;
}
