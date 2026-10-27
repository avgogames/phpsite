<?php

declare(strict_types=1);

/*
 * Loads the demo catalog and creates the initial admin account from ADMIN_EMAIL / ADMIN_PASSWORD.
 * Safe to run more than once.
 *
 * Usage: php bin/seed.php
 */

use App\Core\Config;
use App\Core\Database;
use App\Repositories\UserRepository;

require dirname(__DIR__) . '/bootstrap.php';

$pdo = Database::connection();

foreach (glob(BASE_PATH . '/database/seeds/*.sql') as $file) {
    echo 'Seeding ' . basename($file) . " ... ";
    $pdo->exec(file_get_contents($file));
    echo "done\n";
}

$email = mb_strtolower((string) Config::get('ADMIN_EMAIL', 'admin@example.com'));
$password = (string) Config::get('ADMIN_PASSWORD', '');
$users = new UserRepository();

if ($users->findByEmail($email) !== null) {
    echo "Admin {$email} already exists, leaving it unchanged.\n";
} elseif (strlen($password) < 10) {
    echo "Skipping admin: set ADMIN_PASSWORD (10+ characters) in .env, or run bin/create-admin.php.\n";
} else {
    $users->create($email, 'Store Admin', password_hash($password, PASSWORD_DEFAULT), 'admin');
    echo "Created admin {$email}.\n";
    if (Config::isProduction() && $password === 'change-me-now') {
        echo "WARNING: you are using the example admin password in production. Change it now.\n";
    }
}
