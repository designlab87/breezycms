<?php

/**
 * Front controller. All public + admin requests route through here.
 */

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\App;
use App\Core\Router;
use App\Core\Request;

$root = dirname(__DIR__);

require $root . '/app/Core/Autoloader.php';
require $root . '/app/Core/helpers.php';
(new Autoloader($root . '/app'))->register();

$config = require $root . '/config/config.php';

// Harden the session cookie. lifetime=0 keeps it a browser-session cookie;
// idle expiry is enforced server-side in App\Core\Auth.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

$app = new App($config);
$request = new Request($config['base_path']);
$router = new Router($app);

// ---------------------------------------------------------------------------
// Public routes
// ---------------------------------------------------------------------------
$router->get('/', ['App\Controllers\HomeController', 'index']);
$router->get('/page/{slug}', ['App\Controllers\PageController', 'show']);
$router->post('/gate/{slug}', ['App\Controllers\GateController', 'submit']);
$router->get('/media/{id}', ['App\Controllers\MediaController', 'serve']);
$router->get('/download/{id}', ['App\Controllers\DownloadController', 'download']);
$router->get('/session/status', ['App\Controllers\SessionController', 'status']);

// ---------------------------------------------------------------------------
// Admin routes
// ---------------------------------------------------------------------------
$router->get('/admin', ['App\Controllers\Admin\DashboardController', 'index']);
$router->get('/admin/login', ['App\Controllers\Admin\AuthController', 'showLogin']);
$router->post('/admin/login', ['App\Controllers\Admin\AuthController', 'login']);
$router->post('/admin/logout', ['App\Controllers\Admin\AuthController', 'logout']);
$router->get('/admin/dashboard', ['App\Controllers\Admin\DashboardController', 'index']);

// Home page (special builder page)
$router->get('/admin/home/edit', ['App\Controllers\Admin\PageController', 'editHome']);
$router->get('/admin/home/preview', ['App\Controllers\Admin\PageController', 'previewHome']);
$router->post('/admin/home', ['App\Controllers\Admin\PageController', 'updateHome']);

// Pages
$router->get('/admin/pages', ['App\Controllers\Admin\PageController', 'index']);
$router->get('/admin/pages/create', ['App\Controllers\Admin\PageController', 'create']);
$router->post('/admin/pages', ['App\Controllers\Admin\PageController', 'store']);
$router->post('/admin/pages/reorder', ['App\Controllers\Admin\PageController', 'reorder']);
$router->get('/admin/pages/{slug}/edit', ['App\Controllers\Admin\PageController', 'edit']);
$router->get('/admin/pages/{slug}/preview', ['App\Controllers\Admin\PageController', 'preview']);
$router->post('/admin/pages/{slug}', ['App\Controllers\Admin\PageController', 'update']);
$router->post('/admin/pages/{slug}/delete', ['App\Controllers\Admin\PageController', 'destroy']);

// Media library
$router->get('/admin/media', ['App\Controllers\Admin\MediaController', 'index']);
$router->get('/admin/media/json', ['App\Controllers\Admin\MediaController', 'catalog']);
$router->post('/admin/media', ['App\Controllers\Admin\MediaController', 'upload']);
$router->post('/admin/media/{id}/delete', ['App\Controllers\Admin\MediaController', 'destroy']);

// Theme
$router->get('/admin/theme', ['App\Controllers\Admin\ThemeController', 'index']);
$router->post('/admin/theme', ['App\Controllers\Admin\ThemeController', 'update']);

// Settings
$router->get('/admin/settings', ['App\Controllers\Admin\SettingsController', 'index']);
$router->post('/admin/settings', ['App\Controllers\Admin\SettingsController', 'update']);

$router->dispatch($request);
