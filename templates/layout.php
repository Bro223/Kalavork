<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Template::e($page_title ?? SITE_NAME) ?></title>
    <meta name="description" content="<?= Template::e($meta_description ?? '') ?>">
    <?php if (!empty($keywords)): ?>
    <meta name="keywords" content="<?= Template::e($keywords) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= Template::e($canonical ?? SITE_URL) ?>">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= Template::e($page_title ?? SITE_NAME) ?>">
    <meta property="og:description" content="<?= Template::e($meta_description ?? '') ?>">
    <meta property="og:url" content="<?= Template::e($canonical ?? SITE_URL) ?>">
    <meta property="og:type" content="<?= ($is_home ?? false) ? 'website' : 'article' ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">

    <link rel="stylesheet" href="/css/style.css?v=20250608">

    <?= $jsonld ?? '' ?>

    <?php if (defined('GA_MEASUREMENT_ID') && GA_MEASUREMENT_ID): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= GA_MEASUREMENT_ID ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= GA_MEASUREMENT_ID ?>');
    </script>
    <?php endif; ?>
</head>
<body>
<?php
$currentPath = Router::stripLanguagePrefix($_SERVER['REQUEST_URI'] ?? '/');
$homeUrl = Router::url('/', $lang);
$shippingUrl = Router::url('/shipping', $lang);
$orderUrl = Router::url('/order', $lang);
$blogUrl = Router::url('/blog', $lang);
$contactUrl = Router::url('/contact', $lang);
?>

<header class="site-header">
    <div class="header-inner">
        <a href="<?= Template::e($homeUrl) ?>" class="logo">
            <img src="/assets/favicon.ico" alt="">
            <?= SITE_NAME ?>
        </a>

        <div class="lang-switcher">
            <?php foreach (AVAILABLE_LANGUAGES as $l): ?>
            <a href="<?= Template::e(Router::currentUrlForLanguage($l)) ?>"
               class="<?= $lang === $l ? 'active' : '' ?>">
                <?= strtoupper($l) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu" title="Menu"></button>

        <nav>
            <ul class="main-nav">
                <li><a href="<?= Template::e($homeUrl) ?>" class="<?= $currentPath === '/' ? 'active' : '' ?>"><?= $t('nav_home') ?></a></li>
                <li><a href="<?= Template::e($shippingUrl) ?>" class="<?= $currentPath === '/shipping' ? 'active' : '' ?>"><?= $t('nav_shipping') ?></a></li>
                <li><a href="<?= Template::e($orderUrl) ?>" class="<?= $currentPath === '/order' ? 'active' : '' ?>"><?= $t('nav_order') ?></a></li>
                <li><a href="<?= Template::e($blogUrl) ?>" class="<?= str_starts_with($currentPath, '/blog') ? 'active' : '' ?>"><?= $t('nav_blog') ?></a></li>
                <li><a href="<?= Template::e($contactUrl) ?>" class="<?= $currentPath === '/contact' ? 'active' : '' ?>"><?= $t('nav_contact') ?></a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
<nav class="mobile-nav" id="mobileNav">
    <button class="mobile-nav-close" id="mobileNavClose" aria-label="Close menu"></button>
    <a href="<?= Template::e($homeUrl) ?>" class="<?= $currentPath === '/' ? 'active' : '' ?>"><?= $t('nav_home') ?></a>
    <a href="<?= Template::e($shippingUrl) ?>" class="<?= $currentPath === '/shipping' ? 'active' : '' ?>"><?= $t('nav_shipping') ?></a>
    <a href="<?= Template::e($orderUrl) ?>" class="<?= $currentPath === '/order' ? 'active' : '' ?>"><?= $t('nav_order') ?></a>
    <a href="<?= Template::e($blogUrl) ?>" class="<?= str_starts_with($currentPath, '/blog') ? 'active' : '' ?>"><?= $t('nav_blog') ?></a>
    <a href="<?= Template::e($contactUrl) ?>" class="<?= $currentPath === '/contact' ? 'active' : '' ?>"><?= $t('nav_contact') ?></a>
    <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-light); margin-top: auto;">
        <?php foreach (AVAILABLE_LANGUAGES as $l): ?>
        <a href="<?= Template::e(Router::currentUrlForLanguage($l)) ?>" class="<?= $lang === $l ? 'active' : '' ?>" style="display: inline-block; padding: 0.5rem 0.75rem; margin-right: 0.5rem;"><?= strtoupper($l) ?></a>
        <?php endforeach; ?>
    </div>
</nav>

<script>
const menuToggle = document.getElementById('menuToggle');
const mobileNav = document.getElementById('mobileNav');
const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
const mobileNavClose = document.getElementById('mobileNavClose');

function openMobileMenu() {
    mobileNav.classList.add('active');
    mobileMenuOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMobileMenu() {
    mobileNav.classList.remove('active');
    mobileMenuOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

menuToggle.addEventListener('click', openMobileMenu);
mobileNavClose.addEventListener('click', closeMobileMenu);
mobileMenuOverlay.addEventListener('click', closeMobileMenu);

// Close menu when a link is clicked
mobileNav.querySelectorAll('a:not(.mobile-nav-close)').forEach(link => {
    link.addEventListener('click', closeMobileMenu);
});

// Close menu on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMobileMenu();
});
</script>

<main class="main-content">
    <?= $content ?? '' ?>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div>
            &copy; <?= date('Y') ?> <?= SITE_NAME ?>. <?= $t('contact_phone_label') ?>:
            <a href="tel:<?= SITE_PHONE ?>"><?= SITE_PHONE_DISPLAY ?></a>
            &nbsp;|&nbsp;
            <?= $t('contact_email_label') ?>:
            <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>
        </div>
        <div>
            <a href="<?= Template::e($contactUrl) ?>"><?= $t('nav_contact') ?></a>
        </div>
    </div>
</footer>

</body>
</html>
