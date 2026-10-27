<?php use App\Core\View; ?>
<h1>My orders</h1>
<?php if ($orders === []): ?>
    <div class="empty-state">
        <p>You have not placed any orders yet.</p>
        <a class="btn" href="/products">Start shopping</a>
    </div>
<?php else: ?>
    <table class="table">
        <thead><tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col">Items</th><th scope="col">Status</th><th scope="col">Total</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td data-label="Order"><a href="/account/orders/<?= (int) $order['id'] ?>">#<?= (int) $order['id'] ?></a></td>
                <td data-label="Date"><?= e(date('j M Y', strtotime($order['created_at']))) ?></td>
                <td data-label="Items"><?= (int) $order['item_count'] ?></td>
                <td data-label="Status"><?= View::partial('status-badge', ['status' => $order['status']]) ?></td>
                <td data-label="Total"><?= e(money($order['total_cents'])) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
