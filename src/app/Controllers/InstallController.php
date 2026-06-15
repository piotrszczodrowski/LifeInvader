<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use Exception;

class InstallController extends Controller
{
    public function index()
    {
        $this->render('install_step1');
    }

    public function run()
    {
        // check for install lock
        $lockFilePath = dirname(__DIR__, 2) . '/install.lock';

        if (file_exists($lockFilePath)) {
            $error = new ErrorController();
            $error->show(403, "System jest już zainstalowany! Usuń plik install.lock, aby ponowić proces.");
            return;
        }

        try {
            $db = Database::getConnection();

            // check if db empty
            if ($db->query("SHOW TABLES LIKE 'users'")->rowCount() > 0) {
                throw new Exception("W bazie istnieją już dane! Zablokowano próbę nadpisania.");
            }

            // import SQL scheme
            $sqlFilePath = dirname(__DIR__, 2) . '/database/init.sql';
            if (!file_exists($sqlFilePath)) {
                throw new Exception("Nie znaleziono pliku struktury bazy danych (init.sql).");
            }
            $db->exec(file_get_contents($sqlFilePath));

            // set root account
            $email = $_ENV['ADMIN_EMAIL'] ?? null;
            $password = $_ENV['ADMIN_PASSWORD'] ?? null;

            if (!$email || !$password) {
                throw new Exception("Brak ADMIN_EMAIL lub ADMIN_PASSWORD w pliku .env!");
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, role, must_change_password) 
                VALUES ('Administrator', :email, :password_hash, 'admin', 1)
            ");

            $stmt->execute([
                'email' => $email,
                'password_hash' => $hash
            ]);

            // place install lock
            file_put_contents($lockFilePath, "Zainstalowano: " . date('Y-m-d H:i:s'));

            // finish message
            $this->render('install_success', ['email' => $email]);

        } catch (Exception $e) {
            $error = new ErrorController();
            $error->show(500, "Błąd instalacji: " . $e->getMessage());
        }
    }
}