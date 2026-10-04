<?php /** @var array $letter @var array $events */ ob_start();
$status = $letter['status'];
$id = (int) $letter['id'];
[$label, $cls] = status_badge($status);
$csrf = \App\Csrf::field();
// Which transition is available from the current status.
$next = ['draft' => 'submit', 'submitted' => 'verify', 'verified' => 'approve'][$status] ?? null;
$nextPerm = $next ? \App\Letters::permissionFor($next) : null;
?>
<div class="row-between">
    <h1>Letter <?= e($letter['number'] ?? '(draft)') ?></h1>
    <span class="badge <?= e($cls) ?>"><?= e($label) ?></span>
</div>

<div class="card">
    <table class="meta">
        <tr><th>Subject</th><td><?= e($letter['subject']) ?></td></tr>
        <tr><th>Recipient</th><td><?= e($letter['recipient']) ?></td></tr>
        <tr><th>Author</th><td><?= e($letter['author']) ?></td></tr>
        <tr><th>Created</th><td><?= e($letter['created_at']) ?></td></tr>
    </table>
    <div class="letter-body"><?= nl2br(e($letter['body'])) ?></div>
</div>

<div class="actions">
    <?php if (\App\Auth::can('letter.pdf')): ?>
        <a class="btn" href="/letters/pdf?id=<?= $id ?>" target="_blank" rel="noopener">Export PDF</a>
    <?php endif; ?>

    <?php if ($next && \App\Auth::can($nextPerm)): ?>
        <form method="post" action="/letters/<?= e($next) ?>" class="inline">
            <?= $csrf ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="text" name="note" placeholder="Optional note" maxlength="500">
            <button class="btn btn-primary" type="submit"><?= ucfirst($next) ?></button>
        </form>
    <?php endif; ?>

    <?php if (in_array($status, ['submitted','verified'], true) && \App\Auth::can('letter.reject')): ?>
        <form method="post" action="/letters/reject" class="inline">
            <?= $csrf ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="text" name="note" placeholder="Reason for rejection" maxlength="500">
            <button class="btn btn-danger" type="submit">Reject</button>
        </form>
    <?php endif; ?>
</div>

<?php if (\App\Auth::can('audit.view')): ?>
<h2>Audit trail</h2>
<table class="table">
    <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>From → To</th><th>Note</th></tr></thead>
    <tbody>
    <?php foreach ($events as $ev): ?>
        <tr>
            <td><?= e($ev['created_at']) ?></td>
            <td><?= e($ev['actor']) ?></td>
            <td><?= e($ev['action']) ?></td>
            <td><?= e(($ev['from_status'] ?? '—') . ' → ' . ($ev['to_status'] ?? '—')) ?></td>
            <td><?= e($ev['note'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<p><a href="/letters">&larr; Back to letters</a></p>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Letter', 'content' => $content]);
