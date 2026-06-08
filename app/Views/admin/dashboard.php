<?php
/** @var \App\Core\App $app @var array $pages @var int $mediaCount */
$published = count(array_filter($pages, fn ($p) => ($p['status'] ?? '') === 'published'));
?>
<div class="page-head">
    <h1>Dashboard</h1>
</div>

<div class="stat-grid">
    <a class="stat-card" href="<?= e($app->url('/admin/pages')) ?>">
        <span class="stat-card__num"><?= count($pages) ?></span>
        <span class="stat-card__label">Pages (<?= $published ?> published)</span>
    </a>
    <a class="stat-card" href="<?= e($app->url('/admin/media')) ?>">
        <span class="stat-card__num"><?= (int) $mediaCount ?></span>
        <span class="stat-card__label">Media files</span>
    </a>
</div>

<div class="card">
    <div class="card__head">
        <h2>Quick actions</h2>
    </div>
    <div class="btn-row">
        <a class="btn btn--primary" href="<?= e($app->url('/admin/home/edit')) ?>">Edit home page</a>
        <a class="btn btn--primary" href="<?= e($app->url('/admin/pages/create')) ?>">+ New page</a>
        <a class="btn" href="<?= e($app->url('/admin/media')) ?>">Upload media</a>
        <a class="btn" href="<?= e($app->url('/admin/settings')) ?>">Site settings</a>
    </div>
</div>
