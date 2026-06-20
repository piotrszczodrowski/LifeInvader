<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\InstallService;

class InstallController extends Controller
{
    public function __construct(private InstallService $installService)
    {
        parent::__construct();
    }

    public function index()
    {
        $this->render('install_step1');
    }

    public function run()
    {
        $result = $this->installService->runInstall();

        if ($result['success']) {
            $this->render('install_success', ['email' => $result['email']]);
        } else {
            (new ErrorController())->show(500, $result['message']);
        }
    }
}