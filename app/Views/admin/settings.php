<?php
/** @var \App\Core\App $app @var array $settings @var \App\Core\Csrf $csrf @var string|null $adminEmail */
?>
<div class="page-head"><h1>Settings</h1></div>

<form method="post" action="<?= e($app->url('/admin/settings')) ?>" class="editor-form">
    <?= $csrf->field() ?>

    <div class="card">
        <h2 class="card__title">Site</h2>
        <label class="field">
            <span class="field__label">Site title</span>
            <input type="text" name="site_title" value="<?= e($settings['site_title'] ?? '') ?>" required>
        </label>
    </div>

    <div class="card">
        <h2 class="card__title">Passwords</h2>
        <p class="muted">Leave blank to keep the current password.</p>
        <label class="field">
            <span class="field__label">
                Change your password
                <?php if (!empty($adminEmail)): ?>
                    <span class="muted">(<?= e($adminEmail) ?>)</span>
                <?php endif; ?>
            </span>
            <input type="password" name="admin_password" autocomplete="new-password" placeholder="••••••••">
        </label>
        <p class="field__hint">Protected pages now use their own password, set per page in the page editor.</p>
        <p class="field__hint">Admin accounts live in <code>storage/content/users.json</code>. Add more accounts there (email, name, and a bcrypt <code>password_hash</code>).</p>
    </div>

    <div class="card editor-actions">
        <button class="btn btn--primary" type="submit">Save settings</button>
    </div>
</form>
