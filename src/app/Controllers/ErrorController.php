<?php

namespace App\Controllers;

class ErrorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function show($code, $message)
    {
        $this->render('error', [
            'code' => $code,
            'message' => $message
        ]);
    }
}