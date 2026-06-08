<?php

namespace App\Core;

/**
 * Base controller. Provides shared helpers for rendering, redirects, auth
 * guards, CSRF verification, and flash messages.
 */
abstract class Controller
{
    protected App $app;
    protected Request $request;

    public function __construct(App $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
    }

    protected function render(string $view, array $data = [], ?string $layout = 'public'): void
    {
        $data['flash'] = $this->takeFlash();
        $data['csrf'] = $this->app->csrf();
        $data['auth'] = $this->app->auth();
        echo $this->app->view()->render($view, $data, $layout);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $this->app->url($path));
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function abort(int $status, string $message = ''): void
    {
        http_response_code($status);
        echo $this->app->view()->render('errors/' . $status, [
            'title'   => $status . ' Error',
            'message' => $message,
        ], 'public');
        exit;
    }

    protected function requireAdmin(): void
    {
        if (!$this->app->auth()->adminLoggedIn()) {
            $this->redirect('/admin/login');
        }
        // Genuine admin navigation/action — refresh the idle timer.
        $this->app->auth()->touchAdmin();
    }

    protected function requireCsrf(): void
    {
        if (!$this->app->csrf()->verify($this->request->post('_csrf'))) {
            $this->abort(419, 'Your session expired or the form token was invalid. Please try again.');
        }
    }

    // --- Flash messages ----------------------------------------------------

    protected function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['_flash'] = ['message' => $message, 'type' => $type];
    }

    private function takeFlash(): ?array
    {
        $flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return $flash;
    }
}
