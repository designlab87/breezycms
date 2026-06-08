<?php

namespace App\Core;

/**
 * Minimal PSR-4-style autoloader mapping the `App\` namespace to the `app/`
 * directory. No Composer required.
 */
class Autoloader
{
    private string $baseDir;
    private string $prefix = 'App\\';

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/') . '/';
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    public function load(string $class): void
    {
        if (!str_starts_with($class, $this->prefix)) {
            return;
        }

        $relative = substr($class, strlen($this->prefix));
        $file = $this->baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
}
