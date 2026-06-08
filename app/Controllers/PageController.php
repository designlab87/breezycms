<?php

namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller
{
    public function show(string $slug): void
    {
        $page = $this->app->pages()->findBySlug($slug);

        if (!$page || ($page['status'] ?? '') !== 'published') {
            $this->abort(404, 'Page not found.');
        }

        // Password gate for protected pages.
        if (!empty($page['is_protected']) && !$this->app->auth()->gateOpen($slug)) {
            echo $this->app->view()->render('partials/gate-form', [
                'page'  => $page,
                'csrf'  => $this->app->csrf(),
                'error' => null,
                'title' => $page['title'],
            ], 'public');
            return;
        }

        // Genuine view of an unlocked protected page — refresh the gate timer
        // and tell the layout to run the session heartbeat for this slug.
        $gateSlug = null;
        if (!empty($page['is_protected'])) {
            $this->app->auth()->touchGate();
            $gateSlug = $slug;
        }

        $this->render('templates/page/render', [
            'title'     => $page['title'],
            'layout'    => $page['layout'] ?? [],
            'page'      => $page,
            'gate_slug' => $gateSlug,
        ], 'public');
    }
}
