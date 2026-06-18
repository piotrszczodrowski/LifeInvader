<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    protected Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../views');
        $this->twig = new Environment($loader, ['cache' => false]);
    }

    protected function render(string $view, array $data = [])
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->twig->addGlobal('session', $_SESSION ?? []);
            if (isset($_SESSION['user_id'])) {
                $messageModel = new \App\Models\Message();
                $data['global_unread_count'] = $messageModel->getGlobalUnreadCount($_SESSION['user_id']);
            }
        }
        echo $this->twig->render($view . '.html.twig', $data);
    }
}
