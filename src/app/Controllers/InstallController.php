<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use Exception;

class InstallController extends Controller
{
    /**
     * Wyświetla stronę instalacji.
     */
    public function index()
    {
        $this->render('install_step1');
    }

    /**
     * Uruchamia proces instalacji: tworzy strukturę bazy danych i konto administratora.
     */
    public function run()
    {
        $lockFilePath = dirname(__DIR__, 2) . '/install.lock';
        if (file_exists($lockFilePath)) {
            (new ErrorController())->show(403, "System jest już zainstalowany.");
            return;
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

            $this->render('install_success', ['email' => $email]);

        } catch (Exception $e) {
            (new ErrorController())->show(500, "Błąd instalacji: " . $e->getMessage());
        }
    }
}
