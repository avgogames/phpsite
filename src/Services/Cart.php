<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Session;
use App\Repositories\ProductRepository;

/**
 * Session-backed shopping cart. Only product ids and quantities live in the session;
 * names, prices and stock are always re-read from the database so they are never stale or tampered with.
 */
final class Cart
{
    public const MAX_QTY_PER_LINE = 99;
    private const KEY = 'cart';

    /** @return array<int, int> product id => quantity */
    public function raw(): array
    {
        return Session::get(self::KEY, []);
    }

    public function add(int $productId, int $qty): void
    {
        $items = $this->raw();
        $items[$productId] = min(self::MAX_QTY_PER_LINE, ($items[$productId] ?? 0) + max(1, $qty));
        Session::set(self::KEY, $items);
    }

    public function set(int $productId, int $qty): void
    {
        $items = $this->raw();
        if ($qty <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = min(self::MAX_QTY_PER_LINE, $qty);
        }
        Session::set(self::KEY, $items);
    }

    public function remove(int $productId): void
    {
        $this->set($productId, 0);
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /**
     * Resolves the cart against current catalog data.
     * Drops products that no longer exist or are inactive, and caps quantities at available stock.
     *
     * @return array{lines: list<array>, subtotal_cents: int, shipping_cents: int, total_cents: int, adjusted: bool}
     */
    public function summary(): array
    {
        $raw = $this->raw();
        $products = (new ProductRepository())->findManyActive(array_keys($raw));
        $lines = [];
        $subtotal = 0;
        $adjusted = false;

        foreach ($raw as $id => $qty) {
            $product = $products[$id] ?? null;
            if ($product === null || $product['stock'] <= 0) {
                $adjusted = true;
                $this->remove($id);
                continue;
            }
            if ($qty > $product['stock']) {
                $qty = (int) $product['stock'];
                $adjusted = true;
                $this->set($id, $qty);
            }

            $lineTotal = $product['price_cents'] * $qty;
            $subtotal += $lineTotal;
            $lines[] = ['product' => $product, 'quantity' => $qty, 'line_total_cents' => $lineTotal];
        }

        $shipping = self::shippingFor($subtotal);

        return [
            'lines' => $lines,
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'total_cents' => $subtotal + $shipping,
            'adjusted' => $adjusted,
        ];
    }

    public static function shippingFor(int $subtotalCents): int
    {
        if ($subtotalCents === 0 || $subtotalCents >= Config::int('FREE_SHIPPING_THRESHOLD_CENTS', 5000)) {
            return 0;
        }
        return Config::int('SHIPPING_FLAT_CENTS', 599);
    }
}
