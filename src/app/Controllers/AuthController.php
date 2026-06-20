<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\UserService;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private UserService $userService
    ) {
        parent::__construct();
    }

    public function showLogin()
    {
        $this->render('login', ['cf_site_key' => $_ENV['CLOUDFLARE_SITE_KEY'] ?? '']);
    }

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

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'birth_date' => $_POST['birth_date'] ?? '',
                'tos' => isset($_POST['tos']),
                'turnstileResponse' => $_POST['cf-turnstile-response'] ?? '',
                'theme_preference' => $_SESSION['theme_preference'] ?? 'light'
            ];

            $result = $this->authService->register($data);

            if (isset($result['errors'])) {
                $_SESSION['errors'] = $result['errors'];
                $_SESSION['old'] = ['username' => $data['username'], 'email' => $data['email'], 'birth_date' => $data['birth_date']];
                header('Location: /register');
                exit;
            }

            header('Location: /login');
            exit;
        }
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';

            $user = $this->authService->login($email, $password, $turnstileResponse);

            if (isset($user['error'])) {
                $this->render('login', ['error' => $user['error'], 'cf_site_key' => $_ENV['CLOUDFLARE_SITE_KEY'] ?? '']);
                return;
            }

            if ($user) {
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
        }
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
            'message' => 'To Twoje pierwsze logowanie. Ze względów bezpieczeństwa musisz ustawić nowe hasło.',
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

        if (empty($password) || $password !== $passwordConfirm) {
            $this->render('change_password', [
                'title' => 'Wymagana zmiana hasła',
                'message' => 'To Twoje pierwsze logowanie...',
                'error' => 'Hasła są puste lub się nie zgadzają.'
            ]);
            return;
        }

        if ($this->userService->changePassword($_SESSION['user_id'], $password)) {
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