<?php
/** @var string $content */
/** @var string $title */
use App\Services\Cart;

$user = current_user();
$cartCount = (new Cart())->count();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? '') ?> · <?= e(app_name()) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'Screws, bolts, anchors, hinges and hand tools delivered to your door.') ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/" aria-label="<?= e(app_name()) ?> home">
            <span class="logo-mark" aria-hidden="true">⬢</span> <?= e(app_name()) ?>
        </a>

        <form class="search" action="/products" method="get" role="search">
            <label class="visually-hidden" for="site-search">Search products</label>
            <input id="site-search" type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Search screws, bolts, M8…">
            <button type="submit">Search</button>
        </form>

        <nav class="main-nav" aria-label="Main">
            <a href="/products">Shop</a>
            <a href="/cart" class="cart-link">Cart<?php if ($cartCount > 0): ?> <span class="badge" aria-label="<?= $cartCount ?> items"><?= $cartCount ?></span><?php endif ?></a>
            <?php if ($user): ?>
                <a href="/account/orders">My orders</a>
                <?php if ($user['role'] === 'admin'): ?><a href="/admin">Admin</a><?php endif ?>
                <form action="/logout" method="post" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">Sign out</button>
                </form>
            <?php else: ?>
                <a href="/login">Sign in</a>
            <?php endif ?>
        </nav>
    </div>
</header>

<main id="main" class="container">
    <?= \App\Core\View::partial('flash') ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= e(app_name()) ?>. Free shipping on orders over <?= e(money(\App\Core\Config::int('FREE_SHIPPING_THRESHOLD_CENTS', 5000))) ?>.</p>
    </div>
</footer>
</body>
</html>
