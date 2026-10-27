<?php

use App\Core\View;
use App\Services\Cart;

?>
<h1>Your cart</h1>

<?php if ($cart['lines'] === []): ?>
    <div class="empty-state">
        <p>Your cart is empty.</p>
        <a class="btn" href="/products">Start shopping</a>
    </div>
<?php else: ?>
    <div class="cart-layout">
        <form action="/cart/update" method="post" class="cart-table-form">
            <?= csrf_field() ?>
            <table class="table cart-table">
                <thead>
                <tr><th scope="col">Product</th><th scope="col">Price</th><th scope="col">Quantity</th><th scope="col">Total</th><th scope="col"><span class="visually-hidden">Remove</span></th></tr>
                </thead>
                <tbody>
                <?php foreach ($cart['lines'] as $line): $p = $line['product']; ?>
                    <tr>
                        <td data-label="Product">
                            <a href="/products/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a>
                            <div class="muted small">SKU <?= e($p['sku']) ?></div>
                        </td>
                        <td data-label="Price"><?= e(money($p['price_cents'])) ?></td>
                        <td data-label="Quantity">
                            <label class="visually-hidden" for="qty-<?= (int) $p['id'] ?>">Quantity of <?= e($p['name']) ?></label>
                            <input id="qty-<?= (int) $p['id'] ?>" class="qty-input" type="number" name="quantities[<?= (int) $p['id'] ?>]"
                                   value="<?= (int) $line['quantity'] ?>" min="0" max="<?= min(Cart::MAX_QTY_PER_LINE, (int) $p['stock']) ?>" inputmode="numeric">
                        </td>
                        <td data-label="Total"><?= e(money($line['line_total_cents'])) ?></td>
                        <td>
                            <button type="submit" class="link-button danger" form="remove-<?= (int) $p['id'] ?>">Remove</button>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-secondary">Update cart</button>
        </form>

        <?php foreach ($cart['lines'] as $line): ?>
            <form id="remove-<?= (int) $line['product']['id'] ?>" action="/cart/remove" method="post" hidden>
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $line['product']['id'] ?>">
            </form>
        <?php endforeach ?>

        <aside class="card summary-card">
            <h2>Summary</h2>
            <?= View::partial('order-summary', ['totals' => $cart]) ?>
            <?php if ($cart['shipping_cents'] > 0): ?>
                <p class="muted small">Add <?= e(money(\App\Core\Config::int('FREE_SHIPPING_THRESHOLD_CENTS', 5000) - $cart['subtotal_cents'])) ?> more for free shipping.</p>
            <?php endif ?>
            <a class="btn btn-block" href="/checkout">Checkout</a>
        </aside>
    </div>
<?php endif ?>
