<?php /** @var array $totals subtotal_cents, shipping_cents, total_cents */ ?>
<dl class="totals">
    <div><dt>Subtotal</dt><dd><?= e(money($totals['subtotal_cents'])) ?></dd></div>
    <div><dt>Shipping</dt><dd><?= $totals['shipping_cents'] === 0 ? 'Free' : e(money($totals['shipping_cents'])) ?></dd></div>
    <div class="grand-total"><dt>Total</dt><dd><?= e(money($totals['total_cents'])) ?></dd></div>
</dl>
