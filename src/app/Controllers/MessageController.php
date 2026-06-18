<?php

namespace App\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Core\Controller;


class MessageController extends Controller
{
    // Czat jest dostępny tylko dla zalogowanych!
    public function __construct()
    {
        parent::__construct();

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    // Główny widok skrzynki (na razie zwracamy tylko pusty widok z listą kontaktów)
    public function index()
    {
        $messageModel = new Message();
        $userModel = new User();

        $contacts = $messageModel->getInbox($_SESSION['user_id']);
        $allUsers = $userModel->getAllExcept($_SESSION['user_id']);

        $activeContact = null;
        $conversation = [];

        $activeContactId = $_GET['user_id'] ?? null;
        if ($activeContactId) {
            $activeContact = $userModel->findById((int)$activeContactId);
            if ($activeContact) {
                // KIEDY WCHODZĘ W CZAT: Oznacz jako przeczytane
                $messageModel->markAsRead($_SESSION['user_id'], $activeContact['id']);
                $conversation = $messageModel->getConversation($_SESSION['user_id'], $activeContact['id']);
            }
        }

        $this->render('messages/index', [
            'contacts' => $contacts,
            'active_contact' => $activeContact,
            'conversation' => $conversation,
            'all_users' => $allUsers
        ]);
    }

    // --- WYMÓG AKADEMICKI: ENDPOINT ZWRACAJĄCY JSON ---
    // Służy wyłącznie do komunikacji z JavaScriptem (AJAX)
    public function fetchNew()
    {
        header('Content-Type: application/json');
        $interlocutorId = $_GET['user_id'] ?? 0;
        $lastMessageId = $_GET['last_id'] ?? 0;

        if (!$interlocutorId || !$lastMessageId) {
            echo json_encode(['error' => 'Brak parametrów']);
            exit;
        }

        $messageModel = new Message();
        $messageModel->markAsRead($_SESSION['user_id'], (int)$interlocutorId);

        $rawMessages = $messageModel->getNewMessages($_SESSION['user_id'], (int)$interlocutorId, (int)$lastMessageId);

        $cleanMessages = [];
        foreach ($rawMessages as $msg) {
            $msg['content'] = htmlspecialchars($msg['content']);
            $cleanMessages[] = $msg;
        }

        // NOWE: Pobieramy ID ostatniej przeczytanej wiadomości
        $maxReadId = $messageModel->getMaxReadId($_SESSION['user_id'], (int)$interlocutorId);

        // Zwracamy to do JavaScriptu
        echo json_encode([
            'status' => 'success',
            'messages' => $cleanMessages,
            'current_user_id' => $_SESSION['user_id'],
            'max_read_id' => $maxReadId // <--- DODANE
        ]);
        exit;
    }

    public function send()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Zły protokół zapytań']);
            exit;
        }

        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if (!$receiverId || empty($content)) {
            echo json_encode(['status' => 'error', 'message' => 'Treść wiadomości nie może być pusta']);
            exit;
        }

        if ($receiverId === $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'Nie możesz pisać sam ze sobą']);
            exit;
        }

        $messageModel = new Message();
        $success = $messageModel->send($_SESSION['user_id'], $receiverId, $content);

        if ($success) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Błąd zapisu w bazie danych']);
        }
        exit;
    }

    public function getCounters()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Niezalogowany']);
            exit;
        }

        $messageModel = new Message();
        $globalUnread = $messageModel->getGlobalUnreadCount($_SESSION['user_id']);
        $inbox = $messageModel->getInbox($_SESSION['user_id']);

        $threads = [];
        foreach ($inbox as $c) {
            $threads[] = [
                'user_id' => (int)$c['id'],
                'unread_count' => (int)$c['unread_count']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'global_unread_count' => $globalUnread,
            'threads' => $threads
        ]);
        exit;
    }
}