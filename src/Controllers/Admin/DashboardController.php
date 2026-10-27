<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = new OrderRepository();

        return $this->view('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => $orders->stats() + ['customers' => (new UserRepository())->count()],
            'recentOrders' => $orders->adminList(null, 1, 5)['items'],
            'lowStock' => (new ProductRepository())->lowStock(),
        ], layout: 'layouts/admin');
    }
}
