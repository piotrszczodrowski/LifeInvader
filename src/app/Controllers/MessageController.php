<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\MessageService;

class MessageController extends Controller
{
    public function __construct(private MessageService $messageService)
    {
        parent::__construct();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    public function index()
    {
        $activeContactId = $_GET['user_id'] ?? null;
        $data = $this->messageService->getMessageData($_SESSION['user_id'], $activeContactId);
        $this->render('messages/index', $data);
    }

    public function fetchNew()
    {
        header('Content-Type: application/json');
        $interlocutorId = $_GET['user_id'] ?? 0;
        $lastMessageId = $_GET['last_id'] ?? 0;
        $response = $this->messageService->fetchNewMessages($_SESSION['user_id'], (int)$interlocutorId, (int)$lastMessageId);
        echo json_encode($response);
        exit;
    }

    public function send()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Zły protokół']);
            exit;
        }
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $response = $this->messageService->sendMessage($_SESSION['user_id'], $receiverId, $content);
        echo json_encode($response);
        exit;
    }

    public function getCounters()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Niezalogowany']);
            exit;
        }
        $response = $this->messageService->getCounters($_SESSION['user_id']);
        echo json_encode($response);
        exit;
    }
}