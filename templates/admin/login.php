<div class="login-page">
    <div class="login-box">
        <h1><?= SITE_NAME ?> Admin</h1>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= Template::e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/manage/login">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</div>
