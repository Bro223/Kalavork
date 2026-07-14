<div class="admin-header">
    <h1>📦 Products</h1>
    <a href="/manage/product/new" class="btn btn-primary">+ New Product</a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name (ET)</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Visible</th>
                <th>Set Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
            <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted)">No products yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <?php if (!empty($p['image'])): ?>
                    <img src="/assets/img/<?= Template::e($p['image']) ?>" style="width:50px;height:50px;object-fit:contain;background:#f0f4f8;border-radius:4px">
                    <?php endif; ?>
                </td>
                <td><strong><?= Template::e($p['name']['et'] ?? $p['id']) ?></strong></td>
                <td><?= Template::e($p['category'] ?? '') ?></td>
                <td>€<?= number_format($p['price'] ?? 0, 2) ?></td>
                <td>
                    <?php $s = $p['stock'] ?? 0; ?>
                    <span style="font-weight:600;color:<?= $s <= 0 ? 'var(--danger)' : ($s <= 5 ? 'var(--warning)' : 'var(--success)') ?>">
                        <?= $s ?>
                    </span>
                </td>
                <td><?= !empty($p['visible']) ? '✅' : '❌' ?></td>
                <td>
                    <form method="post" action="/manage/product/quick-image" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <select name="image" onchange="this.form.submit()" style="max-width:120px;font-size:0.75rem;padding:2px 4px">
                            <option value="">— No image —</option>
                            <?php foreach ($asset_images ?? [] as $img): ?>
                            <option value="<?= Template::e($img) ?>" <?= ($p['image'] ?? '') === $img ? 'selected' : '' ?>><?= Template::e($img) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <a href="/manage/product/<?= $p['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <form method="post" action="/manage/product/delete" class="inline-form" onsubmit="return confirm('Delete this product?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
