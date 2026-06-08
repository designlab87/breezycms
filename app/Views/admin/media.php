<?php
/** @var \App\Core\App $app @var array $images @var array $videos @var array $files @var array $uploads @var int $uploadMaxBytes @var \App\Core\Csrf $csrf */

use App\Core\UploadLimits;

$humanSize = [UploadLimits::class, 'humanSize'];
$maxText   = UploadLimits::humanSize($uploadMaxBytes);

$uploadForm = function (string $type) use ($app, $csrf, $uploads, $maxText) {
    $rules = $uploads[$type] ?? [];
    $exts  = $rules['extensions'] ?? [];
    // Build an accept hint for the file picker from the allowed extensions.
    $accept  = $exts ? '.' . implode(',.', $exts) : null;
    ?>
    <form method="post" action="<?= e($app->url('/admin/media')) ?>" enctype="multipart/form-data" class="upload-form">
        <?= $csrf->field() ?>
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="file" name="file"<?= $accept ? ' accept="' . e($accept) . '"' : '' ?> required>
        <button class="btn btn--small btn--primary" type="submit">Upload</button>
    </form>
    <p class="upload-limits">
        <?php if ($exts): ?>
            <span class="upload-limits__item"><strong>Accepted:</strong> <?= e(implode(', ', $exts)) ?></span>
        <?php endif; ?>
        <span class="upload-limits__item"><strong>Max size:</strong> <?= e($maxText) ?> <span class="muted">(PHP limit)</span></span>
    </p>
    <?php
};

$deleteForm = function (array $m) use ($app, $csrf) {
    ?>
    <form method="post" action="<?= e($app->url('/admin/media/' . $m['id'] . '/delete')) ?>"
          class="inline-form" onsubmit="return confirm('Delete this file?');">
        <?= $csrf->field() ?>
        <button class="btn btn--small btn--danger" type="submit">Delete</button>
    </form>
    <?php
};
?>
<div class="page-head"><h1>Media library</h1></div>

<div class="card">
    <div class="card__head"><h2>Images</h2></div>
    <?php $uploadForm('image'); ?>
    <div class="media-grid">
        <?php foreach ($images as $m): ?>
            <div class="media-tile">
                <img src="<?= e($app->url('/media/' . $m['id'])) ?>" alt="">
                <div class="media-tile__meta">
                    <span class="media-tile__name" title="<?= e($m['original_name']) ?>"><?= e($m['original_name']) ?></span>
                    <span class="muted"><?= e($humanSize((int) $m['size'])) ?></span>
                </div>
                <?php $deleteForm($m); ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$images): ?><p class="muted">No images yet.</p><?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card__head"><h2>Videos</h2></div>
    <?php $uploadForm('video'); ?>
    <div class="media-grid">
        <?php foreach ($videos as $m): ?>
            <div class="media-tile">
                <video src="<?= e($app->url('/media/' . $m['id'])) ?>" controls preload="metadata"></video>
                <div class="media-tile__meta">
                    <span class="media-tile__name" title="<?= e($m['original_name']) ?>"><?= e($m['original_name']) ?></span>
                    <span class="muted"><?= e($humanSize((int) $m['size'])) ?></span>
                </div>
                <?php $deleteForm($m); ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$videos): ?><p class="muted">No videos yet.</p><?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card__head"><h2>Files</h2></div>
    <?php $uploadForm('file'); ?>
    <ul class="file-list">
        <?php foreach ($files as $m): ?>
            <li class="file-list__item">
                <a href="<?= e($app->url('/download/' . $m['id'])) ?>">&#128206; <?= e($m['original_name']) ?></a>
                <span class="muted"><?= e($humanSize((int) $m['size'])) ?></span>
                <?php $deleteForm($m); ?>
            </li>
        <?php endforeach; ?>
        <?php if (!$files): ?><li class="muted">No files yet.</li><?php endif; ?>
    </ul>
</div>
