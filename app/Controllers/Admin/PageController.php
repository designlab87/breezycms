<?php

namespace App\Controllers\Admin;

use App\Core\Html;

/**
 * Pages are built with a drag-and-drop builder: an ordered list of containers
 * (1–4 column rows); each column holds an ordered list of widgets (richtext,
 * image, video, file). The whole tree is posted as a single `layout_json` blob
 * and rebuilt/sanitized server-side here.
 */
class PageController extends AdminContentController
{
    /** Widget types the builder is allowed to persist. */
    private const WIDGET_TYPES = ['richtext', 'image', 'video', 'file', 'button'];

    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/pages-index', [
            'title' => 'Pages',
            'pages' => $this->app->pages()->all(),
        ], 'admin');
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->render('admin/page-builder', [
            'title'      => 'New page',
            'mode'       => 'create',
            'formAction' => $this->app->url('/admin/pages'),
            'page'       => [
                'slug' => '', 'title' => '', 'is_protected' => false,
                'status' => 'draft', 'sort' => 0, 'layout' => [],
            ],
        ], 'admin');
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $title = trim((string) $this->request->post('title', ''));
        if ($title === '') {
            $this->flash('Title is required.', 'error');
            $this->redirect('/admin/pages/create');
        }

        $slug = $this->uniqueSlug($this->slugify((string) ($this->request->post('slug') ?: $title)));

        $isProtected = (bool) $this->request->post('is_protected');
        $pagePassword = (string) $this->request->post('page_password', '');
        if ($isProtected && $pagePassword === '') {
            $this->flash('Set a password to protect this page.', 'error');
            $this->redirect('/admin/pages/create');
        }

        $page = [
            'slug'          => $slug,
            'title'         => $title,
            'is_protected'  => $isProtected,
            'password_hash' => $isProtected ? password_hash($pagePassword, PASSWORD_DEFAULT) : null,
            'in_menu'       => (bool) $this->request->post('in_menu'),
            'status'        => $this->request->post('status') === 'published' ? 'published' : 'draft',
            'sort'          => count($this->app->pages()->all()), // append to end of order
            'layout'        => $this->buildLayout(),
        ];

        $this->app->pages()->create($page);
        $this->flash('Page "' . $title . '" created.');
        $this->redirect('/admin/pages/' . $slug . '/edit');
    }

    public function edit(string $slug): void
    {
        $this->requireAdmin();
        $page = $this->app->pages()->findBySlug($slug);
        if (!$page) {
            $this->flash('Page not found.', 'error');
            $this->redirect('/admin/pages');
        }

        $this->render('admin/page-builder', [
            'title'      => 'Edit: ' . $page['title'],
            'mode'       => 'edit',
            'formAction' => $this->app->url('/admin/pages/' . $slug),
            'page'       => $page,
        ], 'admin');
    }

    /**
     * Render a page using the real public layout/renderer, but inside an admin
     * preview chrome and regardless of status or password protection. Lets an
     * admin preview drafts without publishing them.
     */
    public function preview(string $slug): void
    {
        $this->requireAdmin();
        $page = $this->app->pages()->findBySlug($slug);
        if (!$page) {
            $this->flash('Page not found.', 'error');
            $this->redirect('/admin/pages');
        }

        $isLive = ($page['status'] ?? '') === 'published';
        $this->render('templates/page/render', [
            'title'     => $page['title'],
            'layout'    => $page['layout'] ?? [],
            'page'      => $page,
            'gate_slug' => null,
            'preview'   => [
                'title'   => $page['title'],
                'status'  => $page['status'] ?? 'draft',
                'isLive'  => $isLive,
                'editUrl' => $this->app->url('/admin/pages/' . $slug . '/edit'),
                'liveUrl' => $this->app->url('/page/' . $slug),
            ],
        ], 'public');
    }

    public function update(string $slug): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $existing = $this->app->pages()->findBySlug($slug);
        if (!$existing) {
            $this->flash('Page not found.', 'error');
            $this->redirect('/admin/pages');
        }

        $title = trim((string) $this->request->post('title', '')) ?: $existing['title'];

        $newSlug = $this->slugify((string) ($this->request->post('slug') ?: $title));
        if ($newSlug !== $slug) {
            $newSlug = $this->uniqueSlug($newSlug, $slug);
        }

        $isProtected = (bool) $this->request->post('is_protected');
        [$passwordHash, $passwordError] = $this->resolvePasswordHash($isProtected, $existing);
        if ($passwordError !== null) {
            $this->flash($passwordError, 'error');
            $this->redirect('/admin/pages/' . $slug . '/edit');
        }

        $page = array_merge($existing, [
            'slug'          => $newSlug,
            'title'         => $title,
            'is_protected'  => $isProtected,
            'password_hash' => $passwordHash,
            'in_menu'       => (bool) $this->request->post('in_menu'),
            'status'        => $this->request->post('status') === 'published' ? 'published' : 'draft',
            'layout'        => $this->buildLayout(),
        ]);
        // Old template-based pages had these keys; drop them on save.
        unset($page['template_key'], $page['content']);

        $this->app->pages()->update($slug, $page);
        $this->flash('Page saved.');
        $this->redirect('/admin/pages/' . $newSlug . '/edit');
    }

    public function destroy(string $slug): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->app->pages()->delete($slug);
        $this->flash('Page deleted.');
        $this->redirect('/admin/pages');
    }

    /** Persist a new page order (menu order) from the drag-and-drop list. */
    public function reorder(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $slugs = $this->request->post('order', []);
        if (!is_array($slugs)) {
            $slugs = [];
        }
        $this->app->pages()->setOrder(array_map('strval', $slugs));
        $this->json(['ok' => true]);
    }

    // --- Home page (special builder page) -----------------------------------

    public function editHome(): void
    {
        $this->requireAdmin();
        $home = $this->app->pages()->home();

        $this->render('admin/page-builder', [
            'title'      => 'Edit home page',
            'mode'       => 'edit',
            'isHome'     => true,
            'formAction' => $this->app->url('/admin/home'),
            'page'       => ['slug' => '', 'title' => 'Home', 'layout' => $home['layout'] ?? []],
        ], 'admin');
    }

    public function updateHome(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $this->app->pages()->saveHome($this->buildLayout());
        $this->flash('Home page saved.');
        $this->redirect('/admin/home/edit');
    }

    public function previewHome(): void
    {
        $this->requireAdmin();
        $home = $this->app->pages()->home();

        $this->render('templates/page/render', [
            'title'     => 'Home',
            'layout'    => $home['layout'] ?? [],
            'gate_slug' => null,
            'preview'   => [
                'title'   => 'Home page',
                'status'  => 'published',
                'isLive'  => true,
                'editUrl' => $this->app->url('/admin/home/edit'),
                'liveUrl' => $this->app->url('/'),
            ],
        ], 'public');
    }

    // --- Layout building ---------------------------------------------------

    private const HERO_HEIGHTS = ['small', 'medium', 'large', 'full'];
    private const HERO_ALIGNS  = ['left', 'center', 'right'];

    /**
     * Decode and sanitize the posted layout tree. The structure is rebuilt
     * from scratch (never trusting client-supplied shapes) so malformed or
     * malicious input can't persist. Top-level items are either a container
     * (1–4 column row) or a hero (full-width background with overlay content).
     */
    private function buildLayout(): array
    {
        $decoded = json_decode((string) $this->request->post('layout_json', ''), true);
        if (!is_array($decoded)) {
            return [];
        }

        $layout = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $layout[] = ($item['type'] ?? '') === 'hero'
                ? $this->buildHero($item)
                : $this->buildContainer($item);
        }

        return $layout;
    }

    private function buildContainer(array $item): array
    {
        // Ratio layouts (asymmetric widths) are always two columns; "equal" keeps
        // the column count from the palette.
        $layout = in_array($item['layout'] ?? '', ['1-2', '1-3', '2-1', '3-1'], true)
            ? $item['layout']
            : 'equal';
        $columns = $layout === 'equal'
            ? max(1, min(4, (int) ($item['columns'] ?? 1)))
            : 2;
        $colsIn = is_array($item['cols'] ?? null) ? $item['cols'] : [];

        $cols = [];
        for ($i = 0; $i < $columns; $i++) {
            $cols[] = $this->buildWidgetList($colsIn[$i] ?? []);
        }

        // Background image: store a validated media id; fit controls sizing.
        $bgImageIn = $item['background_image'] ?? null;
        if (is_array($bgImageIn)) {
            $bgImageIn = $bgImageIn['media_id'] ?? '';
        }
        $bgImage = trim((string) $bgImageIn);
        if ($bgImage !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/', $bgImage)) {
            $bgImage = '';
        }
        $bgFit = in_array($item['background_fit'] ?? '', ['cover', 'contain', '100%'], true)
            ? $item['background_fit']
            : 'cover';

        return [
            'id'               => bin2hex(random_bytes(3)),
            'type'             => 'container',
            'columns'          => $columns,
            'layout'           => $layout,
            'background_color' => $this->sanitizeColor($item['background_color'] ?? null),
            'background_image' => $bgImage !== '' ? $bgImage : null,
            'background_fit'   => $bgFit,
            'cols'             => $cols,
        ];
    }

    /**
     * Accept only a safe CSS color (hex or rgb/rgba) to prevent style/CSS
     * injection via the inline background-color. Returns null when absent/invalid.
     */
    private function sanitizeColor($value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $v)) {
            return $v;
        }
        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\)$/', $v)) {
            return $v;
        }
        return null;
    }

    private function buildHero(array $item): array
    {
        // Background: an image or video media item, or none.
        $background = null;
        $bgIn = is_array($item['background'] ?? null) ? $item['background'] : [];
        $kind = ($bgIn['kind'] ?? '') === 'video' ? 'video' : 'image';
        $mid = trim((string) ($bgIn['media_id'] ?? ''));
        if ($mid !== '') {
            $background = ['kind' => $kind, 'media_id' => $mid];
        }

        $height = in_array($item['height'] ?? '', self::HERO_HEIGHTS, true) ? $item['height'] : 'medium';
        $align  = in_array($item['align'] ?? '', self::HERO_ALIGNS, true) ? $item['align'] : 'center';

        // Overlay: legacy boolean true -> 'dark'; validate against allowed set.
        $overlay = $item['overlay'] ?? 'none';
        if ($overlay === true) {
            $overlay = 'dark';
        }
        if (!in_array($overlay, ['none', 'dark', 'light'], true)) {
            $overlay = 'none';
        }

        $colsIn = is_array($item['cols'] ?? null) ? $item['cols'] : [];

        return [
            'id'         => bin2hex(random_bytes(3)),
            'type'       => 'hero',
            'background' => $background,
            'overlay'    => $overlay,
            'height'     => $height,
            'align'      => $align,
            'cols'       => [$this->buildWidgetList($colsIn[0] ?? [])],
        ];
    }

    /** @param mixed $widgetsIn @return array<int, array> */
    private function buildWidgetList($widgetsIn): array
    {
        $widgets = [];
        if (is_array($widgetsIn)) {
            foreach ($widgetsIn as $w) {
                if (is_array($w) && ($widget = $this->sanitizeWidget($w)) !== null) {
                    $widgets[] = $widget;
                }
            }
        }
        return $widgets;
    }

    /** Validate + sanitize a single widget; returns null for unknown types. */
    private function sanitizeWidget(array $w): ?array
    {
        $type = (string) ($w['type'] ?? '');
        if (!in_array($type, self::WIDGET_TYPES, true)) {
            return null;
        }
        $value = $w['value'] ?? null;

        switch ($type) {
            case 'richtext':
                return ['type' => 'richtext', 'value' => Html::sanitize(is_string($value) ? $value : '')];

            case 'image':
                $mid = is_array($value) ? trim((string) ($value['media_id'] ?? '')) : '';
                if ($mid === '') {
                    return ['type' => 'image', 'value' => null];
                }
                $img = ['media_id' => $mid, 'alt' => trim((string) ($value['alt'] ?? ''))];
                // Optional width in px (1–4000); blank/invalid = native size.
                $width = is_array($value) ? (int) ($value['width'] ?? 0) : 0;
                if ($width > 0) {
                    $img['width'] = min($width, 4000);
                }
                // Optional alignment; default (left) is omitted.
                $align = is_array($value) ? (string) ($value['align'] ?? '') : '';
                if (in_array($align, ['center', 'right'], true)) {
                    $img['align'] = $align;
                }
                return ['type' => 'image', 'value' => $img];

            case 'video':
                $mid = is_array($value) ? trim((string) ($value['media_id'] ?? '')) : '';
                $url = is_array($value) ? trim((string) ($value['url'] ?? '')) : '';
                $out = null;
                if ($mid !== '') {
                    $out = ['media_id' => $mid];
                } elseif ($url !== '' && preg_match('#^https?://#i', $url)) {
                    $out = ['url' => $url];
                }
                return ['type' => 'video', 'value' => $out];

            case 'file':
                $mid = is_array($value) ? trim((string) ($value['media_id'] ?? '')) : '';
                $label = is_array($value) ? trim((string) ($value['label'] ?? '')) : '';
                return [
                    'type'  => 'file',
                    'value' => $mid !== '' ? ['media_id' => $mid, 'label' => $label !== '' ? $label : 'Download'] : null,
                ];

            case 'button':
                $text = is_array($value) ? trim((string) ($value['text'] ?? '')) : '';
                $url  = is_array($value) ? trim((string) ($value['url'] ?? '')) : '';
                // Only allow safe link targets (absolute http(s), root/relative, anchor, mailto/tel).
                if ($url !== '' && !preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $url)) {
                    $url = '';
                }
                $size = (is_array($value) && ($value['size'] ?? '') === 'large') ? 'large' : 'normal';
                $variant = (is_array($value) && ($value['variant'] ?? '') === 'accent') ? 'accent' : 'primary';
                $align = is_array($value) ? (string) ($value['align'] ?? '') : '';
                if (!in_array($align, ['center', 'right'], true)) {
                    $align = 'left';
                }
                return [
                    'type'  => 'button',
                    'value' => [
                        'text'       => $text !== '' ? $text : 'Button',
                        'url'        => $url,
                        'variant'    => $variant,
                        'size'       => $size,
                        'align'      => $align,
                        'full_width' => is_array($value) && !empty($value['full_width']),
                    ],
                ];
        }

        return null;
    }

    /**
     * Decide the bcrypt password hash to persist for a page being updated.
     * Returns [hash|null, errorMessage|null].
     *
     * - Not protected: hash is cleared (null).
     * - A new password was typed: hash it.
     * - Blank, but a per-page hash already exists: keep it.
     * - Blank with no per-page hash but a legacy site-wide code exists: leave
     *   null and rely on the legacy fallback at verify time.
     * - Otherwise (protected with no usable password anywhere): error.
     */
    private function resolvePasswordHash(bool $isProtected, array $existing): array
    {
        if (!$isProtected) {
            return [null, null];
        }

        $new = (string) $this->request->post('page_password', '');
        if ($new !== '') {
            return [password_hash($new, PASSWORD_DEFAULT), null];
        }

        $existingHash = $existing['password_hash'] ?? null;
        if (is_string($existingHash) && $existingHash !== '') {
            return [$existingHash, null];
        }

        $legacy = $this->app->settings()->get('visitor_gate_hash');
        if (is_string($legacy) && $legacy !== '') {
            return [null, null];
        }

        return [null, 'Set a password to protect this page.'];
    }

    private function uniqueSlug(string $slug, ?string $exceptSlug = null): string
    {
        $base = $slug;
        $i = 2;
        while ($this->app->pages()->slugExists($slug, $exceptSlug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
