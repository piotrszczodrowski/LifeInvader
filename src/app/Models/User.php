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
        // Pamiętaj o użyciu mocnego hasha, np. PASSWORD_ARGON2ID lub PASSWORD_DEFAULT
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

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, username, email, bio, avatar_path, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateTheme(int $userId, string $theme): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
        return $stmt->execute([$theme, $userId]);
    }

    public function delete(int $userId): bool
    {
        // 1. Pobieramy ścieżkę awatara z bazy danych zanim usuniemy rekord użytkownika
        $stmt = $this->db->prepare("SELECT avatar_path FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && !empty($user['avatar_path'])) {
            $avatarPath = $user['avatar_path'];

            // Ścieżka do domyślnego zdjęcia - tego pliku pod żadnym pozorem NIE usuwamy
            $defaultAvatar = '/uploads/avatars/default.png';

            // 2. Warunek: usuwamy tylko, jeśli to NIE jest domyślny awatar
            if ($avatarPath !== $defaultAvatar) {

                // Sklejamy ścieżkę bezwzględną na serwerze, używając katalogu głównego dokumentów
                $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $avatarPath;

                // 3. Sprawdzamy, czy plik na pewno fizycznie znajduje się na dysku serwera
                if (file_exists($fullPath) && is_file($fullPath)) {
                    unlink($fullPath); // Fizyczne usunięcie pliku graficznego
                }
            }
        }

        // 4. Dopiero po wyczyszczeniu plików usuwamy wiersz z bazy danych
        // (Kaskada w bazie wyczyści powiązane posty, komentarze i lajki)
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllExcept(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, avatar_path 
            FROM users 
            WHERE id != ? 
            ORDER BY username ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


}