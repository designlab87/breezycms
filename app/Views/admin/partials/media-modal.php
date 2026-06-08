<?php /** @var \App\Core\App $app @var \App\Core\Csrf $csrf */ ?>
<div class="media-modal" id="mediaModal" hidden
     data-list-url="<?= e($app->url('/admin/media/json')) ?>"
     data-upload-url="<?= e($app->url('/admin/media')) ?>">
    <div class="media-modal__backdrop" data-close></div>
    <div class="media-modal__dialog" role="dialog" aria-modal="true" aria-label="Media library">
        <div class="media-modal__head">
            <h3 class="media-modal__title">Media</h3>
            <button type="button" class="btn btn--ghost" data-close aria-label="Close">&times;</button>
        </div>
        <form class="media-modal__upload" data-upload enctype="multipart/form-data">
            <?= $csrf->field() ?>
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="type" class="media-modal__type" value="image">
            <input type="file" name="file" class="media-modal__file" required>
            <button class="btn btn--primary" type="submit">Upload</button>
            <span class="media-modal__status" aria-live="polite"></span>
        </form>
        <div class="media-modal__grid" data-grid></div>
    </div>
</div>
