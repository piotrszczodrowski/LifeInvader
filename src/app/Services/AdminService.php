<?php

namespace App\Services;

use App\Models\User;
use App\Models\Audit;
use App\Core\Database;

class AdminService
{
    private $userModel;
    private $auditModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->auditModel = new Audit();
    }

    public function getDashboardData(): array
    {
        $users = $this->userModel->getAll();
        $db = Database::getConnection();
        $stats = [
            'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_posts' => $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
            'total_messages' => $db->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
        ];

        return [
            'users' => $users,
            'stats' => $stats,
        ];
    }

    public function deleteUser(int $userId, int $currentUserId): bool
    {
        if ($userId === $currentUserId) {
            return false;
        }

        $user = $this->userModel->findById($userId);
        if ($user) {
            $this->auditModel->log($currentUserId, "Usunięto użytkownika: {$user['username']} (ID: $userId)");
            return $this->userModel->delete($userId);
        }
        return false;
    }

    public function toggleRole(int $userId, int $currentUserId): bool
    {
        if ($userId === $currentUserId) {
            return false;
        }

        $user = $this->userModel->findById($userId);
        if ($user) {
            $newRole = ($user['role'] === 'admin') ? 'user' : 'admin';
            $this->auditModel->log($currentUserId, "Zmieniono rolę użytkownika {$user['username']} (ID: $userId) na: $newRole");
            return $this->userModel->updateRole($userId, $newRole);
        }
        return false;
    }

    public function getAuditLogs(): array
    {
        return $this->auditModel->getLogs();
    }
}