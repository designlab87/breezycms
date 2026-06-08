<?php

namespace App\Controllers\Admin;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();

        $this->render('admin/dashboard', [
            'title'      => 'Dashboard',
            'pages'      => $this->app->pages()->all(),
            'mediaCount' => count($this->app->media()->all()),
        ], 'admin');
    }
}
