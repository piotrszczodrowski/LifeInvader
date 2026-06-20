<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ProfileService;
use App\Models\User;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService,
        private User $userModel
    ) {
        parent::__construct();
    }

    public function edit()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        $this->render('profile_edit', ['user' => $user]);
    }

    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $bio = substr(trim($_POST['bio'] ?? ''), 0, 255);
        $avatarFile = $_FILES['avatar'] ?? null;

        $result = $this->profileService->updateProfile($_SESSION['user_id'], $bio, $avatarFile);

        $_SESSION['avatar_path'] = $result['avatar_path'];
        header('Location: /profile/edit?success=1');
        exit;
    }



    public function show($username)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $profileData = $this->profileService->getProfile($username, $_SESSION['user_id']);

        if (!$profileData) {
            (new ErrorController())->show(404, "Not Found");
            return;
        }

        $this->render('profile', $profileData);
    }
}