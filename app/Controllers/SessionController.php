<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Read-only heartbeat endpoint used by public/assets/js/session.js.
 *
 * IMPORTANT: this only *checks* whether the session is still alive (which may
 * expire it if the idle timeout has passed). It never refreshes the activity
 * timer, so polling it does not keep a session alive.
 */
class SessionController extends Controller
{
    public function status(): void
    {
        $scope = (string) $this->request->query('scope', 'admin');

        if ($scope === 'gate') {
            $slug = (string) $this->request->query('slug', '');
            $alive = $slug !== '' && $this->app->auth()->gateOpen($slug);
        } else {
            $alive = $this->app->auth()->adminLoggedIn();
        }

        $this->json([
            'alive' => $alive,
            // Fresh token so the JS re-login form has a valid CSRF value even
            // if the page it was rendered on is now stale.
            'csrf'  => $this->app->csrf()->token(),
        ]);
    }
}
