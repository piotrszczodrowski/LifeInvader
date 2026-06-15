<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        // Singleton db-connection
        $this->db = Database::getConnection();
    }

    // registration
    public function create(string $username, string $email, string $password): bool
    {
        // WYMÓG 10%: "Hasła przechowywane są w postaci hashu (...) nigdy w postaci jawnej"
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

    // search user by e-mail
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);

        return $stmt->fetch();
    }
}