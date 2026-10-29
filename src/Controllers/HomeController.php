<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home/index', [
            'title' => 'Small hardware, delivered',
            'categories' => (new CategoryRepository())->allWithCounts(),
            'products' => (new ProductRepository())->latest(8),
        ]);
    }
}
