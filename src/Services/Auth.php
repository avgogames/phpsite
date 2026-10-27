<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Repositories\UserRepository;

/**
 * Session-based authentication with login throttling.
 */
final class Auth
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_MINUTES = 15;

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            self::$user = is_int($id) ? (new UserRepository())->find($id) : null;
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === 'admin';
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
    }

    /**
     * Verifies credentials. Returns the user on success, null on bad credentials.
     * Always runs password_verify so response time does not reveal whether the email exists.
     */
    public static function attempt(string $email, string $password): ?array
    {
        $users = new UserRepository();
        $user = $users->findByEmail($email);
        $hash = $user['password_hash'] ?? password_hash('timing-equalizer', PASSWORD_DEFAULT);

        if (!password_verify($password, $hash) || $user === null) {
            return null;
        }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        return $user;
    }

    /** Locks out an email after MAX_ATTEMPTS failures, and an IP after 4x that, within the lockout window. */
    public static function tooManyAttempts(string $email, string $ip): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT count(*) FILTER (WHERE email = :email) AS by_email,
                    count(*) FILTER (WHERE ip = CAST(:ip AS inet)) AS by_ip
             FROM login_attempts
             WHERE attempted_at > now() - make_interval(mins => :mins)"
        );
        $stmt->execute(['email' => mb_strtolower($email), 'ip' => $ip, 'mins' => self::LOCKOUT_MINUTES]);
        $row = $stmt->fetch();

        return $row['by_email'] >= self::MAX_ATTEMPTS || $row['by_ip'] >= self::MAX_ATTEMPTS * 4;
    }

    public static function recordFailure(string $email, string $ip): void
    {
        Database::connection()
            ->prepare('INSERT INTO login_attempts (email, ip) VALUES (:email, :ip)')
            ->execute(['email' => mb_strtolower($email), 'ip' => $ip]);
    }

    public static function clearFailures(string $email): void
    {
        Database::connection()
            ->prepare('DELETE FROM login_attempts WHERE email = :email')
            ->execute(['email' => mb_strtolower($email)]);
    }
}
