<div class="admin-header">
    <h1>Order #<?= Template::e($order['id'] ?? '') ?></h1>
    <a href="/manage/orders" class="btn btn-outline">← Back to Orders</a>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem">
    <div class="admin-card">
        <h2>Customer Info</h2>
        <table class="admin-table">
            <tr><td><strong>Name</strong></td><td><?= Template::e($order['name'] ?? '') ?></td></tr>
            <tr><td><strong>Email</strong></td><td><a href="mailto:<?= Template::e($order['email'] ?? '') ?>"><?= Template::e($order['email'] ?? '') ?></a></td></tr>
            <tr><td><strong>Phone</strong></td><td><a href="tel:<?= Template::e($order['phone'] ?? '') ?>"><?= Template::e($order['phone'] ?? '') ?></a></td></tr>
            <tr><td><strong>Fulfillment</strong></td><td><?= Template::e(ucfirst($order['fulfillment'] ?? 'delivery')) ?></td></tr>
            <?php if (!empty($order['address'])): ?>
            <tr><td><strong>Address</strong></td>
                <td>
                    <?= Template::e($order['address']['street'] ?? '') ?>,
                    <?= Template::e($order['address']['city'] ?? '') ?>
                    <?= Template::e($order['address']['postal'] ?? '') ?>,
                    <?= Template::e($order['address']['county'] ?? '') ?>,
                    <?= Template::e($order['address']['country'] ?? 'Estonia') ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($order['notes'])): ?>
            <tr><td><strong>Notes</strong></td><td><?= nl2br(Template::e($order['notes'])) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="admin-card">
        <h2>Status</h2>
        <p><span class="badge badge-<?= $order['status'] ?? 'new' ?>" style="font-size:1rem"><?= $order['status'] ?? 'new' ?></span></p>
        <p style="margin-top:0.5rem">Created: <?= Template::date($order['created_at'] ?? '', 'd.m.Y H:i') ?></p>
        <p>Updated: <?= Template::date($order['updated_at'] ?? '', 'd.m.Y H:i') ?></p>
        <p>IP: <?= Template::e($order['ip'] ?? '') ?></p>

        <form method="post" action="/manage/order/status" style="margin-top:1rem">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <div style="display:flex;gap:0.5rem;align-items:center">
                <select name="status" class="form-group" style="width:auto;margin-bottom:0">
                    <option value="new" <?= ($order['status'] ?? '') === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="processing" <?= ($order['status'] ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= ($order['status'] ?? '') === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="completed" <?= ($order['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= ($order['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
            </div>
        </form>
    </div>
</div>

<div class="admin-card">
    <h2>Order Items</h2>
    <table class="admin-table">
        <thead>
            <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] ?? [] as $item): ?>
            <tr>
                <td>
                    <?= Template::e($item['name']['et'] ?? ($item['name']['en'] ?? $item['product_id'] ?? '')) ?>
                    <br><small style="color:var(--text-muted)"><?= Template::e($item['product_id'] ?? '') ?></small>
                </td>
                <td><?= $item['quantity'] ?? 1 ?></td>
                <td>€<?= number_format($item['unit_price'] ?? 0, 2) ?></td>
                <td><strong>€<?= number_format($item['line_total'] ?? 0, 2) ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight:700;border-top:2px solid var(--border)">
                <td colspan="3" style="text-align:right">Total:</td>
                <td>€<?= number_format($order['total'] ?? 0, 2) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<style>
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
