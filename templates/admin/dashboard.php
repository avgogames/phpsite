<?php use App\Core\View; ?>
<h1>Dashboard</h1>
<div class="stat-grid">
    <div class="stat card"><span class="stat-label">Revenue (30 days)</span><span class="stat-value"><?= e(money($stats['revenue_30d_cents'])) ?></span></div>
    <div class="stat card"><span class="stat-label">Revenue (all time)</span><span class="stat-value"><?= e(money($stats['revenue_cents'])) ?></span></div>
    <div class="stat card"><span class="stat-label">Pending orders</span><span class="stat-value"><?= (int) $stats['orders_pending'] ?></span></div>
    <div class="stat card"><span class="stat-label">Customers</span><span class="stat-value"><?= (int) $stats['customers'] ?></span></div>
</div>

<div class="admin-columns">
    <section class="card">
        <h2>Recent orders</h2>
        <?php if ($recentOrders === []): ?>
            <p class="muted">No orders yet.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Status</th><th scope="col">Total</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td data-label="Order"><a href="/admin/orders/<?= (int) $order['id'] ?>">#<?= (int) $order['id'] ?></a></td>
                        <td data-label="Customer"><?= e($order['customer_email']) ?></td>
                        <td data-label="Status"><?= View::partial('status-badge', ['status' => $order['status']]) ?></td>
                        <td data-label="Total"><?= e(money($order['total_cents'])) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
        <a href="/admin/orders">All orders →</a>
    </section>

    <section class="card">
        <h2>Low stock</h2>
        <?php if ($lowStock === []): ?>
            <p class="muted">Everything is well stocked.</p>
        <?php else: ?>
            <ul class="plain-list">
                <?php foreach ($lowStock as $p): ?>
                    <li>
                        <a href="/admin/products/<?= (int) $p['id'] ?>/edit"><?= e($p['name']) ?></a>
                        <span class="stock <?= $p['stock'] === 0 ? 'stock-out' : 'stock-low' ?>"><?= (int) $p['stock'] ?> left</span>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>
</div>
