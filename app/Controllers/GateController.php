<?php

namespace App\Controllers;

use App\Core\Controller;

class GateController extends Controller
{
    public function submit(string $slug): void
    {
        $this->requireCsrf();

        $page = $this->app->pages()->findBySlug($slug);
        if (!$page) {
            $this->abort(404, 'Page not found.');
        }

        if (!$this->app->turnstile()->verify(
            $this->request->post('cf-turnstile-response'),
            $_SERVER['REMOTE_ADDR'] ?? null
        )) {
            echo $this->app->view()->render('partials/gate-form', [
                'page'  => $page,
                'csrf'  => $this->app->csrf(),
                'error' => 'Bot verification failed. Please try again.',
                'title' => $page['title'],
            ], 'public');
            return;
        }

        $password = (string) $this->request->post('password', '');

        if ($this->app->auth()->attemptGate($slug, $password, $page['password_hash'] ?? null)) {
            $this->redirect('/page/' . $slug);
        }

        echo $this->app->view()->render('partials/gate-form', [
            'page'  => $page,
            'csrf'  => $this->app->csrf(),
            'error' => 'Incorrect access code. Please try again.',
            'title' => $page['title'],
        ], 'public');
    }
}
