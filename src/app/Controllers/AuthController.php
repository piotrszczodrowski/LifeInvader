<?php

namespace App\Controllers;

use App\Models\User;
use App\Core\Controller;

class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Wyświetla formularz logowania.
     */
    public function showLogin()
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        $this->render('login');
    }

    /**
     * Wyświetla formularz rejestracji z obsługą błędów i zapamiętywaniem starych danych.
     */
    public function showRegister()
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        $cfSiteKey = $_ENV['CLOUDFLARE_SITE_KEY'] ?? '';

        $this->render('register', [
            'errors' => $errors,
            'old' => $old,
            'cf_site_key' => $cfSiteKey
        ]);
    }

    /**
     * Przetwarza dane z formularza rejestracji, waliduje je i tworzy nowego użytkownika.
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $birthDate = $_POST['birth_date'] ?? '';
            $tos = isset($_POST['tos']);
            $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';

            $errors = [];

            // Walidacja Cloudflare Turnstile
            if (empty($turnstileResponse)) {
                $errors['captcha'] = "Potwierdź, że jesteś człowiekiem.";
            } else {
                $secretKey = $_ENV['CLOUDFLARE_SECRET_KEY'] ?? '';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $secretKey, 'response' => $turnstileResponse]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (!$response['success']) {
                    $errors['captcha'] = "Weryfikacja anty-botowa nie powiodła się.";
                }
            }

            // Walidacja danych
            if (strlen($username) < 3) $errors['username'] = "Login musi mieć co najmniej 3 znaki.";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Niepoprawny format e-mail.";
            if (strlen($password) < 6) $errors['password'] = "Hasło musi mieć minimum 6 znaków.";
            if (empty($birthDate)) $errors['birth_date'] = "Musisz podać datę urodzenia.";
            if (!$tos) $errors['tos'] = "Musisz zapoznać się z regulaminem.";

            // Walidacja unikalności
            $userModel = new \App\Models\User();
            if (empty($errors)) {
                if ($userModel->findByUsername($username)) $errors['username'] = "Taki użytkownik już istnieje.";
                if ($userModel->findByEmail($email)) $errors['email'] = "Ten e-mail jest już zajęty.";
            }

            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['old'] = ['username' => $username, 'email' => $email, 'birth_date' => $birthDate];
                header('Location: /register');
                exit;
            }

            $currentTheme = $_SESSION['theme_preference'] ?? 'light';
            $userModel->create($username, $email, $password, $birthDate, $currentTheme);
            header('Location: /login');
            exit;
        }
    }

    /**
     * Przetwarza logowanie użytkownika i ustawia sesję.
     */
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
            } else {
                header('Location: /');
            }
            exit;
        }

        $this->render('login', ['error' => 'Nieprawidłowy email lub hasło.']);
    }

    /**
     * Wylogowuje użytkownika.
     */
    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: /login');
        exit;
    }

    /**
     * Wyświetla formularz wymuszonej zmiany hasła.
     */
    public function showForceChangePassword()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $this->render('change_password', [
            'title' => 'Wymagana zmiana hasła',
            'message' => 'To Twoje pierwsze logowanie. Ze względów bezpieczeństwa musisz ustawić nowe hasło.',
            'form_action' => '/force-password-change'
        ]);
    }

    /**
     * Przetwarza wymuszoną zmianę hasła.
     */
    public function forceChangePassword()
    {.
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($password) || $password !== $passwordConfirm) {
            $this->render('change_password', [
                'title' => 'Wymagana zmiana hasła',
                'message' => 'To Twoje pierwsze logowanie...',
                'error' => 'Hasła są puste lub się nie zgadzają.'
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
                'message' => 'To Twoje pierwsze logowanie...',
                'error' => 'Wystąpił błąd bazy danych.'
            ]);
        }
    }
}
