<?php
$langNames = ['et' => 'Eesti', 'ru' => 'Русский', 'fi' => 'Suomi', 'en' => 'English'];
$currentLangName = $langNames[$lang] ?? strtoupper($lang);
?>
<h2><?= $t('cart_title') ?></h2>

<?php if ($success ?? false): ?>
<div class="alert alert-success">
    <?= $t('order_success') ?>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['order_error'])): ?>
<div class="alert alert-danger"><?= Template::e($_SESSION['order_error']) ?></div>
<?php unset($_SESSION['order_error']); endif; ?>

<div class="cart-panel">
    <div class="cart-items" id="cart-items">
        <div class="empty-cart"><?= $t('cart_empty') ?></div>
    </div>
    <div class="cart-summary" id="cart-summary"></div>
</div>

<form method="POST" action="<?= Template::e(Router::url('/order', $lang)) ?>" id="order-form">
    <?= CSRF::field() ?>
    <input type="hidden" name="cart_items" id="cart-items-input" value="">

    <div class="cart-panel">
        <h3><?= $t('nav_contact') ?></h3>

        <div class="form-row">
            <div class="form-group">
                <label for="name"><?= $t('form_name') ?></label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="phone"><?= $t('contact_phone_label') ?> *</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email"><?= $t('form_email') ?></label>
            <input type="email" id="email" name="email" required>
        </div>
    </div>

    <div class="cart-panel">
        <h3><?= $t('shipping_title') ?></h3>

        <div class="form-group">
            <?php 
            $firstWayId = null;
            foreach ($shipping_ways as $index => $way): 
                if ($firstWayId === null) $firstWayId = $way['id'];
                $isFirst = $index === 0;
                $requiresAddress = $way['requires_address'] ?? false;
            ?>
            <label>
                <input type="radio" name="fulfillment" value="<?= Template::e($way['id']) ?>" <?= $isFirst ? 'checked' : '' ?> onchange="toggleFulfillment()">
                <?= Template::e($way['label'][$lang] ?? $way['label']['et'] ?? $way['id']) ?>
            </label>
            <?php if ($index < count($shipping_ways) - 1): ?>
            &nbsp;&nbsp;
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div id="delivery-fields" style="display:<?= ($shipping_ways[0]['requires_address'] ?? false) ? 'block' : 'none' ?>">
            <div class="form-group">
                <label for="street"><?= $t('form_address_label') ?></label>
                <input type="text" id="street" name="street" placeholder="Street, house, apartment">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" placeholder="Tallinn">
                </div>
                <div class="form-group">
                    <label for="postal">Postal code</label>
                    <input type="text" id="postal" name="postal">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="county">County</label>
                    <input type="text" id="county" name="county">
                </div>
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="Estonia">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="notes"><?= $t('form_message') ?></label>
            <textarea id="notes" name="notes" rows="3" placeholder="<?= $t('form_message_placeholder') ?>"></textarea>
        </div>
    </div>

    <div class="form-group" style="text-align:center">
        <div class="g-recaptcha" data-sitekey="<?= Template::e($recaptcha_site_key ?? '') ?>" style="display:inline-block"></div>
    </div>

    <button type="submit" class="btn btn-success" style="width:100%; padding:0.8rem; font-size:1.1rem" id="submit-btn" disabled>
        <?= $t('cart_checkout') ?>
    </button>
</form>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
const orderPlaced = <?= json_encode((bool)($success ?? false)) ?>;
if (orderPlaced) {
    localStorage.removeItem('kalavork_cart');
}

// Cart state
let cart = JSON.parse(localStorage.getItem('kalavork_cart') || '{}');

// Product data for price calculation
const productsData = <?= json_encode($products, JSON_HEX_APOS) ?>;

function saveCart() {
    localStorage.setItem('kalavork_cart', JSON.stringify(cart));
    document.getElementById('cart-items-input').value = JSON.stringify(
        Object.entries(cart).map(([id, qty]) => ({id, quantity: qty}))
    );
    renderCart();
}

function addToCart(productId) {
    cart[productId] = (cart[productId] || 0) + 1;
    saveCart();
    const orderUrl = <?= json_encode(Router::url('/order', $lang)) ?>;
    if (window.location.pathname !== orderUrl.replace(/\/$/, '')) {
        window.location.href = orderUrl;
    }
}

function removeFromCart(productId) {
    delete cart[productId];
    saveCart();
}

function updateQty(productId, delta) {
    cart[productId] = (cart[productId] || 0) + delta;
    if (cart[productId] <= 0) delete cart[productId];
    saveCart();
}

function calcPrice(productId, qty) {
    const p = productsData[productId];
    if (!p) return 0;
    let price = parseFloat(p.price || 0);
    if (p.tier_pricing && p.tier_pricing.length) {
        const tiers = [...p.tier_pricing].sort((a,b) => (b.min_qty||0) - (a.min_qty||0));
        for (const t of tiers) {
            if (qty >= (t.min_qty || 0)) { price = parseFloat(t.price || price); break; }
        }
    }
    return price;
}

function renderCart() {
    const container = document.getElementById('cart-items');
    const summary = document.getElementById('cart-summary');
    const submitBtn = document.getElementById('submit-btn');
    const ids = Object.keys(cart);

    if (ids.length === 0) {
        container.innerHTML = '<div class="empty-cart"><?= $t('cart_empty') ?></div>';
        summary.innerHTML = '';
        submitBtn.disabled = true;
        return;
    }

    submitBtn.disabled = false;
    let total = 0;
    let html = '';

    for (const id of ids) {
        const p = productsData[id];
        if (!p) continue;
        const qty = cart[id];
        const unitPrice = calcPrice(id, qty);
        const lineTotal = unitPrice * qty;
        total += lineTotal;
        const basePrice = parseFloat(p.price || 0);
        const hasDiscount = unitPrice < basePrice;

        const pName = p.name ? (p.name['<?= $lang ?>'] || p.name['et'] || '') : '';
        const img = p.image || '';

        html += '<div class="cart-item">';
        if (img) html += '<img class="cart-item-img" src="/assets/img/' + img + '" alt="">';
        html += '<div class="cart-item-info">';
        html += '<div class="name">' + pName + '</div>';
        html += '<div class="price">' + unitPrice.toFixed(2) + '€ / tk</div>';
        if (hasDiscount) html += '<div class="tier-applied">Bulk discount applied (base: ' + basePrice.toFixed(2) + '€)</div>';
        html += '</div>';
        html += '<div class="cart-item-qty">';
        html += '<button onclick="updateQty(\'' + id + '\', -1)">-</button>';
        html += '<input type="number" value="' + qty + '" min="1" onchange="cart[\'' + id + '\']=Math.max(1,parseInt(this.value)||1);saveCart()">';
        html += '<button onclick="updateQty(\'' + id + '\', 1)">+</button>';
        html += '</div>';
        html += '<div style="font-weight:600;min-width:60px;text-align:right">' + lineTotal.toFixed(2) + '€</div>';
        html += '<button class="cart-item-remove" onclick="removeFromCart(\'' + id + '\')" title="Remove">&times;</button>';
        html += '</div>';
    }

    container.innerHTML = html;
    summary.innerHTML = '<?= $t('cart_checkout') ?>: <strong>' + total.toFixed(2) + '€</strong>';
    document.getElementById('cart-items-input').value = JSON.stringify(
        Object.entries(cart).map(([id, qty]) => ({id, quantity: qty}))
    );
}

function toggleFulfillment() {
    const selectedValue = document.querySelector('input[name="fulfillment"]:checked').value;
    const shippingWaysData = <?= json_encode($shipping_ways, JSON_HEX_APOS) ?>;
    const selectedWay = shippingWaysData.find(w => w.id === selectedValue);
    const requiresAddress = selectedWay?.requires_address || false;
    document.getElementById('delivery-fields').style.display = requiresAddress ? 'block' : 'none';
}

// Init
renderCart();
toggleFulfillment();
</script>
