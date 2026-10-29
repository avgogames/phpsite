<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function router(): Router
    {
        $router = new Router();
        $router->middleware('csrf', static fn () => null);
        $router->middleware('block', static fn () => Response::redirect('/login'));
        $router->get('/products/{slug}', [EchoController::class, 'slug']);
        $router->get('/secret', [EchoController::class, 'slug'], ['block']);
        return $router;
    }

    public function testMatchesParameters(): void
    {
        $response = $this->router()->dispatch(new Request('GET', '/products/hex-bolts', [], [], '127.0.0.1'));
        self::assertSame('hex-bolts', $response->body);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $response = $this->router()->dispatch(new Request('GET', '/secret', [], [], '127.0.0.1'));
        self::assertSame(303, $response->status);
    }

    public function testUnknownPathIs404AndWrongMethodIs405(): void
    {
        try {
            $this->router()->dispatch(new Request('GET', '/nope', [], [], '127.0.0.1'));
            self::fail('Expected 404');
        } catch (HttpException $e) {
            self::assertSame(404, $e->status);
        }

        $this->expectExceptionCode(405);
        $this->router()->dispatch(new Request('POST', '/products/x', [], [], '127.0.0.1'));
    }

    public function testParametersCannotContainSlashesOrDots(): void
    {
        $this->expectExceptionCode(404);
        $this->router()->dispatch(new Request('GET', '/products/../etc', [], [], '127.0.0.1'));
    }
}

final class EchoController
{
    public function slug(Request $request): Response
    {
        return new Response($request->param('slug'));
    }
}
