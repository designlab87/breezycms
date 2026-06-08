<?php

namespace App\Controllers\Admin;

use App\Core\Controller;

class SettingsController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/settings', [
            'title'      => 'Settings',
            'settings'   => $this->app->settings()->all(),
            'adminEmail' => $this->app->auth()->adminEmail(),
        ], 'admin');
    }

    public function update(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $settings = $this->app->settings();

        $settings->set('site_title', trim((string) $this->request->post('site_title', '')) ?: 'My Site');

        // Optional password changes (only when a new value is provided).
        $messages = [];
        if ($new = (string) $this->request->post('admin_password', '')) {
            $email = $this->app->auth()->adminEmail();
            if ($email && $this->app->users()->updatePassword($email, password_hash($new, PASSWORD_DEFAULT))) {
                $messages[] = 'your password updated';
            }
        }

        $msg = 'Settings saved.';
        if ($messages) {
            $msg .= ' (' . implode(', ', $messages) . ')';
        }
        $this->flash($msg);
        $this->redirect('/admin/settings');
    }
}
