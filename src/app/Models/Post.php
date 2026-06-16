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

    public function create(int $userId, string $content): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO posts (user_id, content, created_at, last_activity_at) 
            VALUES (:user_id, :content, NOW(), NOW())
        ");

        return $stmt->execute([
            'user_id' => $userId,
            'content' => $content
        ]);
    }

    public function getAll(int $currentUserId, string $sortBy = 'last_activity_at'): array
    {
        $allowedSort = ['created_at', 'last_activity_at'];
        $orderBy = in_array($sortBy, $allowedSort) ? $sortBy : 'last_activity_at';

        // Dodajemy sub-zapytanie sprawdzające obecność lajka dla danego usera
        $stmt = $this->db->prepare("
        SELECT p.*, u.username,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
        EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = :uid) as is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.$orderBy DESC
    ");

        $stmt->execute(['uid' => $currentUserId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
}