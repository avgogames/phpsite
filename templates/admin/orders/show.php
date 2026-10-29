<?php use App\Core\View; ?>
<p><a href="/admin/orders">← Orders</a></p>
<div class="page-header">
    <h1>Order #<?= (int) $order['id'] ?></h1>
    <?= View::partial('status-badge', ['status' => $order['status']]) ?>
</div>
<p class="muted">
    Placed <?= e(date('j M Y, H:i', strtotime($order['created_at']))) ?> UTC by
    <?= e($order['customer_name']) ?> &lt;<a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a>&gt;
</p>

<?php if ($nextStatuses): ?>
    <div class="card status-actions">
        <h2>Update status</h2>
        <?php foreach ($nextStatuses as $next): ?>
            <form action="/admin/orders/<?= (int) $order['id'] ?>/status" method="post" class="inline-form"
                  <?= $next === 'cancelled' ? 'data-confirm="Cancel this order and return its items to stock?"' : '' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="<?= e($next) ?>">
                <button type="submit" class="btn <?= $next === 'cancelled' ? 'btn-danger' : '' ?>">Mark as <?= e($next) ?></button>
            </form>
        <?php endforeach ?>
    </div>
<?php endif ?>

<?= View::partial('order-detail', ['order' => $order, 'items' => $items]) ?>
