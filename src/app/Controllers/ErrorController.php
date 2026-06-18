<?php

namespace App\Controllers;

use App\Core\Controller;

class ErrorController extends Controller
{
    private const HTTP_STATUS_CODES = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Wyświetla stronę błędu z odpowiednim kodem HTTP.
     */
    public function show(int $code, string $message, string $file = '', int $line = 0)
    {
        http_response_code($code);

        $logMessage = sprintf("[%s] Błąd %d: %s w %s:%d\n", date('Y-m-d H:i:s'), $code, $message, $file, $line);
        error_log($logMessage, 3, dirname(__DIR__, 2) . '/logs/errors.log');

        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        $displayMessage = self::HTTP_STATUS_CODES[$code] ?? 'Error';

        $this->render('error', [
            'code' => $code,
            'message' => $displayMessage,
            'details' => $isDebug ? "Szczegóły: $message <br> Plik: $file, Linia: $line" : ''
        ]);
        exit;
    }

    /**
     * Obsługuje nieprzechwycone wyjątki.
     */
    public function handleException(\Throwable $exception)
    {
        $this->show(500, $exception->getMessage(), $exception->getFile(), $exception->getLine());
    }

    /**
     * Konwertuje błędy PHP na wyjątki.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Obsługuje błędy krytyczne, które zatrzymują wykonanie skryptu.
     */
    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $this->show(500, $error['message'], $error['file'], $error['line']);
        }
    }
}
