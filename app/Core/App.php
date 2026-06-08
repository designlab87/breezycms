<?php

namespace App\Core;

use App\Repositories\SettingsRepository;
use App\Repositories\PageRepository;
use App\Repositories\MediaRepository;
use App\Repositories\UsersRepository;

/**
 * Tiny service container. Holds config and lazily builds shared services so
 * controllers receive a single dependency.
 */
class App
{
    private array $config;
    private array $services = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function config(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? $default;
    }

    public function path(string $name): string
    {
        return $this->config['paths'][$name];
    }

    /** Build a public URL from an app-relative path. */
    public function url(string $path = '/'): string
    {
        $base = rtrim($this->config['base_path'], '/');
        return $base . '/' . ltrim($path, '/');
    }

    public function settings(): SettingsRepository
    {
        return $this->services['settings'] ??= new SettingsRepository($this->path('content'));
    }

    public function users(): UsersRepository
    {
        return $this->services['users'] ??= new UsersRepository($this->path('content'));
    }

    public function pages(): PageRepository
    {
        return $this->services['pages'] ??= new PageRepository($this->path('content'));
    }

    public function media(): MediaRepository
    {
        return $this->services['media'] ??= new MediaRepository(
            $this->path('content'),
            $this->path('uploads'),
            $this->config['uploads']
        );
    }

    public function view(): View
    {
        return $this->services['view'] ??= new View($this->path('views'), $this);
    }

    public function auth(): Auth
    {
        return $this->services['auth'] ??= new Auth(
            $this->settings(),
            $this->users(),
            (int) ($this->config['session']['idle_timeout'] ?? 1800)
        );
    }

    public function csrf(): Csrf
    {
        return $this->services['csrf'] ??= new Csrf();
    }

    public function turnstile(): Turnstile
    {
        return $this->services['turnstile'] ??= new Turnstile($this->config['turnstile'] ?? []);
    }

    /** Curated font registry (config/fonts.php). */
    public function fonts(): array
    {
        return $this->services['fonts'] ??= require $this->config['paths']['config'] . '/fonts.php';
    }

    public function theme(): Theme
    {
        return $this->services['theme'] ??= new Theme(
            (array) $this->settings()->get('theme', []),
            $this->fonts()
        );
    }
}
