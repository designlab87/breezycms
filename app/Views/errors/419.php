<?php /** @var \App\Core\App $app @var string $message */ ?>
<section class="container error-page">
    <h1>Session expired</h1>
    <p><?= e($message ?? 'Your session expired. Please try again.') ?></p>
    <p><a class="btn" href="<?= e($app->url('/')) ?>">Back to home</a></p>
</section>
