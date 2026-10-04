<?php /** @var string $title @var string $content */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Official Letter Workflow') ?> · OLW Demo</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="/">OLW&nbsp;Demo</a>
    <nav>
        <?php if (\App\Auth::check()): ?>
            <a href="/">Dashboard</a>
            <a href="/letters">Letters</a>
            <?php if (\App\Auth::can('letter.create')): ?><a href="/letters/create">New letter</a><?php endif; ?>
            <span class="who"><?= e(\App\Auth::user()['name']) ?> (<?= e(\App\Auth::role()) ?>)</span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Csrf::field() ?>
                <button class="link-btn" type="submit">Sign out</button>
            </form>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
    <?php foreach (take_flashes() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<footer class="footer">
    Demo project — all data is fictitious. Built with plain PHP, PDO &amp; Dompdf.
</footer>
</body>
</html>
