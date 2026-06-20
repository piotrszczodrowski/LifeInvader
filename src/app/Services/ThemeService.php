<?php

namespace App\Services;

use App\Models\User;

class ThemeService
{
    public function toggleTheme(string $currentTheme, ?int $userId): string
    {
        $newTheme = ($currentTheme === 'dark') ? 'light' : 'dark';

        if ($userId) {
            $userModel = new User();
            $userModel->updateTheme($userId, $newTheme);
        }

        return $newTheme;
    }
}