<div class="admin-header">
    <h1><?= $product ? 'Edit Product' : 'New Product' ?></h1>
    <a href="/manage/products" class="btn btn-outline">← Back to Products</a>
</div>

<div class="admin-card">
    <form method="post" action="/manage/product/save" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="product_id" value="<?= Template::e($product_id ?? '') ?>">

        <!-- Multilingual Name -->
        <?php foreach ($languages['available'] as $l): ?>
        <div class="form-group">
            <label>Name (<?= strtoupper($l) ?>)</label>
            <input type="text" name="name_<?= $l ?>" value="<?= Template::e($product['name'][$l] ?? '') ?>">
        </div>
        <?php endforeach; ?>

        <!-- Multilingual Description -->
        <?php foreach ($languages['available'] as $l): ?>
        <div class="form-group">
            <label>Description (<?= strtoupper($l) ?>)</label>
            <textarea name="description_<?= $l ?>" rows="3"><?= Template::e($product['description'][$l] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>

        <!-- Price & Stock -->
        <div class="admin-grid admin-grid-3">
            <div class="form-group">
                <label>Base Price (€)</label>
                <input type="number" name="price" step="0.01" value="<?= $product['price'] ?? '' ?>" required>
            </div>
            <div class="form-group">
                <label>Stock Qty</label>
                <input type="number" name="stock" value="<?= $product['stock'] ?? 0 ?>">
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" value="<?= Template::e($product['category'] ?? '') ?>" placeholder="e.g. hiina, soome">
            </div>
        </div>

        <!-- Image -->
        <h3 style="margin:1.5rem 0 0.5rem">Image</h3>
        <div class="product-image-editor">
            <div class="image-edit-panel">
                <h4>Product Image</h4>
                <?php if (!empty($product['image'])): ?>
                <div class="image-preview-row">
                    <img src="/assets/img/<?= Template::e($product['image']) ?>" alt="">
                    <strong><?= Template::e($product['image']) ?></strong>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Choose Existing Image</label>
                    <select name="image">
                        <option value="">No image</option>
                        <?php foreach ($asset_images ?? [] as $image): ?>
                        <option value="<?= Template::e($image) ?>" <?= ($product['image'] ?? '') === $image ? 'selected' : '' ?>>
                            <?= Template::e($image) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Upload New Image</label>
                    <input type="file" name="image_upload" accept="image/*">
                </div>
                <?php if (!empty($product['image'])): ?>
                <label><input type="checkbox" name="delete_old_image" value="1"> Delete old image file when upload succeeds and it is unused</label>
                <label><input type="checkbox" name="remove_image" value="1"> Remove image from product</label>
                <label><input type="checkbox" name="delete_removed_image" value="1"> Delete removed image file if unused</label>
                <?php endif; ?>
            </div>
        </div>

        <!-- Specs -->
        <h3 style="margin:1rem 0 0.5rem">Specifications</h3>
        <div class="admin-grid admin-grid-4">
            <div class="form-group">
                <label>Length</label>
                <input type="text" name="spec_length" value="<?= Template::e($product['specs']['length'] ?? '') ?>" placeholder="e.g. 80m">
            </div>
            <div class="form-group">
                <label>Height</label>
                <input type="text" name="spec_height" value="<?= Template::e($product['specs']['height'] ?? '') ?>" placeholder="e.g. 7m">
            </div>
            <div class="form-group">
                <label>Mesh Size</label>
                <input type="text" name="spec_mesh" value="<?= Template::e($product['specs']['mesh'] ?? '') ?>" placeholder="e.g. 70mm">
            </div>
            <div class="form-group">
                <label>Line Thickness</label>
                <input type="text" name="spec_line" value="<?= Template::e($product['specs']['line'] ?? '') ?>" placeholder="e.g. 0.18mm">
            </div>
        </div>

        <!-- Tier Pricing -->
        <h3 style="margin:1.5rem 0 0.5rem">Tier / Bulk Pricing</h3>
        <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.5rem">
            Leave empty for no tier pricing. Higher quantities = lower per-unit price.
        </p>
        <div id="tier-pricing-container">
            <?php $tiers = $product['tier_pricing'] ?? []; ?>
            <?php if (empty($tiers)): $tiers = [['min_qty' => '', 'price' => '']]; endif; ?>
            <?php foreach ($tiers as $i => $tier): ?>
            <div class="tier-pricing-row">
                <span>From</span>
                <input type="number" name="tier_qty[]" value="<?= $tier['min_qty'] ?? '' ?>" placeholder="Qty" min="1">
                <span>units: €</span>
                <input type="number" name="tier_price[]" step="0.01" value="<?= $tier['price'] ?? '' ?>" placeholder="Price">
                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()" title="Remove tier">×</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline" onclick="addTier()">+ Add Tier</button>

        <!-- Visibility -->
        <div class="form-group" style="margin-top:1rem">
            <label>
                <input type="checkbox" name="visible" value="1" <?= !isset($product['visible']) || $product['visible'] ? 'checked' : '' ?>>
                Visible on site
            </label>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:1rem">Save Product</button>
    </form>
</div>

<?php if ($product && !empty($stock_history)): ?>
<div class="admin-card">
    <h2>📋 Stock History (last 50 entries)</h2>
    <table class="admin-table">
        <thead><tr><th>Date</th><th>Delta</th><th>Reason</th></tr></thead>
        <tbody>
        <?php foreach ($stock_history as $sh): ?>
        <tr>
            <td><?= Template::date($sh['timestamp'] ?? '', 'd.m.Y H:i') ?></td>
            <td style="<?= ($sh['delta'] ?? 0) > 0 ? 'color:var(--success)' : 'color:var(--danger)' ?>">
                <?= ($sh['delta'] ?? 0) > 0 ? '+' : '' ?><?= $sh['delta'] ?? 0 ?>
            </td>
            <td><?= Template::e($sh['reason'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Quick Stock Adjust -->
<div class="admin-card">
    <h2>🔧 Quick Stock Adjustment</h2>
    <form method="post" action="/manage/product/stock-adjust" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <div class="admin-inline-fields">
            <div class="form-group">
                <label>Adjust by</label>
                <input type="number" name="delta" value="0" required style="width:120px">
            </div>
            <div class="form-group">
                <label>Reason</label>
                <input type="text" name="reason" value="manual_adjust" style="width:150px">
            </div>
            <button type="submit" class="btn btn-primary">Update Stock</button>
        </div>
    </form>
</div>
<?php endif; ?>

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

<script>
function addTier() {
    var div = document.createElement('div');
    div.className = 'tier-pricing-row';
    div.innerHTML = '<span>From</span> ' +
        '<input type="number" name="tier_qty[]" placeholder="Qty" min="1"> ' +
        '<span>units: €</span> ' +
        '<input type="number" name="tier_price[]" step="0.01" placeholder="Price"> ' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">×</button>';
    document.getElementById('tier-pricing-container').appendChild(div);
}
</script>
