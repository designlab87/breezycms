<?php
/**
 * @var \App\Core\App $app @var \App\Core\Csrf $csrf
 * @var array $theme @var array $fonts @var array $corners
 */
$fontOptions = function (string $selected) use ($fonts) {
    $out = '';
    foreach ($fonts as $key => $def) {
        $sel = $key === $selected ? ' selected' : '';
        $out .= '<option value="' . e($key) . '"' . $sel . '>' . e($def['label']) . '</option>';
    }
    return $out;
};
?>
<div class="page-head"><h1>Theme</h1></div>

<form method="post" action="<?= e($app->url('/admin/theme')) ?>" class="editor-form" id="themeForm">
    <?= $csrf->field() ?>

    <div class="card">
        <h2 class="card__title">Colors</h2>
        <p class="muted">Enter a hex code for each color (e.g. <code>#2563eb</code>). Text on buttons and colored areas automatically switches between dark and light for legibility.</p>
        <div class="theme-colors">
            <div class="theme-color">
                <span class="field__label">Primary <span class="muted">(buttons, links, accents)</span></span>
                <div class="theme-color__row hex-field">
                    <span class="hex-field__swatch" style="background: <?= e($theme['color_primary']) ?>"></span>
                    <input type="text" class="hex-field__input" name="color_primary" id="color_primary" value="<?= e($theme['color_primary']) ?>" placeholder="#2563eb" spellcheck="false" autocomplete="off" maxlength="7">
                </div>
            </div>
            <div class="theme-color">
                <span class="field__label">Accent <span class="muted">(secondary highlights)</span></span>
                <div class="theme-color__row hex-field">
                    <span class="hex-field__swatch" style="background: <?= e($theme['color_accent']) ?>"></span>
                    <input type="text" class="hex-field__input" name="color_accent" id="color_accent" value="<?= e($theme['color_accent']) ?>" placeholder="#10b981" spellcheck="false" autocomplete="off" maxlength="7">
                </div>
            </div>
            <div class="theme-color">
                <span class="field__label">Base <span class="muted">(header, footer, bands)</span></span>
                <div class="theme-color__row hex-field">
                    <span class="hex-field__swatch" style="background: <?= e($theme['color_base']) ?>"></span>
                    <input type="text" class="hex-field__input" name="color_base" id="color_base" value="<?= e($theme['color_base']) ?>" placeholder="#f7f8fa" spellcheck="false" autocomplete="off" maxlength="7">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="card__title">Fonts</h2>
        <p class="muted">Self-hosted Google Fonts. These apply across the site and are also available in the rich-text editor.</p>
        <label class="field">
            <span class="field__label">Heading 1 font <span class="muted">(page / hero titles)</span></span>
            <select name="font_h1" class="font-select"><?= $fontOptions($theme['font_h1']) ?></select>
        </label>
        <label class="field">
            <span class="field__label">Heading 2 &amp; 3 font</span>
            <select name="font_h2" class="font-select"><?= $fontOptions($theme['font_h2']) ?></select>
        </label>
        <label class="field">
            <span class="field__label">Body font</span>
            <select name="font_body" class="font-select"><?= $fontOptions($theme['font_body']) ?></select>
        </label>
    </div>

    <div class="card">
        <h2 class="card__title">Font sizes</h2>
        <p class="muted">Optional. Set a size in pixels for each content style, or leave blank to use the default. Range <?= e((string) \App\Core\Theme::SIZE_MIN) ?>&ndash;<?= e((string) \App\Core\Theme::SIZE_MAX) ?>px.</p>
        <div class="size-grid">
            <label class="size-field">
                <span class="field__label">Heading 1</span>
                <span class="size-field__input">
                    <input type="number" name="size_h1" min="<?= e((string) \App\Core\Theme::SIZE_MIN) ?>" max="<?= e((string) \App\Core\Theme::SIZE_MAX) ?>" placeholder="Default" value="<?= e((string) ($theme['size_h1'] ?? '')) ?>">
                    <span class="size-field__unit">px</span>
                </span>
            </label>
            <label class="size-field">
                <span class="field__label">Heading 2</span>
                <span class="size-field__input">
                    <input type="number" name="size_h2" min="<?= e((string) \App\Core\Theme::SIZE_MIN) ?>" max="<?= e((string) \App\Core\Theme::SIZE_MAX) ?>" placeholder="Default" value="<?= e((string) ($theme['size_h2'] ?? '')) ?>">
                    <span class="size-field__unit">px</span>
                </span>
            </label>
            <label class="size-field">
                <span class="field__label">Heading 3</span>
                <span class="size-field__input">
                    <input type="number" name="size_h3" min="<?= e((string) \App\Core\Theme::SIZE_MIN) ?>" max="<?= e((string) \App\Core\Theme::SIZE_MAX) ?>" placeholder="Default" value="<?= e((string) ($theme['size_h3'] ?? '')) ?>">
                    <span class="size-field__unit">px</span>
                </span>
            </label>
            <label class="size-field">
                <span class="field__label">Normal</span>
                <span class="size-field__input">
                    <input type="number" name="size_body" min="<?= e((string) \App\Core\Theme::SIZE_MIN) ?>" max="<?= e((string) \App\Core\Theme::SIZE_MAX) ?>" placeholder="Default" value="<?= e((string) ($theme['size_body'] ?? '')) ?>">
                    <span class="size-field__unit">px</span>
                </span>
            </label>
        </div>
    </div>

    <div class="card">
        <h2 class="card__title">Corners</h2>
        <p class="muted">Applies to images, buttons, cards, and inputs across the site.</p>
        <div class="corner-presets">
            <?php foreach ($corners as $c): ?>
                <label class="corner-option">
                    <input type="radio" name="corners" value="<?= e($c) ?>" <?= ($theme['corners'] ?? '') === $c ? 'checked' : '' ?>>
                    <span class="corner-option__box corner-option__box--<?= e($c) ?>"></span>
                    <span class="corner-option__label"><?= e(ucfirst($c)) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card editor-actions">
        <button class="btn btn--primary" type="submit">Save theme</button>
    </div>
</form>

<script>
(function () {
    // Live swatch preview next to each hex input (feedback only — no picker).
    document.querySelectorAll('.hex-field').forEach(function (field) {
        var input = field.querySelector('.hex-field__input');
        var swatch = field.querySelector('.hex-field__swatch');
        var update = function () {
            var v = input.value.trim();
            if (v !== '' && v.charAt(0) !== '#') { v = '#' + v; }
            swatch.style.background = /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v) ? v : '';
        };
        input.addEventListener('input', update);
        // Normalize a leading # on blur so the saved value is tidy.
        input.addEventListener('blur', function () {
            var v = input.value.trim();
            if (v !== '' && v.charAt(0) !== '#') { input.value = '#' + v; update(); }
        });
        update();
    });

    // Live font preview: render each select's options in their own font.
    var families = <?= json_encode(array_map(fn ($f) => $f['family'], $fonts), JSON_UNESCAPED_SLASHES) ?>;
    document.querySelectorAll('.font-select').forEach(function (sel) {
        var apply = function () { sel.style.fontFamily = families[sel.value] || ''; };
        sel.addEventListener('change', apply);
        apply();
    });
})();
</script>
