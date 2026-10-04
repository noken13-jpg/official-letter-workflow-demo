<?php ob_start(); ?>
<div class="card auth">
    <h1>Sign in</h1>
    <p class="muted">Demo accounts (password <code>Password123!</code>):
        <code>admin</code>, <code>verifikator</code>, <code>staff</code>.</p>
    <form method="post" action="/login">
        <?= \App\Csrf::field() ?>
        <label>Username
            <input type="text" name="username" autocomplete="username" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn" type="submit">Sign in</button>
    </form>
</div>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Sign in', 'content' => $content]);
