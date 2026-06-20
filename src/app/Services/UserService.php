<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function changePassword(int $userId, string $password): bool
    {
        return $this->userModel->updatePassword($userId, $password);
    }
}