<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
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
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        $this->render('register');
    }

    public function register()
    {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm)) {
            $this->render('register', ['error' => 'Wypełnij wszystkie pola!']);
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->render('register', ['error' => 'Podane hasła nie są identyczne. Spróbuj ponownie.']);
            return;
        }

        $userModel = new User();
        try {
            $userModel->create($username, $email, $password);
            $this->render('login', ['success' => 'Konto utworzone! Możesz się zalogować.']);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $this->render('register', ['error' => 'Użytkownik o tym mailu lub nazwie już istnieje.']);
            } else {
                $this->render('register', ['error' => 'Błąd bazy danych: ' . $e->getMessage()]);
            }
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

            $_SESSION['must_change_password'] = $user['must_change_password'];

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
        // Bezpieczeństwo: Jeśli ktoś nie jest zalogowany, nie ma tu czego szukać
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Wywołujemy uniwersalny szablon ze zdefiniowaną wiadomością dedykowaną dla wymuszenia
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