<?php use App\Core\View; ?>
<p><a href="/account/orders">← All orders</a></p>
<div class="page-header">
    <h1>Order #<?= (int) $order['id'] ?></h1>
    <?= View::partial('status-badge', ['status' => $order['status']]) ?>
</div>
<p class="muted">Placed <?= e(date('j M Y, H:i', strtotime($order['created_at']))) ?> UTC</p>
<?php if ($order['status'] === 'pending'): ?>
    <p class="flash flash-info">We have your order. Payment instructions are on their way to <?= e($order['customer_email']) ?>.</p>
<?php endif ?>
<?= View::partial('order-detail', ['order' => $order, 'items' => $items]) ?>
