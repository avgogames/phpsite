<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DomainException;
use PDO;

/**
 * Order placement and lifecycle. Placing an order is a single transaction that locks the
 * affected product rows, re-checks stock and prices, writes the order and decrements stock,
 * so two shoppers can never buy the last item twice.
 */
final class OrderService
{
    /** Allowed status transitions. Cancelling returns stock to inventory. */
    public const TRANSITIONS = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    /**
     * @param array<int, int> $items product id => quantity
     * @param array $shipping ship_name, ship_address, ship_city, ship_postcode, ship_country, ship_phone, notes
     * @throws DomainException when the cart is empty or stock is insufficient
     */
    public function place(int $userId, array $items, array $shipping): int
    {
        $items = array_filter($items, static fn (int $qty) => $qty > 0);
        if ($items === []) {
            throw new DomainException('Your cart is empty.');
        }

        return Database::transaction(function (PDO $pdo) use ($userId, $items, $shipping): int {
            ksort($items); // lock rows in a consistent order to avoid deadlocks
            $ids = array_keys($items);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT id, sku, name, price_cents, stock FROM products
                 WHERE id IN ({$placeholders}) AND is_active ORDER BY id FOR UPDATE"
            );
            $stmt->execute($ids);
            $products = [];
            foreach ($stmt->fetchAll() as $row) {
                $products[(int) $row['id']] = $row;
            }

            $subtotal = 0;
            foreach ($items as $id => $qty) {
                $product = $products[$id] ?? null;
                if ($product === null) {
                    throw new DomainException('A product in your cart is no longer available.');
                }
                if ($product['stock'] < $qty) {
                    throw new DomainException(sprintf(
                        'Only %d of "%s" left in stock.',
                        $product['stock'],
                        $product['name'],
                    ));
                }
                $subtotal += $product['price_cents'] * $qty;
            }
            $shippingCents = Cart::shippingFor($subtotal);

            $order = $pdo->prepare(
                'INSERT INTO orders (user_id, subtotal_cents, shipping_cents, total_cents,
                    ship_name, ship_address, ship_city, ship_postcode, ship_country, ship_phone, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
            );
            $order->execute([
                $userId, $subtotal, $shippingCents, $subtotal + $shippingCents,
                $shipping['ship_name'], $shipping['ship_address'], $shipping['ship_city'],
                $shipping['ship_postcode'], $shipping['ship_country'], $shipping['ship_phone'] ?? '',
                $shipping['notes'] ?? '',
            ]);
            $orderId = (int) $order->fetchColumn();

            $line = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, sku, unit_price_cents, quantity, line_total_cents)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $decrement = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($items as $id => $qty) {
                $p = $products[$id];
                $line->execute([$orderId, $id, $p['name'], $p['sku'], $p['price_cents'], $qty, $p['price_cents'] * $qty]);
                $decrement->execute([$qty, $id]);
            }

            return $orderId;
        });
    }

    /** @throws DomainException on an invalid transition */
    public function changeStatus(int $orderId, string $newStatus): void
    {
        Database::transaction(function (PDO $pdo) use ($orderId, $newStatus): void {
            $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $current = $stmt->fetchColumn();

            if ($current === false) {
                throw new DomainException('Order not found.');
            }
            if (!in_array($newStatus, self::TRANSITIONS[$current] ?? [], true)) {
                throw new DomainException("An order cannot move from {$current} to {$newStatus}.");
            }

            if ($newStatus === 'cancelled') {
                $pdo->prepare(
                    'UPDATE products p SET stock = p.stock + oi.quantity
                     FROM order_items oi WHERE oi.order_id = ? AND oi.product_id = p.id'
                )->execute([$orderId]);
            }

            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $orderId]);
        });
    }
}
