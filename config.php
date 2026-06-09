<?php
/**
 * Kalavork.ee — Public Configuration
 * ===================================
 * This file contains NO secrets. All credentials, API keys, and personal
 * contact details live in config.local.php (gitignored). If that file is
 * missing (e.g. first clone / CI), safe dummy defaults are used so the
 * site boots without errors but email/captcha/auth won't work.
 *
 * To set up a real deployment:
 *   cp config.local.example.php config.local.php
 *   nano config.local.php
 */

// ───────────────────────────────────────────────────────────────
// 0. Production hardening — never leak paths or errors
// ───────────────────────────────────────────────────────────────
error_reporting(0);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');   // write to server log only, not to the browser

// ───────────────────────────────────────────────────────────────
// 1. Load local secrets (NOT tracked by git)
// ───────────────────────────────────────────────────────────────
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

// ───────────────────────────────────────────────────────────────
// 2. Public site identity
// ───────────────────────────────────────────────────────────────
define('SITE_NAME', 'Kalavork.ee');
define('SITE_URL', 'https://kalavork.ee');

// ───────────────────────────────────────────────────────────────
// 3. Fallback defaults (used when config.local.php is missing)
// ───────────────────────────────────────────────────────────────

// Contact / Business Owner
defined('SITE_EMAIL')         or define('SITE_EMAIL', 'dev@localhost');
defined('SITE_PHONE')         or define('SITE_PHONE', '+37200000000');
defined('SITE_PHONE_DISPLAY') or define('SITE_PHONE_DISPLAY', '+372 0000 0000');
defined('CONTACT_NAME')       or define('CONTACT_NAME', 'Dev');

// Admin
defined('ADMIN_USERNAME')       or define('ADMIN_USERNAME', 'admin');
defined('ADMIN_PASSWORD_HASH')  or define('ADMIN_PASSWORD_HASH', '$2b$10$dummy...............................................');

// reCAPTCHA (test keys that work on localhost)
defined('RECAPTCHA_SITE_KEY')   or define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
defined('RECAPTCHA_SECRET_KEY') or define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');

// SMTP
defined('SMTP_HOST')       or define('SMTP_HOST', 'localhost');
defined('SMTP_PORT')       or define('SMTP_PORT', 1025);
defined('SMTP_SECURE')     or define('SMTP_SECURE', '');
defined('SMTP_USERNAME')   or define('SMTP_USERNAME', '');
defined('SMTP_PASSWORD')   or define('SMTP_PASSWORD', '');
defined('SMTP_FROM_EMAIL') or define('SMTP_FROM_EMAIL', 'noreply@localhost');
defined('SMTP_FROM_NAME')  or define('SMTP_FROM_NAME', 'Kalavork.ee');

// Admin notification
defined('ADMIN_ORDER_EMAIL') or define('ADMIN_ORDER_EMAIL', 'dev@localhost');

// Google Analytics
defined('GA_MEASUREMENT_ID') or define('GA_MEASUREMENT_ID', '');

// ───────────────────────────────────────────────────────────────
// 4. Paths
// ───────────────────────────────────────────────────────────────
define('BASE_PATH', __DIR__);
define('DATA_PATH', BASE_PATH . '/data');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('SRC_PATH', BASE_PATH . '/src');
define('LIB_PATH', BASE_PATH . '/lib');

// ───────────────────────────────────────────────────────────────
// 5. Session
// ───────────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 86400); // 24 hours

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => true,  // HTTPS-only (production)
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ───────────────────────────────────────────────────────────────
// 6. Localisation
// ───────────────────────────────────────────────────────────────
define('DEFAULT_LANGUAGE', 'et');
define('AVAILABLE_LANGUAGES', ['et', 'ru', 'fi', 'en']);
date_default_timezone_set('Europe/Tallinn');

// ───────────────────────────────────────────────────────────────
// 7. Security tokens
// ───────────────────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);
define('RATE_LIMIT_WINDOW', 30);  // seconds
define('RATE_LIMIT_MAX', 5);      // requests per window
