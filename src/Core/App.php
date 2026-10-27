<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\Auth;

/**
 * Wires the router, middleware and error pages together for one HTTP request.
 */
final class App
{
    public static function handle(Request $request): Response
    {
        Session::start();
        $router = new Router();
        self::registerMiddleware($router);
        (require BASE_PATH . '/config/routes.php')($router);

        try {
            return $router->dispatch($request);
        } catch (HttpException $e) {
            $template = in_array($e->status, [403, 404], true) ? 'errors/' . $e->status : 'errors/generic';
            return Response::html(View::render($template, [
                'title' => $e->getMessage() ?: 'Error',
                'status' => $e->status,
            ]), $e->status);
        }
    }

    /** Headers sent on every response. The CSP allows only same-origin scripts and styles. */
    public static function securityHeaders(): void
    {
        header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: DENY');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        if (str_starts_with((string) Config::get('APP_URL', ''), 'https://')) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private static function registerMiddleware(Router $router): void
    {
        $router->middleware('csrf', static function (Request $request): ?Response {
            if (!Csrf::verify($request->input('_token'))) {
                throw new HttpException(419, 'Your session expired. Please go back, refresh the page and try again.');
            }
            return null;
        });

        $router->middleware('auth', static function (Request $request): ?Response {
            if (Auth::check()) {
                return null;
            }
            if ($request->method === 'GET') {
                Session::set('intended', $request->path);
            }
            Session::flash('warning', 'Please sign in to continue.');
            return Response::redirect('/login');
        });

        $router->middleware('guest', static fn (): ?Response => Auth::check() ? Response::redirect('/') : null);

        $router->middleware('admin', static function (Request $request): ?Response {
            if (!Auth::check()) {
                Session::set('intended', $request->path);
                return Response::redirect('/login');
            }
            if (!Auth::isAdmin()) {
                throw HttpException::forbidden();
            }
            return null;
        });
    }
}
