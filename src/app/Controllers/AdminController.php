<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Audit;
use App\Core\Database;

class AdminController extends Controller
{
    private Audit $audit;

    public function __construct()
    {
        parent::__construct();
        $this->audit = new Audit();
    }

    /**
     * Wyświetla główny panel administratora z listą użytkowników i statystykami.
     */
    public function index()
    {
        $userModel = new User();
        $users = $userModel->getAll();

        $db = Database::getConnection();
        $stats = [
            'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_posts' => $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
            'total_messages' => $db->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
        ];

        $this->render('admin/index', [
            'users' => $users,
            'stats' => $stats
        ]);
    }

    /**
     * Usuwa użytkownika o podanym ID.
     */
    public function deleteUser(int $id)
    {
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['error'] = "Nie możesz usunąć własnego konta administratora.";
            header('Location: /admin');
            exit;
        }

        $userModel = new User();
        $user = $userModel->findById($id);
        if ($user) {
            $this->audit->log($_SESSION['user_id'], "Usunięto użytkownika: {$user['username']} (ID: $id)");
            $userModel->delete($id);
        }

        header('Location: /admin');
        exit;
    }

    /**
     * Zmienia rolę użytkownika (admin/user).
     */
    public function toggleRole(int $id)
    {
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['error'] = "Nie możesz zmienić własnych uprawnień administratora.";
            header('Location: /admin');
            exit;
        }

        $userModel = new User();
        $user = $userModel->findById($id);

        if ($user) {
            $newRole = ($user['role'] === 'admin') ? 'user' : 'admin';
            $this->audit->log($_SESSION['user_id'], "Zmieniono rolę użytkownika {$user['username']} (ID: $id) na: $newRole");
            $userModel->updateRole($id, $newRole);
        }

        header('Location: /admin');
        exit;
    }
    
    /**
     * Wyświetla dziennik audytu.
     */
    public function audit()
    {
        $logs = $this->audit->getLogs();
        $this->render('admin/audit_log', ['logs' => $logs]);
    }
}
