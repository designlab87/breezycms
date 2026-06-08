<?php
/**
 * Drag-and-drop page builder.
 *
 * @var \App\Core\App $app  @var \App\Core\View $view  @var \App\Core\Csrf $csrf
 * @var string $mode  @var string $formAction  @var array $page
 * @var bool $isHome
 */
$isHome = $isHome ?? false;
$layoutJson = json_encode($page['layout'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$previewUrl = $isHome
    ? $app->url('/admin/home/preview')
    : $app->url('/admin/pages/' . $page['slug'] . '/preview');
?>
<div class="page-head">
    <h1><?= $isHome ? 'Edit home page' : ($mode === 'create' ? 'New page' : 'Edit page') ?></h1>
    <div class="btn-row">
        <?php if ($isHome || $mode === 'edit'): ?>
            <a class="btn" href="<?= e($previewUrl) ?>">Preview</a>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e($app->url('/admin/pages')) ?>">Back</a>
    </div>
</div>

<form method="post" action="<?= e($formAction) ?>" class="builder-form" id="builderForm">
    <?= $csrf->field() ?>
    <input type="hidden" name="layout_json" id="layoutJson" value="">

    <div class="builder-grid">
        <div class="builder-main">
            <div class="builder-palette card">
                <div class="builder-palette__tabs" role="tablist">
                    <button type="button" class="builder-palette__tab is-active" data-palette-tab="layout" role="tab" aria-selected="true">Layout</button>
                    <button type="button" class="builder-palette__tab" data-palette-tab="widgets" role="tab" aria-selected="false">Widgets</button>
                </div>

                <div class="builder-palette__panel" data-palette-panel="layout">
                    <div class="palette-list" id="paletteLayout">
                        <div class="palette-item palette-item--hero" data-kind="hero"><span class="palette-item__icon">★</span> Hero</div>
                        <div class="palette-item palette-item--container" data-kind="container" data-columns="1"><span class="palette-item__icon">▭</span> 1 column</div>
                        <div class="palette-item palette-item--container" data-kind="container" data-columns="2"><span class="palette-item__icon">▭▭</span> 2 columns</div>
                        <div class="palette-item palette-item--container" data-kind="container" data-columns="3"><span class="palette-item__icon">▭▭▭</span> 3 columns</div>
                        <div class="palette-item palette-item--container" data-kind="container" data-columns="4"><span class="palette-item__icon">▭▭▭▭</span> 4 columns</div>
                        <div class="palette-item palette-item--container" data-kind="container" data-columns="2" data-layout="1-2"><span class="palette-item__icon">▪▭</span> 1:2 columns</div>
                        <div class="palette-item palette-item--container" data-kind="container" data-columns="2" data-layout="1-3"><span class="palette-item__icon">▪▭▭</span> 1:3 columns</div>
                        <div class="palette-item palette-item--container" data-kind="container" data-columns="2" data-layout="2-1"><span class="palette-item__icon">▭▪</span> 2:1 columns</div>
                        <div class="palette-item palette-item--container" data-kind="container" data-columns="2" data-layout="3-1"><span class="palette-item__icon">▭▭▪</span> 3:1 columns</div>
                    </div>
                    <p class="builder-palette__hint">Drag a hero or container onto the page.</p>
                </div>

                <div class="builder-palette__panel" data-palette-panel="widgets" hidden>
                    <div class="palette-list" id="paletteWidgets">
                        <div class="palette-item palette-item--widget" data-kind="widget" data-widget-type="richtext"><span class="palette-item__icon">¶</span> Rich text</div>
                        <div class="palette-item palette-item--widget" data-kind="widget" data-widget-type="image"><span class="palette-item__icon">🖼</span> Image</div>
                        <div class="palette-item palette-item--widget" data-kind="widget" data-widget-type="video"><span class="palette-item__icon">🎬</span> Video</div>
                        <div class="palette-item palette-item--widget" data-kind="widget" data-widget-type="file"><span class="palette-item__icon">📎</span> File download</div>
                        <div class="palette-item palette-item--widget" data-kind="widget" data-widget-type="button"><span class="palette-item__icon">🔘</span> Button</div>
                    </div>
                    <p class="builder-palette__hint">Drag a widget into a container column.</p>
                </div>
            </div>

            <div class="builder-canvas card"
                 id="builderCanvas"
                 data-initial="<?= e($layoutJson) ?>"
                 data-media-prefix="<?= e($app->url('/media/')) ?>">
                <div class="builder-canvas__empty">Drag a hero or container here to start building your page.</div>
            </div>
        </div>

        <aside class="builder-side">
            <div class="card">
                <h2 class="card__title"><?= $isHome ? 'Home page' : 'Page settings' ?></h2>
                <?php if ($isHome): ?>
                    <p class="muted">The home page can't be deleted, password protected, or set to draft. It always lives at the site root.</p>
                <?php else: ?>
                    <label class="field">
                        <span class="field__label">Title</span>
                        <input type="text" name="title" value="<?= e($page['title']) ?>" required>
                    </label>
                    <label class="field">
                        <span class="field__label">URL slug</span>
                        <input type="text" name="slug" value="<?= e($page['slug']) ?>" placeholder="auto from title">
                        <span class="field__hint">Leave blank to generate from the title.</span>
                    </label>
                    <label class="field">
                        <span class="field__label">Status</span>
                        <select name="status">
                            <option value="draft" <?= ($page['status'] ?? '') !== 'published' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </label>
                    <label class="field field--check">
                        <input type="checkbox" name="is_protected" value="1" id="isProtected" <?= !empty($page['is_protected']) ? 'checked' : '' ?>>
                        <span>Password protect this page</span>
                    </label>
                    <label class="field" id="pagePasswordField"<?= empty($page['is_protected']) ? ' hidden' : '' ?>>
                        <span class="field__label">Page password</span>
                        <input type="password" name="page_password" autocomplete="new-password" placeholder="••••••••">
                        <span class="field__hint">
                            <?php if (!empty($page['password_hash'])): ?>
                                Leave blank to keep the current password.
                            <?php elseif (!empty($page['is_protected'])): ?>
                                This page currently uses the legacy site-wide code. Enter a password to give it its own.
                            <?php else: ?>
                                Set the password visitors must enter to view this page.
                            <?php endif; ?>
                        </span>
                    </label>
                    <label class="field field--check">
                        <input type="checkbox" name="in_menu" value="1" <?= ($page['in_menu'] ?? true) ? 'checked' : '' ?>>
                        <span>Show in navigation menu</span>
                    </label>
                <?php endif; ?>
            </div>
            <div class="card editor-actions">
                <span class="builder-status is-saved" id="builderStatus">All changes saved</span>
                <button class="btn btn--primary btn--block" type="submit"><?= $isHome ? 'Save home page' : 'Save page' ?></button>
            </div>
        </aside>
    </div>
</form>

<?= $view->partial('admin/partials/media-modal', ['app' => $app, 'csrf' => $csrf]) ?>

<script src="<?= e($app->url('/assets/vendor/sortable/Sortable.min.js')) ?>"></script>
<script src="<?= e($app->url('/assets/js/builder.js')) ?>"></script>
