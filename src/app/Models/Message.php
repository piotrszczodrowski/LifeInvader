<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Message
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Zapisuje nową wiadomość
    public function send(int $senderId, int $receiverId, string $content): bool
    {
        $stmt = $this->db->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        return $stmt->execute([$senderId, $receiverId, $content]);
    }

    // Pobiera listę użytkowników, z którymi kiedykolwiek pisaliśmy (do lewego paska "Skrzynki")
    public function getInbox(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.avatar_path, MAX(m.created_at) as latest_msg,
                   SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
            FROM users u
            JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
            WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
            GROUP BY u.id, u.username, u.avatar_path
            ORDER BY latest_msg DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pobiera całą historię rozmowy przy pierwszym załadowaniu strony
    public function getConversation(int $user1, int $user2): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.avatar_path
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user1, $user2, $user2, $user1]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // SERCE AJAXA: Pobiera tylko te wiadomości, których ID jest większe od ostatnio wyświetlonego
    public function getNewMessages(int $user1, int $user2, int $lastMessageId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.avatar_path
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
            AND m.id > ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user1, $user2, $user2, $user1, $lastMessageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead(int $receiverId, int $senderId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
        ");
        return $stmt->execute([$receiverId, $senderId]);
    }

    public function getGlobalUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getMaxReadId(int $senderId, int $receiverId): int
    {
        $stmt = $this->db->prepare("SELECT MAX(id) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 1");
        $stmt->execute([$senderId, $receiverId]);
        return (int)$stmt->fetchColumn();
    }
}