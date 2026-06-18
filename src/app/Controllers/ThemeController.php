<?php
namespace App\Controllers;

use App\Models\User;

class ThemeController extends BaseController {

    public function toggle() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Pobieramy obecny motyw z sesji (domyślnie light)
        $currentTheme = $_SESSION['theme_preference'] ?? 'light';
        $newTheme = ($currentTheme === 'dark') ? 'light' : 'dark';

        // 1. Aktualizacja w bazie danych
        $userModel = new User();
        $userModel->updateTheme($_SESSION['user_id'], $newTheme);

        // 2. Aktualizacja w sesji (żeby Twig od razu zobaczył zmianę)
        $_SESSION['theme_preference'] = $newTheme;

        // Powrót na stronę, na której użytkownik się znajdował
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }
}