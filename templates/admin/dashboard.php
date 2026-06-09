<h1>📊 Dashboard</h1>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $total_products ?></div>
        <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $total_stock ?></div>
        <div class="stat-label">Total Stock</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-value"><?= count($low_stock) ?></div>
        <div class="stat-label">Low Stock (≤5)</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-value"><?= count($out_of_stock) ?></div>
        <div class="stat-label">Out of Stock</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $total_orders ?></div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value"><?= $new_orders ?></div>
        <div class="stat-label">New Orders</div>
    </div>
</div>

<?php if (!empty($low_stock)): ?>
<div class="admin-card">
    <h2>⚠ Low Stock Alerts</h2>
    <table class="admin-table">
        <thead><tr><th>Product</th><th>Stock</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($low_stock as $p): ?>
        <tr>
            <td><?= Template::e($p['name']['et'] ?? $p['id']) ?></td>
            <td><strong style="color:var(--warning)"><?= $p['stock'] ?></strong></td>
            <td><a href="/manage/product/<?= $p['id'] ?>" class="btn btn-sm btn-outline">Edit</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($recent_orders)): ?>
<div class="admin-card">
    <h2>🧾 Recent Orders</h2>
    <table class="admin-table">
        <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent_orders as $o): ?>
        <tr>
            <td><strong><?= Template::e($o['id']) ?></strong></td>
            <td><?= Template::e($o['name'] ?? '') ?></td>
            <td>€<?= number_format($o['total'] ?? 0, 2) ?></td>
            <td><span class="badge badge-<?= $o['status'] ?? 'new' ?>"><?= $o['status'] ?? 'new' ?></span></td>
            <td><?= Template::date($o['created_at'] ?? '', 'd.m.Y H:i') ?></td>
            <td><a href="/manage/order/<?= $o['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
