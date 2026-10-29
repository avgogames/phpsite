<?php

use App\Core\Session;
use App\Core\View;
use App\Services\Money;

/** @var ?array $product null when creating */
$isNew = $product === null;
// After a failed submit, show what the admin typed; otherwise show the saved product.
$hasOld = Session::errors() !== [];
$val = static fn (string $key, string $saved = ''): string => $hasOld ? old($key) : $saved;
$selectedCategory = $val('category_id', (string) ($product['category_id'] ?? ''));
$isActive = $hasOld ? old('is_active') === '1' : ($product['is_active'] ?? true);
?>
<p><a href="/admin/products">← Products</a></p>
<h1><?= e($title) ?></h1>

<form action="<?= $isNew ? '/admin/products' : '/admin/products/' . (int) $product['id'] ?>" method="post" class="card form form-wide" novalidate>
    <?= csrf_field() ?>
    <?= View::partial('field', ['name' => 'name', 'label' => 'Name', 'value' => $val('name', $product['name'] ?? ''), 'extra' => 'maxlength="200"']) ?>
    <div class="field-row">
        <?= View::partial('field', ['name' => 'sku', 'label' => 'SKU', 'value' => $val('sku', $product['sku'] ?? ''), 'extra' => 'maxlength="64"']) ?>
        <?= View::partial('field', ['name' => 'slug', 'label' => 'URL slug', 'required' => false, 'value' => $val('slug', $product['slug'] ?? ''), 'hint' => 'Leave blank to generate from the name.']) ?>
    </div>
    <div class="field-row">
        <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">Choose…</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"<?= (string) $c['id'] === $selectedCategory ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach ?>
            </select>
            <?= error_for('category_id') ?>
        </div>
        <?= View::partial('field', ['name' => 'price', 'label' => 'Price', 'value' => $val('price', isset($product) ? Money::toDecimal($product['price_cents']) : ''), 'extra' => 'inputmode="decimal" placeholder="9.99"']) ?>
        <?= View::partial('field', ['name' => 'stock', 'label' => 'Stock', 'type' => 'number', 'value' => $val('stock', (string) ($product['stock'] ?? '0')), 'extra' => 'min="0" step="1"']) ?>
    </div>
    <div class="field">
        <label for="description">Description <span class="muted">(optional)</span></label>
        <textarea id="description" name="description" rows="6" maxlength="5000"><?= e($val('description', $product['description'] ?? '')) ?></textarea>
        <?= error_for('description') ?>
    </div>
    <?= View::partial('field', ['name' => 'image_url', 'label' => 'Image URL', 'type' => 'url', 'required' => false, 'value' => $val('image_url', $product['image_url'] ?? '')]) ?>
    <div class="field checkbox">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" type="checkbox" name="is_active" value="1"<?= $isActive ? ' checked' : '' ?>>
        <label for="is_active">Visible in the store</label>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn"><?= $isNew ? 'Create product' : 'Save changes' ?></button>
    </div>
</form>

<?php if (!$isNew): ?>
    <form action="/admin/products/<?= (int) $product['id'] ?>/delete" method="post" class="danger-zone card"
          data-confirm="Delete <?= e($product['name']) ?>? Products with past orders are hidden instead.">
        <?= csrf_field() ?>
        <h2>Delete product</h2>
        <p class="muted">Products that appear in past orders are hidden from the store instead of deleted, so order history stays intact.</p>
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
<?php endif ?>
