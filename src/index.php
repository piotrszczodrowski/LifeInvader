<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Inicjalizacja obsługi błędów
$errorController = new \App\Controllers\ErrorController();
set_exception_handler([$errorController, 'handleException']);
set_error_handler([$errorController, 'handleError']);
register_shutdown_function([$errorController, 'handleShutdown']);

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Sprawdzenie, czy system jest zainstalowany
$lockFilePath = __DIR__ . '/install.lock';
if (!file_exists($lockFilePath) && $uri !== '/install' && $uri !== '/install/run') {
    header('Location: /install');
    exit;
}

// Sprawdzenie, czy użytkownik jest zalogowany
$allowedRoutes = ['/login', '/register'];
if (!isset($_SESSION['user_id']) && !in_array($uri, $allowedRoutes)) {
    if ($uri === '/' || $uri === '/index.php') {
        (new \App\Controllers\AuthController())->showLogin();
    } else {
        header('Location: /login');
    }
    exit;
}

// Sprawdzenie, czy wymagana jest zmiana hasła
if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
    if ($uri !== '/force-password-change' && $uri !== '/logout') {
        header('Location: /force-password-change');
        exit;
    }
}

// Routing
$handled = false;
$authController = new \App\Controllers\AuthController();

switch ($uri) {
    case '/': (new \App\Controllers\HomeController())->index(); $handled = true; break;
    case '/install': (new \App\Controllers\InstallController())->index(); $handled = true; break;
    case '/install/run': if ($method === 'POST') { (new \App\Controllers\InstallController())->run(); } else { header('Location: /install'); } $handled = true; break;
    case '/login': ($method === 'POST') ? $authController->login() : $authController->showLogin(); $handled = true; break;
    case '/register': ($method === 'POST') ? $authController->register() : $authController->showRegister(); $handled = true; break;
    case '/logout': $authController->logout(); $handled = true; break;
    case '/force-password-change': ($method === 'POST') ? $authController->forceChangePassword() : $authController->showForceChangePassword(); $handled = true; break;
    case '/post/create': (new \App\Controllers\HomeController())->createPost(); $handled = true; break;
    case '/profile/edit': (new \App\Controllers\ProfileController())->edit(); $handled = true; break;
    case '/profile/update': (new \App\Controllers\ProfileController())->update(); $handled = true; break;
    case '/toggle-theme': (new \App\Controllers\HomeController())->toggleTheme(); $handled = true; break;
    case '/messages': (new \App\Controllers\MessageController())->index(); $handled = true; break;
    case '/messages/send': (new \App\Controllers\MessageController())->send(); $handled = true; break;
}

if (!$handled) {
    if (preg_match('/^\/post\/edit\/(\d+)$/', $uri, $matches)) { (new \App\Controllers\HomeController())->editPost($matches[1]); $handled = true; }
    elseif (preg_match('/^\/post\/like\/(\d+)$/', $uri, $matches)) { (new \App\Controllers\HomeController())->toggleLike($matches[1]); $handled = true; }
    elseif (preg_match('/^\/profile\/([a-zA-Z0-9_-]+)$/', $uri, $matches)) { (new \App\Controllers\ProfileController())->show($matches[1]); $handled = true; }
    elseif (preg_match('/^\/post\/delete\/(\d+)$/', $uri, $matches)) { (new \App\Controllers\HomeController())->deletePost($matches[1]); $handled = true; }
    elseif (preg_match('/^\/post\/update\/(\d+)$/', $uri, $matches)) { (new \App\Controllers\HomeController())->updatePost($matches[1]); $handled = true; }
    elseif (preg_match('/^\/post\/comment\/(\d+)$/', $uri, $matches)) { (new \App\Controllers\HomeController())->addComment($matches[1]); $handled = true; }
    elseif (preg_match('/^\/api\/messages\/fetch/', $uri)) { (new \App\Controllers\MessageController())->fetchNew(); $handled = true; }
    elseif (preg_match('/^\/api\/messages\/counters/', $uri)) { (new \App\Controllers\MessageController())->getCounters(); $handled = true; }
    elseif ($uri === '/admin' || str_starts_with($uri, '/admin/')) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $errorController->show(403, 'Forbidden');
        }

        $adminController = new \App\Controllers\AdminController();
        if ($uri === '/admin') { $adminController->index(); $handled = true; }
        elseif (preg_match('/^\/admin\/user\/delete\/(\d+)$/', $uri, $matches)) { $adminController->deleteUser((int)$matches[1]); $handled = true; }
        elseif (preg_match('/^\/admin\/user\/role\/(\d+)$/', $uri, $matches)) { $adminController->toggleRole((int)$matches[1]); $handled = true; }
        elseif ($uri === '/admin/audit') { $adminController->audit(); $handled = true; }
    }
}

if (!$handled) {
    $errorController->show(404, "Not Found");
}
