<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Post
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(int $userId, string $content): int
    {
        $stmt = $this->db->prepare("INSERT INTO posts (user_id, content) VALUES (:uid, :content)");
        $stmt->execute(['uid' => $userId, 'content' => $content]);
        return (int) $this->db->lastInsertId();
    }

    public function addImage(int $postId, string $imagePath)
    {
        $stmt = $this->db->prepare("INSERT INTO post_images (post_id, image_path) VALUES (?, ?)");
        return $stmt->execute([$postId, $imagePath]);
    }

    // Prywatna metoda do zamiany @username na klikalne linki do profilu
    private function formatMentions(string $content): string
    {
        return preg_replace_callback('/@([a-zA-Z0-9_-]+)/', function ($matches) {
            static $verifiedUsers = []; // Prosty cache w pamięci
            $username = $matches[1];

            // Sprawdzamy bazę tylko jeśli jeszcze nie weryfikowaliśmy tego nicku
            if (!isset($verifiedUsers[$username])) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $verifiedUsers[$username] = (bool)$stmt->fetch();
            }

            // Jeśli user istnieje w bazie, robimy z niego klikalny link
            if ($verifiedUsers[$username]) {
                return '<a href="/profile/' . $username . '" class="text-primary fw-bold text-decoration-none">@' . $username . '</a>';
            }

            // Jeśli to fejk, zwracamy nietknięty tekst (np. "@wymyslony_nick")
            return $matches[0];
        }, $content);
    }

    public function getAll(int $currentUserId, string $sortBy = 'last_activity_at'): array
    {
        $allowedSort = ['created_at', 'last_activity_at'];
        $orderBy = in_array($sortBy, $allowedSort) ? $sortBy : 'last_activity_at';

        $stmt = $this->db->prepare("
            SELECT p.*, u.username, u.avatar_path,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
            EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = :uid) as is_liked,
            (SELECT GROUP_CONCAT(image_path SEPARATOR ',') FROM post_images WHERE post_id = p.id) as images
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.$orderBy DESC
        ");

        $stmt->execute(['uid' => $currentUserId]);
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $commentModel = new \App\Models\Comment();

        // Rozbijamy string ze zdjęciami na tablicę i formatujemy tagi @user
        foreach ($posts as &$post) {
            $post['images'] = $post['images'] ? explode(',', $post['images']) : [];
            $post['content'] = $this->formatMentions($post['content']);
            $post['comments_list'] = $commentModel->getByPostId($post['id']);
        }

        return $posts;
    }

    public function updateActivity(int $postId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE posts 
            SET last_activity_at = NOW() 
            WHERE id = :id
        ");

        return $stmt->execute(['id' => $postId]);
    }

    public function delete(int $postId, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM posts WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $postId, 'user_id' => $userId]);
    }

    public function getAllByUserId(int $profileUserId, int $currentUserId): array
    {
        $stmt = $this->db->prepare("
        SELECT p.*, u.username, u.avatar_path,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
        EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = :current_uid) as is_liked,
        (SELECT GROUP_CONCAT(image_path SEPARATOR ',') FROM post_images WHERE post_id = p.id) as images
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.user_id = :profile_uid
        ORDER BY p.created_at DESC
        ");

        $stmt->execute([
            'current_uid' => $currentUserId,
            'profile_uid' => $profileUserId
        ]);

        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $commentModel = new \App\Models\Comment();

        // Rozbijamy string ze zdjęciami na tablicę i formatujemy tagi @user
        foreach ($posts as &$post) {
            $post['images'] = $post['images'] ? explode(',', $post['images']) : [];
            $post['content'] = $this->formatMentions($post['content']);
            $post['comments_list'] = $commentModel->getByPostId($post['id']);
        }

        return $posts;
    }

    public function getImagesByPostId(int $postId): array
    {
        $stmt = $this->db->prepare("SELECT image_path FROM post_images WHERE post_id = ?");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN); // Zwraca płaską tablicę ze ścieżkami
    }

    // Pobiera pojedynczy post (potrzebne do formularza edycji)
    public function getById(int $postId)
    {
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Aktualizuje treść posta (tylko jeśli autorem jest dany użytkownik)
    public function updateContent(int $postId, int $userId, string $content): bool
    {
        $stmt = $this->db->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$content, $postId, $userId]);
    }
}