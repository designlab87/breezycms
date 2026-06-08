<?php

/**
 * Application configuration.
 *
 * Paths are absolute and derived from the project root so the app works
 * regardless of where it is deployed.
 */

$root = dirname(__DIR__);

return [
    'site_title' => 'My Site',

    // Filesystem paths (no trailing slash).
    'paths' => [
        'root'      => $root,
        'app'       => $root . '/app',
        'views'     => $root . '/app/Views',
        'config'    => $root . '/config',
        'content'   => $root . '/storage/content',
        'uploads'   => $root . '/storage/uploads',
        'public'    => $root . '/public',
    ],

    // Public URL base path. Empty string when served from the domain root
    // (e.g. via `php -S localhost:8000 -t public`). Set to e.g. '/cms' if the
    // app lives in a subdirectory.
    'base_path' => '',

    // Cloudflare Turnstile (bot protection) for the admin login, the JS
    // re-login modal, and the visitor password gate.
    //
    // The defaults are Cloudflare's official TEST keys, which ALWAYS PASS — fine
    // for local development. Before going live, replace them with your real keys
    // from the Cloudflare dashboard (or set the TURNSTILE_SITE_KEY /
    // TURNSTILE_SECRET_KEY environment variables). Set 'enabled' => false (or
    // the TURNSTILE_ENABLED=0 environment variable) to turn it off entirely.
    'turnstile' => [
        'enabled'    => getenv('TURNSTILE_ENABLED') !== '0',
        'site_key'   => getenv('TURNSTILE_SITE_KEY') ?: '1x00000000000000000000AA',
        'secret_key' => getenv('TURNSTILE_SECRET_KEY') ?: '1x0000000000000000000000000000000AA',
    ],

    // Session behaviour.
    'session' => [
        // Idle timeout in seconds. The admin login and the visitor page gate
        // both expire after this much inactivity. Real navigation/actions
        // refresh the timer; the JS heartbeat does NOT (so polling can't keep
        // a session alive forever). Set to 0 to disable idle expiry.
        'idle_timeout' => 30 * 60,

        // How often (ms) the client heartbeat re-checks that the session is
        // still alive. Used by public/assets/js/session.js.
        'heartbeat_ms' => 2 * 60 * 1000,
    ],

    // Upload constraints, per media type.
    'uploads' => [
        // Max file size is governed by PHP ini (upload_max_filesize / post_max_size).
        'image' => [
            'dir'        => 'images',
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'mimes'      => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        ],
        'video' => [
            'dir'        => 'videos',
            'extensions' => ['mp4', 'webm', 'ogg', 'mov'],
            'mimes'      => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
        ],
        'file' => [
            'dir'        => 'files',
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mimes'      => ['application/pdf', 'image/jpeg', 'image/png'],
        ],
    ],
];
