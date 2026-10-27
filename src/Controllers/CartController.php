<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ProductRepository;
use App\Services\Cart;

final class CartController extends Controller
{
    public function show(Request $request): Response
    {
        $summary = (new Cart())->summary();
        if ($summary['adjusted']) {
            Session::flash('warning', 'Some items in your cart changed because of stock levels.');
            return $this->redirect('/cart');
        }

        return $this->view('cart/show', ['title' => 'Your cart', 'cart' => $summary]);
    }

    public function add(Request $request): Response
    {
        $productId = (int) $request->input('product_id', 0);
        $qty = max(1, (int) $request->input('quantity', 1));
        $product = (new ProductRepository())->findManyActive([$productId])[$productId] ?? null;

        if ($product === null) {
            Session::flash('error', 'That product is not available.');
            return $this->redirect('/products');
        }
        if ($product['stock'] <= 0) {
            Session::flash('error', "{$product['name']} is out of stock.");
            return $this->redirect('/products/' . $product['slug']);
        }

        $cart = new Cart();
        $cart->add($productId, $qty);
        Session::flash('success', "Added {$product['name']} to your cart.");

        return $this->redirect($request->input('return') === 'product' ? '/products/' . $product['slug'] : '/cart');
    }

    public function update(Request $request): Response
    {
        $cart = new Cart();
        $quantities = $request->input('quantities', []);
        if (is_array($quantities)) {
            foreach ($quantities as $productId => $qty) {
                $cart->set((int) $productId, (int) $qty);
            }
        }
        Session::flash('success', 'Cart updated.');
        return $this->redirect('/cart');
    }

    public function remove(Request $request): Response
    {
        (new Cart())->remove((int) $request->input('product_id', 0));
        Session::flash('success', 'Item removed.');
        return $this->redirect('/cart');
    }
}
