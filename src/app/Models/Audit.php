<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Audit
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Zapisuje zdarzenie w dzienniku audytu.
     */
    public function log(int $userId, string $action): bool
    {
        $stmt = $this->db->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
        return $stmt->execute([$userId, $action]);
    }

    /**
     * Pobiera wszystkie wpisy z dziennika audytu.
     */
    public function getLogs(): array
    {
        $stmt = $this->db->query("
            SELECT a.action, a.timestamp, u.username 
            FROM audit_log a 
            JOIN users u ON a.user_id = u.id 
            ORDER BY a.timestamp DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
