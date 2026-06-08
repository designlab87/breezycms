<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\UploadLimits;

class MediaController extends Controller
{
    private const TYPES = ['image', 'video', 'file'];

    public function index(): void
    {
        $this->requireAdmin();
        $media = $this->app->media();
        $this->render('admin/media', [
            'title'   => 'Media library',
            'images'  => $media->ofType('image'),
            'videos'  => $media->ofType('video'),
            'files'   => $media->ofType('file'),
            'uploads'        => $this->app->config('uploads', []),
            'uploadMaxBytes' => UploadLimits::maxBytes(),
        ], 'admin');
    }

    /** JSON list of media for the editor's picker modal. */
    public function catalog(): void
    {
        $this->requireAdmin();
        $type = (string) $this->request->query('type', 'image');
        if (!in_array($type, self::TYPES, true)) {
            $type = 'image';
        }

        $items = array_map(function ($m) {
            return [
                'id'            => $m['id'],
                'type'          => $m['type'],
                'url'           => $this->app->url('/media/' . $m['id']),
                'download_url'  => $this->app->url('/download/' . $m['id']),
                'original_name' => $m['original_name'],
                'size'          => $m['size'],
            ];
        }, $this->app->media()->ofType($type));

        $this->json(['items' => $items]);
    }

    public function upload(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $type = (string) $this->request->post('type', 'image');
        $isAjax = (bool) $this->request->post('ajax');

        if (!in_array($type, self::TYPES, true)) {
            $type = 'image';
        }

        try {
            $entry = $this->app->media()->add($this->request->file('file') ?? [], $type);
        } catch (\RuntimeException $e) {
            if ($isAjax) {
                $this->json(['error' => $e->getMessage()], 422);
            }
            $this->flash($e->getMessage(), 'error');
            $this->redirect('/admin/media');
        }

        if ($isAjax) {
            $this->json([
                'item' => [
                    'id'            => $entry['id'],
                    'type'          => $entry['type'],
                    'url'           => $this->app->url('/media/' . $entry['id']),
                    'download_url'  => $this->app->url('/download/' . $entry['id']),
                    'original_name' => $entry['original_name'],
                    'size'          => $entry['size'],
                ],
            ]);
        }

        $this->flash('Uploaded "' . $entry['original_name'] . '".');
        $this->redirect('/admin/media');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        if ($this->app->media()->isReferenced($id)) {
            $this->flash('That file is in use on a page or ad and was not deleted.', 'error');
            $this->redirect('/admin/media');
        }

        $this->app->media()->delete($id);
        $this->flash('Media deleted.');
        $this->redirect('/admin/media');
    }
}
