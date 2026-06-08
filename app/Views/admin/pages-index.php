<?php
/** @var \App\Core\App $app @var array $pages @var \App\Core\Csrf $csrf */
$blockCount = function (array $page): int {
    $n = 0;
    foreach ($page['layout'] ?? [] as $item) {
        foreach ($item['cols'] ?? [] as $col) {
            $n += count($col ?? []);
        }
    }
    return $n;
};
?>
<div class="page-head">
    <h1>Pages</h1>
    <a class="btn btn--primary" href="<?= e($app->url('/admin/pages/create')) ?>">+ New page</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr><th></th><th>Title</th><th>Blocks</th><th>Status</th><th>Access</th><th></th></tr>
        </thead>
        <!-- Home is a special page: always first, can't be reordered or deleted. -->
        <tbody>
            <tr class="row-home">
                <td class="drag-cell"><span class="drag-handle drag-handle--locked" title="Home is always first">⌂</span></td>
                <td>
                    <strong>Home</strong><br>
                    <span class="muted">/</span>
                </td>
                <td>—</td>
                <td><span class="badge badge--green">published</span></td>
                <td>Public</td>
                <td class="table__actions">
                    <a class="btn btn--small" href="<?= e($app->url('/')) ?>" target="_blank">View</a>
                    <a class="btn btn--small" href="<?= e($app->url('/admin/home/edit')) ?>">Edit</a>
                </td>
            </tr>
        </tbody>

        <tbody id="pagesSortable"
               data-reorder-url="<?= e($app->url('/admin/pages/reorder')) ?>"
               data-csrf="<?= e($csrf->token()) ?>">
            <?php foreach ($pages as $p): ?>
                <tr data-slug="<?= e($p['slug']) ?>">
                    <td class="drag-cell"><span class="drag-handle" title="Drag to reorder">⠿</span></td>
                    <td>
                        <strong><?= e($p['title']) ?></strong><br>
                        <span class="muted">/page/<?= e($p['slug']) ?></span>
                    </td>
                    <td><?= (int) $blockCount($p) ?></td>
                    <td>
                        <span class="badge badge--<?= ($p['status'] ?? '') === 'published' ? 'green' : 'gray' ?>">
                            <?= e($p['status'] ?? 'draft') ?>
                        </span>
                    </td>
                    <td>
                        <?= !empty($p['is_protected']) ? '&#128274; Protected' : 'Public' ?>
                        <?= ($p['in_menu'] ?? true) ? '' : '<span class="muted"> · hidden from menu</span>' ?>
                    </td>
                    <td class="table__actions">
                        <a class="btn btn--small" href="<?= e($app->url('/page/' . $p['slug'])) ?>" target="_blank">View</a>
                        <a class="btn btn--small" href="<?= e($app->url('/admin/pages/' . $p['slug'] . '/edit')) ?>">Edit</a>
                        <form method="post" action="<?= e($app->url('/admin/pages/' . $p['slug'] . '/delete')) ?>"
                              class="inline-form" onsubmit="return confirm('Delete this page?');">
                            <?= $csrf->field() ?>
                            <button class="btn btn--small btn--danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$pages): ?>
        <p class="muted reorder-hint">No additional pages yet. Create one to get started.</p>
    <?php else: ?>
        <p class="muted reorder-hint">Drag the handle to set the order pages appear in the navigation menu.</p>
    <?php endif; ?>
</div>

<script src="<?= e($app->url('/assets/vendor/sortable/Sortable.min.js')) ?>"></script>
<script>
(function () {
    var tbody = document.getElementById('pagesSortable');
    if (!tbody || typeof Sortable === 'undefined') { return; }
    var url = tbody.getAttribute('data-reorder-url');
    var csrf = tbody.getAttribute('data-csrf');

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'row-ghost',
        onEnd: function () {
            var data = new FormData();
            data.append('_csrf', csrf);
            tbody.querySelectorAll('tr[data-slug]').forEach(function (tr) {
                data.append('order[]', tr.getAttribute('data-slug'));
            });
            fetch(url, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } })
                .catch(function () { /* best-effort; order will be correct on next save */ });
        }
    });
})();
</script>
