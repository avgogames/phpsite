<?php

declare(strict_types=1);

/*
 * Route table. Middleware: "auth" requires a signed-in user, "guest" a signed-out one,
 * "admin" an admin. Every POST route is CSRF-checked automatically by the router.
 */

use App\Controllers\AccountController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\ProductController as AdminProductController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CatalogController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Core\Router;

return static function (Router $r): void {
    // Storefront
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/products', [CatalogController::class, 'index']);
    $r->get('/products/{slug}', [CatalogController::class, 'show']);

    // Cart and checkout
    $r->get('/cart', [CartController::class, 'show']);
    $r->post('/cart/add', [CartController::class, 'add']);
    $r->post('/cart/update', [CartController::class, 'update']);
    $r->post('/cart/remove', [CartController::class, 'remove']);
    $r->get('/checkout', [CheckoutController::class, 'show'], ['auth']);
    $r->post('/checkout', [CheckoutController::class, 'place'], ['auth']);

    // Accounts
    $r->get('/login', [AuthController::class, 'showLogin'], ['guest']);
    $r->post('/login', [AuthController::class, 'login'], ['guest']);
    $r->get('/register', [AuthController::class, 'showRegister'], ['guest']);
    $r->post('/register', [AuthController::class, 'register'], ['guest']);
    $r->post('/logout', [AuthController::class, 'logout'], ['auth']);
    $r->get('/account/orders', [AccountController::class, 'orders'], ['auth']);
    $r->get('/account/orders/{id}', [AccountController::class, 'order'], ['auth']);

    // Admin
    $r->get('/admin', [DashboardController::class, 'index'], ['admin']);
    $r->get('/admin/products', [AdminProductController::class, 'index'], ['admin']);
    $r->get('/admin/products/new', [AdminProductController::class, 'create'], ['admin']);
    $r->post('/admin/products', [AdminProductController::class, 'store'], ['admin']);
    $r->get('/admin/products/{id}/edit', [AdminProductController::class, 'edit'], ['admin']);
    $r->post('/admin/products/{id}', [AdminProductController::class, 'update'], ['admin']);
    $r->post('/admin/products/{id}/delete', [AdminProductController::class, 'delete'], ['admin']);
    $r->get('/admin/orders', [AdminOrderController::class, 'index'], ['admin']);
    $r->get('/admin/orders/{id}', [AdminOrderController::class, 'show'], ['admin']);
    $r->post('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus'], ['admin']);
};
