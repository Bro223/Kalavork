<div class="admin-header">
    <h1>👥 CRM</h1>
    <div class="admin-header-actions">
        <a href="/manage/crm/new" class="btn btn-primary">+ Add Contact</a>
        <a href="/manage/crm/export" class="btn btn-outline">📥 Export All</a>
        <button type="button" class="btn btn-outline" id="crm-import-toggle">📤 Import CSV</button>
    </div>
</div>

<!-- Import form (hidden by default) -->
<div id="crm-import-form" style="display:none; margin-bottom:1.5rem;">
    <form method="POST" action="/manage/crm/import" enctype="multipart/form-data" class="card">
        <?= CSRF::field() ?>
        <p style="margin-bottom:0.75rem;"><strong>Upload a CSV file</strong> — contacts are matched by email then phone to avoid duplicates.</p>
        <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
            <input type="file" name="csv_file" accept=".csv,text/csv" required style="flex:1; min-width:200px;">
            <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
        </div>
        <details style="margin-top:0.75rem; font-size:0.85rem; color:var(--text-muted);">
            <summary style="cursor:pointer;">CSV column format</summary>
            <pre style="margin-top:0.5rem; background:var(--color-light); padding:0.75rem; border-radius:6px; overflow-x:auto; font-size:0.8rem;">name, email, phone, status, address_street, address_city, address_postal, address_county, address_country, notes
Jaan Tamm, jaan@example.com, +3725551234, active_buyer, Pärnu mnt 10, Tallinn, 10145, Harjumaa, Estonia, Called on 2025-01-15 — prefers SMS</pre>
        </details>
    </form>
</div>

<script>
document.getElementById('crm-import-toggle').addEventListener('click', function() {
    var el = document.getElementById('crm-import-form');
    if (el.style.display === 'none') {
        el.style.display = 'block';
        this.textContent = '📤 Hide Import';
    } else {
        el.style.display = 'none';
        this.textContent = '📤 Import CSV';
    }
});
</script>

<?php if (empty($contacts)): ?>
<div class="empty-state">
    <p>No contacts yet. They will appear here when customers place orders or submit the contact form.</p>
</div>
<?php else: ?>

<form method="POST" action="/manage/crm/bulk" id="crm-bulk-form">
<?= CSRF::field() ?>

<!-- Bulk actions toolbar -->
<div class="crm-bulk-bar" id="crm-bulk-bar" style="display:none; margin-bottom:1rem; padding:0.75rem 1rem; background:var(--color-light); border-radius:8px; display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
    <span id="crm-selected-count" style="font-weight:600;">0 selected</span>
    <select name="bulk_action" id="bulk-action-select" style="padding:0.4rem 0.6rem; border:1px solid var(--border-color); border-radius:6px;">
        <option value="">— Bulk action —</option>
        <option value="delete">Delete selected</option>
        <option value="active_buyer">Mark as Active Buyer</option>
        <option value="contact">Mark as Contact</option>
        <option value="export">📥 Export selected</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary" id="crm-bulk-apply">Apply</button>
    <button type="button" class="btn btn-sm btn-outline" id="crm-clear-selection">Clear selection</button>
</div>

<div class="table-wrap">
<table class="admin-table" id="crm-table">
    <thead>
        <tr>
            <th style="width:40px;"><input type="checkbox" id="crm-select-all" title="Select all"></th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Orders</th>
            <th>Source</th>
            <th>Created</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($contacts as $contact): ?>
        <tr>
            <td><input type="checkbox" name="ids[]" value="<?= Template::e($contact['id']) ?>" class="crm-row-checkbox"></td>
            <td>
                <a href="/manage/crm/<?= Template::e($contact['id']) ?>"><strong><?= Template::e($contact['name'] ?? '—') ?></strong></a>
            </td>
            <td><?= Template::e($contact['email'] ?? '—') ?></td>
            <td><?= Template::e($contact['phone'] ?? '—') ?></td>
            <td>
                <span class="badge badge-<?= ($contact['status'] ?? 'contact') === 'active_buyer' ? 'success' : 'neutral' ?>">
                    <?= ($contact['status'] ?? 'contact') === 'active_buyer' ? 'Active Buyer' : 'Contact' ?>
                </span>
            </td>
            <td><?= count($contact['order_ids'] ?? []) ?></td>
            <td><small><?= Template::e(ucfirst($contact['source'] ?? 'manual')) ?></small></td>
            <td><small><?= Template::e(date('Y-m-d', strtotime($contact['created_at'] ?? ''))) ?></small></td>
            <td>
                <a href="/manage/crm/<?= Template::e($contact['id']) ?>/edit" class="btn btn-sm btn-outline">Edit</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</form>

<script>
(function() {
    var selectAll = document.getElementById('crm-select-all');
    var checkboxes = document.querySelectorAll('.crm-row-checkbox');
    var bulkBar = document.getElementById('crm-bulk-bar');
    var countEl = document.getElementById('crm-selected-count');
    var clearBtn = document.getElementById('crm-clear-selection');
    var bulkSelect = document.getElementById('bulk-action-select');
    var bulkApply = document.getElementById('crm-bulk-apply');

    function updateBulkBar() {
        var checked = document.querySelectorAll('.crm-row-checkbox:checked');
        var count = checked.length;
        if (count > 0) {
            bulkBar.style.display = 'flex';
            countEl.textContent = count + ' selected';
        } else {
            bulkBar.style.display = 'none';
            bulkSelect.value = '';
        }
    }

    // Select-all toggle
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
        updateBulkBar();
    });

    // Individual checkbox change
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            // Sync select-all state
            var allChecked = document.querySelectorAll('.crm-row-checkbox:checked').length === checkboxes.length;
            selectAll.checked = allChecked;
            selectAll.indeterminate = !allChecked && document.querySelectorAll('.crm-row-checkbox:checked').length > 0;
            updateBulkBar();
        });
    });

    // Clear selection
    clearBtn.addEventListener('click', function() {
        checkboxes.forEach(function(cb) { cb.checked = false; });
        selectAll.checked = false;
        selectAll.indeterminate = false;
        updateBulkBar();
    });

    // Confirm before delete
    document.getElementById('crm-bulk-form').addEventListener('submit', function(e) {
        if (bulkSelect.value === 'delete') {
            var count = document.querySelectorAll('.crm-row-checkbox:checked').length;
            if (!confirm('Delete ' + count + ' contact(s)? This cannot be undone.')) {
                e.preventDefault();
            }
        }
        if (bulkSelect.value === '') {
            e.preventDefault();
            alert('Please select a bulk action.');
        }
    });
})();
</script>
<?php endif; ?>
