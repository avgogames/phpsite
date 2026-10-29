<?php
/** @var array $product */
/** Real image when the product has one, otherwise a category glyph so the grid never looks broken. */
$glyphs = [
    'screws' => '🔩', 'nuts-bolts' => '⚙️', 'nails' => '📌',
    'anchors' => '⚓', 'hinges-brackets' => '🚪', 'hand-tools' => '🪛',
];
$size = $size ?? 'card';
?>
<?php if (!empty($product['image_url'])): ?>
    <img class="product-image product-image-<?= e($size) ?>" src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
<?php else: ?>
    <div class="product-image product-image-<?= e($size) ?> placeholder" role="img" aria-label="<?= e($product['name']) ?>">
        <span aria-hidden="true"><?= $glyphs[$product['category_slug'] ?? ''] ?? '🧰' ?></span>
    </div>
<?php endif ?>
