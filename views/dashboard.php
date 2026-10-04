<?php /** @var array $letters @var array $counts */ ob_start(); ?>
<h1>Dashboard</h1>
<div class="stats">
    <?php foreach (['draft','submitted','verified','approved','rejected'] as $s):
        [$label, $cls] = status_badge($s); ?>
        <div class="stat">
            <span class="badge <?= e($cls) ?>"><?= e($label) ?></span>
            <strong><?= (int) ($counts[$s] ?? 0) ?></strong>
        </div>
    <?php endforeach; ?>
</div>

<h2>Recent letters</h2>
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
echo view('layout', ['title' => $title ?? 'Dashboard', 'content' => $content]);
