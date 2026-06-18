<?php

namespace App\Controllers;

use App\Models\Post;
use App\Core\Database;
use App\Core\Controller;

class HomeController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $sortBy = $_GET['sort'] ?? 'last_activity_at';
        $postModel = new Post();

        $posts = $postModel->getAll($_SESSION['user_id'], $sortBy);

        echo $this->twig->render('home.html.twig', [
            'posts' => $posts,
            'current_sort' => $sortBy,
            'user_id' => $_SESSION['user_id']
        ]);
    }

    public function createPost()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $rawContent = $_POST['content'] ?? '';

            // Czyszczenie HTML z syfu (zostawiamy tylko dozwolone formatowanie Quilla)
            $cleanContent = strip_tags($rawContent, '<p><br><b><i><u><strong><em><a><ul><ol><li>');

            // Sprawdzamy czy post nie jest pusty po wycięciu tagów
            if (!empty(trim(strip_tags($cleanContent)))) {
                $postModel = new \App\Models\Post();
                $postId = $postModel->create($_SESSION['user_id'], $cleanContent);

                // Obsługa wgrywania zdjęć
                if (!empty($_FILES['images']['name'][0])) {
                    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/posts/';
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

                    // Iterujemy po wszystkich wrzuconych plikach
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileType = mime_content_type($tmpName);

                            if (in_array($fileType, $allowedTypes)) {
                                $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                                $fileName = uniqid('post_' . $postId . '_') . '.' . $ext;

                                if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                                    $postModel->addImage($postId, '/uploads/posts/' . $fileName);
                                }
                            }
                        }
                    }
                }
            }
        }
        header('Location: /');
        exit;
    }

    public function toggleLike($postId)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $db = Database::getConnection();

        // Pobierz autora posta
        $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        // Nie pozwalamy lajkować własnych postów
        if ($post && $post['user_id'] != $_SESSION['user_id']) {
            $stmt = $db->prepare("SELECT * FROM post_likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$_SESSION['user_id'], $postId]);

            if ($stmt->fetch()) {
                $db->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?")
                    ->execute([$_SESSION['user_id'], $postId]);
            } else {
                $db->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)")
                    ->execute([$_SESSION['user_id'], $postId]);

                (new Post())->updateActivity($postId);
            }
        }

        header('Location: /');
        exit;
    }

    public function deletePost($id)
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        // Najpierw musimy się upewnić, że user próbuje usunąć SWÓJ post
        $db = \App\Core\Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);

        if ($stmt->fetch()) {
            $postModel = new \App\Models\Post();

            // 1. Pobieramy ścieżki do plików
            $images = $postModel->getImagesByPostId($id);

            // 2. Usuwamy fizyczne pliki z serwera
            $documentRoot = $_SERVER['DOCUMENT_ROOT'];
            foreach ($images as $imgPath) {
                $fullPath = $documentRoot . $imgPath;
                // Sprawdzamy czy plik istnieje na dysku, żeby nie wywaliło błędu
                if (file_exists($fullPath) && is_file($fullPath)) {
                    unlink($fullPath);
                }
            }

            // 3. Usuwamy z bazy (ON DELETE CASCADE załatwi wyczyszczenie tabeli post_images)
            $postModel->delete($id, $_SESSION['user_id']);
        }

        // Wracamy tam, skąd kliknięto
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }

    // Wyświetla formularz edycji
    public function editPost($id)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $postModel = new \App\Models\Post();
        $post = $postModel->getById($id);

        // Zabezpieczenie: post musi istnieć i należeć do Ciebie
        if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
            (new ErrorController())->show(403, "Nie masz uprawnień do edycji tego wpisu.");
            return;
        }

        $this->render('post_edit', ['post' => $post]);
    }

    // Zapisuje zmiany do bazy
    public function updatePost($id)
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $rawContent = $_POST['content'] ?? '';
        $cleanContent = strip_tags($rawContent, '<p><br><b><i><u><strong><em><a><ul><ol><li><s><strike><span>');

        if (!empty(trim(strip_tags($cleanContent)))) {
            $postModel = new \App\Models\Post();
            // Przekazujemy ID usera z sesji jako dodatkowe zabezpieczenie na poziomie zapytania SQL
            $postModel->updateContent($id, $_SESSION['user_id'], $cleanContent);
        }

        // Po edycji wracamy na stronę główną
        header('Location: /');
        exit;
    }

    public function addComment($postId)
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        // Z komentarzy wycinamy cały HTML (nie chcemy tu Quilla, czysty tekst)
        $content = trim(strip_tags($_POST['content'] ?? ''));

        if (!empty($content)) {
            $commentModel = new \App\Models\Comment();
            $commentModel->add($postId, $_SESSION['user_id'], $content);

            // Podbijamy aktywność posta, żeby skoczył na górę tablicy (bump)
            $postModel = new \App\Models\Post();
            $postModel->updateActivity($postId);
        }

        // Wracamy tam, skąd kliknięto (główna albo profil)
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }

    public function toggleTheme()
    {
        // Odczytujemy aktualny motyw (domyślnie light)
        $currentTheme = $_SESSION['theme_preference'] ?? 'light';
        // Odwracamy go
        $newTheme = ($currentTheme === 'dark') ? 'light' : 'dark';

        // Zapisujemy nowy motyw w sesji
        $_SESSION['theme_preference'] = $newTheme;

        // Jeśli user jest zalogowany, aktualizujemy też bazę danych
        if (isset($_SESSION['user_id'])) {
            $userModel = new \App\Models\User();
            $userModel->updateTheme($_SESSION['user_id'], $newTheme);
        }

        // Przekierowujemy z powrotem na stronę, na której user kliknął guzik
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }
}