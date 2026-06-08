<?php
/**
 * @var \App\Core\App $app
 * @var string $content
 * @var string|null $title
 */
$siteTitle = $app->settings()->get('site_title', 'My Site');
$flash = $flash ?? null;
$heartbeatMs = $app->config('session')['heartbeat_ms'] ?? 120000;
$ts = $app->turnstile();
$current = $_SERVER['REQUEST_URI'] ?? '';
$navItem = function (string $path, string $label) use ($app, $current) {
    $href = $app->url($path);
    $active = str_starts_with(parse_url($current, PHP_URL_PATH) ?? '', $href) && $path !== '/admin/dashboard'
        || (parse_url($current, PHP_URL_PATH) === $href);
    return '<a class="admin-nav__link' . ($active ? ' is-active' : '') . '" href="' . e($href) . '">' . e($label) . '</a>';
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Admin') . ' — ' . $siteTitle) ?></title>
    <link rel="stylesheet" href="<?= e($app->url('/assets/vendor/quill/quill.snow.css')) ?>">
    <link rel="stylesheet" href="<?= e($app->url('/assets/css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e($app->url('/assets/css/admin.css')) ?>">
    <script>window.THEME_FONTS = <?= json_encode(array_map(fn ($f) => $f['label'], $app->fonts()), JSON_UNESCAPED_SLASHES) ?>;</script>
    <?php $adminTheme = $app->theme(); ?>
    <script>window.THEME_COLORS = <?= json_encode(array_values(array_filter([
        $adminTheme->get('color_primary'),
        $adminTheme->get('color_accent'),
        $adminTheme->get('color_base'),
    ])), JSON_UNESCAPED_SLASHES) ?>;</script>
</head>
<body class="admin"
    data-session-scope="admin"
    data-session-status-url="<?= e($app->url('/session/status?scope=admin')) ?>"
    data-session-action-url="<?= e($app->url('/admin/login')) ?>"
    data-session-heartbeat-ms="<?= e((string) $heartbeatMs) ?>"<?php if ($ts->enabled()): ?>
    data-turnstile-sitekey="<?= e($ts->siteKey()) ?>"<?php endif; ?>>
    <header class="admin-topbar">
        <div class="admin-topbar__inner">
            <a class="admin-brand" href="<?= e($app->url('/admin/dashboard')) ?>">
                <span class="admin-brand__site"><?= e($siteTitle) ?></span>
                <span class="admin-brand__cms">Breezy&nbsp;CMS</span>
            </a>
            <div class="admin-topbar__right">
                <a class="admin-link" href="<?= e($app->url('/')) ?>" target="_blank">View site &#8599;</a>
                <?php if ($email = $app->auth()->adminEmail()): ?>
                    <span class="admin-user" title="Signed in as <?= e($email) ?>"><?= e($email) ?></span>
                <?php endif; ?>
                <form method="post" action="<?= e($app->url('/admin/logout')) ?>" class="inline-form">
                    <?= $csrf->field() ?>
                    <button class="btn btn--ghost admin-signout" type="submit">Sign out</button>
                </form>
            </div>
        </div>
    </header>

    <div class="admin-shell">
        <nav class="admin-nav">
            <?= $navItem('/admin/dashboard', 'Dashboard') ?>
            <?= $navItem('/admin/pages', 'Pages') ?>
            <?= $navItem('/admin/media', 'Media') ?>
            <?= $navItem('/admin/theme', 'Theme') ?>
            <?= $navItem('/admin/settings', 'Settings') ?>
        </nav>

        <main class="admin-content">
            <?php if ($flash): ?>
                <div class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>

    <script src="<?= e($app->url('/assets/vendor/quill/quill.js')) ?>"></script>
    <script src="<?= e($app->url('/assets/js/admin.js')) ?>"></script>
    <?php if ($ts->enabled()): ?>
        <script src="<?= e($ts->apiJs()) ?>" async defer></script>
    <?php endif; ?>
    <script src="<?= e($app->url('/assets/js/session.js')) ?>"></script>
</body>
</html>
