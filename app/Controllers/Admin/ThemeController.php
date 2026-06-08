<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Theme;

/**
 * Site theme editor: 3 colors (with auto text-contrast), 3 font roles, and a
 * corner-style preset. Persisted under the `theme` key in settings.json and
 * consumed by App\Core\Theme to emit CSS variables on the public site.
 */
class ThemeController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $theme = $this->app->theme();
        $this->render('admin/theme', [
            'title'   => 'Theme',
            'theme'   => array_merge($theme->defaults(), (array) $this->app->settings()->get('theme', [])),
            'fonts'   => $this->app->fonts(),
            'corners' => $theme->cornerPresets(),
        ], 'admin');
    }

    public function update(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $fonts    = $this->app->fonts();
        $defaults = $this->app->theme()->defaults();
        $corners  = $this->app->theme()->cornerPresets();

        $fontKey = function (string $field) use ($fonts, $defaults) {
            $v = (string) $this->request->post($field, '');
            return isset($fonts[$v]) ? $v : $defaults[$field];
        };

        // Optional px sizes: blank or out-of-range stores '' (= use default).
        $size = function (string $field): string {
            $v = trim((string) $this->request->post($field, ''));
            if ($v === '') {
                return '';
            }
            $n = (int) $v;
            return ($n >= Theme::SIZE_MIN && $n <= Theme::SIZE_MAX) ? (string) $n : '';
        };

        $theme = [
            'color_primary' => $this->color($this->request->post('color_primary'), $defaults['color_primary']),
            'color_accent'  => $this->color($this->request->post('color_accent'), $defaults['color_accent']),
            'color_base'    => $this->color($this->request->post('color_base'), $defaults['color_base']),
            'font_h1'       => $fontKey('font_h1'),
            'font_h2'       => $fontKey('font_h2'),
            'font_body'     => $fontKey('font_body'),
            'size_h1'       => $size('size_h1'),
            'size_h2'       => $size('size_h2'),
            'size_h3'       => $size('size_h3'),
            'size_body'     => $size('size_body'),
            'corners'       => in_array($this->request->post('corners'), $corners, true)
                ? (string) $this->request->post('corners')
                : $defaults['corners'],
        ];

        $this->app->settings()->set('theme', $theme);
        $this->flash('Theme saved.');
        $this->redirect('/admin/theme');
    }

    /**
     * Validate a hex color, else fall back to the default. Accepts an optional
     * leading '#' and 3- or 6-digit hex; always returns a normalized #rrggbb
     * (or #rgb) lowercase value.
     */
    private function color($value, string $default): string
    {
        $v = strtolower(trim((string) $value));
        $v = ltrim($v, '#');
        return preg_match('/^([0-9a-f]{3}|[0-9a-f]{6})$/', $v) ? '#' . $v : $default;
    }
}
