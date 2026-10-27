<?php
/** @var array{page: int, pages: int, total: int, query: array} $pagination */
/** @var string $base */
['page' => $page, 'pages' => $pages, 'query' => $query] = $pagination;
if ($pages <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a href="<?= e(url($base, $query + ['page' => $page - 1])) ?>" rel="prev">← Previous</a>
    <?php endif ?>
    <span>Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?>
        <a href="<?= e(url($base, $query + ['page' => $page + 1])) ?>" rel="next">Next →</a>
    <?php endif ?>
</nav>
