<?php
namespace App\Controllers;

use App\Models\User;
use App\Core\Controller;

class ProfileController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userModel = new User();
        $user = $userModel->findById($_SESSION['user_id']);

        $this->render('profile_edit', ['user' => $user]);
    }

    public function update() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $userModel = new User();
        $currentUser = $userModel->findById($_SESSION['user_id']);

        // Obcinamy bio do 255 znaków dla bezpieczeństwa
        $bio = substr(trim($_POST['bio'] ?? ''), 0, 255);
        $avatarPath = $currentUser['avatar_path']; // Domyślnie zostaje stary awatar

        // Obsługa uploadu pliku
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Ścieżka docelowa (zakładając, że index.php jest w katalogu głównym)
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/';

            // Weryfikacja typu pliku (MIME type)
            $fileTmp = $_FILES['avatar']['tmp_name'];
            $fileType = mime_content_type($fileTmp);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (in_array($fileType, $allowedTypes)) {
                // Generujemy unikalną nazwę pliku, np. avatar_64f1a2b3c.jpg
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('avatar_') . '.' . $ext;
                $destination = $uploadDir . $fileName;

                // Przenosimy plik z folderu tymczasowego do naszego
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

    public function show($username)
    {
        // Jeśli ktoś nie jest zalogowany, wywalamy na login
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getConnection();

        // Szukamy użytkownika po nazwie
        $stmt = $db->prepare("SELECT id, username, bio, avatar_path, created_at FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $profileUser = $stmt->fetch();

        if (!$profileUser) {
            (new ErrorController())->show(404, "Nie znaleziono takiego użytkownika.");
            return;
        }

        // Pobieramy jego posty
        $postModel = new \App\Models\Post();
        $posts = $postModel->getAllByUserId($profileUser['id'], $_SESSION['user_id']);

        $this->render('profile', [
            'profile_user' => $profileUser,
            'posts' => $posts
        ]);
    }
}