<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use DateTime;
use DateTimeZone;

class MessageService
{
    private $messageModel;
    private $userModel;

    public function __construct()
    {
        $this->messageModel = new Message();
        $this->userModel = new User();
    }

    /**
     * Correctly formats an array of messages, interpreting DB time as UTC
     * and converting it to a full ISO 8601 string for JavaScript.
     */
    private function formatMessages(array $messages): array
    {
        return array_map(function ($msg) {
            // 1. Create a DateTime object, telling it the input string is UTC.
            $date = new DateTime($msg['created_at'], new DateTimeZone('UTC'));
            // 2. Set the object's timezone to the app's timezone for correct offset calculation.
            $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
            // 3. Format to ISO 8601. JS will handle the rest.
            $msg['created_at'] = $date->format(DateTime::ISO8601);
            return $msg;
        }, $messages);
    }

    public function getMessageData(int $userId, ?int $activeContactId): array
    {
        $contacts = $this->messageModel->getInbox($userId);
        $allUsers = $this->userModel->getAllExcept($userId);
        $activeContact = null;
        $conversation = [];

        if ($activeContactId) {
            $activeContact = $this->userModel->findById($activeContactId);
            if ($activeContact) {
                $this->messageModel->markAsRead($userId, $activeContact['id']);
                // Pass conversation through the formatter
                $conversation = $this->formatMessages($this->messageModel->getConversation($userId, $activeContact['id']));
            }
        }

        return [
            'contacts' => $contacts,
            'active_contact' => $activeContact,
            'conversation' => $conversation,
            'all_users' => $allUsers,
        ];
    }

    public function fetchNewMessages(int $userId, int $interlocutorId, int $lastMessageId): array
    {
        if (!$interlocutorId) {
            return ['error' => 'Brak parametrów'];
        }

        $this->messageModel->markAsRead($userId, $interlocutorId);
        $rawMessages = $this->messageModel->getNewMessages($userId, $interlocutorId, $lastMessageId);
        $formattedMessages = $this->formatMessages($rawMessages);

        $maxReadId = $this->messageModel->getMaxReadId($userId, $interlocutorId);

        return [
            'status' => 'success',
            'messages' => $formattedMessages,
            'current_user_id' => $userId,
            'max_read_id' => $maxReadId
        ];
    }

    public function sendMessage(int $senderId, int $receiverId, string $content): array
    {
        if (!$receiverId || empty($content)) {
            return ['status' => 'error', 'message' => 'Treść nie może być pusta'];
        }

        if ($receiverId === $senderId) {
            return ['status' => 'error', 'message' => 'Nie możesz pisać sam ze sobą'];
        }

        $message = $this->messageModel->send($senderId, $receiverId, $content);

        if ($message) {
            // Also format the single message on send
            $formattedMessage = $this->formatMessages([$message])[0];
            return ['status' => 'success', 'message' => $formattedMessage];
        }

        return ['status' => 'error', 'message' => 'Nie udało się wysłać wiadomości.'];
    }

    public function getCounters(int $userId): array
    {
        $globalUnread = $this->messageModel->getGlobalUnreadCount($userId);
        $inbox = $this->messageModel->getInbox($userId);

        $threads = array_map(fn($c) => ['user_id' => (int)$c['id'], 'unread_count' => (int)$c['unread_count']], $inbox);

        return [
            'status' => 'success',
            'global_unread_count' => $globalUnread,
            'threads' => $threads
        ];
    }
}