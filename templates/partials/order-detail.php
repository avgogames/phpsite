<?php
/** Shared by the customer and admin order pages. @var array $order @var array $items */
use App\Core\View;
?>
<div class="order-detail">
    <section class="card">
        <h2>Items</h2>
        <table class="table">
            <thead><tr><th scope="col">Product</th><th scope="col">Unit price</th><th scope="col">Qty</th><th scope="col">Total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td data-label="Product">
                        <?php if ($item['product_slug']): ?>
                            <a href="/products/<?= e($item['product_slug']) ?>"><?= e($item['product_name']) ?></a>
                        <?php else: ?>
                            <?= e($item['product_name']) ?>
                        <?php endif ?>
                        <div class="muted small">SKU <?= e($item['sku']) ?></div>
                    </td>
                    <td data-label="Unit price"><?= e(money($item['unit_price_cents'])) ?></td>
                    <td data-label="Qty"><?= (int) $item['quantity'] ?></td>
                    <td data-label="Total"><?= e(money($item['line_total_cents'])) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?= View::partial('order-summary', ['totals' => $order]) ?>
    </section>

    <section class="card">
        <h2>Shipping to</h2>
        <address>
            <?= e($order['ship_name']) ?><br>
            <?= e($order['ship_address']) ?><br>
            <?= e($order['ship_city']) ?> <?= e($order['ship_postcode']) ?><br>
            <?= e($order['ship_country']) ?>
            <?php if ($order['ship_phone']): ?><br><?= e($order['ship_phone']) ?><?php endif ?>
        </address>
        <?php if ($order['notes']): ?>
            <h3>Notes</h3>
            <p><?= nl2br(e($order['notes'])) ?></p>
        <?php endif ?>
    </section>
</div>
