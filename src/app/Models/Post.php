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

    /**
     * Formatuje wzmianki @username w treści posta na klikalne linki.
     */
    private function formatMentions(string $content): string
    {
        return preg_replace_callback('/@([a-zA-Z0-9_-]+)/', function ($matches) {
            static $verifiedUsers = [];
            $username = $matches[1];

            if (!isset($verifiedUsers[$username])) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $verifiedUsers[$username] = (bool)$stmt->fetch();
            }

            if ($verifiedUsers[$username]) {
                return '<a href="/profile/' . $username . '" class="text-primary fw-bold text-decoration-none">@' . $username . '</a>';
            }

            return $matches[0];
        }, $content);
    }

    /**
     * Pobiera stronę z postami z uwzględnieniem wyszukiwania i sortowania.
     */
    public function getPaginatedPosts(int $limit, int $offset, string $searchQuery = '', string $sort = 'last_activity_at'): array
    {
        $orderBy = 'p.last_activity_at DESC';
        if ($sort === 'created_at') {
            $orderBy = 'p.created_at DESC';
        }

        $sql = "
            SELECT p.*, u.username, u.avatar_path,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                   (SELECT GROUP_CONCAT(image_path SEPARATOR ',') FROM post_images WHERE post_id = p.id) as images
            FROM posts p JOIN users u ON p.user_id = u.id
        ";

        $params = [];
        if (!empty($searchQuery)) {
            $sql .= " WHERE p.content LIKE ? OR u.username LIKE ? ";
            $params[] = "%$searchQuery%";
            $params[] = "%$searchQuery%";
        }

        $sql .= " ORDER BY $orderBy LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param, \PDO::PARAM_STR);
        }
        $stmt->bindValue($paramIndex++, $limit, \PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, \PDO::PARAM_INT);

        $stmt->execute();
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($posts as &$post) {
            $post['images'] = $post['images'] ? explode(',', $post['images']) : [];
            $post['content'] = $this->formatMentions($post['content']);
        }

        return $posts;
    }

    /**
     * Zwraca całkowitą liczbę postów pasujących do zapytania.
     */
    public function getTotalCount(string $searchQuery = ''): int
    {
        $sql = "SELECT COUNT(*) FROM posts p JOIN users u ON p.user_id = u.id";
        $params = [];

        if (!empty($searchQuery)) {
            $sql .= " WHERE p.content LIKE ? OR u.username LIKE ?";
            $params[] = "%$searchQuery%";
            $params[] = "%$searchQuery%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function updateActivity(int $postId): bool
    {
        $stmt = $this->db->prepare("UPDATE posts SET last_activity_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $postId]);
    }

    public function delete(int $postId, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM posts WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $postId, 'user_id' => $userId]);
    }

    /**
     * Pobiera wszystkie posty danego użytkownika.
     */
    public function getAllByUserId(int $profileUserId, int $currentUserId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, u.avatar_path,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
            EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = :current_uid) as is_liked,
            (SELECT GROUP_CONCAT(image_path SEPARATOR ',') FROM post_images WHERE post_id = p.id) as images
            FROM posts p JOIN users u ON p.user_id = u.id
            WHERE p.user_id = :profile_uid
            ORDER BY p.created_at DESC
        ");

        $stmt->execute(['current_uid' => $currentUserId, 'profile_uid' => $profileUserId]);
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $commentModel = new \App\Models\Comment();
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
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getById(int $postId)
    {
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateContent(int $postId, int $userId, string $content): bool
    {
        $stmt = $this->db->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$content, $postId, $userId]);
    }
}
