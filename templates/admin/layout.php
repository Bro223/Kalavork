<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Template::e($page_title ?? 'Admin') ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/css/style.css?v=20250608">
</head>
<body class="admin-body">

<div class="admin-layout">
    <aside class="admin-sidebar">
        <a href="/manage" class="logo">⚙ <?= SITE_NAME ?></a>
        <button class="admin-menu-toggle" id="adminMenuToggle" aria-label="Toggle menu" title="Menu"></button>
        
        <nav id="adminNav">
            <a href="/manage" class="<?= ($page_title ?? '') === 'Dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="/manage/products" class="<?= strpos($page_title ?? '', 'Product') !== false ? 'active' : '' ?>">📦 Products</a>
            <a href="/manage/orders" class="<?= ($page_title ?? '') === 'Orders' || strpos($page_title ?? '', 'Order #') === 0 ? 'active' : '' ?>">🧾 Orders</a>
            <a href="/manage/statistics" class="<?= ($page_title ?? '') === 'Statistics' ? 'active' : '' ?>">📈 Statistics</a>
            <a href="/manage/content" class="<?= ($page_title ?? '') === 'Content' ? 'active' : '' ?>">🧩 Content</a>
            <a href="/manage/blog" class="<?= strpos($page_title ?? '', 'Post') !== false || ($page_title ?? '') === 'Blog' ? 'active' : '' ?>">📝 Blog</a>
            <a href="/manage/translations" class="<?= ($page_title ?? '') === 'Translations' ? 'active' : '' ?>">🌐 Translations</a>
            <a href="/manage/languages" class="<?= ($page_title ?? '') === 'Languages' ? 'active' : '' ?>">🏳 Languages</a>
            <a href="/manage/shipping-ways" class="<?= ($page_title ?? '') === 'Shipping Ways' ? 'active' : '' ?>">🚚 Shipping Ways</a>
            <a href="/manage/crm" class="<?= ($page_title ?? '') === 'CRM' || strpos($page_title ?? '', 'Contact') === 0 ? 'active' : '' ?>">👥 CRM</a>
            <a href="/manage/settings" class="<?= ($page_title ?? '') === 'Settings' ? 'active' : '' ?>">⚙ Settings</a>
            <hr style="border-color:#334155;margin:1rem 0">
            <a href="/" target="_blank">🔗 View Site</a>
            <a href="/manage/logout" style="color:#f87171">🚪 Logout</a>
        </nav>
    </aside>
    
    <!-- Admin Mobile Menu -->
    <div class="admin-mobile-menu-overlay" id="adminMobileMenuOverlay"></div>
    <nav class="admin-mobile-nav" id="adminMobileNav">
        <button class="admin-mobile-nav-close" id="adminMobileNavClose" aria-label="Close menu"></button>
        <a href="/manage" class="<?= ($page_title ?? '') === 'Dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="/manage/products" class="<?= strpos($page_title ?? '', 'Product') !== false ? 'active' : '' ?>">📦 Products</a>
        <a href="/manage/orders" class="<?= ($page_title ?? '') === 'Orders' || strpos($page_title ?? '', 'Order #') === 0 ? 'active' : '' ?>">🧾 Orders</a>
        <a href="/manage/statistics" class="<?= ($page_title ?? '') === 'Statistics' ? 'active' : '' ?>">📈 Statistics</a>
        <a href="/manage/content" class="<?= ($page_title ?? '') === 'Content' ? 'active' : '' ?>">🧩 Content</a>
        <a href="/manage/blog" class="<?= strpos($page_title ?? '', 'Post') !== false || ($page_title ?? '') === 'Blog' ? 'active' : '' ?>">📝 Blog</a>
        <a href="/manage/translations" class="<?= ($page_title ?? '') === 'Translations' ? 'active' : '' ?>">🌐 Translations</a>
        <a href="/manage/languages" class="<?= ($page_title ?? '') === 'Languages' ? 'active' : '' ?>">🏳 Languages</a>
        <a href="/manage/shipping-ways" class="<?= ($page_title ?? '') === 'Shipping Ways' ? 'active' : '' ?>">🚚 Shipping Ways</a>
        <a href="/manage/crm" class="<?= ($page_title ?? '') === 'CRM' || strpos($page_title ?? '', 'Contact') === 0 ? 'active' : '' ?>">👥 CRM</a>
        <a href="/manage/settings" class="<?= ($page_title ?? '') === 'Settings' ? 'active' : '' ?>">⚙ Settings</a>
        <hr style="border-color:#334155;margin:1rem 0">
        <a href="/" target="_blank">🔗 View Site</a>
        <a href="/manage/logout" style="color:#f87171">🚪 Logout</a>
    </nav>
    
    <main class="admin-main">
        <?php if (!empty($_SESSION['admin_success'])): ?>
        <div class="alert alert-success"><?= Template::e($_SESSION['admin_success']) ?></div>
        <?php unset($_SESSION['admin_success']); endif; ?>

        <?php if (!empty($_SESSION['admin_error'])): ?>
        <div class="alert alert-danger"><?= Template::e($_SESSION['admin_error']) ?></div>
        <?php unset($_SESSION['admin_error']); endif; ?>

        <?= $content ?? '' ?>
    </main>
</div>

<script>
const adminMenuToggle = document.getElementById('adminMenuToggle');
const adminMobileNav = document.getElementById('adminMobileNav');
const adminMobileMenuOverlay = document.getElementById('adminMobileMenuOverlay');
const adminMobileNavClose = document.getElementById('adminMobileNavClose');

function openAdminMobileMenu() {
    adminMobileNav.classList.add('active');
    adminMobileMenuOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAdminMobileMenu() {
    adminMobileNav.classList.remove('active');
    adminMobileMenuOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

adminMenuToggle.addEventListener('click', openAdminMobileMenu);
adminMobileNavClose.addEventListener('click', closeAdminMobileMenu);
adminMobileMenuOverlay.addEventListener('click', closeAdminMobileMenu);

// Close menu when a link is clicked
adminMobileNav.querySelectorAll('a:not(.admin-mobile-nav-close)').forEach(link => {
    link.addEventListener('click', closeAdminMobileMenu);
});

// Close menu on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAdminMobileMenu();
});
</script>
