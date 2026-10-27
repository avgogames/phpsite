<?php

use App\Core\View;
use App\Services\OrderService;

?>
<h1>Orders</h1>
<nav class="tabs" aria-label="Filter by status">
    <a href="/admin/orders"<?= $status === null ? ' aria-current="page"' : '' ?>>All</a>
    <?php foreach (array_keys(OrderService::TRANSITIONS) as $s): ?>
        <a href="<?= e(url('/admin/orders', ['status' => $s])) ?>"<?= $status === $s ? ' aria-current="page"' : '' ?>><?= e(ucfirst($s)) ?></a>
    <?php endforeach ?>
</nav>

<?php if ($orders === []): ?>
    <p class="muted">No orders<?= $status ? ' with status ' . e($status) : '' ?>.</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col">Customer</th><th scope="col">Status</th><th scope="col">Total</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td data-label="Order"><a href="/admin/orders/<?= (int) $order['id'] ?>">#<?= (int) $order['id'] ?></a></td>
                <td data-label="Date"><?= e(date('j M Y H:i', strtotime($order['created_at']))) ?></td>
                <td data-label="Customer"><?= e($order['customer_email']) ?></td>
                <td data-label="Status"><?= View::partial('status-badge', ['status' => $order['status']]) ?></td>
                <td data-label="Total"><?= e(money($order['total_cents'])) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <?= View::partial('pagination', ['pagination' => $pagination, 'base' => '/admin/orders']) ?>
<?php endif ?>
