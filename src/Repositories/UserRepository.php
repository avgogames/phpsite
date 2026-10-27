<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, email, name, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Includes password_hash; only for authentication. */
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([mb_strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $email, string $name, string $passwordHash, string $role = 'customer'): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (email, name, password_hash, role) VALUES (?, ?, ?, ?) RETURNING id'
        );
        $stmt->execute([mb_strtolower(trim($email)), $name, $passwordHash, $role]);
        return (int) $stmt->fetchColumn();
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$passwordHash, $id]);
    }

    public function setRole(int $id, string $role): void
    {
        Database::connection()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
    }

    public function count(): int
    {
        return (int) Database::connection()->query("SELECT count(*) FROM users WHERE role = 'customer'")->fetchColumn();
    }
}
