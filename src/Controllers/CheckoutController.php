<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\Auth;
use App\Services\Cart;
use App\Services\OrderService;
use DomainException;

final class CheckoutController extends Controller
{
    private const FIELDS = ['ship_name', 'ship_address', 'ship_city', 'ship_postcode', 'ship_country', 'ship_phone', 'notes'];

    private const RULES = [
        'ship_name' => 'required|max:120',
        'ship_address' => 'required|max:255',
        'ship_city' => 'required|max:120',
        'ship_postcode' => 'required|max:20',
        'ship_country' => 'required|max:80',
        'ship_phone' => 'max:40',
        'notes' => 'max:500',
    ];

    public function show(Request $request): Response
    {
        $summary = (new Cart())->summary();
        if ($summary['lines'] === []) {
            Session::flash('warning', 'Your cart is empty.');
            return $this->redirect('/cart');
        }

        return $this->view('checkout/show', [
            'title' => 'Checkout',
            'cart' => $summary,
            'user' => Auth::user(),
        ]);
    }

    public function place(Request $request): Response
    {
        $input = $request->only(self::FIELDS);
        $errors = Validator::validate($input, self::RULES);
        if ($errors) {
            return $this->backWithErrors('/checkout', $input, $errors);
        }

        $cart = new Cart();
        try {
            $orderId = (new OrderService())->place((int) Auth::user()['id'], $cart->raw(), $input);
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect('/cart');
        }

        $cart->clear();
        Logger::info('Order placed', ['order_id' => $orderId, 'user_id' => Auth::user()['id']]);
        Session::flash('success', 'Thank you! Your order has been placed.');

        return $this->redirect('/account/orders/' . $orderId);
    }
}
