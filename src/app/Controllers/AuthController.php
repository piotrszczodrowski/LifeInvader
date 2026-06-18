<?php

namespace App\Controllers;

use App\Models\User;
use App\Core\Controller;

class AuthController extends BaseController
{
    public function __construct() {
        parent::__construct();
    }

    public function showLogin()
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        $this->render('login');
    }

    public function showRegister()
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];

        unset($_SESSION['errors'], $_SESSION['old']);

        // Pobieramy SITE_KEY z .env, żeby przekazać do front-endu
        $cfSiteKey = $_ENV['CLOUDFLARE_SITE_KEY'] ?? '';

        $this->render('register', [
            'errors' => $errors,
            'old' => $old,
            'cf_site_key' => $cfSiteKey // Wrzucamy to do Twiga
        ]);
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $emailConfirm = trim($_POST['email_confirm'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $birthDate = $_POST['birth_date'] ?? '';
            $evaluation = $_POST['evaluation'] ?? '';
            $tos = isset($_POST['tos']);

            // Pobranie tokenu Cloudflare Turnstile
            $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';

            $errors = [];

            // --- WALIDACJA CLOUDFLARE TURNSTILE ---
            if (empty($turnstileResponse)) {
                $errors['captcha'] = "Potwierdź, że jesteś człowiekiem.";
            } else {
                // Czytamy sekret z pliku .env!
                $secretKey = $_ENV['CLOUDFLARE_SECRET_KEY'] ?? '';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'secret' => $secretKey,
                    'response' => $turnstileResponse
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (!$response['success']) {
                    $errors['captcha'] = "Weryfikacja anty-botowa nie powiodła się. Spróbuj ponownie.";
                }
            }

            // --- WALIDACJA DANYCH WEJŚCIOWYCH ---
            if (strlen($username) < 3) {
                $errors['username'] = "Login musi mieć co najmniej 3 znaki.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Niepoprawny format adresu e-mail.";
            }
            if ($email !== $emailConfirm) {
                $errors['email_confirm'] = "Podane adresy e-mail nie są identyczne.";
            }
            if (strlen($password) < 6) {
                $errors['password'] = "Hasło musi mieć minimum 6 znaków.";
            }
            if ($password !== $passwordConfirm) {
                $errors['password_confirm'] = "Podane hasła się nie zgadzają.";
            }
            if (empty($birthDate)) {
                $errors['birth_date'] = "Musisz podać datę urodzenia.";
            }
            if (!in_array($evaluation, ["Na 5-tkę!", "Nie mam uwag", "100/100p"])) {
                $errors['evaluation'] = "Proszę wybrać poprawną ocenę projektu.";
            }
            if (!$tos) {
                $errors['tos'] = "Musisz zapoznać się z regulaminem.";
            }

            // Sprawdzanie duplikatów
            $userModel = new \App\Models\User();
            if (empty($errors)) {
                if ($userModel->findByUsername($username)) {
                    $errors['username'] = "Taki użytkownik już istnieje.";
                }
                if ($userModel->findByEmail($email)) {
                    $errors['email'] = "Ten adres e-mail jest już zajęty.";
                }
            }

            // Przekierowanie w przypadku błędów
            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['old'] = [
                    'username' => $username,
                    'email' => $email,
                    'email_confirm' => $emailConfirm,
                    'birth_date' => $birthDate,
                    'evaluation' => $evaluation
                ];
                header('Location: /register');
                exit;
            }

            // Zapis użytkownika
            $currentTheme = $_SESSION['theme_preference'] ?? 'light';
            $userModel->create($username, $email, $password, $birthDate, $currentTheme);
            header('Location: /login');
            exit;
        }
    }

    public function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['theme_preference'] = $user['theme_preference'] ?? 'light';
            $_SESSION['must_change_password'] = $user['must_change_password'];
            $_SESSION['avatar_path'] = $user['avatar_path'] ?? '/uploads/avatars/default.png';

            if ($_SESSION['must_change_password'] == 1) {
                header('Location: /force-password-change');
                exit;
            }

            header('Location: /');
            exit;
        }

        $this->render('login', ['error' => 'Nieprawidłowy email lub hasło.']);
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function showForceChangePassword()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $this->render('change_password', [
            'title' => 'Wymagana zmiana hasła',
            'message' => 'To Twoje pierwsze logowanie po instalacji systemu. Ze względów bezpieczeństwa musisz ustawić własne, bezpieczne hasło roota przed przejściem dalej.',
            'form_action' => '/force-password-change'
        ]);
    }

    public function forceChangePassword()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($password) || empty($passwordConfirm)) {
            $this->render('change_password', [
                'title' => 'Wymagana zmiana hasła',
                'message' => 'To Twoje pierwsze logowanie po instalacji systemu...',
                'error' => 'Wypełnij oba pola haseł!'
            ]);
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->render('change_password', [
                'title' => 'Wymagana zmiana hasła',
                'message' => 'To Twoje pierwsze logowanie po instalacji systemu...',
                'error' => 'Podane hasła nie są identyczne.'
            ]);
            return;
        }

        $userModel = new User();

        if ($userModel->updatePassword($_SESSION['user_id'], $password)) {
            $_SESSION['must_change_password'] = 0;
            header('Location: /');
            exit;
        } else {
            $this->render('change_password', [
                'title' => 'Wymagana zmiana hasła',
                'message' => 'To Twoje pierwsze logowanie po instalacji systemu...',
                'error' => 'Wystąpił nieoczekiwany błąd bazy danych.'
            ]);
        }
    }
}