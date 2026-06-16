<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. Check install
$lockFilePath = __DIR__ . '/install.lock';
$isInstalled = file_exists($lockFilePath);

if (!$isInstalled && $uri !== '/install' && $uri !== '/install/run') {
    header('Location: /install');
    exit;
}

// 2. Check password change
if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
    if ($uri !== '/force-password-change' && $uri !== '/logout') {
        header('Location: /force-password-change');
        exit;
    }
}

// 3. Routing (Static & Dynamic)

// A. Trasy Statyczne (Switch)
$handled = false;

switch ($uri) {
    case '/':
    case '/index.php':
        (new \App\Controllers\HomeController())->index();
        $handled = true;
        break;

    case '/install':
        (new \App\Controllers\InstallController())->index();
        $handled = true;
        break;

    case '/install/run':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new \App\Controllers\InstallController())->run();
        } else {
            header('Location: /install');
        }
        $handled = true;
        break;

    case '/login':
        $auth = new \App\Controllers\AuthController();
        ($_SERVER['REQUEST_METHOD'] === 'POST') ? $auth->login() : $auth->showLogin();
        $handled = true;
        break;

    case '/register':
        $auth = new \App\Controllers\AuthController();
        ($_SERVER['REQUEST_METHOD'] === 'POST') ? $auth->register() : $auth->showRegister();
        $handled = true;
        break;

    case '/logout':
        (new \App\Controllers\AuthController())->logout();

    case '/force-password-change':
        $auth = new \App\Controllers\AuthController();
        ($_SERVER['REQUEST_METHOD'] === 'POST') ? $auth->forceChangePassword() : $auth->showForceChangePassword();
        $handled = true;
        break;

    case '/post/create':
        (new \App\Controllers\HomeController())->createPost();
}

// B. Trasy Dynamiczne (Regex) - jeśli switch nic nie obsłużył
if (!$handled) {
    // Edit post: /post/edit/123
    if (preg_match('/^\/post\/edit\/(\d+)$/', $uri, $matches)) {
        (new \App\Controllers\HomeController())->editPost($matches[1]);
        $handled = true;
    }
    // Like post: /post/like/123
    elseif (preg_match('/^\/post\/like\/(\d+)$/', $uri, $matches)) {
        (new \App\Controllers\HomeController())->toggleLike($matches[1]);
    }
}

// 4. Default 404
if (!$handled) {
    (new \App\Controllers\ErrorController())->show(404, "Strona nie istnieje.");
}