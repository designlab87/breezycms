<?php

namespace App\Controllers\Admin;

use App\Core\Controller;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if ($this->app->auth()->adminLoggedIn()) {
            $this->redirect('/admin/dashboard');
        }
        $this->render('admin/login', ['title' => 'Log in'], 'auth');
    }

    public function login(): void
    {
        $this->requireCsrf();

        if (!$this->app->turnstile()->verify(
            $this->request->post('cf-turnstile-response'),
            $_SERVER['REMOTE_ADDR'] ?? null
        )) {
            $this->flash('Bot verification failed. Please try again.', 'error');
            $this->redirect('/admin/login');
        }

        $email = (string) $this->request->post('email', '');
        $password = (string) $this->request->post('password', '');

        if ($this->app->auth()->attemptAdmin($email, $password)) {
            $this->redirect('/admin/dashboard');
        }

        $this->flash('Incorrect email or password.', 'error');
        $this->redirect('/admin/login');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        $this->app->auth()->logoutAdmin();
        $this->flash('You have been logged out.');
        $this->redirect('/admin/login');
    }
}
