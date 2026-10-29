<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\OrderRepository;
use App\Services\Auth;

final class AccountController extends Controller
{
    public function orders(Request $request): Response
    {
        return $this->view('account/orders', [
            'title' => 'My orders',
            'orders' => (new OrderRepository())->forUser((int) Auth::user()['id']),
        ]);
    }

    public function order(Request $request): Response
    {
        $orders = new OrderRepository();
        $order = $orders->find($this->intParam($request, 'id'));

        // Respond 404 rather than 403 so order ids belonging to others are not confirmed to exist.
        if ($order === null || (int) $order['user_id'] !== (int) Auth::user()['id']) {
            throw HttpException::notFound();
        }

        return $this->view('account/order', [
            'title' => 'Order #' . $order['id'],
            'order' => $order,
            'items' => $orders->items((int) $order['id']),
        ]);
    }
}
