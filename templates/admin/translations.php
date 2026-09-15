<?php
$formatTranslationValue = function($value): string {
    if (is_array($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return (string)($value ?? '');
};
?>

<div class="admin-header">
    <h1>🌐 Translations</h1>
    <button class="btn btn-primary" onclick="document.getElementById('add-form').style.display='block'; this.style.display='none'">+ New Translation Key</button>
</div>

<!-- Add new key form -->
<div id="add-form" style="display:none" class="admin-card">
    <h2>Add Translation Key</h2>
    <form method="post" action="/manage/translations/save" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <div class="form-group">
            <label>Key</label>
            <input type="text" name="key" required placeholder="e.g. nav_about">
        </div>
        <?php foreach ($languages['available'] as $l): ?>
        <div class="form-group">
            <label>Value (<?= strtoupper($l) ?>)</label>
            <input type="text" name="value_<?= $l ?>">
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Add Key</button>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('add-form').style.display='none'; document.querySelector('.admin-header .btn-primary').style.display='inline-block'">Cancel</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table translation-table">
        <thead>
            <tr>
                <th>Key</th>
                <?php foreach ($languages['available'] as $l): ?>
                <th><?= strtoupper($l) ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($translations)): ?>
            <tr><td colspan="<?= count($languages['available']) + 2 ?>" style="text-align:center;padding:2rem;color:var(--text-muted)">No translations yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($translations as $key => $vals): ?>
            <tr>
                <td><code style="font-size:0.8rem;background:#f0f4f8;padding:0.15rem 0.4rem;border-radius:3px"><?= Template::e($key) ?></code></td>
                <?php foreach ($languages['available'] as $l): ?>
                <td class="translation-cell">
                    <form method="post" action="/manage/translations/save" class="translation-edit-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="key" value="<?= Template::e($key) ?>">
                        <?php foreach ($languages['available'] as $l2): ?>
                        <input type="hidden" name="value_<?= $l2 ?>" value="<?= Template::e($formatTranslationValue($vals[$l2] ?? '')) ?>">
                        <?php endforeach; ?>
                        <textarea name="value_<?= $l ?>" rows="4"><?= Template::e($formatTranslationValue($vals[$l] ?? '')) ?></textarea>
                        <button type="submit" class="btn btn-sm btn-outline">Save</button>
                    </form>
                </td>
                <?php endforeach; ?>
                <td class="translation-actions">
                    <form method="post" action="/manage/translations/delete" class="inline-form" onsubmit="return confirm('Delete key \'<?= Template::e($key) ?>\'?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="key" value="<?= Template::e($key) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
