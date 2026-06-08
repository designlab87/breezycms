<?php
/**
 * Renders a single content block on the front end.
 *
 * @var array $block  ['type' => ..., 'value' => ...]
 * @var \App\Core\App $app
 */
$type  = $block['type'] ?? 'richtext';
$value = $block['value'] ?? null;

switch ($type) {
    case 'richtext':
        // Stored as sanitized HTML from the editor.
        echo is_string($value) ? $value : '';
        break;

    case 'image':
        if (!empty($value['media_id'])) {
            $src = $app->url('/media/' . $value['media_id']);
            $align = in_array($value['align'] ?? '', ['center', 'right'], true) ? $value['align'] : 'left';
            $width = (int) ($value['width'] ?? 0);
            $classes = 'content-image content-image--align-' . $align;
            $style = $width > 0 ? ' style="width:' . $width . 'px"' : '';
            echo '<figure class="content-image-wrap content-image-wrap--align-' . $align . '">'
                . '<img class="' . $classes . '" src="' . e($src) . '" alt="' . e($value['alt'] ?? '') . '"' . $style . '>'
                . '</figure>';
        }
        break;

    case 'video':
        if (!empty($value['media_id'])) {
            $src = $app->url('/media/' . $value['media_id']);
            echo '<video class="content-video" controls preload="metadata" src="' . e($src) . '"></video>';
        } elseif (!empty($value['url'])) {
            echo '<div class="content-embed"><iframe src="' . e($value['url'])
                . '" allowfullscreen loading="lazy"></iframe></div>';
        }
        break;

    case 'file':
        if (!empty($value['media_id'])) {
            $href  = $app->url('/download/' . $value['media_id']);
            $label = $value['label'] ?? 'Download';
            echo '<a class="content-download" href="' . e($href) . '">'
                . '<span class="content-download__icon" aria-hidden="true">&#8595;</span> '
                . e($label) . '</a>';
        }
        break;

    case 'button':
        if (!empty($value['text'])) {
            $href = ($value['url'] ?? '') !== '' ? $value['url'] : '#';
            $align = in_array($value['align'] ?? '', ['center', 'right'], true) ? $value['align'] : 'left';
            $classes = 'content-button';
            if (($value['variant'] ?? '') === 'accent') {
                $classes .= ' content-button--accent';
            }
            if (($value['size'] ?? '') === 'large') {
                $classes .= ' content-button--large';
            }
            if (!empty($value['full_width'])) {
                $classes .= ' content-button--full';
            }
            echo '<div class="content-button-wrap content-button-wrap--align-' . $align . '">'
                . '<a class="' . $classes . '" href="' . e($href) . '">' . e($value['text']) . '</a>'
                . '</div>';
        }
        break;
}
