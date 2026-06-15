<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        // pass 'naglowek' var to template home.html.twig
        $this->render('home', [
            'naglowek' => 'Witaj w LifeInvader'
        ]);
    }
}