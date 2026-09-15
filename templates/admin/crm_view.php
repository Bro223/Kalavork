<div class="admin-header">
    <h1><?= Template::e($contact['name'] ?? 'Unnamed') ?></h1>
    <div>
        <a href="/manage/crm/<?= Template::e($contact['id']) ?>/edit" class="btn btn-outline">Edit</a>
        <a href="/manage/crm" class="btn btn-outline">← All Contacts</a>
    </div>
</div>

<div class="card" style="margin-bottom:2rem">
    <h3>Contact Info</h3>
    <table class="admin-table" style="max-width:500px">
        <tr><th style="width:100px">Name</th><td><?= Template::e($contact['name'] ?? '—') ?></td></tr>
        <tr><th>Email</th><td><a href="mailto:<?= Template::e($contact['email'] ?? '') ?>"><?= Template::e($contact['email'] ?? '—') ?></a></td></tr>
        <tr><th>Phone</th><td><a href="tel:<?= Template::e($contact['phone'] ?? '') ?>"><?= Template::e($contact['phone'] ?? '—') ?></a></td></tr>
        <tr><th>Status</th><td><?= ($contact['status'] ?? 'contact') === 'active_buyer' ? '🟢 Active Buyer' : '🟡 Contact' ?></td></tr>
        <tr><th>Source</th><td><?= Template::e(ucfirst($contact['source'] ?? 'manual')) ?></td></tr>
        <tr><th>Created</th><td><?= Template::e($contact['created_at'] ?? '—') ?></td></tr>
        <tr><th>Updated</th><td><?= Template::e($contact['updated_at'] ?? '—') ?></td></tr>
        <?php if (!empty($contact['address']['street'])): ?>
        <tr><th>Address</th><td>
            <?= Template::e($contact['address']['street'] ?? '') ?>,
            <?= Template::e($contact['address']['city'] ?? '') ?>
            <?= Template::e($contact['address']['postal'] ?? '') ?>,
            <?= Template::e($contact['address']['country'] ?? '') ?>
        </td></tr>
        <?php endif; ?>
    </table>

    <?php if (!empty($contact['notes'])): ?>
    <h4 style="margin-top:1rem">Admin Notes</h4>
    <p style="white-space:pre-wrap"><?= Template::e($contact['notes']) ?></p>
    <?php endif; ?>
</div>

<?php if (!empty($contact_orders)): ?>
<div class="card" style="margin-bottom:2rem">
    <h3>📦 Order History (<?= count($contact_orders) ?>)</h3>
    <div class="table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contact_orders as $o): ?>
            <tr>
                <td><a href="/manage/order/<?= Template::e($o['id']) ?>"><strong>#<?= Template::e($o['id']) ?></strong></a></td>
                <td><small><?= Template::e(date('Y-m-d', strtotime($o['created_at'] ?? ''))) ?></small></td>
                <td><?= count($o['items'] ?? []) ?> items</td>
                <td><?= number_format($o['total'] ?? 0, 2) ?>€</td>
                <td><span class="badge badge-<?= $o['status'] === 'completed' ? 'success' : ($o['status'] === 'cancelled' ? 'danger' : 'neutral') ?>"><?= Template::e($o['status'] ?? 'new') ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($contact['message_history'])): ?>
<div class="card" style="margin-bottom:2rem">
    <h3>💬 Message History (<?= count($contact['message_history']) ?>)</h3>
    <?php foreach (array_reverse($contact['message_history']) as $msg): ?>
    <div style="border-left:3px solid var(--accent, #2e7d32); margin-bottom:1rem; padding-left:1rem">
        <small style="color:#888"><?= Template::e($msg['date'] ?? '') ?> — <?= Template::e($msg['source'] ?? '') ?></small>
        <?php if (!empty($msg['order_id'])): ?>
            <small> (<a href="/manage/order/<?= Template::e($msg['order_id']) ?>">#<?= Template::e($msg['order_id']) ?></a>)</small>
        <?php endif; ?>
        <p style="white-space:pre-wrap; margin-top:0.3rem"><?= Template::e($msg['message'] ?? '') ?></p>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
