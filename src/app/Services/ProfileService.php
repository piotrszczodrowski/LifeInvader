<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;

class ProfileService
{
    private $userModel;
    private $postModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->postModel = new Post();
    }

    public function updateProfile(int $userId, ?string $bio, ?array $avatarFile): array
    {
        $currentUser = $this->userModel->findById($userId);
        $avatarPath = $currentUser['avatar_path'];

        if (isset($avatarFile) && $avatarFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/';
            $fileTmp = $avatarFile['tmp_name'];
            $fileType = mime_content_type($fileTmp);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (in_array($fileType, $allowedTypes)) {
                $ext = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('avatar_') . '.' . $ext;
                $destination = $uploadDir . $fileName;

                if (move_uploaded_file($fileTmp, $destination)) {
                    if ($currentUser['avatar_path'] && $currentUser['avatar_path'] !== '/uploads/avatars/default.png' && file_exists($_SERVER['DOCUMENT_ROOT'] . $currentUser['avatar_path'])) {
                        unlink($_SERVER['DOCUMENT_ROOT'] . $currentUser['avatar_path']);
                    }
                    $avatarPath = '/uploads/avatars/' . $fileName;
                }
            }
        }

        $this->userModel->updateProfile($userId, $bio, $avatarPath);

        return ['avatar_path' => $avatarPath];
    }

    public function getProfile(string $username, int $currentUserId): ?array
    {
        $profileUser = $this->userModel->findByUsername($username);

        if (!$profileUser) {
            return null;
        }

        $posts = $this->postModel->getAllByUserId($profileUser['id'], $currentUserId);

        return [
            'profile_user' => $profileUser,
            'posts' => $posts
        ];
    }
}