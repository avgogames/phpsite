<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\UserRepository;
use App\Services\OrderService;
use DomainException;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Runs against DB_NAME (hardware_store_test by default, see phpunit.xml).
 * The schema is rebuilt from the migrations once per run.
 */
final class OrderServiceTest extends TestCase
{
    private static bool $migrated = false;
    private PDO $pdo;
    private int $userId;

    private const SHIPPING = [
        'ship_name' => 'Test', 'ship_address' => '1 Test St', 'ship_city' => 'Testville',
        'ship_postcode' => '00000', 'ship_country' => 'Testland',
    ];

    protected function setUp(): void
    {
        // This test wipes the schema, so refuse to run against anything but a *_test database.
        if (!str_ends_with((string) getenv('DB_NAME'), '_test')) {
            self::markTestSkipped('Set DB_NAME to a database ending in _test to run integration tests.');
        }
        $this->pdo = Database::connection();
        if (!self::$migrated) {
            $this->pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');
            passthru('DB_NAME=' . escapeshellarg((string) getenv('DB_NAME')) . ' php ' . BASE_PATH . '/bin/migrate.php > /dev/null', $code);
            self::assertSame(0, $code, 'migrations failed');
            self::$migrated = true;
        }
        $this->pdo->exec('TRUNCATE order_items, orders, products, categories, users RESTART IDENTITY CASCADE');
        $this->pdo->exec("INSERT INTO categories (name, slug) VALUES ('Screws', 'screws')");
        $this->pdo->exec("INSERT INTO products (category_id, sku, name, slug, price_cents, stock) VALUES
            (1, 'A', 'Screw A', 'screw-a', 1000, 5),
            (1, 'B', 'Screw B', 'screw-b', 250, 2)");
        $this->userId = (new UserRepository())->create('t@example.com', 'T', 'x');
    }

    public function testPlacesOrderSnapshotsPricesAndDecrementsStock(): void
    {
        $orderId = (new OrderService())->place($this->userId, [1 => 2, 2 => 1], self::SHIPPING);

        $order = $this->pdo->query("SELECT * FROM orders WHERE id = {$orderId}")->fetch();
        self::assertSame(2250, $order['subtotal_cents']);
        self::assertSame(599, $order['shipping_cents']);
        self::assertSame(2849, $order['total_cents']);

        $stock = $this->pdo->query('SELECT stock FROM products ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        self::assertSame([3, 1], $stock);

        // Later price changes must not alter the order.
        $this->pdo->exec('UPDATE products SET price_cents = 1 WHERE id = 1');
        $line = $this->pdo->query("SELECT unit_price_cents FROM order_items WHERE order_id = {$orderId} AND product_id = 1")->fetchColumn();
        self::assertSame(1000, $line);
    }

    public function testFreeShippingAboveThreshold(): void
    {
        $orderId = (new OrderService())->place($this->userId, [1 => 5], self::SHIPPING);
        self::assertSame(0, $this->pdo->query("SELECT shipping_cents FROM orders WHERE id = {$orderId}")->fetchColumn());
    }

    public function testRejectsInsufficientStockWithoutSideEffects(): void
    {
        try {
            (new OrderService())->place($this->userId, [1 => 1, 2 => 3], self::SHIPPING);
            self::fail('Expected DomainException');
        } catch (DomainException $e) {
            self::assertStringContainsString('Only 2 of "Screw B"', $e->getMessage());
        }

        self::assertSame(0, $this->pdo->query('SELECT count(*) FROM orders')->fetchColumn());
        self::assertSame(5, $this->pdo->query('SELECT stock FROM products WHERE id = 1')->fetchColumn());
    }

    public function testRejectsInactiveProducts(): void
    {
        $this->pdo->exec('UPDATE products SET is_active = false WHERE id = 1');
        $this->expectException(DomainException::class);
        (new OrderService())->place($this->userId, [1 => 1], self::SHIPPING);
    }

    public function testCancellingRestocksAndTransitionsAreEnforced(): void
    {
        $service = new OrderService();
        $orderId = $service->place($this->userId, [1 => 4], self::SHIPPING);
        $service->changeStatus($orderId, 'paid');

        try {
            $service->changeStatus($orderId, 'delivered');
            self::fail('paid -> delivered should be refused');
        } catch (DomainException) {
        }

        $service->changeStatus($orderId, 'cancelled');
        self::assertSame(5, $this->pdo->query('SELECT stock FROM products WHERE id = 1')->fetchColumn());

        $this->expectException(DomainException::class);
        $service->changeStatus($orderId, 'paid');
    }
}
