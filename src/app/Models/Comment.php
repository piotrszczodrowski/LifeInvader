<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Comment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Dodawanie nowego komentarza
    public function add(int $postId, int $userId, string $content): bool
    {
        $stmt = $this->db->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        return $stmt->execute([$postId, $userId, $content]);
    }

    // Pobieranie komentarzy dla konkretnego posta wraz z danymi autora
    public function getByPostId(int $postId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.avatar_path 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($comments as &$comment) {
            // Zabezpieczamy czysty tekst przed kodem HTML
            $safeContent = htmlspecialchars($comment['content']);
            // Odpalamy tagowanie na bezpiecznym tekście
            $comment['content'] = $this->formatMentions($safeContent);
        }

        return $comments;
    }

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
}