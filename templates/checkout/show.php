<?php

use App\Core\View;

$field = static fn (string $name, string $label, string $autocomplete, bool $required = true, string $type = 'text', string $default = ''): string =>
    View::partial('field', compact('name', 'label', 'autocomplete', 'required', 'type') + ['value' => old($name, $default)]);
?>
<h1>Checkout</h1>

<div class="checkout-layout">
    <form action="/checkout" method="post" class="card form" novalidate>
        <?= csrf_field() ?>
        <h2>Shipping address</h2>
        <?= $field('ship_name', 'Full name', 'name', default: $user['name']) ?>
        <?= $field('ship_address', 'Street address', 'street-address') ?>
        <div class="field-row">
            <?= $field('ship_city', 'City', 'address-level2') ?>
            <?= $field('ship_postcode', 'Postcode', 'postal-code') ?>
        </div>
        <?= $field('ship_country', 'Country', 'country-name') ?>
        <?= $field('ship_phone', 'Phone', 'tel', false, 'tel') ?>
        <div class="field">
            <label for="notes">Delivery notes <span class="muted">(optional)</span></label>
            <textarea id="notes" name="notes" rows="3" maxlength="500"><?= e(old('notes')) ?></textarea>
            <?= error_for('notes') ?>
        </div>

        <h2>Payment</h2>
        <p class="muted">Pay by bank transfer or card on delivery. We will email payment instructions with your order confirmation.</p>

        <button type="submit" class="btn btn-block" data-once>Place order · <?= e(money($cart['total_cents'])) ?></button>
    </form>

    <aside class="card summary-card">
        <h2>Your order</h2>
        <ul class="summary-lines">
            <?php foreach ($cart['lines'] as $line): ?>
                <li><span><?= (int) $line['quantity'] ?> × <?= e($line['product']['name']) ?></span><span><?= e(money($line['line_total_cents'])) ?></span></li>
            <?php endforeach ?>
        </ul>
        <?= View::partial('order-summary', ['totals' => $cart]) ?>
        <a href="/cart" class="small">Edit cart</a>
    </aside>
</div>
