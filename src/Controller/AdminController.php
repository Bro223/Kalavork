<?php
/**
 * Admin Controller - product CRUD, orders, blog, translations, dashboard
 * All actions require admin authentication
 */
class AdminController
{
    /* ================================================================
     * AUTH
     * ================================================================ */

    public static function loginPage(): void
    {
        if (Auth::isLoggedIn()) {
            header('Location: /manage');
            exit;
        }
        $csrf = CSRF::token();
        $error = $_SESSION['login_error'] ?? '';
        unset($_SESSION['login_error']);

        $template = new Template();
        $body = $template->render('admin/login', [
            'lang' => 'et',
            't' => fn($k) => $k,
            'page_title' => 'Admin Login',
            'csrf_token' => $csrf,
            'error' => $error,
            'is_home' => false,
        ], ['no_layout' => true]);

        // Standalone minimal page — no sidebar, no admin nav
        echo '<!DOCTYPE html><html lang="et"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . SITE_NAME . ' Admin</title><link rel="stylesheet" href="/css/style.css?v=20250608"></head><body class="admin-body admin-login-body">' . $body . '</body></html>';
    }

    public static function loginSubmit(): void
    {
        // Rate limit: max 5 login attempts per 60 seconds per IP
        try {
            $rl = new RateLimiter('login', 60, 5);
            $rl->checkOrFail();
        } catch (RateLimitExceededException $e) {
            $_SESSION['login_error'] = 'Too many login attempts. Please wait ' . $e->retryAfter . ' seconds.';
            header('Location: /manage/login');
            exit;
        }

        if (!CSRF::verify()) {
            $_SESSION['login_error'] = 'Invalid request.';
            header('Location: /manage/login');
            exit;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Record the attempt before checking credentials
        $rl->record();

        if (Auth::login($username, $password)) {
            header('Location: /manage');
            exit;
        }

        $_SESSION['login_error'] = 'Invalid credentials.';
        header('Location: /manage/login');
        exit;
    }

    public static function logout(): void
    {
        Auth::logout();
        header('Location: /manage/login');
        exit;
    }

    /* ================================================================
     * DASHBOARD
     * ================================================================ */

    public static function dashboard(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();

        $products = Storage::read('products');
        $orders = Storage::read('orders');
        $totalProducts = count($products);
        $totalStock = array_sum(array_column($products, 'stock'));
        $lowStock = array_filter($products, fn($p) => ($p['stock'] ?? 0) > 0 && ($p['stock'] ?? 0) <= 5);
        $outOfStock = array_filter($products, fn($p) => ($p['stock'] ?? 0) <= 0);
        $newOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'new');
        $allOrders = $orders;
        uasort($allOrders, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        $recentOrders = array_slice($allOrders, 0, 10);

        $template = new Template();
        echo $template->render('admin/dashboard', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Dashboard',
            'csrf_token' => $csrf,
            'total_products' => $totalProducts,
            'total_stock' => $totalStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'new_orders' => count($newOrders),
            'recent_orders' => $recentOrders,
            'total_orders' => count($orders),
            'is_home' => false,
        ]);
    }

    /* ================================================================
     * PRODUCTS CRUD
     * ================================================================ */

    public static function products(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $products = Storage::read('products');

        $template = new Template();
        echo $template->render('admin/products', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Products',
            'csrf_token' => $csrf,
            'products' => $products,
            'languages' => Storage::read('languages'),
            'is_home' => false,
        ]);
    }

    public static function productEdit(array $params = []): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $productId = $params['id'] ?? null;
        $product = $productId ? Storage::get('products', $productId) : null;

        if ($productId && !$product) {
            header('Location: /manage/products');
            exit;
        }

        // Get stock history for this product
        $stockHistory = [];
        if ($productId) {
            $allHistory = Storage::read('stock_history');
            $stockHistory = array_filter($allHistory, fn($h) => ($h['product_id'] ?? '') === $productId);
            $stockHistory = array_values(array_reverse($stockHistory));
            $stockHistory = array_slice($stockHistory, 0, 50);
        }

        $template = new Template();
        echo $template->render('admin/product_edit', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => $productId ? 'Edit Product' : 'New Product',
            'csrf_token' => $csrf,
            'product' => $product,
            'product_id' => $productId,
            'stock_history' => $stockHistory,
            'languages' => Storage::read('languages'),
            'asset_images' => self::assetImages(),
            'is_home' => false,
        ]);
    }

    public static function productSave(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/products');
            exit;
        }

        $productId = $_POST['product_id'] ?: Storage::newId('prod-');
        $isNew = !Storage::get('products', $productId);

        // Build multilingual name/description
        $langs = Storage::read('languages');
        $name = [];
        $description = [];
        foreach ($langs['available'] as $l) {
            $name[$l] = trim($_POST['name_' . $l] ?? '');
            $description[$l] = trim($_POST['description_' . $l] ?? '');
        }

        $tierPricing = [];
        $tierQtys = $_POST['tier_qty'] ?? [];
        $tierPrices = $_POST['tier_price'] ?? [];
        if (is_array($tierQtys) && is_array($tierPrices)) {
            for ($i = 0; $i < min(count($tierQtys), count($tierPrices)); $i++) {
                $qty = (int)$tierQtys[$i];
                $price = (float)$tierPrices[$i];
                if ($qty > 0 && $price > 0) {
                    $tierPricing[] = ['min_qty' => $qty, 'price' => $price];
                }
            }
        }

        $oldProduct = Storage::get('products', $productId);
        $oldStock = $oldProduct['stock'] ?? 0;

        $image = trim($_POST['image'] ?? '');
        $galleryImage = trim($_POST['gallery_image'] ?? $image);
        $uploadedImage = self::handleImageUpload('image_upload', 'product');
        $uploadedGalleryImage = self::handleImageUpload('gallery_image_upload', 'product');

        if ($uploadedImage) {
            if (!empty($_POST['delete_old_image']) && !empty($oldProduct['image']) && $oldProduct['image'] !== $galleryImage) {
                self::deleteAssetImage($oldProduct['image'], $productId);
            }
            $image = $uploadedImage;
        } elseif (!empty($_POST['remove_image'])) {
            if (!empty($_POST['delete_removed_image']) && !empty($oldProduct['image']) && $oldProduct['image'] !== $galleryImage) {
                self::deleteAssetImage($oldProduct['image'], $productId);
            }
            $image = '';
        } elseif ($oldProduct && !empty($oldProduct['image']) && empty($image)) {
            // No upload, no removal requested, and POST image is empty — preserve existing image
            $image = $oldProduct['image'];
        }

        if ($uploadedGalleryImage) {
            if (!empty($_POST['delete_old_gallery_image']) && !empty($oldProduct['gallery_image']) && $oldProduct['gallery_image'] !== $image) {
                self::deleteAssetImage($oldProduct['gallery_image'], $productId);
            }
            $galleryImage = $uploadedGalleryImage;
        } elseif (!empty($_POST['remove_gallery_image'])) {
            if (!empty($_POST['delete_removed_gallery_image']) && !empty($oldProduct['gallery_image']) && $oldProduct['gallery_image'] !== $image) {
                self::deleteAssetImage($oldProduct['gallery_image'], $productId);
            }
            $galleryImage = '';
        } elseif ($oldProduct && !empty($oldProduct['gallery_image']) && empty($galleryImage)) {
            // No upload, no removal requested, and POST gallery image is empty — preserve existing
            $galleryImage = $oldProduct['gallery_image'];
        }

        if ($galleryImage === '') {
            $galleryImage = $image;
        }

        $product = [
            'id' => $productId,
            'name' => $name,
            'description' => $description,
            'price' => (float)($_POST['price'] ?? 0),
            'tier_pricing' => $tierPricing,
            'stock' => (int)($_POST['stock'] ?? 0),
            'image' => $image,
            'gallery_image' => $galleryImage,
            'category' => trim($_POST['category'] ?? ''),
            'visible' => !empty($_POST['visible']),
            'specs' => [
                'length' => trim($_POST['spec_length'] ?? ''),
                'height' => trim($_POST['spec_height'] ?? ''),
                'mesh' => trim($_POST['spec_mesh'] ?? ''),
                'line' => trim($_POST['spec_line'] ?? ''),
            ],
            'created_at' => $oldProduct['created_at'] ?? date('c'),
            'updated_at' => date('c'),
        ];

        // Log stock change
        $newStock = $product['stock'];
        $stockDelta = $newStock - $oldStock;
        if ($stockDelta !== 0 && !$isNew) {
            self::logStockChange($productId, $stockDelta, $stockDelta > 0 ? 'manual_increase' : 'manual_decrease');
        } elseif ($isNew && $newStock > 0) {
            self::logStockChange($productId, $newStock, 'initial_stock');
        }

        Storage::save('products', $productId, $product);
        SEO::generateSitemap();

        $_SESSION['admin_success'] = $isNew ? 'Product created.' : 'Product updated.';
        header('Location: /manage/products');
        exit;
    }

    public static function productDelete(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/products');
            exit;
        }

        $productId = $_POST['product_id'] ?? '';
        if ($productId) {
            Storage::delete('products', $productId);
            $_SESSION['admin_success'] = 'Product deleted.';
        }

        header('Location: /manage/products');
        exit;
    }

    /**
     * Adjust stock via AJAX or form
     */
    public static function productStockAdjust(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/products');
            exit;
        }

        $productId = $_POST['product_id'] ?? '';
        $delta = (int)($_POST['delta'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'manual_adjust');

        $product = Storage::get('products', $productId);
        if (!$product || $delta === 0) {
            header('Location: /manage/products');
            exit;
        }

        $product['stock'] = max(0, ($product['stock'] ?? 0) + $delta);
        Storage::save('products', $productId, $product);
        self::logStockChange($productId, $delta, $reason);

        $_SESSION['admin_success'] = 'Stock updated.';
        header('Location: /manage/product/' . $productId);
        exit;
    }

    /* ================================================================
     * ORDERS
     * ================================================================ */

    public static function orders(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $orders = Storage::read('orders');
        uasort($orders, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        $template = new Template();
        echo $template->render('admin/orders', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Orders',
            'csrf_token' => $csrf,
            'orders' => $orders,
            'is_home' => false,
        ]);
    }

    public static function orderView(array $params = []): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $orderId = $params['id'] ?? '';
        $order = Storage::get('orders', $orderId);

        if (!$order) {
            header('Location: /manage/orders');
            exit;
        }

        $template = new Template();
        echo $template->render('admin/order_view', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Order #' . $orderId,
            'csrf_token' => $csrf,
            'order' => $order,
            'is_home' => false,
        ]);
    }

    public static function orderUpdateStatus(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/orders');
            exit;
        }

        $orderId = $_POST['order_id'] ?? '';
        $newStatus = $_POST['status'] ?? '';
        $order = Storage::get('orders', $orderId);

        if ($order && in_array($newStatus, ['new', 'processing', 'shipped', 'completed', 'cancelled'])) {
            $order['status'] = $newStatus;
            $order['updated_at'] = date('c');
            Storage::save('orders', $orderId, $order);
            $_SESSION['admin_success'] = 'Order status updated.';
        }

        header('Location: /manage/order/' . $orderId);
        exit;
    }

    public static function orderDelete(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/orders');
            exit;
        }

        $orderId = $_POST['order_id'] ?? '';
        if ($orderId) {
            Storage::delete('orders', $orderId);
            $_SESSION['admin_success'] = 'Order deleted.';
        }

        header('Location: /manage/orders');
        exit;
    }

    /* ================================================================
     * BLOG
     * ================================================================ */

    public static function blog(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $posts = Storage::read('blog');
        uasort($posts, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        $template = new Template();
        echo $template->render('admin/blog', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Blog',
            'csrf_token' => $csrf,
            'posts' => $posts,
            'is_home' => false,
        ]);
    }

    public static function blogEdit(array $params = []): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $postId = $params['id'] ?? null;
        $post = $postId ? Storage::get('blog', $postId) : null;

        $template = new Template();
        echo $template->render('admin/blog_edit', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => $postId ? 'Edit Post' : 'New Post',
            'csrf_token' => $csrf,
            'post' => $post,
            'post_id' => $postId,
            'languages' => Storage::read('languages'),
            'is_home' => false,
        ]);
    }

    public static function blogSave(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/blog');
            exit;
        }

        $postId = $_POST['post_id'] ?: Storage::newId('post-');
        $isNew = !Storage::get('blog', $postId);

        $langs = Storage::read('languages');
        $title = [];
        $content = [];
        $metaDescription = [];
        foreach ($langs['available'] as $l) {
            $title[$l] = trim($_POST['title_' . $l] ?? '');
            $content[$l] = trim($_POST['content_' . $l] ?? '');
            $metaDescription[$l] = trim($_POST['meta_description_' . $l] ?? '');
        }

        $slug = trim($_POST['slug'] ?? '');
        if (!$slug) {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title['et'] ?? $postId));
            $slug = trim($slug, '-');
        }

        $status = $_POST['status'] ?? 'draft';
        $publishedAt = ($status === 'published' && empty($post['published_at']))
            ? date('c')
            : ($post['published_at'] ?? null);

        $post = [
            'id' => $postId,
            'title' => $title,
            'content' => $content,
            'meta_description' => $metaDescription,
            'slug' => $slug,
            'image' => trim($_POST['image'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))),
            'status' => $status,
            'published_at' => $publishedAt,
            'created_at' => $post['created_at'] ?? date('c'),
            'updated_at' => date('c'),
        ];

        Storage::save('blog', $postId, $post);
        SEO::generateSitemap();

        $_SESSION['admin_success'] = $isNew ? 'Post created.' : 'Post updated.';
        header('Location: /manage/blog');
        exit;
    }

    public static function blogDelete(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/blog');
            exit;
        }

        $postId = $_POST['post_id'] ?? '';
        if ($postId) {
            Storage::delete('blog', $postId);
            $_SESSION['admin_success'] = 'Post deleted.';
        }

        header('Location: /manage/blog');
        exit;
    }

    /* ================================================================
     * CONTENT BLOCKS
     * ================================================================ */

    public static function content(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $translations = Storage::read('translations');
        $languages = Storage::read('languages');
        $settings = Storage::read('settings');
        $availableLanguages = $languages['available'] ?? AVAILABLE_LANGUAGES;

        $homeDefaults = [
            'hero_background_enabled' => true,
            'hero_background_image' => 'backgroung.webp',
        ];

        $template = new Template();
        echo $template->render('admin/content', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Content',
            'csrf_token' => $csrf,
            'languages' => $languages,
            'why_us_list' => $translations['why_us_list'] ?? [],
            'faq_items' => self::faqItemsFromTranslations($translations, $availableLanguages),
            'home_settings' => array_merge($homeDefaults, $settings['home'] ?? []),
            'asset_images' => self::assetImages(),
            'is_home' => false,
        ]);
    }

    public static function contentSave(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/content');
            exit;
        }

        $languages = Storage::read('languages');
        $availableLanguages = $languages['available'] ?? AVAILABLE_LANGUAGES;
        $translations = Storage::read('translations');

        foreach ($availableLanguages as $language) {
            $whyItems = $_POST['why_us_list'][$language] ?? [];
            if (!is_array($whyItems)) {
                $whyItems = [];
            }
            $translations['why_us_list'][$language] = array_values(array_filter(
                array_map('trim', $whyItems),
                fn($value) => $value !== ''
            ));

            $questions = $_POST['faq_question'][$language] ?? [];
            $answers = $_POST['faq_answer'][$language] ?? [];
            if (!is_array($questions)) $questions = [];
            if (!is_array($answers)) $answers = [];

            $faqItems = [];
            $maxRows = max(count($questions), count($answers));
            for ($i = 0; $i < $maxRows; $i++) {
                $question = trim($questions[$i] ?? '');
                $answer = trim($answers[$i] ?? '');
                if ($question === '' && $answer === '') {
                    continue;
                }
                $faqItems[] = ['question' => $question, 'answer' => $answer];
            }
            $translations['faq_items'][$language] = $faqItems;

            // Keep the older fixed FAQ keys populated as a fallback.
            $faqCount = count($faqItems);
            for ($i = 1; $i <= $faqCount; $i++) {
                $translations['faq_q' . $i][$language] = $faqItems[$i - 1]['question'] ?? '';
                $translations['faq_a' . $i][$language] = $faqItems[$i - 1]['answer'] ?? '';
            }
            // Clear any stale keys beyond the current count.
            for ($i = $faqCount + 1; $i <= 10; $i++) {
                unset($translations['faq_q' . $i]);
                unset($translations['faq_a' . $i]);
            }
        }

        Storage::write('translations', $translations);

        $settings = Storage::read('settings');
        $homeSettings = $settings['home'] ?? [];
        $currentHeroImage = $homeSettings['hero_background_image'] ?? 'backgroung.webp';
        $heroImage = basename(trim($_POST['hero_background_image'] ?? $currentHeroImage));
        $uploadedHeroImage = self::handleImageUpload('hero_background_upload', 'home');

        if ($uploadedHeroImage) {
            if (!empty($_POST['delete_old_hero_background']) && $currentHeroImage) {
                self::deleteAssetImage($currentHeroImage, '', true);
            }
            $heroImage = $uploadedHeroImage;
        }

        if (!empty($_POST['remove_hero_background'])) {
            if (!empty($_POST['delete_removed_hero_background']) && $currentHeroImage) {
                self::deleteAssetImage($currentHeroImage, '', true);
            }
            $heroImage = '';
        }

        $settings['home'] = [
            'hero_background_enabled' => !empty($_POST['hero_background_enabled']) && $heroImage !== '',
            'hero_background_image' => $heroImage,
        ];
        Storage::write('settings', $settings);

        $_SESSION['admin_success'] = 'Content updated.';
        header('Location: /manage/content');
        exit;
    }

    /* ================================================================
     * STATISTICS
     * ================================================================ */

    public static function statistics(): void
    {
        Auth::requireAdmin();
        $orders = Storage::read('orders');
        $products = Storage::read('products');
        $productStats = [];
        $totalRevenue = 0;
        $totalUnits = 0;
        $activeOrders = 0;
        $cancelledOrders = 0;

        foreach ($orders as $order) {
            if (($order['status'] ?? '') === 'cancelled') {
                $cancelledOrders++;
                continue;
            }

            $activeOrders++;
            $seenProducts = [];
            foreach ($order['items'] ?? [] as $item) {
                $productId = $item['product_id'] ?? 'unknown';
                $quantity = max(0, (int)($item['quantity'] ?? 0));
                $lineTotal = isset($item['line_total'])
                    ? (float)$item['line_total']
                    : ((float)($item['unit_price'] ?? 0) * $quantity);

                if (!isset($productStats[$productId])) {
                    $productStats[$productId] = [
                        'product_id' => $productId,
                        'name' => self::translationText($item['name'] ?? ($products[$productId]['name'] ?? $productId)),
                        'quantity' => 0,
                        'revenue' => 0,
                        'orders' => 0,
                    ];
                }

                $productStats[$productId]['quantity'] += $quantity;
                $productStats[$productId]['revenue'] += $lineTotal;
                if (empty($seenProducts[$productId])) {
                    $productStats[$productId]['orders']++;
                    $seenProducts[$productId] = true;
                }

                $totalUnits += $quantity;
                $totalRevenue += $lineTotal;
            }
        }

        uasort($productStats, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        $template = new Template();
        echo $template->render('admin/statistics', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Statistics',
            'product_stats' => $productStats,
            'total_revenue' => $totalRevenue,
            'total_units' => $totalUnits,
            'active_orders' => $activeOrders,
            'cancelled_orders' => $cancelledOrders,
            'is_home' => false,
        ]);
    }

    /* ================================================================
     * TRANSLATIONS
     * ================================================================ */

    public static function translations(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $translations = Storage::read('translations');
        $languages = Storage::read('languages');

        $template = new Template();
        echo $template->render('admin/translations', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Translations',
            'csrf_token' => $csrf,
            'translations' => $translations,
            'languages' => $languages,
            'is_home' => false,
        ]);
    }

    public static function translationSave(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/translations');
            exit;
        }

        $key = trim($_POST['key'] ?? '');
        $langs = Storage::read('languages');
        $translations = Storage::read('translations');

        if (!$key) {
            $_SESSION['admin_error'] = 'Translation key is required.';
            header('Location: /manage/translations');
            exit;
        }

        foreach ($langs['available'] as $l) {
            $translations[$key][$l] = self::decodePostedValue($_POST['value_' . $l] ?? '');
        }

        Storage::write('translations', $translations);
        $_SESSION['admin_success'] = 'Translations updated.';
        header('Location: /manage/translations');
        exit;
    }

    public static function translationDelete(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/translations');
            exit;
        }

        $key = $_POST['key'] ?? '';
        $translations = Storage::read('translations');

        if (isset($translations[$key])) {
            unset($translations[$key]);
            Storage::write('translations', $translations);
            $_SESSION['admin_success'] = 'Translation key deleted.';
        }

        header('Location: /manage/translations');
        exit;
    }

    /* ================================================================
     * LANGUAGES
     * ================================================================ */

    public static function languages(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $languages = Storage::read('languages');
        $translations = Storage::read('translations');

        $template = new Template();
        echo $template->render('admin/languages', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Languages',
            'csrf_token' => $csrf,
            'languages' => $languages,
            'translations' => $translations,
            'is_home' => false,
        ]);
    }

    public static function languageAdd(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/languages');
            exit;
        }

        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');

        if (strlen($code) !== 2 || !$name) {
            $_SESSION['admin_error'] = 'Invalid language code or name.';
            header('Location: /manage/languages');
            exit;
        }

        $languages = Storage::read('languages');
        if (!in_array($code, $languages['available'])) {
            $languages['available'][] = $code;
            $languages['names'][$code] = $name;
            Storage::write('languages', $languages);
            $_SESSION['admin_success'] = "Language '{$name}' added.";
        } else {
            $languages['names'][$code] = $name;
            Storage::write('languages', $languages);
            $_SESSION['admin_success'] = "Language '{$name}' updated.";
        }

        header('Location: /manage/languages');
        exit;
    }

    public static function languageRemove(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/languages');
            exit;
        }

        $code = $_POST['code'] ?? '';
        $languages = Storage::read('languages');

        if ($code === DEFAULT_LANGUAGE) {
            $_SESSION['admin_error'] = 'Cannot remove default language.';
            header('Location: /manage/languages');
            exit;
        }

        $index = array_search($code, $languages['available']);
        if ($index !== false) {
            unset($languages['available'][$index]);
            $languages['available'] = array_values($languages['available']);
            unset($languages['names'][$code]);
            Storage::write('languages', $languages);
            $_SESSION['admin_success'] = 'Language removed.';
        }

        header('Location: /manage/languages');
        exit;
    }

    /* ================================================================
     * SETTINGS
     * ================================================================ */

    public static function settings(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();

        $template = new Template();
        echo $template->render('admin/settings', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Settings',
            'csrf_token' => $csrf,
            'site_name' => SITE_NAME,
            'site_email' => SITE_EMAIL,
            'site_phone' => SITE_PHONE_DISPLAY,
            'is_home' => false,
        ]);
    }

    public static function settingsSave(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/settings');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if ($newPassword) {
            if (!password_verify($currentPassword, ADMIN_PASSWORD_HASH)) {
                $_SESSION['admin_error'] = 'Current password is incorrect.';
                header('Location: /manage/settings');
                exit;
            }

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $configFile = BASE_PATH . '/config.php';
            $config = file_get_contents($configFile);
            $config = preg_replace(
                "/define\('ADMIN_PASSWORD_HASH',\s*'[^']*'\);/",
                "define('ADMIN_PASSWORD_HASH', '{$newHash}');",
                $config
            );
            file_put_contents($configFile, $config);
            $_SESSION['admin_success'] = 'Password updated.';
        }

        header('Location: /manage/settings');
        exit;
    }

    /* ================================================================
     * SHIPPING WAYS
     * ================================================================ */

    public static function shippingWays(): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $settings = Storage::read('settings');
        $shippingWays = $settings['shipping_ways'] ?? [];
        $languages = Storage::read('languages');

        $template = new Template();
        echo $template->render('admin/shipping_ways', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Shipping Ways',
            'csrf_token' => $csrf,
            'shipping_ways' => $shippingWays,
            'languages' => $languages,
            'is_home' => false,
        ]);
    }

    public static function shippingWaysSave(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/shipping-ways');
            exit;
        }

        $action = $_POST['action'] ?? '';
        $settings = Storage::read('settings');
        $shippingWays = $settings['shipping_ways'] ?? [];
        $languages = Storage::read('languages')['available'] ?? ['et'];

        if ($action === 'add') {
            $id = trim($_POST['id'] ?? '');
            $requiresAddress = (bool)($_POST['requires_address'] ?? false);

            if (empty($id)) {
                $_SESSION['admin_error'] = 'Shipping way ID is required.';
                header('Location: /manage/shipping-ways');
                exit;
            }

            // Check if ID already exists
            if (isset(array_filter($shippingWays, fn($w) => $w['id'] === $id)[0])) {
                $_SESSION['admin_error'] = 'This ID already exists.';
                header('Location: /manage/shipping-ways');
                exit;
            }

            $label = [];
            foreach ($languages as $lang) {
                $label[$lang] = trim($_POST["label_$lang"] ?? '');
            }

            if (empty(array_filter($label))) {
                $_SESSION['admin_error'] = 'At least one language label is required.';
                header('Location: /manage/shipping-ways');
                exit;
            }

            $shippingWays[] = [
                'id' => $id,
                'label' => $label,
                'enabled' => true,
                'requires_address' => $requiresAddress,
            ];

            $_SESSION['admin_success'] = 'Shipping way added.';
        } elseif ($action === 'edit') {
            $id = trim($_POST['id'] ?? '');
            $requiresAddress = (bool)($_POST['requires_address'] ?? false);

            $index = array_search($id, array_column($shippingWays, 'id'));
            if ($index === false) {
                $_SESSION['admin_error'] = 'Shipping way not found.';
                header('Location: /manage/shipping-ways');
                exit;
            }

            $label = [];
            foreach ($languages as $lang) {
                $label[$lang] = trim($_POST["label_$lang"] ?? '');
            }

            if (empty(array_filter($label))) {
                $_SESSION['admin_error'] = 'At least one language label is required.';
                header('Location: /manage/shipping-ways');
                exit;
            }

            $shippingWays[$index]['label'] = $label;
            $shippingWays[$index]['requires_address'] = $requiresAddress;
            $_SESSION['admin_success'] = 'Shipping way updated.';
        } elseif ($action === 'delete') {
            $id = trim($_POST['id'] ?? '');
            $index = array_search($id, array_column($shippingWays, 'id'));
            if ($index === false) {
                $_SESSION['admin_error'] = 'Shipping way not found.';
                header('Location: /manage/shipping-ways');
                exit;
            }

            array_splice($shippingWays, $index, 1);
            $_SESSION['admin_success'] = 'Shipping way deleted.';
        } elseif ($action === 'toggle') {
            $id = trim($_POST['id'] ?? '');
            $index = array_search($id, array_column($shippingWays, 'id'));
            if ($index === false) {
                $_SESSION['admin_error'] = 'Shipping way not found.';
                header('Location: /manage/shipping-ways');
                exit;
            }

            $shippingWays[$index]['enabled'] = !($shippingWays[$index]['enabled'] ?? true);
            $_SESSION['admin_success'] = 'Shipping way updated.';
        }

        $settings['shipping_ways'] = $shippingWays;
        Storage::write('settings', $settings);

        header('Location: /manage/shipping-ways');
        exit;
    }

    /* ================================================================
     * CRM
     * ================================================================ */

    public static function crm(): void
    {
        Auth::requireAdmin();
        $contacts = Storage::read('contacts');
        uasort($contacts, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        $template = new Template();
        echo $template->render('admin/crm', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'CRM',
            'contacts' => $contacts,
            'is_home' => false,
        ]);
    }

    public static function crmView(array $params = []): void
    {
        Auth::requireAdmin();
        $contactId = $params['id'] ?? '';
        $contact = Storage::get('contacts', $contactId);

        if (!$contact) {
            header('Location: /manage/crm');
            exit;
        }

        // Gather related orders
        $allOrders = Storage::read('orders');
        $contactOrders = [];
        foreach (($contact['order_ids'] ?? []) as $oid) {
            if (isset($allOrders[$oid])) {
                $contactOrders[] = $allOrders[$oid];
            }
        }
        uasort($contactOrders, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        $template = new Template();
        echo $template->render('admin/crm_view', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => 'Contact: ' . ($contact['name'] ?? 'Unnamed'),
            'contact' => $contact,
            'contact_orders' => $contactOrders,
            'is_home' => false,
        ]);
    }

    public static function crmEdit(array $params = []): void
    {
        Auth::requireAdmin();
        $csrf = CSRF::token();
        $contactId = $params['id'] ?? null;
        $contact = $contactId ? Storage::get('contacts', $contactId) : null;

        $template = new Template();
        echo $template->render('admin/crm_edit', [
            'lang' => 'et', 't' => fn($k) => $k,
            'page_title' => $contactId ? 'Edit Contact' : 'New Contact',
            'csrf_token' => $csrf,
            'contact' => $contact,
            'is_home' => false,
        ]);
    }

    public static function crmSave(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/crm');
            exit;
        }

        $contactId = $_POST['contact_id'] ?: Storage::newId('contact-');
        $existing = Storage::get('contacts', $contactId);

        $contact = [
            'id' => $contactId,
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => in_array($_POST['status'] ?? '', ['active_buyer', 'contact']) ? $_POST['status'] : 'contact',
            'address' => [
                'street' => trim($_POST['street'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'postal' => trim($_POST['postal'] ?? ''),
                'county' => trim($_POST['county'] ?? ($existing['address']['county'] ?? '')),
                'country' => trim($_POST['country'] ?? 'Estonia'),
            ],
            'notes' => trim($_POST['notes'] ?? ''),
            'source' => $existing['source'] ?? 'manual',
            'order_ids' => $existing['order_ids'] ?? [],
            'message_history' => $existing['message_history'] ?? [],
            'created_at' => $existing['created_at'] ?? date('c'),
            'updated_at' => date('c'),
        ];

        Storage::save('contacts', $contactId, $contact);

        $_SESSION['admin_success'] = $existing ? 'Contact updated.' : 'Contact created.';
        header('Location: /manage/crm');
        exit;
    }

    public static function crmDelete(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/crm');
            exit;
        }

        $contactId = $_POST['contact_id'] ?? '';
        if ($contactId) {
            Storage::delete('contacts', $contactId);
            $_SESSION['admin_success'] = 'Contact deleted.';
        }

        header('Location: /manage/crm');
        exit;
    }

    /**
     * Bulk actions: delete, export selected, or change status.
     */
    public static function crmBulk(): void
    {
        Auth::requireAdmin();
        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/crm');
            exit;
        }

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            $_SESSION['admin_error'] = 'No contacts selected.';
            header('Location: /manage/crm');
            exit;
        }

        $action = $_POST['bulk_action'] ?? '';

        // ---- EXPORT SELECTED ----
        if ($action === 'export') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="crm_contacts_selected_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'name', 'email', 'phone', 'status',
                'address_street', 'address_city', 'address_postal',
                'address_county', 'address_country', 'notes',
                'source', 'created_at', 'updated_at',
            ]);

            $contacts = Storage::read('contacts');
            foreach ($ids as $id) {
                if (!isset($contacts[$id])) continue;
                $c = $contacts[$id];
                $addr = $c['address'] ?? [];
                fputcsv($out, [
                    $c['name'] ?? '', $c['email'] ?? '', $c['phone'] ?? '',
                    $c['status'] ?? 'contact',
                    $addr['street'] ?? '', $addr['city'] ?? '', $addr['postal'] ?? '',
                    $addr['county'] ?? '', $addr['country'] ?? '',
                    $c['notes'] ?? '', $c['source'] ?? 'manual',
                    $c['created_at'] ?? '', $c['updated_at'] ?? '',
                ]);
            }
            fclose($out);
            exit;
        }

        // ---- DELETE ----
        if ($action === 'delete') {
            $deleted = 0;
            foreach ($ids as $id) {
                if (Storage::get('contacts', $id)) {
                    Storage::delete('contacts', $id);
                    $deleted++;
                }
            }
            $_SESSION['admin_success'] = "{$deleted} contact(s) deleted.";
            header('Location: /manage/crm');
            exit;
        }

        // ---- CHANGE STATUS ----
        if (in_array($action, ['active_buyer', 'contact'])) {
            $updated = 0;
            foreach ($ids as $id) {
                $c = Storage::get('contacts', $id);
                if ($c) {
                    $c['status'] = $action;
                    $c['updated_at'] = date('c');
                    Storage::save('contacts', $id, $c);
                    $updated++;
                }
            }
            $label = $action === 'active_buyer' ? 'Active Buyer' : 'Contact';
            $_SESSION['admin_success'] = "{$updated} contact(s) changed to \"{$label}\".";
            header('Location: /manage/crm');
            exit;
        }

        $_SESSION['admin_error'] = 'Unknown bulk action.';
        header('Location: /manage/crm');
        exit;
    }

    /**
     * Export all contacts as a CSV file.
     * Columns: name, email, phone, status, address_street, address_city, address_postal,
     *          address_county, address_country, notes, source, created_at, updated_at
     */
    public static function crmExport(): void
    {
        Auth::requireAdmin();

        $contacts = Storage::read('contacts');
        uasort($contacts, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="crm_contacts_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // BOM for Excel UTF-8 compatibility
        fprintf($out, "\xEF\xBB\xBF");

        // Header row
        fputcsv($out, [
            'name', 'email', 'phone', 'status',
            'address_street', 'address_city', 'address_postal',
            'address_county', 'address_country', 'notes',
            'source', 'created_at', 'updated_at',
        ]);

        foreach ($contacts as $c) {
            $addr = $c['address'] ?? [];
            fputcsv($out, [
                $c['name'] ?? '',
                $c['email'] ?? '',
                $c['phone'] ?? '',
                $c['status'] ?? 'contact',
                $addr['street'] ?? '',
                $addr['city'] ?? '',
                $addr['postal'] ?? '',
                $addr['county'] ?? '',
                $addr['country'] ?? '',
                $c['notes'] ?? '',
                $c['source'] ?? 'manual',
                $c['created_at'] ?? '',
                $c['updated_at'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * Import contacts from an uploaded CSV file.
     * Matches by email first, then phone to avoid duplicates. Merges with existing.
     */
    public static function crmImport(): void
    {
        Auth::requireAdmin();

        if (!CSRF::verify()) {
            $_SESSION['admin_error'] = 'Invalid request.';
            header('Location: /manage/crm');
            exit;
        }

        $file = $_FILES['csv_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['admin_error'] = 'No file uploaded or upload error.';
            header('Location: /manage/crm');
            exit;
        }

        $tmpPath = $file['tmp_name'];
        $handle = fopen($tmpPath, 'r');

        if (!$handle) {
            $_SESSION['admin_error'] = 'Could not open uploaded file.';
            header('Location: /manage/crm');
            exit;
        }

        // Read BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            $_SESSION['admin_error'] = 'CSV file is empty or has no header row.';
            header('Location: /manage/crm');
            exit;
        }

        // Normalize headers to lowercase with underscores
        $headers = array_map(function($h) {
            return strtolower(trim(str_replace([' ', '-'], '_', $h)));
        }, $headers);

        // Build column index map
        $idx = [];
        $columns = [
            'name', 'email', 'phone', 'status',
            'address_street', 'address_city', 'address_postal',
            'address_county', 'address_country', 'notes',
            'source', 'created_at', 'updated_at',
        ];
        foreach ($columns as $col) {
            $pos = array_search($col, $headers);
            if ($pos !== false) {
                $idx[$col] = $pos;
            }
        }

        // Must have at least name or email or phone
        if (!isset($idx['name']) && !isset($idx['email']) && !isset($idx['phone'])) {
            fclose($handle);
            $_SESSION['admin_error'] = 'CSV must have at least one of: name, email, phone.';
            header('Location: /manage/crm');
            exit;
        }

        $imported = 0;
        $merged = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip completely empty rows
            $trimmed = array_map('trim', $row);
            if (count(array_filter($trimmed, fn($v) => $v !== '')) === 0) {
                continue;
            }

            $name = isset($idx['name']) ? trim($row[$idx['name']] ?? '') : '';
            $email = isset($idx['email']) ? trim($row[$idx['email']] ?? '') : '';
            $phone = isset($idx['phone']) ? trim($row[$idx['phone']] ?? '') : '';

            // Skip rows with no identifiable data
            if ($name === '' && $email === '' && $phone === '') {
                $skipped++;
                continue;
            }

            $status = 'contact';
            if (isset($idx['status'])) {
                $rawStatus = strtolower(trim($row[$idx['status']] ?? ''));
                if (in_array($rawStatus, ['active_buyer', 'active buyer', 'buyer'])) {
                    $status = 'active_buyer';
                }
            }

            $address = [];
            foreach (['street', 'city', 'postal', 'county', 'country'] as $field) {
                $key = 'address_' . $field;
                if (isset($idx[$key])) {
                    $address[$field] = trim($row[$idx[$key]] ?? '');
                }
            }

            $notes = isset($idx['notes']) ? trim($row[$idx['notes']] ?? '') : '';
            $source = isset($idx['source']) ? trim($row[$idx['source']] ?? '') : 'csv_import';
            $createdAt = isset($idx['created_at']) ? trim($row[$idx['created_at']] ?? '') : '';
            $updatedAt = isset($idx['updated_at']) ? trim($row[$idx['updated_at']] ?? '') : '';

            // Match existing contacts
            $contacts = Storage::read('contacts');
            $matchedId = null;

            // Match by email (case-insensitive)
            $emailLower = mb_strtolower($email);
            if ($emailLower !== '') {
                foreach ($contacts as $cid => $c) {
                    if (mb_strtolower($c['email'] ?? '') === $emailLower) {
                        $matchedId = $cid;
                        break;
                    }
                }
            }

            // Match by phone (normalized) if no email match
            if ($matchedId === null) {
                $phoneNorm = preg_replace('/[^0-9+]/', '', $phone);
                if ($phoneNorm !== '') {
                    foreach ($contacts as $cid => $c) {
                        $cPhone = preg_replace('/[^0-9+]/', '', $c['phone'] ?? '');
                        if ($cPhone !== '' && $cPhone === $phoneNorm) {
                            $matchedId = $cid;
                            break;
                        }
                    }
                }
            }

            if ($matchedId !== null) {
                // Merge into existing
                $existing = $contacts[$matchedId];

                if (mb_strlen($name) > mb_strlen($existing['name'] ?? '')) {
                    $existing['name'] = $name;
                }
                if (empty($existing['name']) && $name !== '') {
                    $existing['name'] = $name;
                }
                if (!empty($email) && empty($existing['email'])) {
                    $existing['email'] = $email;
                }
                if (!empty($phone) && empty($existing['phone'])) {
                    $existing['phone'] = $phone;
                }
                if ($status === 'active_buyer') {
                    $existing['status'] = 'active_buyer';
                }
                foreach (['street', 'city', 'postal', 'county', 'country'] as $field) {
                    if (!empty($address[$field]) && empty($existing['address'][$field])) {
                        $existing['address'][$field] = $address[$field];
                    }
                }
                if ($notes !== '' && empty($existing['notes'])) {
                    $existing['notes'] = $notes;
                }
                if ($source !== 'csv_import' && $existing['source'] === 'csv_import') {
                    $existing['source'] = $source;
                }
                $existing['updated_at'] = date('c');
                Storage::save('contacts', $matchedId, $existing);
                $merged++;
            } else {
                // Create new
                $newId = Storage::newId('contact-');
                $newContact = [
                    'id' => $newId,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'status' => $status,
                    'address' => $address,
                    'notes' => $notes,
                    'source' => $source ?: 'csv_import',
                    'order_ids' => [],
                    'message_history' => [],
                    'created_at' => $createdAt ?: date('c'),
                    'updated_at' => $updatedAt ?: date('c'),
                ];
                Storage::save('contacts', $newId, $newContact);
                $imported++;
            }
        }

        fclose($handle);

        $_SESSION['admin_success'] = "Import complete: {$imported} new contacts, {$merged} merged into existing, {$skipped} skipped.";
        header('Location: /manage/crm');
        exit;
    }

    /**
     * Upsert a contact from an order or contact form submission.
     * Matches by email first, then phone. Merges data into the existing contact.
     */
    public static function upsertContact(string $name, string $email, string $phone, array $address = [], string $message = '', string $source = 'order', string $orderId = ''): ?string
    {
        if (empty(trim($email)) && empty(trim($phone))) {
            return null;
        }

        $contacts = Storage::read('contacts');
        $matchedId = null;

        // Try match by email (case-insensitive)
        $emailLower = mb_strtolower(trim($email));
        if ($emailLower !== '') {
            foreach ($contacts as $cid => $c) {
                if (mb_strtolower($c['email'] ?? '') === $emailLower) {
                    $matchedId = $cid;
                    break;
                }
            }
        }

        // Try match by phone (normalized) if no email match
        if ($matchedId === null) {
            $phoneNorm = preg_replace('/[^0-9+]/', '', trim($phone));
            if ($phoneNorm !== '') {
                foreach ($contacts as $cid => $c) {
                    $cPhone = preg_replace('/[^0-9+]/', '', $c['phone'] ?? '');
                    if ($cPhone !== '' && $cPhone === $phoneNorm) {
                        $matchedId = $cid;
                        break;
                    }
                }
            }
        }

        if ($matchedId !== null) {
            // Merge into existing contact
            $existing = $contacts[$matchedId];

            // Update name if new one is longer or existing is empty
            if (mb_strlen(trim($name)) > mb_strlen($existing['name'] ?? '')) {
                $existing['name'] = trim($name);
            }
            if (empty($existing['name'])) {
                $existing['name'] = trim($name);
            }

            // Update email if we have one and existing doesn't
            if (!empty(trim($email)) && empty($existing['email'])) {
                $existing['email'] = trim($email);
            }

            // Update phone if we have one and existing doesn't
            if (!empty(trim($phone)) && empty($existing['phone'])) {
                $existing['phone'] = trim($phone);
            }

            // Merge address — fill missing fields
            if (!empty($address['street']) || !empty($address['city'])) {
                foreach (['street', 'city', 'postal', 'county', 'country'] as $field) {
                    if (!empty($address[$field]) && empty($existing['address'][$field])) {
                        $existing['address'][$field] = $address[$field];
                    }
                }
            }

            // Add order ID if this is an order and not already linked
            if ($source === 'order' && $orderId !== '' && !in_array($orderId, $existing['order_ids'] ?? [])) {
                $existing['order_ids'][] = $orderId;
                // Elevate to active buyer
                $existing['status'] = 'active_buyer';
            }

            // Append message to history
            if (trim($message) !== '') {
                $entry = [
                    'source' => $source,
                    'message' => trim($message),
                    'date' => date('c'),
                ];
                if ($orderId !== '') {
                    $entry['order_id'] = $orderId;
                }
                $existing['message_history'][] = $entry;
            }

            $existing['updated_at'] = date('c');
            Storage::save('contacts', $matchedId, $existing);
            return $matchedId;
        }

        // Create new contact
        $newId = Storage::newId('contact-');
        $newContact = [
            'id' => $newId,
            'name' => trim($name),
            'email' => trim($email),
            'phone' => trim($phone),
            'status' => ($source === 'order') ? 'active_buyer' : 'contact',
            'address' => $address,
            'notes' => '',
            'source' => $source,
            'order_ids' => ($source === 'order' && $orderId !== '') ? [$orderId] : [],
            'message_history' => [],
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        if (trim($message) !== '') {
            $entry = [
                'source' => $source,
                'message' => trim($message),
                'date' => date('c'),
            ];
            if ($orderId !== '') {
                $entry['order_id'] = $orderId;
            }
            $newContact['message_history'][] = $entry;
        }

        Storage::save('contacts', $newId, $newContact);
        return $newId;
    }

    /* ================================================================
     * HELPERS
     * ================================================================ */

    private static function faqItemsFromTranslations(array $translations, array $languages): array
    {
        $faqItems = [];
        foreach ($languages as $language) {
            $items = $translations['faq_items'][$language] ?? null;
            if (is_string($items)) {
                $decoded = json_decode($items, true);
                $items = is_array($decoded) ? $decoded : null;
            }

            if (is_array($items) && !empty($items)) {
                $faqItems[$language] = array_values(array_filter(array_map(function ($item) {
                    if (!is_array($item)) {
                        return null;
                    }
                    return [
                        'question' => (string)($item['question'] ?? ''),
                        'answer' => (string)($item['answer'] ?? ''),
                    ];
                }, $items)));
                continue;
            }

            $faqItems[$language] = [];
            for ($i = 1; $i <= 4; $i++) {
                $question = $translations['faq_q' . $i][$language] ?? '';
                $answer = $translations['faq_a' . $i][$language] ?? '';
                if ($question !== '' || $answer !== '') {
                    $faqItems[$language][] = [
                        'question' => (string)$question,
                        'answer' => (string)$answer,
                    ];
                }
            }
        }

        return $faqItems;
    }

    private static function decodePostedValue(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed !== '' && in_array($trimmed[0], ['[', '{'], true)) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    private static function translationText($value): string
    {
        if (!is_array($value)) {
            return (string)($value ?? '');
        }

        $fallback = $value[DEFAULT_LANGUAGE] ?? reset($value);
        return is_scalar($fallback) ? (string)$fallback : '';
    }

    private static function assetImages(): array
    {
        $dir = ASSETS_PATH . '/img';
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (is_file($path) && in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $files[] = $file;
            }
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        return $files;
    }

    private static function handleImageUpload(string $field, string $prefix): string
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $_SESSION['admin_error'] = 'Image upload failed.';
            return '';
        }

        $tmpName = $_FILES[$field]['tmp_name'] ?? '';
        $originalName = $_FILES[$field]['name'] ?? 'image';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $_SESSION['admin_error'] = 'Only JPG, PNG, WebP, and GIF images are allowed.';
            return '';
        }

        if (!is_uploaded_file($tmpName) || @getimagesize($tmpName) === false) {
            $_SESSION['admin_error'] = 'Uploaded file is not a valid image.';
            return '';
        }

        $dir = ASSETS_PATH . '/img';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $baseName = strtolower(pathinfo($originalName, PATHINFO_FILENAME));
        $baseName = trim(preg_replace('/[^a-z0-9]+/', '-', $baseName), '-');
        if ($baseName === '') {
            $baseName = 'image';
        }

        $filename = $prefix . '-' . $baseName . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
        if (!move_uploaded_file($tmpName, $dir . '/' . $filename)) {
            $_SESSION['admin_error'] = 'Could not save uploaded image.';
            return '';
        }

        return $filename;
    }

    private static function deleteAssetImage(string $filename, string $excludeProductId = '', bool $ignoreHeroReference = false): bool
    {
        $filename = basename($filename);
        if ($filename === '') {
            return false;
        }

        if (self::imageIsUsed($filename, $excludeProductId, $ignoreHeroReference)) {
            $_SESSION['admin_error'] = "Image '{$filename}' is still used somewhere else and was not deleted.";
            return false;
        }

        $path = ASSETS_PATH . '/img/' . $filename;
        return is_file($path) ? unlink($path) : false;
    }

    private static function imageIsUsed(string $filename, string $excludeProductId = '', bool $ignoreHeroReference = false): bool
    {
        foreach (Storage::read('products') as $productId => $product) {
            if ((string)$productId === $excludeProductId) {
                continue;
            }
            if (($product['image'] ?? '') === $filename || ($product['gallery_image'] ?? '') === $filename) {
                return true;
            }
        }

        if ($ignoreHeroReference) {
            return false;
        }

        $settings = Storage::read('settings');
        return ($settings['home']['hero_background_image'] ?? '') === $filename;
    }

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
        if (count($history) > 5000) {
            $history = array_slice($history, -5000);
        }
        Storage::write('stock_history', $history);
    }
}
