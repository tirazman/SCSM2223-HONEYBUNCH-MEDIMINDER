<?php

namespace App\Repositories;

use App\Data\Database;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password_hash, role, dob, created_at FROM User WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role, dob, created_at FROM User WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, string $role, ?string $dob = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO User (name, email, password_hash, role, dob) VALUES (:name, :email, :password_hash, :role, :dob)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
            'dob' => $dob,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM User WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetch();
    }

    /**
     * Checks if `email` is already used by a *different* user than $excludeId.
     * Used so a user can save their own profile without tripping over their
     * own unchanged email address.
     */
    public function emailExistsForOtherUser(string $email, int $excludeId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM User WHERE email = :email AND id != :id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'id' => $excludeId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Updates only the fields present in $fields. Supported keys:
     * 'name', 'email', 'dob', 'password_hash'. Returns the updated user
     * (without password_hash, via findById) or null if the user doesn't exist.
     */
    public function update(int $id, array $fields): ?array
    {
        $allowed = ['name', 'email', 'dob', 'password_hash'];
        $set = [];
        $params = ['id' => $id];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields)) {
                $set[] = "{$key} = :{$key}";
                $params[$key] = $fields[$key];
            }
        }

        if (empty($set)) {
            return $this->findById($id);
        }

        $sql = 'UPDATE User SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }
}