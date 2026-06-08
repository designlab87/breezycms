<?php /** @var \App\Core\App $app */ ?>
<section class="container error-page">
    <h1>404</h1>
    <p>Sorry, that page could not be found.</p>
    <p><a class="btn" href="<?= e($app->url('/')) ?>">Back to home</a></p>
</section>
