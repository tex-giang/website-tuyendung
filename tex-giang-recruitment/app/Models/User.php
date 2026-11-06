<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function updatePassword(int $id, string $hash): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }

    public static function touchLogin(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id'
        )->fetchAll();
    }

    public static function publicProfile(array $user): array
    {
        return [
            'id'        => (int) $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role_name'],
        ];
    }

    // ------------------------------------------------------------------ ADMIN

    /** @return array<string, string> */
    public static function roles(): array
    {
        $rows = Database::connection()->query('SELECT name, description FROM roles ORDER BY id')->fetchAll();
        $roles = [];
        foreach ($rows as $row) {
            $roles[$row['name']] = $row['description'] ?: $row['name'];
        }
        return $roles;
    }

    public static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = :username' . ($exceptId !== null ? ' AND id <> :id' : '');
        $stmt = Database::connection()->prepare($sql);
        $params = ['username' => $username];
        if ($exceptId !== null) {
            $params['id'] = $exceptId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function roleIdByName(string $roleName): ?int
    {
        $stmt = Database::connection()->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $roleName]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO users (role_id, username, password_hash, full_name, email, phone, is_active)
             VALUES (:role_id, :username, :hash, :full_name, :email, :phone, :is_active)'
        );
        $stmt->execute([
            'role_id'   => $data['role_id'],
            'username'  => $data['username'],
            'hash'      => $data['password_hash'],
            'full_name' => $data['full_name'],
            'email'     => ($data['email'] ?? '') !== '' ? $data['email'] : null,
            'phone'     => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET role_id = :role_id, full_name = :full_name, email = :email,
                    phone = :phone, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'role_id'   => $data['role_id'],
            'full_name' => $data['full_name'],
            'email'     => ($data['email'] ?? '') !== '' ? $data['email'] : null,
            'phone'     => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'id'        => $id,
        ]);
    }
}
