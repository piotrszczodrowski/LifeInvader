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
        echo $this->twig->render($view . '.html.twig', $data);
    }
}