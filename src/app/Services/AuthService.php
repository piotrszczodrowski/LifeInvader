<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Validates the Cloudflare Turnstile response.
     * @return string|null Returns an error message string on failure, or null on success.
     */
    private function validateTurnstile(string $turnstileResponse): ?string
    {
        if (empty($turnstileResponse)) {
            return "Potwierdź, że jesteś człowiekiem.";
        }

        $secretKey = $_ENV['CLOUDFLARE_SECRET_KEY'] ?? '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $secretKey, 'response' => $turnstileResponse]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!$response['success']) {
            return "Weryfikacja anty-botowa nie powiodła się.";
        }

        return null;
    }

    public function register(array $data): array
    {
        $errors = [];

        // 1. Walidacja Turnstile
        $captchaError = $this->validateTurnstile($data['turnstileResponse']);
        if ($captchaError) {
            $errors['captcha'] = $captchaError;
        }

        // 2. Walidacja pozostałych danych
        if (strlen($data['username']) < 3) $errors['username'] = "Login musi mieć co najmniej 3 znaki.";
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = "Niepoprawny format e-mail.";
        if (strlen($data['password']) < 6) $errors['password'] = "Hasło musi mieć minimum 6 znaków.";
        if (empty($data['birth_date'])) $errors['birth_date'] = "Musisz podać datę urodzenia.";
        if (!$data['tos']) $errors['tos'] = "Musisz zapoznać się z regulaminem.";

        // 3. Walidacja unikalności (tylko jeśli nie ma innych błędów)
        if (empty($errors)) {
            if ($this->userModel->findByUsername($data['username'])) $errors['username'] = "Taki użytkownik już istnieje.";
            if ($this->userModel->findByEmail($data['email'])) $errors['email'] = "Ten e-mail jest już zajęty.";
        }
        
        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $this->userModel->create(
            $data['username'],
            $data['email'],
            $data['password'],
            $data['birth_date'],
            $data['theme_preference']
        );

        return ['success' => true];
    }

    public function login(string $email, string $password, string $turnstileResponse): ?array
    {
        // 1. Walidacja Turnstile
        $captchaError = $this->validateTurnstile($turnstileResponse);
        if ($captchaError) {
            return ['error' => $captchaError];
        }

        // 2. Walidacja użytkownika
        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return ['error' => 'Nieprawidłowy email lub hasło.'];
    }
}