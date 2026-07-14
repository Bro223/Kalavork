<?php
/**
 * Kalavork.ee - Front Controller
 * All requests route through here via .htaccess
 */

// Bootstrap
require_once __DIR__ . '/../config.php';

// Autoload source files
spl_autoload_register(function (string $class) {
    $prefixes = [
        [SRC_PATH . '/', ''],
        [SRC_PATH . '/Controller/', ''],
    ];
    foreach ($prefixes as [$dir, $ns]) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Build router
$router = new Router();

// ============================================================
// PUBLIC ROUTES
// ============================================================

$registerPublicRoutes = function (string $prefix, string $lang) use ($router): void {
    $withLang = fn(array $params = []) => array_merge($params, ['lang' => $lang]);

    // Homepage
    $router->get($prefix ?: '/', function() use ($withLang) { SiteController::home($withLang()); });

    // Product detail
    $router->get($prefix . '/product/{slug}', function($p) use ($withLang) { SiteController::product($withLang($p)); });

    // Contact
    $router->get($prefix . '/contact', function() use ($withLang) { SiteController::contact($withLang()); });
    $router->post($prefix . '/contact', function() use ($withLang) { SiteController::contactSubmit($withLang()); });

    // Order / cart
    $router->get($prefix . '/order', function() use ($withLang) { SiteController::order($withLang()); });
    $router->post($prefix . '/order', function() use ($withLang) { SiteController::orderSubmit($withLang()); });

    // Shipping
    $router->get($prefix . '/shipping', function() use ($withLang) { SiteController::shipping($withLang()); });

    // Blog
    $router->get($prefix . '/blog', function() use ($withLang) { SiteController::blog($withLang()); });
    $router->get($prefix . '/blog/{slug}', function($p) use ($withLang) { SiteController::blogPost($withLang($p)); });
};

// Estonian is the default and uses URLs without a language prefix.
$registerPublicRoutes('', DEFAULT_LANGUAGE);

// Language-prefixed routes remain available for switching language.
foreach (AVAILABLE_LANGUAGES as $language) {
    $registerPublicRoutes('/' . $language, $language);
}

// Sitemap
$router->get('/sitemap.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    SEO::generateSitemap();
    readfile(BASE_PATH . '/sitemap.xml');
});

// ============================================================
// ADMIN ROUTES (/manage/*)
// ============================================================

$router->get('/manage/login', function() { AdminController::loginPage(); });
$router->post('/manage/login', function() { AdminController::loginSubmit(); });
$router->get('/manage/logout', function() { AdminController::logout(); });

$router->get('/manage', function() { AdminController::dashboard(); });

// Products
$router->get('/manage/products', function() { AdminController::products(); });
$router->get('/manage/product/new', function() { AdminController::productEdit([]); });
$router->get('/manage/product/{id}', function($p) { AdminController::productEdit($p); });
$router->post('/manage/product/save', function() { AdminController::productSave(); });
$router->post('/manage/product/delete', function() { AdminController::productDelete(); });
$router->post('/manage/product/stock-adjust', function() { AdminController::productStockAdjust(); });
$router->post('/manage/product/quick-image', function() { AdminController::productQuickImage(); });

// Orders
$router->get('/manage/orders', function() { AdminController::orders(); });
$router->get('/manage/order/{id}', function($p) { AdminController::orderView($p); });
$router->post('/manage/order/status', function() { AdminController::orderUpdateStatus(); });
$router->post('/manage/order/delete', function() { AdminController::orderDelete(); });

// Blog
$router->get('/manage/blog', function() { AdminController::blog(); });
$router->get('/manage/blog/new', function() { AdminController::blogEdit([]); });
$router->get('/manage/blog/{id}', function($p) { AdminController::blogEdit($p); });
$router->post('/manage/blog/save', function() { AdminController::blogSave(); });
$router->post('/manage/blog/delete', function() { AdminController::blogDelete(); });

// Content
$router->get('/manage/content', function() { AdminController::content(); });
$router->post('/manage/content/save', function() { AdminController::contentSave(); });

// Statistics
$router->get('/manage/statistics', function() { AdminController::statistics(); });

// Translations
$router->get('/manage/translations', function() { AdminController::translations(); });
$router->post('/manage/translations/save', function() { AdminController::translationSave(); });
$router->post('/manage/translations/delete', function() { AdminController::translationDelete(); });

// Languages
$router->get('/manage/languages', function() { AdminController::languages(); });
$router->post('/manage/languages/add', function() { AdminController::languageAdd(); });
$router->post('/manage/languages/remove', function() { AdminController::languageRemove(); });

// Settings
$router->get('/manage/settings', function() { AdminController::settings(); });
$router->post('/manage/settings', function() { AdminController::settingsSave(); });

// Shipping Ways
$router->get('/manage/shipping-ways', function() { AdminController::shippingWays(); });
$router->post('/manage/shipping-ways', function() { AdminController::shippingWaysSave(); });

// CRM
$router->get('/manage/crm', function() { AdminController::crm(); });
$router->get('/manage/crm/export', function() { AdminController::crmExport(); });
$router->post('/manage/crm/import', function() { AdminController::crmImport(); });
$router->get('/manage/crm/new', function() { AdminController::crmEdit([]); });
$router->get('/manage/crm/{id}', function($p) { AdminController::crmView($p); });
$router->get('/manage/crm/{id}/edit', function($p) { AdminController::crmEdit($p); });
$router->post('/manage/crm/save', function() { AdminController::crmSave(); });
$router->post('/manage/crm/delete', function() { AdminController::crmDelete(); });
$router->post('/manage/crm/bulk', function() { AdminController::crmBulk(); });

// Dispatch
$router->dispatch();
