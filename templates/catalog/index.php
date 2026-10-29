<?php

use App\Core\View;
use App\Repositories\ProductRepository;

$sortOptions = ProductRepository::SORT_LABELS;
if ($q === '') {
    unset($sortOptions['relevance']);
}
?>
<div class="catalog">
    <aside class="catalog-filters" aria-label="Categories">
        <h2 class="small-heading">Categories</h2>
        <ul>
            <li><a href="<?= e(url('/products', ['q' => $q])) ?>"<?= $category === null ? ' aria-current="page"' : '' ?>>All products</a></li>
            <?php foreach ($categories as $c): ?>
                <li>
                    <a href="<?= e(url('/products', ['category' => $c['slug'], 'q' => $q])) ?>"<?= ($category['id'] ?? null) === $c['id'] ? ' aria-current="page"' : '' ?>>
                        <?= e($c['name']) ?> <span class="muted">(<?= (int) $c['product_count'] ?>)</span>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </aside>

    <section class="catalog-results">
        <div class="results-header">
            <div>
                <h1><?= e($title) ?></h1>
                <p class="muted"><?= (int) $pagination['total'] ?> product<?= $pagination['total'] === 1 ? '' : 's' ?></p>
            </div>
            <form method="get" action="/products" class="sort-form">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <?php if ($category): ?><input type="hidden" name="category" value="<?= e($category['slug']) ?>"><?php endif ?>
                <label for="sort">Sort</label>
                <select id="sort" name="sort" data-autosubmit>
                    <?php foreach ($sortOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $sort === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach ?>
                </select>
                <noscript><button type="submit" class="btn btn-small">Apply</button></noscript>
            </form>
        </div>

        <?php if ($products === []): ?>
            <div class="empty-state">
                <p>No products match<?= $q !== '' ? ' “' . e($q) . '”' : '' ?>.</p>
                <a href="/products">Clear filters</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?= View::partial('product-card', ['product' => $product]) ?>
                <?php endforeach ?>
            </div>
            <?= View::partial('pagination', ['pagination' => $pagination, 'base' => '/products']) ?>
        <?php endif ?>
    </section>
</div>
