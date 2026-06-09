<h1>🚚 Shipping Ways</h1>

<?php if (!empty($_SESSION['admin_success'])): ?>
<div class="alert alert-success"><?= Template::e($_SESSION['admin_success']) ?></div>
<?php unset($_SESSION['admin_success']); endif; ?>

<?php if (!empty($_SESSION['admin_error'])): ?>
<div class="alert alert-danger"><?= Template::e($_SESSION['admin_error']) ?></div>
<?php unset($_SESSION['admin_error']); endif; ?>

<!-- Add New Shipping Way -->
<div class="admin-card">
    <h2>Add Shipping Way</h2>
    <form method="post" action="/manage/shipping-ways" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
            <label>ID (unique identifier)</label>
            <input type="text" name="id" required placeholder="e.g. dhl, pickup-store" pattern="^[a-z0-9_-]+$" title="Only lowercase letters, numbers, hyphens, and underscores">
        </div>

        <div class="form-group">
            <label>Requires Address?</label>
            <input type="checkbox" name="requires_address" value="1">
            <small style="color:var(--text-muted);display:block;margin-top:0.25rem">Check if this shipping method requires an address (like delivery). Unchecked for pickup/in-store.</small>
        </div>

        <h3 style="margin-top:1.5rem;margin-bottom:1rem">Labels</h3>
        <?php foreach ($languages['available'] as $lang): ?>
        <div class="form-group">
            <label>Label (<?= strtoupper($lang) ?>)</label>
            <input type="text" name="label_<?= $lang ?>" placeholder="e.g. DHL Parcel" required>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary" style="margin-top:1rem">+ Add Shipping Way</button>
    </form>
</div>

<!-- Existing Shipping Ways -->
<?php if (empty($shipping_ways)): ?>
<div class="admin-card">
    <p style="text-align:center;color:var(--text-muted)">No shipping ways configured yet.</p>
</div>
<?php else: ?>
<div class="admin-card">
    <h2>Configured Shipping Ways</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Requires Address</th>
                <?php foreach ($languages['available'] as $lang): ?>
                <th><?= strtoupper($lang) ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shipping_ways as $way): ?>
            <tr>
                <td><code style="font-size:0.9rem;background:#f0f4f8;padding:0.2rem 0.4rem;border-radius:3px"><?= Template::e($way['id']) ?></code></td>
                <td>
                    <form method="post" action="/manage/shipping-ways" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= Template::e($way['id']) ?>">
                        <button type="submit" class="btn btn-sm" style="background:<?= ($way['enabled'] ?? true) ? '#10b981' : '#ef4444' ?>;color:white;border:none;cursor:pointer">
                            <?= ($way['enabled'] ?? true) ? '✓ Active' : '✗ Disabled' ?>
                        </button>
                    </form>
                </td>
                <td><?= ($way['requires_address'] ?? false) ? '✓ Yes' : '✗ No' ?></td>
                <?php foreach ($languages['available'] as $lang): ?>
                <td><?= Template::e($way['label'][$lang] ?? '-') ?></td>
                <?php endforeach; ?>
                <td>
                    <button class="btn btn-sm btn-outline" onclick="editShippingWay(<?= htmlspecialchars(json_encode($way, JSON_HEX_APOS), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($languages['available'], JSON_HEX_APOS), ENT_QUOTES, 'UTF-8') ?>)">Edit</button>
                    <form method="post" action="/manage/shipping-ways" class="inline-form" onsubmit="return confirm('Delete <?= Template::e($way['id']) ?>?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= Template::e($way['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Edit Modal -->
<div id="editModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1000;padding:2rem;overflow-y:auto">
    <div class="admin-card" style="max-width:600px;margin:0 auto">
        <h2>Edit Shipping Way</h2>
        <form method="post" action="/manage/shipping-ways" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId" value="">

            <div class="form-group">
                <label>ID</label>
                <input type="text" id="editIdDisplay" disabled style="background:#f5f5f5">
            </div>

            <div class="form-group">
                <label>Requires Address?</label>
                <input type="checkbox" name="requires_address" id="editRequiresAddress" value="1">
            </div>

            <h3 style="margin-top:1.5rem;margin-bottom:1rem">Labels</h3>
            <div id="editLabels"></div>

            <div style="margin-top:1.5rem;display:flex;gap:0.5rem">
                <button type="submit" class="btn btn-primary">💾 Save</button>
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.inline-form {
    display: inline-flex;
    gap: 0.5rem;
}

.inline-form button {
    margin-left: 0;
}

/* Custom Dropdown Styling */
select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.25rem;
    padding-right: 2.5rem;
    padding-left: 0.75rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    font-family: inherit;
    background-color: white;
    color: #1f2937;
    cursor: pointer;
    transition: all 0.2s ease;
}

select:hover {
    border-color: #9ca3af;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

select option {
    padding: 0.5rem;
    background-color: white;
    color: #1f2937;
}

select option:checked {
    background: linear-gradient(#3b82f6, #3b82f6);
    background-color: #3b82f6;
    color: white;
}

select:disabled {
    background-color: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
}
</style>

<script>
function editShippingWay(way, languages) {
    document.getElementById('editId').value = way.id;
    document.getElementById('editIdDisplay').value = way.id;
    document.getElementById('editRequiresAddress').checked = way.requires_address || false;

    const labelsContainer = document.getElementById('editLabels');
    labelsContainer.innerHTML = '';
    
    languages.forEach(lang => {
        const label = way.label[lang] || '';
        labelsContainer.innerHTML += `
            <div class="form-group">
                <label>Label (${lang.toUpperCase()})</label>
                <input type="text" name="label_${lang}" value="${escapeHtml(label)}" required>
            </div>
        `;
    });

    document.getElementById('editModal').style.display = 'flex';
    document.getElementById('editModal').style.alignItems = 'flex-start';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal when clicking outside
document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>
