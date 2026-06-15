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

    public function create(string $username, string $email, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash) 
            VALUES (:username, :email, :password_hash)
        ");

        return $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $hash
        ]);
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);

        return $stmt->fetch();
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        // Aktualizujemy hasło i od razu zerujemy flagę must_change_password
        $stmt = $this->db->prepare("
        UPDATE users 
        SET password_hash = :password_hash, must_change_password = 0 
        WHERE id = :id
    ");

        return $stmt->execute([
            'password_hash' => $hash,
            'id' => $userId
        ]);
    }
}