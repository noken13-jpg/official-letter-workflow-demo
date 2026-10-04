<?php ob_start(); ?>
<h1>New letter</h1>
<div class="card">
    <form method="post" action="/letters">
        <?= \App\Csrf::field() ?>
        <label>Subject
            <input type="text" name="subject" maxlength="200" required
                   placeholder="e.g. Assistance Request Reply - Warga Contoh 003">
        </label>
        <label>Recipient
            <input type="text" name="recipient" maxlength="160" required
                   placeholder="e.g. Warga Contoh 003">
        </label>
        <label>Body
            <textarea name="body" rows="8" required
                      placeholder="Letter content (fictitious demo data only)."></textarea>
        </label>
        <button class="btn" type="submit">Create draft</button>
        <a class="btn btn-ghost" href="/letters">Cancel</a>
    </form>
</div>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'New letter', 'content' => $content]);
