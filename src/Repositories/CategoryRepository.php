<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class CategoryRepository
{
    /** @return list<array> Categories with the number of active products in each. */
    public function allWithCounts(): array
    {
        return Database::connection()->query(
            'SELECT c.*, count(p.id) FILTER (WHERE p.is_active) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id
             ORDER BY c.sort_order, c.name'
        )->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }
}
