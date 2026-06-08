<?php

namespace App\Core;

/**
 * Simple regex-based router. Patterns support {param} placeholders that are
 * passed to the controller method as ordered arguments.
 */
class Router
{
    private App $app;
    /** @var array<int, array{method:string, regex:string, params:string[], handler:array}> */
    private array $routes = [];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        $params = [];
        $regex = preg_replace_callback('/\{(\w+)\}/', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $pattern);

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                [$class, $action] = $route['handler'];
                $controller = new $class($this->app, $request);
                $controller->$action(...$matches);
                return;
            }
        }

        $this->notFound();
    }

    private function notFound(): void
    {
        http_response_code(404);
        $view = $this->app->view();
        echo $view->render('errors/404', ['title' => 'Not Found'], 'public');
    }
}
