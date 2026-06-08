<?php
/**
 * @var \App\Core\App $app
 * @var string $content
 * @var string|null $title
 */
$settings = $app->settings();
$siteTitle = $settings->get('site_title', 'My Site');
$navPages = $app->pages()->menu();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$navActive = fn (string $path): bool => $currentPath === $app->url($path);
$pageTitle = isset($title) && $title ? $title . ' — ' . $siteTitle : $siteTitle;
$flash = $flash ?? null;
$gateSlug = $gate_slug ?? null;
$preview = $preview ?? null;
$heartbeatMs = $app->config('session')['heartbeat_ms'] ?? 120000;
$ts = $app->turnstile();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e($app->url('/assets/css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e($app->url('/assets/css/site.css')) ?>">
    <style id="theme-vars"><?= $app->theme()->cssVariables() ?></style>
</head>
<body class="site<?= $preview ? ' has-preview-bar' : '' ?>"<?php if ($gateSlug): ?>
    data-session-scope="gate"
    data-session-status-url="<?= e($app->url('/session/status?scope=gate&slug=' . urlencode($gateSlug))) ?>"
    data-session-action-url="<?= e($app->url('/gate/' . urlencode($gateSlug))) ?>"
    data-session-heartbeat-ms="<?= e((string) $heartbeatMs) ?>"<?php if ($ts->enabled()): ?>
    data-turnstile-sitekey="<?= e($ts->siteKey()) ?>"<?php endif; ?><?php endif; ?>>
    <?php if ($preview): ?>
    <div class="preview-bar">
        <div class="preview-bar__info">
            <span class="preview-bar__tag">Preview</span>
            <span class="preview-bar__title"><?= e($preview['title']) ?></span>
            <span class="preview-bar__status preview-bar__status--<?= $preview['isLive'] ? 'live' : 'draft' ?>"><?= e($preview['status']) ?></span>
        </div>
        <div class="preview-bar__actions">
            <?php if ($preview['isLive']): ?>
                <a class="btn" href="<?= e($preview['liveUrl']) ?>" target="_blank" rel="noopener">View live</a>
            <?php endif; ?>
            <a class="btn btn--primary" href="<?= e($preview['editUrl']) ?>">&larr; Back to edit</a>
        </div>
    </div>
    <?php endif; ?>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="site-brand" href="<?= e($app->url('/')) ?>"><?= e($siteTitle) ?></a>
            <button class="nav-toggle" aria-label="Toggle navigation" onclick="document.body.classList.toggle('nav-open')">
                <span></span><span></span><span></span>
            </button>
            <nav class="site-nav">
                <?php $homeActive = $navActive('/'); ?>
                <a href="<?= e($app->url('/')) ?>"<?= $homeActive ? ' class="is-active" aria-current="page"' : '' ?>>Home</a>
                <?php foreach ($navPages as $p): ?>
                    <?php $active = $navActive('/page/' . $p['slug']); ?>
                    <a href="<?= e($app->url('/page/' . $p['slug'])) ?>"<?= $active ? ' class="is-active" aria-current="page"' : '' ?>>
                        <?= e($p['title']) ?><?= !empty($p['is_protected']) ? ' &#128274;' : '' ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="container"><div class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div></div>
    <?php endif; ?>

    <main class="site-main">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteTitle) ?></p>
        </div>
    </footer>

    <?php if ($gateSlug): ?>
        <?php if ($ts->enabled()): ?>
            <script src="<?= e($ts->apiJs()) ?>" async defer></script>
        <?php endif; ?>
        <script src="<?= e($app->url('/assets/js/session.js')) ?>"></script>
    <?php endif; ?>
</body>
</html>
