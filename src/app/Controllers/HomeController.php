<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PostService;
use App\Services\ThemeService;
use App\Models\Post;

class HomeController extends Controller
{
    public function __construct(
        private PostService $postService,
        private ThemeService $themeService
    ) {
        parent::__construct();
    }

    public function index()
    {
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $sort = $_GET['sort'] ?? 'last_activity_at';

        if (isset($_GET['export']) && $_GET['export'] === 'json') {
            $postModel = new Post();
            $allFilteredPosts = $postModel->getPaginatedPosts(99999, 0, $search, $sort);
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="lifeinvader_posts_'.date('Ymd_Hi').'.json"');
            echo json_encode($allFilteredPosts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $data = $this->postService->getPosts($page, $limit, $search, $sort);

        $this->render('home', [
            'posts' => $data['posts'],
            'current_page' => $data['current_page'],
            'total_pages' => $data['total_pages'],
            'search_query' => $search,
            'current_sort' => $sort
        ]);
    }

    public function createPost()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $this->postService->createPost($_SESSION['user_id'], $_POST['content'] ?? '', $_FILES['images'] ?? null);
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
        $this->postService->toggleLike($postId, $_SESSION['user_id']);
        header('Location: /');
        exit;
    }

    public function deletePost($id)
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }
        $this->postService->deletePost($id, $_SESSION['user_id']);
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }

    public function editPost($id)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $postModel = new Post();
        $post = $postModel->getById($id);

        if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
            (new ErrorController())->show(403, "Nie masz uprawnień do edycji tego wpisu.");
            return;
        }

        $this->render('post_edit', ['post' => $post]);
    }

    public function updatePost($id)
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }
        $this->postService->updatePost($id, $_SESSION['user_id'], $_POST['content'] ?? '');
        header('Location: /');
        exit;
    }

    public function addComment($postId)
    {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }
        $this->postService->addComment($postId, $_SESSION['user_id'], $_POST['content'] ?? '');
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }

    public function toggleTheme()
    {
        $currentTheme = $_SESSION['theme_preference'] ?? 'light';
        $userId = $_SESSION['user_id'] ?? null;
        $newTheme = $this->themeService->toggleTheme($currentTheme, $userId);
        $_SESSION['theme_preference'] = $newTheme;

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: " . $referer);
        exit;
    }
}