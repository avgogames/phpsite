<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable view of the current HTTP request.
 */
final class Request
{
    /** @var array<string, string> Route parameters filled in by the router. */
    public array $params = [];

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $body,
        public readonly string $ip,
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $_POST,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        );
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /** @return array<string, mixed> */
    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->input($key, '');
        }
        return $out;
    }

    public function param(string $key): string
    {
        return $this->params[$key] ?? '';
    }
}
