<h1>📈 Sales Statistics</h1>

<div class="stat-grid">
    <div class="stat-card success">
        <div class="stat-value">€<?= number_format($total_revenue ?? 0, 2) ?></div>
        <div class="stat-label">Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)($total_units ?? 0) ?></div>
        <div class="stat-label">Units Sold</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)($active_orders ?? 0) ?></div>
        <div class="stat-label">Orders Counted</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-value"><?= (int)($cancelled_orders ?? 0) ?></div>
        <div class="stat-label">Cancelled Orders Ignored</div>
    </div>
</div>

<div class="admin-card">
    <h2>Product Sales</h2>
    <table class="admin-table stats-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Product ID</th>
                <th>Units Sold</th>
                <th>Revenue</th>
                <th>Orders</th>
                <th>Average Unit Price</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($product_stats)): ?>
            <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">No sales yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($product_stats as $row): ?>
            <?php $quantity = max(1, (int)($row['quantity'] ?? 0)); ?>
            <tr>
                <td><strong><?= Template::e($row['name'] ?? '') ?></strong></td>
                <td><code><?= Template::e($row['product_id'] ?? '') ?></code></td>
                <td><?= (int)($row['quantity'] ?? 0) ?></td>
                <td>€<?= number_format($row['revenue'] ?? 0, 2) ?></td>
                <td><?= (int)($row['orders'] ?? 0) ?></td>
                <td>€<?= number_format(($row['revenue'] ?? 0) / $quantity, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
