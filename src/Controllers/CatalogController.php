<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

final class CatalogController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request): Response
    {
        $categories = new CategoryRepository();
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $sort = (string) $request->query('sort', $q !== '' ? 'relevance' : 'newest');
        $slug = (string) $request->query('category', '');
        $category = $slug !== '' ? $categories->findBySlug($slug) : null;
        if ($slug !== '' && $category === null) {
            throw HttpException::notFound();
        }

        $page = $this->page($request);
        $result = (new ProductRepository())->search($q, $category['id'] ?? null, $sort, $page, self::PER_PAGE);

        return $this->view('catalog/index', [
            'title' => $category['name'] ?? ($q !== '' ? "Search: {$q}" : 'All products'),
            'categories' => $categories->allWithCounts(),
            'category' => $category,
            'products' => $result['items'],
            'q' => $q,
            'sort' => $sort,
            'pagination' => [
                'page' => $page,
                'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
                'total' => $result['total'],
                'query' => ['q' => $q, 'category' => $slug, 'sort' => $request->query('sort')],
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $products = new ProductRepository();
        $product = $products->findActiveBySlug($request->param('slug'));
        if ($product === null) {
            throw HttpException::notFound();
        }

        return $this->view('catalog/show', [
            'title' => $product['name'],
            'metaDescription' => mb_substr($product['description'], 0, 155),
            'product' => $product,
            'related' => $products->related($product),
        ]);
    }
}
