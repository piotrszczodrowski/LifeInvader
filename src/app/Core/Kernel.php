<?php

namespace App\Core;

class Kernel
{
    public function handle(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Sprawdzenie, czy system jest zainstalowany
        $lockFilePath = dirname(__DIR__, 2) . '/install.lock';
        if (!file_exists($lockFilePath) && !in_array($uri, ['/install', '/install/run'])) {
            header('Location: /install');
            exit;
        }

        $publicRoutes = ['/login', '/register', '/install', '/install/run', '/toggle-theme'];

        // Logika dla zalogowanych użytkowników
        if (isset($_SESSION['user_id'])) {
            // Jeśli zalogowany user próbuje wejść na stronę logowania/rejestracji, przekieruj na stronę główną
            if (in_array($uri, ['/login', '/register'])) {
                header('Location: /');
                exit;
            }

            // Sprawdzenie, czy wymagana jest zmiana hasła
            if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
                if ($uri !== '/force-password-change' && $uri !== '/logout') {
                    header('Location: /force-password-change');
                    exit;
                }
            }

            // Sprawdzenie uprawnień administratora
            if (str_starts_with($uri, '/admin') && ($_SESSION['role'] ?? 'user') !== 'admin') {
                (new \App\Controllers\ErrorController())->show(403, 'Forbidden');
                exit;
            }
        }
        // Logika dla gości (niezalogowanych użytkowników)
        else {
            // Jeśli gość próbuje wejść na stronę wymagającą logowania, przekieruj na logowanie
            if (!in_array($uri, $publicRoutes)) {
                header('Location: /login');
                exit;
            }
        }
    }
}