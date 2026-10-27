<?php /** @var array $product */ ?>
<article class="product-card">
    <a href="/products/<?= e($product['slug']) ?>" class="product-card-link">
        <?= \App\Core\View::partial('product-image', ['product' => $product]) ?>
        <h3><?= e($product['name']) ?></h3>
    </a>
    <p class="muted small"><?= e($product['category_name']) ?> · SKU <?= e($product['sku']) ?></p>
    <div class="product-card-footer">
        <span class="price"><?= e(money($product['price_cents'])) ?></span>
        <?php if ($product['stock'] > 0): ?>
            <form action="/cart/add" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <button type="submit" class="btn btn-small">Add to cart</button>
            </form>
        <?php else: ?>
            <span class="stock stock-out">Out of stock</span>
        <?php endif ?>
    </div>
</article>
