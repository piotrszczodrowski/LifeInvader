<?php

namespace App\Controllers;

use App\Core\Controller;

class ErrorController extends Controller
{
    public function show(int $code, string $message)
    {
        http_response_code($code);

        $this->render('error', [
            'code' => $code,
            'message' => $message
        ]);
    }
}