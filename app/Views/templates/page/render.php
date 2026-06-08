<?php
/**
 * Front-end renderer for builder pages. Top-level items are either a container
 * (1–4 column row) or a hero (full-width background with overlay content).
 *
 * @var \App\Core\App $app
 * @var \App\Core\View $view
 * @var array $layout
 */

/** Render an ordered list of widget blocks. */
$renderBlocks = function (array $widgets) use ($view, $app) {
    foreach ($widgets as $block) {
        echo '<div class="block block--' . e($block['type'] ?? '') . '">';
        echo $view->partial('partials/area', ['block' => $block, 'app' => $app]);
        echo '</div>';
    }
};
?>
<div class="builder-page">
<?php if (empty($layout)): ?>
    <div class="row-section"><div class="container"><p class="muted">This page has no content yet.</p></div></div>
<?php else: ?>
    <?php foreach ($layout as $item): ?>
        <?php if (($item['type'] ?? '') === 'hero'): ?>
            <?php
            $height = in_array($item['height'] ?? '', ['small', 'medium', 'large', 'full'], true) ? $item['height'] : 'medium';
            $align  = in_array($item['align'] ?? '', ['left', 'center', 'right'], true) ? $item['align'] : 'center';
            $bg     = $item['background'] ?? null;
            $hasBg  = !empty($bg['media_id']);
            $overlay = $item['overlay'] ?? 'none';
            if ($overlay === true) { $overlay = 'dark'; } // legacy boolean
            if (!in_array($overlay, ['none', 'dark', 'light'], true)) { $overlay = 'none'; }
            $classes = 'hero hero--h-' . $height . ' hero--align-' . $align;
            if ($hasBg) { $classes .= ' hero--has-bg'; }
            if ($overlay !== 'none') { $classes .= ' hero--overlay-' . $overlay; }
            ?>
            <section class="<?= e($classes) ?>">
                <?php if ($hasBg): ?>
                    <div class="hero__bg">
                        <?php if (($bg['kind'] ?? 'image') === 'video'): ?>
                            <video src="<?= e($app->url('/media/' . $bg['media_id'])) ?>" autoplay muted loop playsinline></video>
                        <?php else: ?>
                            <img src="<?= e($app->url('/media/' . $bg['media_id'])) ?>" alt="">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="hero__content container">
                    <div class="hero__inner">
                        <?php $renderBlocks($item['cols'][0] ?? []); ?>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <?php
            $layout = in_array($item['layout'] ?? '', ['1-2', '1-3', '2-1', '3-1'], true) ? $item['layout'] : 'equal';
            $columns = $layout === 'equal' ? max(1, min(4, (int) ($item['columns'] ?? 1))) : 2;
            $colsClass = $layout === 'equal' ? 'row--cols-' . $columns : 'row--ratio-' . $layout;
            $bgColor = $item['background_color'] ?? null;
            $bgImage = $item['background_image'] ?? null;
            $bgFit = in_array($item['background_fit'] ?? '', ['cover', 'contain', '100%'], true) ? $item['background_fit'] : 'cover';
            $styles = [];
            if ($bgColor) {
                $styles[] = 'background-color: ' . $bgColor;
            }
            if ($bgImage) {
                $styles[] = 'background-image: url(' . $app->url('/media/' . $bgImage) . ')';
                $styles[] = 'background-size: ' . ($bgFit === '100%' ? '100%' : $bgFit);
                $styles[] = 'background-position: center';
                $styles[] = 'background-repeat: no-repeat';
            }
            $sectionClass = 'row-section' . ($styles ? ' row-section--colored' : '');
            $sectionStyle = $styles ? ' style="' . e(implode('; ', $styles)) . '"' : '';
            ?>
            <div class="<?= $sectionClass ?>"<?= $sectionStyle ?>>
                <div class="container">
                    <div class="row <?= $colsClass ?>">
                        <?php foreach (($item['cols'] ?? []) as $col): ?>
                            <div class="row__col"><?php $renderBlocks($col ?? []); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
</div>

