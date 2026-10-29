<?php /** Error layout with no database or session dependencies. */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Error') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main id="main" class="container"><?= $content ?></main>
</body>
</html>
