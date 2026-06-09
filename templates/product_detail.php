<div class="product-detail">
    <div>
        <?php if (!empty($product['image'])): ?>
        <img class="product-detail-img" src="/assets/img/<?= Template::e($product['image']) ?>" alt="<?= Template::e($product_name ?? '') ?>">
        <?php endif; ?>
    </div>

    <div class="product-detail-info">
        <h1><?= Template::e($product_name ?? '') ?></h1>

        <div class="price-block">
            <div class="price"><?= number_format((float)($product['price'] ?? 0), 2) ?>€</div>

            <?php if (!empty($product['tier_pricing'])): ?>
            <table class="tier-table">
                <thead><tr><th>Min Qty</th><th>Price/unit</th></tr></thead>
                <tbody>
                <?php
                usort($product['tier_pricing'], fn($a, $b) => ($a['min_qty'] ?? 0) <=> ($b['min_qty'] ?? 0));
                foreach ($product['tier_pricing'] as $tier):
                ?>
                <tr>
                    <td><?= (int)($tier['min_qty'] ?? 0) ?>+</td>
                    <td><strong><?= number_format((float)($tier['price'] ?? 0), 2) ?>€</strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <p><?= nl2br(Template::e($product_desc ?? '')) ?></p>

        <table class="specs-table">
            <?php if (!empty($product['specs']['length'])): ?>
            <tr><td>Length</td><td><?= Template::e($product['specs']['length']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($product['specs']['height'])): ?>
            <tr><td>Height</td><td><?= Template::e($product['specs']['height']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($product['specs']['mesh'])): ?>
            <tr><td>Mesh</td><td><?= Template::e($product['specs']['mesh']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($product['specs']['line'])): ?>
            <tr><td>Line</td><td><?= Template::e($product['specs']['line']) ?></td></tr>
            <?php endif; ?>
        </table>

        <div style="margin-top:1.5rem;">
            <?php if ($in_stock): ?>
            <span class="stock-badge in-stock" style="margin-right:1rem"><?= $t('stock_in_stock') ?></span>
            <button class="btn btn-primary" onclick="addToCartAndGo('<?= Template::e($product['id']) ?>')">
                <?= $t('order_btn') ?>
            </button>
            <?php else: ?>
            <span class="stock-badge out-of-stock"><?= $t('stock_out_of_stock') ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function addToCartAndGo(id) {
    let cart = JSON.parse(localStorage.getItem('kalavork_cart') || '{}');
    cart[id] = (cart[id] || 0) + 1;
    localStorage.setItem('kalavork_cart', JSON.stringify(cart));
    window.location.href = <?= json_encode(Router::url('/order', $lang)) ?>;
}
</script>
