<?php

declare(strict_types=1);

/*
 * Creates an admin user, or promotes an existing user to admin and resets their password.
 *
 * Usage: php bin/create-admin.php email@example.com "Full Name"
 * The password is read from the terminal (or stdin) so it never lands in shell history.
 */

use App\Repositories\UserRepository;

require dirname(__DIR__) . '/bootstrap.php';

[$script, $email, $name] = array_pad($argv, 3, null);
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php bin/create-admin.php email@example.com \"Full Name\"\n");
    exit(1);
}

fwrite(STDOUT, 'Password (min 10 chars): ');
if (stream_isatty(STDIN)) {
    system('stty -echo');
}
$password = trim((string) fgets(STDIN));
if (stream_isatty(STDIN)) {
    system('stty echo');
    fwrite(STDOUT, PHP_EOL);
}

if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

$users = new UserRepository();
$hash = password_hash($password, PASSWORD_DEFAULT);
$existing = $users->findByEmail($email);

if ($existing) {
    $users->updatePassword((int) $existing['id'], $hash);
    $users->setRole((int) $existing['id'], 'admin');
    echo "Updated {$email}: now an admin with the new password.\n";
} else {
    $users->create(mb_strtolower($email), $name ?: 'Admin', $hash, 'admin');
    echo "Created admin {$email}.\n";
}
