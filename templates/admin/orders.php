<h1>🧾 Orders</h1>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
            <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted)">No orders yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><strong><?= Template::e($o['id']) ?></strong></td>
                <td><?= Template::e($o['name'] ?? '') ?></td>
                <td><a href="mailto:<?= Template::e($o['email'] ?? '') ?>"><?= Template::e($o['email'] ?? '') ?></a></td>
                <td><?= Template::e($o['phone'] ?? '') ?></td>
                <td><?= count($o['items'] ?? []) ?></td>
                <td>€<?= number_format($o['total'] ?? 0, 2) ?></td>
                <td><span class="badge badge-<?= $o['status'] ?? 'new' ?>"><?= $o['status'] ?? 'new' ?></span></td>
                <td><?= Template::date($o['created_at'] ?? '', 'd.m.Y H:i') ?></td>
                <td>
                    <a href="/manage/order/<?= $o['id'] ?>" class="btn btn-sm btn-outline">View</a>
                    <form method="post" action="/manage/order/delete" class="inline-form" onsubmit="return confirm('Delete this order?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
