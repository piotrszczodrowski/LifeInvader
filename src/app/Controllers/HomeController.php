<?php

namespace App\Controllers;

use App\Models\Post;
use App\Core\Database;

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
            $content = trim($_POST['content'] ?? '');
            if (!empty($content)) {
                $postModel = new Post();
                $postModel->create($_SESSION['user_id'], $content);
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

    public function editPost($postId) {
        // Tu będzie logika edycji
        echo "Edycja posta nr: " . $postId;
    }
}