<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use DomainException;

final class OrderController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $status = array_key_exists($status, OrderService::TRANSITIONS) ? $status : null;
        $page = $this->page($request);
        $result = (new OrderRepository())->adminList($status, $page, self::PER_PAGE);

        return $this->view('admin/orders/index', [
            'title' => 'Orders',
            'orders' => $result['items'],
            'status' => $status,
            'pagination' => [
                'page' => $page,
                'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
                'total' => $result['total'],
                'query' => ['status' => $status],
            ],
        ], layout: 'layouts/admin');
    }

    public function show(Request $request): Response
    {
        $orders = new OrderRepository();
        $order = $orders->find($this->intParam($request, 'id'));
        if ($order === null) {
            throw HttpException::notFound();
        }

        return $this->view('admin/orders/show', [
            'title' => 'Order #' . $order['id'],
            'order' => $order,
            'items' => $orders->items((int) $order['id']),
            'nextStatuses' => OrderService::TRANSITIONS[$order['status']],
        ], layout: 'layouts/admin');
    }

    public function updateStatus(Request $request): Response
    {
        $id = $this->intParam($request, 'id');
        try {
            (new OrderService())->changeStatus($id, (string) $request->input('status'));
            Session::flash('success', "Order #{$id} updated.");
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect("/admin/orders/{$id}");
    }
}
