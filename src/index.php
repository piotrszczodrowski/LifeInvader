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

    case '/profile/edit':
        (new \App\Controllers\ProfileController())->edit();
        $handled = true;
        break;
    case '/profile/update':
        (new \App\Controllers\ProfileController())->update();
        $handled = true;
        break;
//    case '/theme/toggle':
//        (new \App\Controllers\ThemeController())->toggle();
//        break;
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

    elseif (preg_match('/^\/profile\/([a-zA-Z0-9_-]+)$/', $uri, $matches)) {
        if ($matches[1] !== 'edit' && $matches[1] !== 'update') {
            (new \App\Controllers\ProfileController())->show($matches[1]);
            $handled = true;
        }
    }

    elseif (preg_match('/^\/post\/delete\/(\d+)$/', $uri, $matches)) {
        (new \App\Controllers\HomeController())->deletePost($matches[1]);
        $handled = true;
    }

    // Wyświetlenie formularza edycji: /post/edit/123
    elseif (preg_match('/^\/post\/edit\/(\d+)$/', $uri, $matches)) {
        (new \App\Controllers\HomeController())->editPost($matches[1]);
        $handled = true;
    }
    // Zapis zmian: /post/update/123
    elseif (preg_match('/^\/post\/update\/(\d+)$/', $uri, $matches)) {
        (new \App\Controllers\HomeController())->updatePost($matches[1]);
        $handled = true;
    }

    elseif (preg_match('/^\/post\/comment\/(\d+)$/', $uri, $matches)) {
        (new \App\Controllers\HomeController())->addComment($matches[1]);
        $handled = true;
    }

    elseif ($uri === '/toggle-theme') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new \App\Controllers\HomeController())->toggleTheme();
        }
        $handled = true;
    }

    // Główna skrzynka odbiorcza
    elseif ($uri === '/messages') {
        (new \App\Controllers\MessageController())->index();
        $handled = true;
    }
    // Endpoint API dla AJAXA (wymóg: JSON) -> np. /api/messages/fetch
    elseif (preg_match('/^\/api\/messages\/fetch/', $uri)) {
        (new \App\Controllers\MessageController())->fetchNew();
        $handled = true;
    }

    elseif ($uri === '/messages/send') { (new \App\Controllers\MessageController())->send(); $handled = true; }

    elseif (preg_match('/^\/api\/messages\/counters/', $uri)) {
        (new \App\Controllers\MessageController())->getCounters();
        $handled = true;
    }
}

// 4. Default 404
if (!$handled) {
    (new \App\Controllers\ErrorController())->show(404, "Strona nie istnieje.");
}