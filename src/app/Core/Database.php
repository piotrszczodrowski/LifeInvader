<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    // db connection Singleton
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        // create connection
        if (self::$instance === null) {
            try {
                // get secrets from .env
                $host = $_ENV['DB_HOST'] ?? 'db';
                $dbName = $_ENV['DB_NAME'] ?? 'devdb';
                $username = $_ENV['DB_USER'] ?? 'devuser';
                $password = $_ENV['DB_PASSWORD'] ?? 'devpassword';

                // DSN (Data Source Name)
                $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";

                // establish PDO connection
                self::$instance = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Błędy rzucają wyjątki
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Zwraca wyniki jako tablice asocjacyjne
                    PDO::ATTR_EMULATE_PREPARES => false, // Zwiększa bezpieczeństwo zapytań
                ]);
            } catch (PDOException $e) {
                die("Krytyczny błąd połączenia z bazą danych: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}