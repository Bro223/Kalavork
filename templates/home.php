<?php
/* Homepage - hero + products + info sections */
$homeSettings = $home_settings ?? [];
$heroBackgroundEnabled = $homeSettings['hero_background_enabled'] ?? true;
$heroBackgroundImage = $homeSettings['hero_background_image'] ?? 'backgroung.webp';
$heroStyle = '';
if ($heroBackgroundEnabled && $heroBackgroundImage) {
    $heroStyle = "background-image: linear-gradient(rgba(44,62,80,.72), rgba(44,62,80,.52)), url('/assets/img/" . Template::e($heroBackgroundImage) . "')";
}
?>
<!-- Hero -->
<div class="hero <?= $heroStyle ? 'hero-has-image' : '' ?>"<?= $heroStyle ? ' style="' . $heroStyle . '"' : '' ?>>
    <h1><?= $t('hero_title') ?></h1>
    <p><?= $t('hero_text') ?></p>
    <a href="<?= Template::e(Router::url('/contact', $lang)) ?>" class="btn-contact"><?= $t('contact_btn') ?></a>
</div>

<!-- Products -->
<h2 class="section-title"><?= $t('products_title') ?></h2>

<?php if (empty($products)): ?>
    <p style="text-align:center; padding:2rem;"><?= $t('products_empty') ?></p>
<?php else: ?>
<div class="product-grid">
    <?php foreach ($products as $p):
        if (empty($p['visible'])) continue;
        $pName = $p['name'][$lang] ?? ($p['name']['et'] ?? '');
        $pDesc = $p['description'][$lang] ?? ($p['description']['et'] ?? '');
        $inStock = ($p['stock'] ?? 0) > 0;
        $hasTier = !empty($p['tier_pricing']);
    ?>
    <div class="product-card" data-product-id="<?= Template::e($p['id']) ?>"
         data-product-name="<?= Template::e($pName) ?>"
         data-price="<?= (float)($p['price'] ?? 0) ?>"
         data-stock="<?= (int)($p['stock'] ?? 0) ?>"
         data-tier='<?= json_encode($p['tier_pricing'] ?? [], JSON_HEX_APOS) ?>'>
        <img class="product-card-img" src="/assets/img/<?= Template::e($p['image'] ?? '') ?>" alt="<?= Template::e($pName) ?>" loading="lazy">
        <div class="product-card-body">
            <h3><a href="<?= Template::e(Router::url('/product/' . $p['id'], $lang)) ?>"><?= Template::e($pName) ?></a></h3>
            <p class="desc"><?= Template::e(mb_substr($pDesc, 0, 100)) ?>...</p>

            <div class="specs">
                <div class="spec-row">
                    <?php if (!empty($p['specs']['length'])): ?><span><?= Template::e($p['specs']['length']) ?></span><?php endif; ?>
                    <?php if (!empty($p['specs']['height'])): ?><span><?= Template::e($p['specs']['height']) ?></span><?php endif; ?>
                    <?php if (!empty($p['specs']['mesh'])): ?><span><?= Template::e($p['specs']['mesh']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="price"><?= number_format((float)($p['price'] ?? 0), 2) ?>€</div>

            <?php if ($hasTier): ?>
            <div class="tier-note">
                <?php
                $bestTier = $p['tier_pricing'][0];
                foreach ($p['tier_pricing'] as $tier) {
                    if (($tier['min_qty'] ?? 0) > ($bestTier['min_qty'] ?? 0)) $bestTier = $tier;
                }
                ?>
                <?= Template::e($bestTier['price'] ?? '') ?>€/tk al <?= Template::e($bestTier['min_qty'] ?? '') ?> tk
            </div>
            <?php endif; ?>

            <?php if ($inStock): ?>
                <span class="stock-badge in-stock"><?= $t('stock_in_stock') ?></span>
                <button class="btn btn-primary add-to-cart-btn" onclick="addToCart('<?= Template::e($p['id']) ?>')">
                    <?= $t('order_btn') ?>
                </button>
            <?php else: ?>
                <span class="stock-badge out-of-stock"><?= $t('stock_out_of_stock') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function addToCart(productId) {
    const cart = JSON.parse(localStorage.getItem('kalavork_cart') || '{}');
    cart[productId] = (cart[productId] || 0) + 1;
    localStorage.setItem('kalavork_cart', JSON.stringify(cart));
    window.location.href = <?= json_encode(Router::url('/order', $lang)) ?>;
}
</script>

<!-- Why Us -->
<div class="info-section">
    <h2><?= $t('why_us_title') ?></h2>
    <p><?= $t('why_us_text') ?></p>
    <ul class="why-us-list">
        <?php $whyList = $t('why_us_list'); ?>
        <?php if (is_string($whyList)) $whyList = json_decode($whyList, true) ?? []; ?>
        <?php foreach ($whyList as $item): ?>
        <li><?= Template::e($item) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Info: How to choose / mesh sizes / China vs Finland -->
<div class="info-section">
    <h2><?= $t('info_title') ?></h2>
    <p><?= $t('info_text') ?></p>
    <h3><?= $t('info_mesh_title') ?></h3>
    <p><?= $t('info_mesh_text') ?></p>
    <h3><?= $t('info_china_vs_fin_title') ?></h3>
    <p><?= $t('info_china_vs_fin_text') ?></p>
</div>

<!-- FAQ -->
<div class="info-section">
    <h2><?= $t('faq_title') ?></h2>
    <?php
    $faqItems = $t('faq_items');
    if (!is_array($faqItems) || empty($faqItems)) {
        $faqItems = [];
        for ($i = 1; $i <= 4; $i++) {
            $faqItems[] = [
                'question' => $t('faq_q' . $i),
                'answer' => $t('faq_a' . $i),
            ];
        }
    }
    ?>
    <?php foreach ($faqItems as $faq): ?>
    <?php if (!is_array($faq)) continue; ?>
    <div class="faq-item">
        <h3><?= Template::e($faq['question'] ?? '') ?></h3>
        <p><?= Template::e($faq['answer'] ?? '') ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Shipping -->
<div class="info-section">
    <h2><?= $t('shipping_title') ?></h2>
    <p><?= $t('shipping_text') ?></p>
</div>

<!-- Blog preview -->
<?php if (!empty($latest_posts)): ?>
<div class="info-section">
    <h2><?= $t('nav_blog') ?></h2>
    <div class="blog-grid">
        <?php foreach ($latest_posts as $bp):
            $bpTitle = $bp['title'][$lang] ?? ($bp['title']['et'] ?? '');
            $bpExcerpt = $bp['meta_description'][$lang] ?? ($bp['meta_description']['et'] ?? '');
        ?>
        <div class="blog-card">
            <?php if (!empty($bp['image'])): ?>
            <img class="blog-card-img" src="/assets/img/<?= Template::e($bp['image']) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <div class="blog-card-body">
                <h3><a href="<?= Template::e(Router::url('/blog/' . ($bp['slug'] ?? $bp['id']), $lang)) ?>"><?= Template::e($bpTitle) ?></a></h3>
                <div class="meta"><?= Template::date($bp['published_at'] ?? '') ?></div>
                <p class="excerpt"><?= Template::e(mb_substr($bpExcerpt, 0, 120)) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
