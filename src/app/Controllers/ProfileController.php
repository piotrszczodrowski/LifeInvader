<?php
namespace App\Controllers;

use App\Models\User;
use App\Core\Controller;

class ProfileController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Wyświetla formularz edycji profilu zalogowanego użytkownika.
     */
    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userModel = new User();
        $user = $userModel->findById($_SESSION['user_id']);

        $this->render('profile_edit', ['user' => $user]);
    }

    /**
     * Aktualizuje profil użytkownika (bio, awatar).
     */
    public function update() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $userModel = new User();
        $currentUser = $userModel->findById($_SESSION['user_id']);

        $bio = substr(trim($_POST['bio'] ?? ''), 0, 255);
        $avatarPath = $currentUser['avatar_path'];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/';
            $fileTmp = $_FILES['avatar']['tmp_name'];
            $fileType = mime_content_type($fileTmp);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (in_array($fileType, $allowedTypes)) {
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('avatar_') . '.' . $ext;
                $destination = $uploadDir . $fileName;

                if (move_uploaded_file($fileTmp, $destination)) {
                    $avatarPath = '/uploads/avatars/' . $fileName;
                }
            }
        }

        $userModel->updateProfile($_SESSION['user_id'], $bio, $avatarPath);
        $_SESSION['avatar_path'] = $avatarPath;
        header('Location: /profile/edit?success=1');
        exit;
    }

    /**
     * Wyświetla publiczny profil użytkownika wraz z jego postami.
     */
    public function show($username)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userModel = new User();
        $profileUser = $userModel->findByUsername($username);

        if (!$profileUser) {
            (new ErrorController())->show(404, "Not Found");
            return;
        }

        $postModel = new \App\Models\Post();
        $posts = $postModel->getAllByUserId($profileUser['id'], $_SESSION['user_id']);

        $this->render('profile', [
            'profile_user' => $profileUser,
            'posts' => $posts
        ]);
    }
}
