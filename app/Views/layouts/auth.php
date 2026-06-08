<?php
/** @var \App\Core\App $app @var string $content @var string|null $title */
$siteTitle = $app->settings()->get('site_title', 'My Site');
$flash = $flash ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Log in') . ' — ' . $siteTitle) ?></title>
    <link rel="stylesheet" href="<?= e($app->url('/assets/css/admin.css')) ?>">
</head>
<body class="admin admin--auth">
    <div class="auth-wrap">
        <?php if ($flash): ?>
            <div class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </div>
</body>
</html>
