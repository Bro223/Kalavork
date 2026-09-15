<?php
/**
 * Local secrets template. Copy to config.local.php and fill in real values.
 * config.local.php is gitignored.
 */

// Contact / business owner
define('SITE_EMAIL', 'your-email@gmail.com');
define('SITE_PHONE', '+37200000000');
define('SITE_PHONE_DISPLAY', '+372 0000 0000');
define('CONTACT_NAME', 'Your Name');

// Admin authentication
define('ADMIN_USERNAME', 'admin');
// Generate with: php -r "echo password_hash('your-password', PASSWORD_BCRYPT);"
define('ADMIN_PASSWORD_HASH', '$2b$10$...replace-with-real-hash...');

// Google reCAPTCHA v2
// Get keys at https://www.google.com/recaptcha/admin
// Use test keys during development (these work on any domain):
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');

// SMTP (Gmail)
// Use a Gmail App Password (not your real password):
//   1. Enable 2FA on your Google account
//   2. Go to https://myaccount.google.com/apppasswords
//   3. Generate an "App password" for "Mail"
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your-sender@gmail.com');
define('SMTP_PASSWORD', 'xxxxxxxxxxxxxx');          // Gmail App Password
define('SMTP_FROM_EMAIL', 'your-sender@gmail.com');
define('SMTP_FROM_NAME', 'Kalavork.ee');

// Admin notification email for new orders
define('ADMIN_ORDER_EMAIL', 'your-email@gmail.com');

// Google Analytics
define('GA_MEASUREMENT_ID', 'G-XXXXXXXXXX');
