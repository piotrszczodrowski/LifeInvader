<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// parse URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// simple MVP Router
switch ($uri) {
    case '/':
    case '/index.php':
        $controller = new \App\Controllers\HomeController();
        $controller->index();
        break;

    // another routes go here

    default:
        http_response_code(404);
        echo "Błąd 404 - Nie znaleziono takiej strony";
        break;
}