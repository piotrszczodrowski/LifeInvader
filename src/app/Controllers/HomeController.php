<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PostService;
use App\Models\User;
use App\Models\Post;

class HomeController extends Controller
{
    private PostService $postService;

    // Twój Router.php z gałęzi refactor automatycznie wstrzyknie tu PostService (Dependency Injection)!
    public function __construct(PostService $postService = null)
    {
        parent::__construct();
        $this->postService = $postService ?? new PostService();
    }

    public function index()
    {
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $sort = $_GET['sort'] ?? 'last_activity_at';

        // Serwis robi całą magię z paginacją, wyszukiwaniem i zapytaniami SQL
        $data = $this->postService->getPosts($page, $limit, $search, $sort);

        if (isset($_GET['export']) && $_GET['export'] === 'json') {
            $allData = $this->postService->getPosts(1, 99999, $search, $sort);
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="lifeinvader_posts_'.date('Ymd_Hi').'.json"');
            echo json_encode($allData['posts'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $openComments = $_SESSION['open_comments'] ?? null;
        unset($_SESSION['open_comments']);

        $this->render('home', array_merge($data, [
            'search_query' => $search,
            'current_sort' => $sort,
            'open_comments' => $openComments
        ]));
    }

    public function createPost()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            // Kontroler jest chudy - po prostu podaje dane dalej do serwisu
            $this->postService->createPost($_SESSION['user_id'], $_POST['content'] ?? '', $_FILES['images'] ?? []);
        }
        header('Location: /');
        exit;
    }

    public function toggleLike($id) // Nazwa parametru dopasowana do Routera
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $this->postService->toggleLike((int)$id, (int)$_SESSION['user_id']);

        $referer = strtok($_SERVER['HTTP_REFERER'] ?? '/', '#');
        header("Location: " . $referer . "#post-" . $id);
        exit;
    }

    public function deletePost($id) // Nazwa parametru dopasowana do Routera
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $this->postService->deletePost((int)$id, (int)$_SESSION['user_id']);

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }

    public function editPost($id) // Nazwa parametru dopasowana do Routera
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Zwykły odczyt można bezpiecznie zostawić w modelu
        $postModel = new Post();
        $post = $postModel->getById($id);

        if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
            (new \App\Controllers\ErrorController())->show(403, "Nie masz uprawnień do edycji tego wpisu.");
            return;
        }

        $this->render('post_edit', ['post' => $post]);
    }

    public function updatePost($id) // Nazwa parametru dopasowana do Routera
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $this->postService->updatePost((int)$id, (int)$_SESSION['user_id'], $_POST['content'] ?? '');

        header('Location: /');
        exit;
    }

    public function addComment($postId) // Dopasowane do {postId} z routes.php
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        // Serwis przyjmuje typ int, więc rzutujemy bezpiecznie
        $this->postService->addComment((int)$postId, (int)$_SESSION['user_id'], $_POST['content'] ?? '');

        $_SESSION['open_comments'] = $postId;

        $referer = strtok($_SERVER['HTTP_REFERER'] ?? '/', '#');
        header("Location: " . $referer . "#post-" . $postId);
        exit;
    }

    public function toggleTheme()
    {
        $currentTheme = $_SESSION['theme_preference'] ?? 'light';
        $newTheme = ($currentTheme === 'dark') ? 'light' : 'dark';
        $_SESSION['theme_preference'] = $newTheme;

        if (isset($_SESSION['user_id'])) {
            $userModel = new User();
            $userModel->updateTheme($_SESSION['user_id'], $newTheme);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }
}

//
//namespace App\Controllers;
//
//use App\Models\Post;
//use App\Core\Database;
//use App\Core\Controller;
//
//class HomeController extends Controller
//{
//    public function __construct()
//    {
//        parent::__construct();
//    }
//
//    /**
//     * Wyświetla stronę główną z postami, obsługuje paginację, wyszukiwanie i sortowanie.
//     * Obsługuje również eksport danych do formatu JSON.
//     */
//    public function index()
//    {
//        $postModel = new Post();
//
//        $search = trim($_GET['q'] ?? '');
//        $page = max(1, (int)($_GET['page'] ?? 1));
//        $limit = 10;
//        $offset = ($page - 1) * $limit;
//        $sort = $_GET['sort'] ?? 'last_activity_at';
//
//        $posts = $postModel->getPaginatedPosts($limit, $offset, $search, $sort);
//
//        if (isset($_GET['export']) && $_GET['export'] === 'json') {
//            $allFilteredPosts = $postModel->getPaginatedPosts(99999, 0, $search, $sort);
//            header('Content-Type: application/json');
//            header('Content-Disposition: attachment; filename="lifeinvader_posts_'.date('Ymd_Hi').'.json"');
//            echo json_encode($allFilteredPosts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
//            exit;
//        }
//
//        $totalPosts = $postModel->getTotalCount($search);
//        $totalPages = ceil($totalPosts / $limit);
//
//        // Odbieramy i od razu niszczymy flagę o otwartym komentarzu, żeby zadziałała tylko po przeładowaniu
//        $openComments = $_SESSION['open_comments'] ?? null;
//        unset($_SESSION['open_comments']);
//
//        $this->render('home', [
//            'posts' => $posts,
//            'current_page' => $page,
//            'total_pages' => $totalPages,
//            'search_query' => $search,
//            'current_sort' => $sort,
//            'open_comments' => $openComments
//        ]);
//    }
//
//    /**
//     * Tworzy nowy post na podstawie danych z formularza, włącznie z obsługą uploadu zdjęć.
//     */
//    public function createPost()
//    {
//        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
//            $rawContent = $_POST['content'] ?? '';
//            $cleanContent = strip_tags($rawContent, '<p><br><b><i><u><strong><em><a><ul><ol><li>');
//
//            if (!empty(trim(strip_tags($cleanContent)))) {
//                $postModel = new \App\Models\Post();
//                $postId = $postModel->create($_SESSION['user_id'], $cleanContent);
//
//                if (!empty($_FILES['images']['name'][0])) {
//                    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/posts/';
//                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
//                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
//                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
//                            $fileType = mime_content_type($tmpName);
//                            if (in_array($fileType, $allowedTypes)) {
//                                $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
//                                $fileName = uniqid('post_' . $postId . '_') . '.' . $ext;
//                                if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
//                                    $postModel->addImage($postId, '/uploads/posts/' . $fileName);
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//        header('Location: /');
//        exit;
//    }
//
//    /**
//     * Przełącza polubienie posta (dodaje lub usuwa).
//     */
//    public function toggleLike($id) // Zmiana z $postId na $id
//    {
//        if (!isset($_SESSION['user_id'])) {
//            header('Location: /login');
//            exit;
//        }
//
//        $db = Database::getConnection();
//        $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
//        $stmt->execute([$id]);
//        $post = $stmt->fetch();
//
//        if ($post && $post['user_id'] != $_SESSION['user_id']) {
//            $stmt = $db->prepare("SELECT * FROM post_likes WHERE user_id = ? AND post_id = ?");
//            $stmt->execute([$_SESSION['user_id'], $id]);
//
//            if ($stmt->fetch()) {
//                $db->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?")
//                    ->execute([$_SESSION['user_id'], $id]);
//            } else {
//                $db->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)")
//                    ->execute([$_SESSION['user_id'], $id]);
//                (new Post())->updateActivity($id);
//            }
//        }
//
//        // Czyścimy stary adres z hashtagów i dodajemy kotwicę (skok do posta)
//        $referer = strtok($_SERVER['HTTP_REFERER'] ?? '/', '#');
//        header("Location: " . $referer . "#post-" . $id);
//        exit;
//    }
//
//    /**
//     * Usuwa post i powiązane z nim zdjęcia.
//     */
//    public function deletePost($id)
//    {
//        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
//            header('Location: /login');
//            exit;
//        }
//
//        $db = \App\Core\Database::getConnection();
//        $stmt = $db->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
//        $stmt->execute([$id, $_SESSION['user_id']]);
//
//        if ($stmt->fetch()) {
//            $postModel = new \App\Models\Post();
//            $images = $postModel->getImagesByPostId($id);
//            $documentRoot = $_SERVER['DOCUMENT_ROOT'];
//            foreach ($images as $imgPath) {
//                $fullPath = $documentRoot . $imgPath;
//                if (file_exists($fullPath) && is_file($fullPath)) {
//                    unlink($fullPath);
//                }
//            }
//            $postModel->delete($id, $_SESSION['user_id']);
//        }
//
//        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
//        header("Location: " . $referer);
//        exit;
//    }
//
//    /**
//     * Wyświetla formularz edycji posta.
//     */
//    public function editPost($id)
//    {
//        if (!isset($_SESSION['user_id'])) {
//            header('Location: /login');
//            exit;
//        }
//
//        $postModel = new \App\Models\Post();
//        $post = $postModel->getById($id);
//
//        if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
//            (new ErrorController())->show(403, "Nie masz uprawnień do edycji tego wpisu.");
//            return;
//        }
//
//        $this->render('post_edit', ['post' => $post]);
//    }
//
//    /**
//     * Aktualizuje treść posta w bazie danych.
//     */
//    public function updatePost($id)
//    {
//        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
//            header('Location: /login');
//            exit;
//        }
//
//        $rawContent = $_POST['content'] ?? '';
//        $cleanContent = strip_tags($rawContent, '<p><br><b><i><u><strong><em><a><ul><ol><li><s><strike><span>');
//
//        if (!empty(trim(strip_tags($cleanContent)))) {
//            $postModel = new \App\Models\Post();
//            $postModel->updateContent($id, $_SESSION['user_id'], $cleanContent);
//        }
//
//        header('Location: /');
//        exit;
//    }
//
//    /**
//     * Dodaje nowy komentarz do posta.
//     */
//    public function addComment($postId)
//    {
//        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
//            header('Location: /login');
//            exit;
//        }
//
//        $content = trim(strip_tags($_POST['content'] ?? ''));
//
//        if (!empty($content)) {
//            $commentModel = new \App\Models\Comment();
//            $commentModel->add($postId, $_SESSION['user_id'], $content);
//            $postModel = new \App\Models\Post();
//            $postModel->updateActivity($postId);
//
//            // Ustawienie flagi dla Twiga: ten post ma mieć otwarte komentarze po przeładowaniu!
//            $_SESSION['open_comments'] = $postId;
//        }
//
//        // Czyścimy stary adres z hashtagów i dodajemy kotwicę (skok do posta)
//        $referer = strtok($_SERVER['HTTP_REFERER'] ?? '/', '#');
//        header("Location: " . $referer . "#post-" . $postId);
//        exit;
//    }
//
//    /**
//     * Przełącza motyw graficzny (jasny/ciemny).
//     */
//    public function toggleTheme()
//    {
//        $currentTheme = $_SESSION['theme_preference'] ?? 'light';
//        $newTheme = ($currentTheme === 'dark') ? 'light' : 'dark';
//        $_SESSION['theme_preference'] = $newTheme;
//
//        if (isset($_SESSION['user_id'])) {
//            $userModel = new \App\Models\User();
//            $userModel->updateTheme($_SESSION['user_id'], $newTheme);
//        }
//
//        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
//        header("Location: " . $referer);
//        exit;
//    }
//}