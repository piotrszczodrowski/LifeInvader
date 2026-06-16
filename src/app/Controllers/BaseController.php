<?php
namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class BaseController {
    protected $twig;

    public function __construct() {
        $loader = new FilesystemLoader(__DIR__ . '/../../views');
        $this->twig = new Environment($loader);
        $this->twig->addGlobal('session', $_SESSION);
    }

    protected function render($view, $data = []) {
        echo $this->twig->render($view . '.html.twig', $data);
    }
}