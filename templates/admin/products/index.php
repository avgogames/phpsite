<?php use App\Core\View; ?>
<div class="page-header">
    <h1>Products</h1>
    <a class="btn" href="/admin/products/new">New product</a>
</div>

<form method="get" action="/admin/products" class="toolbar" role="search">
    <label class="visually-hidden" for="admin-q">Search products</label>
    <input id="admin-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name or SKU">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>

<table class="table">
    <thead>
    <tr><th scope="col">Name</th><th scope="col">SKU</th><th scope="col">Category</th><th scope="col">Price</th><th scope="col">Stock</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr>
    </thead>
    <tbody>
    <?php foreach ($products as $p): ?>
        <tr>
            <td data-label="Name"><a href="/admin/products/<?= (int) $p['id'] ?>/edit"><?= e($p['name']) ?></a></td>
            <td data-label="SKU"><code><?= e($p['sku']) ?></code></td>
            <td data-label="Category"><?= e($p['category_name']) ?></td>
            <td data-label="Price"><?= e(money($p['price_cents'])) ?></td>
            <td data-label="Stock"><span class="stock <?= $p['stock'] === 0 ? 'stock-out' : ($p['stock'] <= 5 ? 'stock-low' : '') ?>"><?= (int) $p['stock'] ?></span></td>
            <td data-label="Status"><?= $p['is_active'] ? 'Live' : '<span class="muted">Hidden</span>' ?></td>
            <td class="actions">
                <?php if ($p['is_active']): ?><a href="/products/<?= e($p['slug']) ?>">View</a><?php endif ?>
                <a href="/admin/products/<?= (int) $p['id'] ?>/edit">Edit</a>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
<?= View::partial('pagination', ['pagination' => $pagination, 'base' => '/admin/products']) ?>
