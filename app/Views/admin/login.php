<?php
/** @var \App\Core\App $app @var \App\Core\Csrf $csrf */
$ts = $app->turnstile();
?>
<div class="auth-card">
    <h1 class="auth-card__title"><?= e($app->settings()->get('site_title', 'My Site')) ?></h1>
    <p class="auth-card__subtitle">Admin sign in</p>
    <form method="post" action="<?= e($app->url('/admin/login')) ?>">
        <?= $csrf->field() ?>
        <label class="field">
            <span class="field__label">Email</span>
            <input type="email" name="email" autocomplete="username" autofocus required>
        </label>
        <label class="field">
            <span class="field__label">Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <?php if ($ts->enabled()): ?>
            <div class="cf-turnstile" data-sitekey="<?= e($ts->siteKey()) ?>" data-theme="light" data-size="flexible"></div>
        <?php endif; ?>
        <button class="btn btn--primary btn--block" type="submit">Log in</button>
    </form>
    <p class="auth-card__back"><a href="<?= e($app->url('/')) ?>">&larr; Back to site</a></p>
</div>
<?php if ($ts->enabled()): ?>
    <script src="<?= e($ts->apiJs()) ?>" async defer></script>
<?php endif; ?>
