<?php

namespace App\Services;

use App\Core\Database;
use Exception;

class InstallService
{
    public function runInstall(): array
    {
        $lockFilePath = dirname(__DIR__, 2) . '/install.lock';
        if (file_exists($lockFilePath)) {
            return ['success' => false, 'message' => 'System jest już zainstalowany.'];
        }

        try {
            $db = Database::getConnection();

            if ($db->query("SHOW TABLES LIKE 'users'")->rowCount() > 0) {
                throw new Exception("Baza danych nie jest pusta.");
            }

            $sqlFilePath = dirname(__DIR__, 2) . '/database/init.sql';
            if (!file_exists($sqlFilePath)) {
                throw new Exception("Nie znaleziono pliku init.sql.");
            }
            $db->exec(file_get_contents($sqlFilePath));

            $email = $_ENV['ADMIN_EMAIL'] ?? null;
            $password = $_ENV['ADMIN_PASSWORD'] ?? null;

            if (!$email || !$password) {
                throw new Exception("Brak ADMIN_EMAIL lub ADMIN_PASSWORD w .env");
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, must_change_password) VALUES ('Administrator', :email, :hash, 'admin', 1)");
            $stmt->execute(['email' => $email, 'hash' => $hash]);

            file_put_contents($lockFilePath, "Zainstalowano: " . date('Y-m-d H:i:s'));

            return ['success' => true, 'email' => $email];

        } catch (Exception $e) {
            return ['success' => false, 'message' => "Błąd instalacji: " . $e->getMessage()];
        }
    }
}