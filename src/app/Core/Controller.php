<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    protected Environment $twig;

    public function __construct()
    {
        // twig templates directory
        $loader = new FilesystemLoader(__DIR__ . '/../../views');

        // twig init
        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);

        $this->twig->addGlobal('session', $_SESSION ?? []);
    }

    // method for controllers to get views
    protected function render(string $view, array $data = [])
    {
        if (isset($_SESSION['user_id'])) {
            $messageModel = new \App\Models\Message();
            $data['global_unread_count'] = $messageModel->getGlobalUnreadCount($_SESSION['user_id']);
        }
        echo $this->twig->render($view . '.html.twig', $data);
    }
}