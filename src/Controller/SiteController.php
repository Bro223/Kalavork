<?php
/**
 * Public site controller - handles all frontend pages
 */
class SiteController
{
    /**
     * Homepage - products listing with hero
     */
    public static function home(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $t = Router::translator($lang);
        $template = new Template();

        $products = Storage::read('products');
        $blogPosts = array_filter(Storage::read('blog'), fn($p) => ($p['status'] ?? 'draft') === 'published');
        $settings = Storage::read('settings');

        // Sort blog posts by date descending, take latest 3
        uasort($blogPosts, fn($a, $b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));
        $latestPosts = array_slice($blogPosts, 0, 3);

        $schemas = [
            SEO::organizationLD(),
            SEO::websiteLD($t('meta_description')),
            SEO::productListLD($products, $lang),
        ];

        // Determine canonical URL
        $canonical = SITE_URL . Router::url('/', $lang);

        echo $template->render('home', [
            'lang' => $lang,
            't' => $t,
            'page_title' => $t('page_title'),
            'meta_description' => $t('meta_description'),
            'keywords' => $t('keywords'),
            'canonical' => $canonical,
            'is_home' => true,
            'products' => $products,
            'latest_posts' => $latestPosts,
            'home_settings' => $settings['home'] ?? [],
            'jsonld' => SEO::jsonLD(...$schemas),
        ]);
    }

    /**
     * Product detail page
     */
    public static function product(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $t = Router::translator($lang);
        $template = new Template();

        $productId = $params['slug'] ?? '';
        $product = Storage::get('products', $productId);

        if (!$product || empty($product['visible'])) {
            http_response_code(404);
            echo $template->render('404', [
                'lang' => $lang, 't' => $t, 'page_title' => '404', 'is_home' => false,
                'canonical' => SITE_URL . Router::url('/404', $lang), 'meta_description' => '',
            ]);
            return;
        }

        $productName = $product['name'][$lang] ?? ($product['name']['et'] ?? '');
        $productDesc = $product['description'][$lang] ?? ($product['description']['et'] ?? '');

        echo $template->render('product_detail', [
            'lang' => $lang,
            't' => $t,
            'page_title' => $productName . ' - ' . SITE_NAME,
            'meta_description' => mb_substr(strip_tags($productDesc), 0, 160),
            'canonical' => SITE_URL . Router::url('/product/' . $productId, $lang),
            'is_home' => false,
            'product' => $product,
            'product_name' => $productName,
            'product_desc' => $productDesc,
            'in_stock' => ($product['stock'] ?? 0) > 0,
            'jsonld' => SEO::jsonLD(
                SEO::organizationLD(),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => $productName,
                    'description' => $productDesc,
                    'image' => SITE_URL . '/assets/img/' . ($product['image'] ?? ''),
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => $product['price'] ?? 0,
                        'priceCurrency' => 'EUR',
                        'availability' => ($product['stock'] ?? 0) > 0
                            ? 'https://schema.org/InStock'
                            : 'https://schema.org/OutOfStock',
                    ],
                ]
            ),
        ]);
    }

    /**
     * Contact page with form
     */
    public static function contact(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $t = Router::translator($lang);
        $template = new Template();
        $csrf = CSRF::token();
        $success = $_SESSION['contact_success'] ?? false;
        unset($_SESSION['contact_success']);

        $canonical = SITE_URL . Router::url('/contact', $lang);

        echo $template->render('contact', [
            'lang' => $lang,
            't' => $t,
            'page_title' => $t('contact_title') . ' - ' . SITE_NAME,
            'meta_description' => $t('meta_description'),
            'canonical' => $canonical,
            'is_home' => false,
            'csrf_token' => $csrf,
            'success' => $success,
            'recaptcha_site_key' => RECAPTCHA_SITE_KEY,
            'jsonld' => SEO::jsonLD(SEO::organizationLD()),
        ]);
    }

    /**
     * Handle contact form submission
     */
    public static function contactSubmit(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $redirect = Router::url('/contact', $lang);

        // Rate limit: max 3 contact submissions per 60 seconds
        try {
            $rl = new RateLimiter('contact', 60, 3);
            $rl->checkOrFail();
        } catch (RateLimitExceededException $e) {
            $_SESSION['contact_error'] = 'Too many messages. Please wait ' . $e->retryAfter . ' seconds.';
            header('Location: ' . $redirect);
            exit;
        }

        if (!CSRF::verify()) {
            $_SESSION['contact_error'] = 'Invalid form submission.';
            header('Location: ' . $redirect);
            exit;
        }

        // Record attempt (after CSRF passes — count all attempts, not just successful ones)
        $rl->record();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $captchaToken = $_POST['g-recaptcha-response'] ?? '';

        if (!$name || !$email || !$message) {
            $_SESSION['contact_error'] = 'Please fill all required fields.';
            header('Location: ' . $redirect);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['contact_error'] = 'Invalid email address.';
            header('Location: ' . $redirect);
            exit;
        }

        if (!Captcha::verify($captchaToken)) {
            $_SESSION['contact_error'] = 'reCAPTCHA verification failed.';
            header('Location: ' . $redirect);
            exit;
        }

        Mailer::sendContactNotification($name, $email, $message);

        // Upsert contact in CRM (no phone from contact form)
        AdminController::upsertContact($name, $email, '', [], $message, 'contact_form', '');

        $_SESSION['contact_success'] = true;
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Order/cart page
     */
    public static function order(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $t = Router::translator($lang);
        $template = new Template();
        $csrf = CSRF::token();
        $success = $_SESSION['order_success'] ?? false;
        unset($_SESSION['order_success']);

        // Get products for cart - only visible ones with stock
        $products = Storage::read('products');
        $settings = Storage::read('settings');
        $shippingWays = array_filter($settings['shipping_ways'] ?? [], fn($w) => ($w['enabled'] ?? true));

        echo $template->render('order', [
            'lang' => $lang,
            't' => $t,
            'page_title' => $t('cart_title') . ' - ' . SITE_NAME,
            'meta_description' => $t('meta_description'),
            'canonical' => SITE_URL . Router::url('/order', $lang),
            'is_home' => false,
            'csrf_token' => $csrf,
            'products' => $products,
            'shipping_ways' => $shippingWays,
            'success' => $success,
            'recaptcha_site_key' => RECAPTCHA_SITE_KEY,
            'jsonld' => SEO::jsonLD(SEO::organizationLD()),
        ]);
    }

    /**
     * Handle order submission
     */
    public static function orderSubmit(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $redirect = Router::url('/order', $lang);

        // Rate limit: max 5 order attempts per 60 seconds
        try {
            $rl = new RateLimiter('order', 60, 5);
            $rl->checkOrFail();
        } catch (RateLimitExceededException $e) {
            $_SESSION['order_error'] = 'Too many attempts. Please wait ' . $e->retryAfter . ' seconds.';
            header('Location: ' . $redirect);
            exit;
        }

        if (!CSRF::verify()) {
            $_SESSION['order_error'] = 'Invalid form submission.';
            header('Location: ' . $redirect);
            exit;
        }

        // Record attempt (count all attempts, not just successful ones)
        $rl->record();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $fulfillment = $_POST['fulfillment'] ?? 'delivery';
        $notes = trim($_POST['notes'] ?? '');
        $captchaToken = $_POST['g-recaptcha-response'] ?? '';

        // Validate shipping way is enabled
        $settings = Storage::read('settings');
        $allowedShippingWays = array_filter($settings['shipping_ways'] ?? [], fn($w) => ($w['enabled'] ?? true));
        $validFulfillment = array_search($fulfillment, array_column($allowedShippingWays, 'id'));
        if ($validFulfillment === false) {
            $_SESSION['order_error'] = 'Invalid shipping method selected.';
            header('Location: ' . $redirect);
            exit;
        }

        // Validate required fields
        if (!$name || !$email || !$phone) {
            $_SESSION['order_error'] = 'Please fill all required fields.';
            header('Location: ' . $redirect);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['order_error'] = 'Invalid email address.';
            header('Location: ' . $redirect);
            exit;
        }

        if (!Captcha::verify($captchaToken)) {
            $_SESSION['order_error'] = 'reCAPTCHA verification failed.';
            header('Location: ' . $redirect);
            exit;
        }

        // Get items from POST (JSON cart data)
        $cartItems = json_decode($_POST['cart_items'] ?? '[]', true);
        if (!is_array($cartItems) || empty($cartItems)) {
            $_SESSION['order_error'] = 'Cart is empty.';
            header('Location: ' . $redirect);
            exit;
        }

        // Validate address for shipping methods that require it
        $currentShippingWay = $allowedShippingWays[$validFulfillment] ?? [];
        $address = [];
        if ($currentShippingWay['requires_address'] ?? false) {
            $address = [
                'street' => trim($_POST['street'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'postal' => trim($_POST['postal'] ?? ''),
                'county' => trim($_POST['county'] ?? ''),
                'country' => trim($_POST['country'] ?? 'Estonia'),
            ];
            if (empty($address['street']) || empty($address['city'])) {
                $_SESSION['order_error'] = 'Please fill delivery address fields.';
                header('Location: ' . $redirect);
                exit;
            }
        }

        // Build order items and calculate prices
        $products = Storage::read('products');
        $orderItems = [];
        $total = 0;
        $errors = [];

        foreach ($cartItems as $ci) {
            $productId = $ci['id'] ?? '';
            $qty = max(1, (int)($ci['quantity'] ?? 1));
            $product = $products[$productId] ?? null;

            if (!$product || empty($product['visible'])) {
                $errors[] = "Product {$productId} is not available.";
                continue;
            }

            if (($product['stock'] ?? 0) < $qty) {
                $errors[] = "Not enough stock for " . ($product['name'][DEFAULT_LANGUAGE] ?? $productId);
                continue;
            }

            // Calculate price with tier pricing
            $unitPrice = (float)($product['price'] ?? 0);
            if (!empty($product['tier_pricing'])) {
                usort($product['tier_pricing'], fn($a, $b) => ($b['min_qty'] ?? 0) <=> ($a['min_qty'] ?? 0));
                foreach ($product['tier_pricing'] as $tier) {
                    if ($qty >= ($tier['min_qty'] ?? 0)) {
                        $unitPrice = (float)($tier['price'] ?? $unitPrice);
                        break;
                    }
                }
            }

            $lineTotal = $unitPrice * $qty;
            $total += $lineTotal;

            $orderItems[] = [
                'product_id' => $productId,
                'name' => $product['name'] ?? [],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            // Reduce stock
            $product['stock'] = ($product['stock'] ?? 0) - $qty;
            Storage::save('products', $productId, $product);

            // Log stock change
            self::logStockChange($productId, -$qty, 'order');
        }

        if (!empty($errors)) {
            $_SESSION['order_error'] = implode('; ', $errors);
            header('Location: ' . $redirect);
            exit;
        }

        // Create order
        $orderId = Storage::newId('ORD-');
        $order = [
            'id' => $orderId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'fulfillment' => $fulfillment,
            'address' => $address,
            'notes' => $notes,
            'items' => $orderItems,
            'total' => round($total, 2),
            'status' => 'new',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        Storage::save('orders', $orderId, $order);

        // Upsert contact in CRM
        AdminController::upsertContact($name, $email, $phone, $address, $notes, 'order', $orderId);

        // Send order notification
        Mailer::sendOrderNotification($order);

        // Refresh sitemap if enabled
        SEO::generateSitemap();

        $_SESSION['order_success'] = true;
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Blog listing
     */
    public static function blog(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $t = Router::translator($lang);
        $template = new Template();

        $allPosts = Storage::read('blog');
        $posts = array_filter($allPosts, fn($p) => ($p['status'] ?? 'draft') === 'published');

        uasort($posts, fn($a, $b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));

        echo $template->render('blog_list', [
            'lang' => $lang,
            't' => $t,
            'page_title' => $t('nav_blog') . ' - ' . SITE_NAME,
            'meta_description' => $t('meta_description'),
            'canonical' => SITE_URL . Router::url('/blog', $lang),
            'is_home' => false,
            'posts' => $posts,
            'jsonld' => SEO::jsonLD(SEO::organizationLD()),
        ]);
    }

    /**
     * Blog post detail
     */
    public static function blogPost(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $t = Router::translator($lang);
        $template = new Template();

        $slug = $params['slug'] ?? '';
        $allPosts = Storage::read('blog');
        $post = null;

        foreach ($allPosts as $p) {
            if (($p['slug'] ?? $p['id']) === $slug && ($p['status'] ?? 'draft') === 'published') {
                $post = $p;
                break;
            }
        }

        if (!$post) {
            http_response_code(404);
            echo $template->render('404', [
                'lang' => $lang, 't' => $t, 'page_title' => '404', 'is_home' => false,
                'canonical' => SITE_URL . Router::url('/404', $lang), 'meta_description' => '',
            ]);
            return;
        }

        $title = $post['title'][$lang] ?? ($post['title']['et'] ?? '');
        $metaDesc = $post['meta_description'][$lang] ?? ($post['meta_description']['et'] ?? '');

        echo $template->render('blog_detail', [
            'lang' => $lang,
            't' => $t,
            'page_title' => $title . ' - ' . SITE_NAME,
            'meta_description' => $metaDesc ?: mb_substr(strip_tags($post['content'][$lang] ?? ($post['content']['et'] ?? '')), 0, 160),
            'canonical' => SITE_URL . Router::url('/blog/' . ($post['slug'] ?? $post['id']), $lang),
            'is_home' => false,
            'post' => $post,
            'post_title' => $title,
            'post_content' => $post['content'][$lang] ?? ($post['content']['et'] ?? ''),
            'jsonld' => SEO::jsonLD(SEO::organizationLD(), SEO::articleLD($post, $lang)),
        ]);
    }

    /**
     * Shipping/delivery info page
     */
    public static function shipping(array $params = []): void
    {
        $lang = $params['lang'] ?? Router::detectLanguage();
        $t = Router::translator($lang);
        $template = new Template();

        echo $template->render('shipping', [
            'lang' => $lang,
            't' => $t,
            'page_title' => $t('shipping_title') . ' - ' . SITE_NAME,
            'meta_description' => $t('meta_description'),
            'canonical' => SITE_URL . Router::url('/shipping', $lang),
            'is_home' => false,
            'jsonld' => SEO::jsonLD(SEO::organizationLD()),
        ]);
    }

    /**
     * Log stock change
     */
    private static function logStockChange(string $productId, int $delta, string $reason): void
    {
        $history = Storage::read('stock_history');
        $history[] = [
            'id' => Storage::newId('sh-'),
            'product_id' => $productId,
            'delta' => $delta,
            'reason' => $reason,
            'timestamp' => date('c'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        // Keep reasonable number of entries
        if (count($history) > 5000) {
            $history = array_slice($history, -5000);
        }
        Storage::write('stock_history', $history);
    }
}
