<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Services\Money;

final class ProductController extends Controller
{
    private const PER_PAGE = 25;
    private const FIELDS = ['name', 'sku', 'slug', 'category_id', 'price', 'stock', 'description', 'image_url', 'is_active'];

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $page = $this->page($request);
        $result = (new ProductRepository())->adminList($q, $page, self::PER_PAGE);

        return $this->view('admin/products/index', [
            'title' => 'Products',
            'products' => $result['items'],
            'q' => $q,
            'pagination' => [
                'page' => $page,
                'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
                'total' => $result['total'],
                'query' => ['q' => $q],
            ],
        ], layout: 'layouts/admin');
    }

    public function create(Request $request): Response
    {
        return $this->form(null, 'New product');
    }

    public function store(Request $request): Response
    {
        [$data, $errors] = $this->validated($request, null);
        if ($errors) {
            return $this->backWithErrors('/admin/products/new', $request->only(self::FIELDS), $errors);
        }

        $id = (new ProductRepository())->create($data);
        Session::flash('success', "Created {$data['name']}.");
        return $this->redirect("/admin/products/{$id}/edit");
    }

    public function edit(Request $request): Response
    {
        $product = (new ProductRepository())->find($this->intParam($request, 'id'));
        if ($product === null) {
            throw HttpException::notFound();
        }
        return $this->form($product, 'Edit ' . $product['name']);
    }

    public function update(Request $request): Response
    {
        $id = $this->intParam($request, 'id');
        $products = new ProductRepository();
        if ($products->find($id) === null) {
            throw HttpException::notFound();
        }

        [$data, $errors] = $this->validated($request, $id);
        if ($errors) {
            return $this->backWithErrors("/admin/products/{$id}/edit", $request->only(self::FIELDS), $errors);
        }

        $products->update($id, $data);
        Session::flash('success', "Saved {$data['name']}.");
        return $this->redirect("/admin/products/{$id}/edit");
    }

    public function delete(Request $request): Response
    {
        $products = new ProductRepository();
        $product = $products->find($this->intParam($request, 'id'));
        if ($product === null) {
            throw HttpException::notFound();
        }

        $deleted = $products->deleteOrDeactivate((int) $product['id']);
        Session::flash('success', $deleted
            ? "Deleted {$product['name']}."
            : "{$product['name']} has past orders, so it was hidden from the store instead of deleted.");

        return $this->redirect('/admin/products');
    }

    private function form(?array $product, string $title): Response
    {
        return $this->view('admin/products/form', [
            'title' => $title,
            'product' => $product,
            'categories' => (new CategoryRepository())->allWithCounts(),
        ], layout: 'layouts/admin');
    }

    /** @return array{0: array, 1: array<string, string>} normalized data and errors */
    private function validated(Request $request, ?int $id): array
    {
        $input = $request->only(self::FIELDS);
        $input['slug'] = $input['slug'] !== '' ? mb_strtolower($input['slug']) : self::slugify($input['name']);
        $input['sku'] = strtoupper($input['sku']);

        $errors = Validator::validate($input, [
            'name' => 'required|max:200',
            'sku' => 'required|max:64',
            'slug' => 'required|slug|max:200',
            'category_id' => 'required|int',
            'price' => 'required|money',
            'stock' => 'required|int|min_value:0',
            'description' => 'max:5000',
            'image_url' => 'url|max:500',
        ]);

        $products = new ProductRepository();
        if (!isset($errors['sku']) && $products->existsWith('sku', $input['sku'], $id)) {
            $errors['sku'] = 'Another product already uses this SKU.';
        }
        if (!isset($errors['slug']) && $products->existsWith('slug', $input['slug'], $id)) {
            $errors['slug'] = 'Another product already uses this URL slug.';
        }
        $categoryIds = array_map('intval', array_column((new CategoryRepository())->allWithCounts(), 'id'));
        if (!isset($errors['category_id']) && !in_array((int) $input['category_id'], $categoryIds, true)) {
            $errors['category_id'] = 'Choose a category.';
        }

        if ($errors) {
            return [[], $errors];
        }

        return [[
            'name' => $input['name'],
            'sku' => $input['sku'],
            'slug' => $input['slug'],
            'category_id' => (int) $input['category_id'],
            'price_cents' => Money::toCents($input['price']),
            'stock' => (int) $input['stock'],
            'description' => $input['description'],
            'image_url' => $input['image_url'],
            'is_active' => $input['is_active'] === '1',
        ], []];
    }

    public static function slugify(string $text): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
        return mb_substr($slug, 0, 200);
    }
}
