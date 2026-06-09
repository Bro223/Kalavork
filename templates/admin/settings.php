<h1>⚙ Settings</h1>

<div class="admin-card">
    <h2>Change Admin Password</h2>
    <form method="post" action="/manage/settings" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>

        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
</div>

<div class="admin-card">
    <h2>Site Information</h2>
    <table class="admin-table">
        <tr><td><strong>Site Name</strong></td><td><?= Template::e($site_name) ?></td></tr>
        <tr><td><strong>Site Email</strong></td><td><?= Template::e($site_email) ?></td></tr>
        <tr><td><strong>Site Phone</strong></td><td><?= Template::e($site_phone) ?></td></tr>
    </table>
    <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.5rem">
        Edit these values in <code>config.php</code> file.
    </p>
</div>

<div class="admin-card">
    <h2>System</h2>
    <table class="admin-table">
        <tr><td><strong>PHP Version</strong></td><td><?= phpversion() ?></td></tr>
        <tr><td><strong>Timezone</strong></td><td><?= date_default_timezone_get() ?></td></tr>
        <tr><td><strong>Data Directory</strong></td><td><code><?= Template::e(DATA_PATH) ?></code></td></tr>
        <tr><td><strong>Assets Directory</strong></td><td><code><?= Template::e(ASSETS_PATH) ?></code></td></tr>
    </table>
</div>
