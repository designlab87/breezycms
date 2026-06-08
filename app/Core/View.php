<?php

namespace App\Core;

/**
 * Plain-PHP view renderer with optional layout wrapping.
 *
 * Inside a template the following are available:
 *   $app   - the App container (for $app->url(), settings, etc.)
 *   $view  - this renderer (for $view->partial(...))
 *   e()    - HTML-escape helper (see helpers.php)
 *   plus every key of the $data array as its own variable.
 */
class View
{
    private string $dir;
    private App $app;

    public function __construct(string $viewsDir, App $app)
    {
        $this->dir = rtrim($viewsDir, '/');
        $this->app = $app;
    }

    public function render(string $viewPath, array $data = [], ?string $layout = null): string
    {
        $content = $this->partial($viewPath, $data);

        if ($layout !== null) {
            $content = $this->partial('layouts/' . $layout, array_merge($data, ['content' => $content]));
        }

        return $content;
    }

    public function partial(string $viewPath, array $data = []): string
    {
        $file = $this->dir . '/' . $viewPath . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$viewPath}");
        }

        $app = $this->app;
        $view = $this;
        extract($data, EXTR_SKIP);

        ob_start();
        include $file;
        return ob_get_clean();
    }
}
