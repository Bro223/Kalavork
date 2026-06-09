<h1>🏳 Languages</h1>

<div class="admin-card">
    <h2>Active Languages</h2>
    <table class="admin-table">
        <thead><tr><th>Code</th><th>Name</th><th>Default</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($languages['available'] as $code): ?>
            <tr>
                <td><strong><?= strtoupper($code) ?></strong></td>
                <td><?= Template::e($languages['names'][$code] ?? $code) ?></td>
                <td><?= $code === ($languages['default'] ?? DEFAULT_LANGUAGE) ? '✅' : '' ?></td>
                <td>
                    <?php if ($code !== DEFAULT_LANGUAGE): ?>
                    <form method="post" action="/manage/languages/remove" class="inline-form" onsubmit="return confirm('Remove language <?= strtoupper($code) ?>?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="code" value="<?= $code ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                    </form>
                    <?php else: ?>
                    <span style="color:var(--text-muted);font-size:0.8rem">Cannot remove default</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-card">
    <h2>Add Language</h2>
    <form method="post" action="/manage/languages/add" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <div style="display:flex;gap:1rem;align-items:end">
            <div class="form-group" style="width:100px">
                <label>Code (2-letter)</label>
                <input type="text" name="code" maxlength="2" required placeholder="lv">
            </div>
            <div class="form-group" style="flex:1">
                <label>Name</label>
                <input type="text" name="name" required placeholder="Latviešu">
            </div>
            <button type="submit" class="btn btn-primary">Add Language</button>
        </div>
    </form>
</div>

<div class="admin-card">
    <h2>Translation Coverage</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Language</th>
                <th>Translated Keys</th>
                <th>Missing / Empty</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($languages['available'] as $code):
                $total = count($translations);
                $filled = 0;
                foreach ($translations as $key => $vals) {
                    if (!empty($vals[$code])) $filled++;
                }
                $missing = $total - $filled;
            ?>
            <tr>
                <td><strong><?= strtoupper($code) ?></strong></td>
                <td style="color:var(--success)"><?= $filled ?> / <?= $total ?></td>
                <td style="color:<?= $missing > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>"><?= $missing ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
