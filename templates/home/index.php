<?php use App\Core\View; ?>
<section class="hero">
    <h1>The small parts that hold everything together.</h1>
    <p>Screws, bolts, anchors, hinges and hand tools, packed in the quantities you actually need and shipped fast.</p>
    <a class="btn" href="/products">Browse the catalog</a>
</section>

<section aria-labelledby="cat-heading">
    <h2 id="cat-heading">Shop by category</h2>
    <ul class="category-grid">
        <?php foreach ($categories as $category): ?>
            <li>
                <a class="category-tile" href="<?= e(url('/products', ['category' => $category['slug']])) ?>">
                    <?= View::partial('product-image', ['product' => ['category_slug' => $category['slug'], 'name' => $category['name']], 'size' => 'icon']) ?>
                    <strong><?= e($category['name']) ?></strong>
                    <span class="muted small"><?= (int) $category['product_count'] ?> products</span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
</section>

<section aria-labelledby="new-heading">
    <h2 id="new-heading">New in stock</h2>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?= View::partial('product-card', ['product' => $product]) ?>
        <?php endforeach ?>
    </div>
</section>
