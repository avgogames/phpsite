<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class OrderRepository
{
    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT o.*, u.email AS customer_email, u.name AS customer_name
             FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function items(int $orderId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT oi.*, p.slug AS product_slug FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ? ORDER BY oi.id'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT o.*, (SELECT sum(quantity) FROM order_items WHERE order_id = o.id) AS item_count
             FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array{items: list<array>, total: int} */
    public function adminList(?string $status, int $page, int $perPage): array
    {
        $where = $status ? 'WHERE o.status = :status' : '';
        $params = $status ? ['status' => $status] : [];
        $pdo = Database::connection();

        $count = $pdo->prepare("SELECT count(*) FROM orders o {$where}");
        $count->execute($params);

        $stmt = $pdo->prepare(
            "SELECT o.*, u.email AS customer_email FROM orders o JOIN users u ON u.id = o.user_id
             {$where} ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }

    /** Headline numbers for the admin dashboard. */
    public function stats(): array
    {
        return Database::connection()->query(
            "SELECT
                count(*) AS orders_total,
                count(*) FILTER (WHERE status = 'pending') AS orders_pending,
                coalesce(sum(total_cents) FILTER (WHERE status <> 'cancelled'), 0) AS revenue_cents,
                coalesce(sum(total_cents) FILTER (WHERE status <> 'cancelled' AND created_at > now() - interval '30 days'), 0) AS revenue_30d_cents
             FROM orders"
        )->fetch();
    }
}
