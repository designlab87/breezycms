<?php
/**
 * Visitor password gate shown before a protected page's content.
 *
 * @var \App\Core\App $app
 * @var array $page
 * @var \App\Core\Csrf $csrf
 * @var string|null $error
 */
$ts = $app->turnstile();
?>
<section class="container gate">
    <div class="gate__card">
        <div class="gate__icon" aria-hidden="true">&#128274;</div>
        <h1 class="gate__title"><?= e($page['title']) ?></h1>
        <p class="gate__intro">This page is password protected. Enter the password to continue.</p>
        <?php if (!empty($error)): ?>
            <p class="gate__error"><?= e($error) ?></p>
        <?php endif; ?>
        <form method="post" action="<?= e($app->url('/gate/' . $page['slug'])) ?>" class="gate__form">
            <?= $csrf->field() ?>
            <input type="password" name="password" placeholder="Password" autocomplete="current-password" autofocus required>
            <?php if ($ts->enabled()): ?>
                <div class="cf-turnstile" data-sitekey="<?= e($ts->siteKey()) ?>" data-theme="light" data-size="flexible"></div>
            <?php endif; ?>
            <button class="btn btn--primary" type="submit">Unlock</button>
        </form>
    </div>
</section>
<?php if ($ts->enabled()): ?>
    <script src="<?= e($ts->apiJs()) ?>" async defer></script>
<?php endif; ?>
