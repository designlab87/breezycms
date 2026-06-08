<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $home = $this->app->pages()->home();

        $this->render('templates/page/render', [
            'title'     => null,
            'layout'    => $home['layout'] ?? [],
            'gate_slug' => null,
        ], 'public');
    }
}
