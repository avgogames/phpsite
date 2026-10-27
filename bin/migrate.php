<?php

declare(strict_types=1);

/*
 * Applies pending SQL migrations from database/migrations in filename order.
 * Each migration runs in its own transaction and is recorded in schema_migrations.
 *
 * Usage: php bin/migrate.php [--status]
 */

use App\Core\Database;

require dirname(__DIR__) . '/bootstrap.php';

$pdo = Database::connection();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    version    text PRIMARY KEY,
    applied_at timestamptz NOT NULL DEFAULT now()
)');

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(BASE_PATH . '/database/migrations/*.sql');
sort($files);

$statusOnly = in_array('--status', $argv, true);
$ran = 0;

foreach ($files as $file) {
    $version = basename($file, '.sql');
    if (in_array($version, $applied, true)) {
        if ($statusOnly) {
            echo "  [x] {$version}\n";
        }
        continue;
    }
    if ($statusOnly) {
        echo "  [ ] {$version}\n";
        continue;
    }

    echo "Migrating {$version} ... ";
    Database::transaction(static function (PDO $pdo) use ($file, $version): void {
        $pdo->exec(file_get_contents($file));
        $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$version]);
    });
    echo "done\n";
    $ran++;
}

if (!$statusOnly) {
    echo $ran === 0 ? "Nothing to migrate.\n" : "Applied {$ran} migration(s).\n";
}
