<?php use App\Core\View; ?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/products">Shop</a> /
    <a href="<?= e(url('/products', ['category' => $product['category_slug']])) ?>"><?= e($product['category_name']) ?></a> /
    <span aria-current="page"><?= e($product['name']) ?></span>
</nav>

<article class="product-detail">
    <?= View::partial('product-image', ['product' => $product, 'size' => 'large']) ?>

    <div class="product-info">
        <h1><?= e($product['name']) ?></h1>
        <p class="muted">SKU <?= e($product['sku']) ?></p>
        <p class="price price-large"><?= e(money($product['price_cents'])) ?></p>

        <?php if ($product['stock'] > 10): ?>
            <p class="stock stock-in">In stock</p>
        <?php elseif ($product['stock'] > 0): ?>
            <p class="stock stock-low">Only <?= (int) $product['stock'] ?> left</p>
        <?php else: ?>
            <p class="stock stock-out">Out of stock</p>
        <?php endif ?>

        <?php if ($product['stock'] > 0): ?>
            <form action="/cart/add" method="post" class="add-to-cart">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="return" value="product">
                <label for="quantity">Quantity</label>
                <input id="quantity" type="number" name="quantity" value="1" min="1" max="<?= min(99, (int) $product['stock']) ?>" inputmode="numeric">
                <button type="submit" class="btn">Add to cart</button>
            </form>
        <?php endif ?>

        <div class="description">
            <?php foreach (preg_split('/\R{2,}/', $product['description']) as $paragraph): ?>
                <p><?= nl2br(e($paragraph)) ?></p>
            <?php endforeach ?>
        </div>
    </div>
</article>

<?php if ($related): ?>
    <section aria-labelledby="related-heading">
        <h2 id="related-heading">More in <?= e($product['category_name']) ?></h2>
        <div class="product-grid">
            <?php foreach ($related as $item): ?>
                <?= View::partial('product-card', ['product' => $item]) ?>
            <?php endforeach ?>
        </div>
    </section>
<?php endif ?>
