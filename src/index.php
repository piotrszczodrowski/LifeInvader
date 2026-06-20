<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\Kernel;

// Inicjalizacja środowiska
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Inicjalizacja obsługi błędów
$errorController = new \App\Controllers\ErrorController();
set_exception_handler([$errorController, 'handleException']);
set_error_handler([$errorController, 'handleError']);
register_shutdown_function([$errorController, 'handleShutdown']);

// Ustawienie domyślnej strefy czasowej
date_default_timezone_set('Europe/Warsaw');

// Utworzenie katalogu na logi
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

session_start();

// Przetwarzanie żądania przez "Kernel" (middleware)
$kernel = new Kernel();
$kernel->handle();

// Routing
$router = new Router();
require_once __DIR__ . '/routes.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);