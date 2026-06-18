<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(string $username, string $email, string $password, string $birthDate, string $theme = 'light'): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, birth_date, theme_preference) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$username, $email, $hash, $birthDate, $theme]);
    }

    public function findByEmail(string $email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByUsername(string $username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id");
        return $stmt->execute(['password_hash' => $hash, 'id' => $userId]);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, username, email, bio, avatar_path, created_at, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateTheme(int $userId, string $theme): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
        return $stmt->execute([$theme, $userId]);
    }

    /**
     * Usuwa użytkownika i jego awatar (jeśli nie jest domyślny).
     */
    public function delete(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT avatar_path FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && !empty($user['avatar_path']) && $user['avatar_path'] !== '/uploads/avatars/default.png') {
            $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $user['avatar_path'];
            if (file_exists($fullPath) && is_file($fullPath)) {
                unlink($fullPath);
            }
        }

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllExcept(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT id, username, avatar_path FROM users WHERE id != ? ORDER BY username ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateRole(int $userId, string $role): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }
}
