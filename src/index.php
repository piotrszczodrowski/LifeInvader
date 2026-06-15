<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();

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

//check must_change_password

if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
    // Pozwalamy wejść tylko na zmianę hasła LUB na wylogowanie
    if ($uri !== '/force-password-change' && $uri !== '/logout') {
        header('Location: /force-password-change');
        exit; // Zatrzymujemy ruch - nie wpuszczamy do routera
    }
}

// simple MVP Router
switch ($uri) {
    case '/':
    case '/index.php':
        $homeController = new \App\Controllers\HomeController();
        $homeController->index();
        break;

    // install
    case '/install':
        $installController = new \App\Controllers\InstallController();
        $installController->index();
        break;

    case '/install/run':
        $installController = new \App\Controllers\InstallController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $installController->run();
        } else {
            header('Location: /install');
        }
        break;

    // auth
    case '/login':
        $authController = new \App\Controllers\AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            $authController->showLogin();
        }
        break;

    case '/register':
        $authController = new \App\Controllers\AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        } else {
            $authController->showRegister();
        }
        break;

    case '/logout':
        $authController = new \App\Controllers\AuthController();
        $authController->logout();

    case '/force-password-change':
        $authController = new \App\Controllers\AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->forceChangePassword();
        } else {
            $authController->showForceChangePassword();
        }
        break;

    // another routes go here

    default:
        $errorController = new \App\Controllers\ErrorController();
        $errorController->show(404, "Niestety, strona której szukasz nie istnieje lub została przeniesiona.");
        break;
}