<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// parse URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

//check if already installed
$lockFilePath = __DIR__ . '/install.lock';
$isInstalled = file_exists($lockFilePath);

//if not installed trigger install
if (!$isInstalled && $uri !== '/install' && $uri !== '/install/run') {
    header('Location: /install');
    exit;
}

// simple MVP Router
switch ($uri) {
    case '/':
    case '/index.php':
        $controller = new \App\Controllers\HomeController();
        $controller->index();
        break;

    case '/install':
        $controller = new \App\Controllers\InstallController();
        $controller->index();
        break;

    case '/install/run':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new \App\Controllers\InstallController();
            $controller->run();
        } else {
            header('Location: /install');
        }
        break;

    // another routes go here

    default:
        $errorController = new \App\Controllers\ErrorController();
        $errorController->show(404, "Niestety, strona której szukasz nie istnieje lub została przeniesiona.");
        break;
}