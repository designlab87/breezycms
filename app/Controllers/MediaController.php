<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Serves uploaded images/videos inline (they live outside the web root).
 */
class MediaController extends Controller
{
    public function serve(string $id): void
    {
        $media = $this->app->media()->find($id);
        if (!$media) {
            $this->abort(404, 'Media not found.');
        }

        $path = $this->app->media()->pathFor($media);
        if (!is_file($path)) {
            $this->abort(404, 'Media file missing.');
        }

        header('Content-Type: ' . ($media['mime'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
