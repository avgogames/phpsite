<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Thrown to short-circuit a request with an HTTP error status (404, 403, 419, ...).
 */
final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }

    public static function notFound(): self
    {
        return new self(404, 'Page not found');
    }

    public static function forbidden(): self
    {
        return new self(403, 'You do not have access to this page');
    }
}
