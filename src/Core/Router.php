<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Maps METHOD + path patterns such as /products/{slug} to controller actions,
 * and runs the named middleware for each route before the action.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: array{class-string, string}, middleware: list<string>}> */
    private array $routes = [];

    /** @var array<string, callable(Request): ?Response> */
    private array $middleware = [];

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /** @param callable(Request): ?Response $fn Returns a Response to stop the request, or null to continue. */
    public function middleware(string $name, callable $fn): void
    {
        $this->middleware[$name] = $fn;
    }

    public function dispatch(Request $request): Response
    {
        $methodAllowed = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            if ($route['method'] !== $request->method) {
                $methodAllowed = true;
                continue;
            }

            $request->params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Every state-changing request is CSRF-checked, whatever the route declares.
            $chain = $request->method === 'POST' ? ['csrf', ...$route['middleware']] : $route['middleware'];
            foreach ($chain as $name) {
                $result = ($this->middleware[$name])($request);
                if ($result instanceof Response) {
                    return $result;
                }
            }

            [$class, $action] = $route['handler'];
            return (new $class())->$action($request);
        }

        throw $methodAllowed ? new HttpException(405, 'Method not allowed') : HttpException::notFound();
    }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[A-Za-z0-9_-]+)', $pattern);
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }
}
