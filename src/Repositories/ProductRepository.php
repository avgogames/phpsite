<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ProductRepository
{
    public const SORTS = [
        'newest' => 'p.created_at DESC, p.id DESC',
        'price_asc' => 'p.price_cents ASC, p.id',
        'price_desc' => 'p.price_cents DESC, p.id',
        'name' => 'p.name ASC',
    ];

    public const SORT_LABELS = [
        'relevance' => 'Best match',
        'newest' => 'Newest',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'name' => 'Name',
    ];

    private const SELECT = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                            FROM products p JOIN categories c ON c.id = p.category_id';

    /**
     * Paginated storefront search over active products.
     *
     * @return array{items: list<array>, total: int}
     */
    public function search(?string $query, ?int $categoryId, string $sort, int $page, int $perPage): array
    {
        $where = ['p.is_active'];
        $params = [];
        $order = self::SORTS[$sort] ?? self::SORTS['newest'];

        if ($categoryId !== null) {
            $where[] = 'p.category_id = :category';
            $params['category'] = $categoryId;
        }
        if ($query !== null && $query !== '') {
            // Full-text match, plus a substring match on name/SKU for partial tokens like "m8".
            // Native prepares cannot reuse a named placeholder, hence the numbered names.
            $where[] = "(p.search @@ websearch_to_tsquery('english', :q1) OR p.name ILIKE :like1 OR p.sku ILIKE :like2)";
            $like = '%' . addcslashes($query, '%_\\') . '%';
            $params += ['q1' => $query, 'like1' => $like, 'like2' => $like];
        }

        $whereSql = implode(' AND ', $where);
        $pdo = Database::connection();

        $count = $pdo->prepare("SELECT count(*) FROM products p WHERE {$whereSql}");
        $count->execute($params);

        if (isset($params['q1']) && $sort === 'relevance') {
            $order = "ts_rank(p.search, websearch_to_tsquery('english', :q2)) DESC, p.name";
            $params['q2'] = $query;
        }

        $stmt = $pdo->prepare(self::SELECT . " WHERE {$whereSql} ORDER BY {$order} LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }

    public function latest(int $limit): array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE p.is_active ORDER BY p.created_at DESC, p.id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findActiveBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE p.slug = ? AND p.is_active');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE p.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** @param list<int> $ids @return array<int, array> keyed by product id */
    public function findManyActive(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(self::SELECT . " WHERE p.id IN ({$placeholders}) AND p.is_active");
        $stmt->execute(array_values($ids));

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['id']] = $row;
        }
        return $out;
    }

    public function related(array $product, int $limit = 4): array
    {
        $stmt = Database::connection()->prepare(
            self::SELECT . ' WHERE p.category_id = ? AND p.id <> ? AND p.is_active ORDER BY random() LIMIT ?'
        );
        $stmt->bindValue(1, $product['category_id'], \PDO::PARAM_INT);
        $stmt->bindValue(2, $product['id'], \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ---- Admin -------------------------------------------------------------

    /** @return array{items: list<array>, total: int} All products including inactive ones. */
    public function adminList(?string $query, int $page, int $perPage): array
    {
        $where = 'TRUE';
        $params = [];
        if ($query !== null && $query !== '') {
            $where = '(p.name ILIKE :like1 OR p.sku ILIKE :like2)';
            $like = '%' . addcslashes($query, '%_\\') . '%';
            $params = ['like1' => $like, 'like2' => $like];
        }

        $pdo = Database::connection();
        $count = $pdo->prepare("SELECT count(*) FROM products p WHERE {$where}");
        $count->execute($params);

        $stmt = $pdo->prepare(self::SELECT . " WHERE {$where} ORDER BY p.name LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO products (category_id, sku, name, slug, description, price_cents, stock, image_url, is_active)
             VALUES (:category_id, :sku, :name, :slug, :description, :price_cents, :stock, :image_url, :is_active)
             RETURNING id'
        );
        $stmt->execute($this->bindable($data));
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE products SET category_id = :category_id, sku = :sku, name = :name, slug = :slug,
                description = :description, price_cents = :price_cents, stock = :stock,
                image_url = :image_url, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute($this->bindable($data) + ['id' => $id]);
    }

    /** Hard-deletes a product that has never been ordered; otherwise deactivates it. Returns true if deleted. */
    public function deleteOrDeactivate(int $id): bool
    {
        $pdo = Database::connection();
        $ordered = $pdo->prepare('SELECT EXISTS (SELECT 1 FROM order_items WHERE product_id = ?)');
        $ordered->execute([$id]);

        if ($ordered->fetchColumn()) {
            $pdo->prepare('UPDATE products SET is_active = false WHERE id = ?')->execute([$id]);
            return false;
        }
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        return true;
    }

    public function existsWith(string $column, string $value, ?int $exceptId = null): bool
    {
        if (!in_array($column, ['sku', 'slug'], true)) {
            throw new \InvalidArgumentException('Unsupported column');
        }
        $stmt = Database::connection()->prepare("SELECT EXISTS (SELECT 1 FROM products WHERE {$column} = ? AND id <> ?)");
        $stmt->execute([$value, $exceptId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    public function lowStock(int $threshold = 5): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, sku, stock FROM products WHERE is_active AND stock <= ? ORDER BY stock, name LIMIT 10'
        );
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }

    private function bindable(array $data): array
    {
        return [
            'category_id' => $data['category_id'],
            'sku' => $data['sku'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'price_cents' => $data['price_cents'],
            'stock' => $data['stock'],
            'image_url' => $data['image_url'] ?: null,
            'is_active' => $data['is_active'] ? 'true' : 'false',
        ];
    }
}
