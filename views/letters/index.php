<?php /** @var array $letters */ ob_start(); ?>
<div class="row-between">
    <h1>Letters</h1>
    <?php if (\App\Auth::can('letter.create')): ?>
        <a class="btn" href="/letters/create">New letter</a>
    <?php endif; ?>
</div>
<table class="table">
    <thead><tr><th>Number</th><th>Subject</th><th>Recipient</th><th>Status</th><th>Author</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($letters as $l): [$label, $cls] = status_badge($l['status']); ?>
        <tr>
            <td><?= e($l['number'] ?? '—') ?></td>
            <td><?= e($l['subject']) ?></td>
            <td><?= e($l['recipient']) ?></td>
            <td><span class="badge <?= e($cls) ?>"><?= e($label) ?></span></td>
            <td><?= e($l['author']) ?></td>
            <td><a href="/letters/show?id=<?= (int) $l['id'] ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$letters): ?><tr><td colspan="6" class="muted">No letters yet.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Letters', 'content' => $content]);
