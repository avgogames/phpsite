<?php
/** @var string $content */
/** @var string $title */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$active = static fn (string $prefix, bool $exact = false): string =>
    ($exact ? $path === $prefix : str_starts_with($path, $prefix)) ? ' aria-current="page"' : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title ?? '') ?> · Admin · <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="admin">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="logo" href="/admin"><span class="logo-mark" aria-hidden="true">⬢</span> Admin</a>
        <nav aria-label="Admin">
            <a href="/admin"<?= $active('/admin', true) ?>>Dashboard</a>
            <a href="/admin/products"<?= $active('/admin/products') ?>>Products</a>
            <a href="/admin/orders"<?= $active('/admin/orders') ?>>Orders</a>
            <a href="/">View store ↗</a>
        </nav>
        <form action="/logout" method="post">
            <?= csrf_field() ?>
            <button type="submit" class="link-button">Sign out <?= e(current_user()['email'] ?? '') ?></button>
        </form>
    </aside>
    <main class="admin-main">
        <?= \App\Core\View::partial('flash') ?>
        <?= $content ?>
    </main>
</div>
</body>
</html>
