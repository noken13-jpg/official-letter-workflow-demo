<?php ob_start(); ?>
<div class="card">
    <h1><?= e($title ?? 'Error') ?></h1>
    <p class="muted"><?= e($message ?? 'Something went wrong.') ?></p>
    <a class="btn" href="/">Back to dashboard</a>
</div>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Error', 'content' => $content]);
