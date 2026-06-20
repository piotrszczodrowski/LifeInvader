<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AdminService;

class AdminController extends Controller
{
    public function __construct(private AdminService $adminService)
    {
        parent::__construct();
    }

    public function index()
    {
        $data = $this->adminService->getDashboardData();
        $this->render('admin/index', $data);
    }

    public function deleteUser(int $id)
    {
        if (!$this->adminService->deleteUser($id, $_SESSION['user_id'])) {
            $_SESSION['error'] = "Nie możesz usunąć własnego konta administratora.";
        }
        header('Location: /admin');
        exit;
    }

    public function toggleRole(int $id)
    {
        if (!$this->adminService->toggleRole($id, $_SESSION['user_id'])) {
            $_SESSION['error'] = "Nie możesz zmienić własnych uprawnień administratora.";
        }
        header('Location: /admin');
        exit;
    }

    public function audit()
    {
        $logs = $this->adminService->getAuditLogs();
        $this->render('admin/audit_log', ['logs' => $logs]);
    }
}