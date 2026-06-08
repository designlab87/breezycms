<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Forces download of an uploaded file with its original name.
 */
class DownloadController extends Controller
{
    public function download(string $id): void
    {
        $media = $this->app->media()->find($id);
        if (!$media) {
            $this->abort(404, 'File not found.');
        }

        $path = $this->app->media()->pathFor($media);
        if (!is_file($path)) {
            $this->abort(404, 'File missing.');
        }

        $name = preg_replace('/[^\w.\- ]+/', '_', basename($media['original_name'] ?? 'download'));

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
