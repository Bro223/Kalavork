<?php
/**
 * Automated SEO: generates JSON-LD schemas, meta tags, sitemaps
 */
class SEO
{
    /**
     * Generate JSON-LD structured data for the organization
     */
    public static function organizationLD(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Kalavork.ee',
            'url' => SITE_URL,
            'logo' => SITE_URL . '/assets/favicon.ico',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+' . substr(SITE_PHONE, 0, 3) . '-' . substr(SITE_PHONE, 3, 3) . '-' . substr(SITE_PHONE, 6),
                'contactType' => 'customer service',
                'email' => SITE_EMAIL,
            ],
        ];
    }

    /**
     * Generate JSON-LD for a WebSite
     */
    public static function websiteLD(string $description): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Kalavork.ee',
            'url' => SITE_URL,
            'description' => $description,
        ];
    }

    /**
     * Generate JSON-LD ItemList from products
     */
    public static function productListLD(array $products, string $lang = 'et'): array
    {
        $items = [];
        $pos = 0;
        foreach ($products as $product) {
            if (empty($product['visible']) && !Auth::isLoggedIn()) {
                continue;
            }
            $pos++;
            $name = $product['name'][$lang] ?? ($product['name']['et'] ?? '');
            $desc = $product['description'][$lang] ?? ($product['description']['et'] ?? '');
            $image = $product['image'] ?? '';
            $price = $product['price'] ?? 0;
            $stock = ($product['stock'] ?? 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

            $items[] = [
                '@type' => 'ListItem',
                'position' => $pos,
                'item' => [
                    '@type' => 'Product',
                    'name' => $name,
                    'description' => $desc,
                    'image' => SITE_URL . '/assets/img/' . $image,
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => $price,
                        'priceCurrency' => 'EUR',
                        'availability' => $stock,
                    ],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Generate JSON-LD for a blog article
     */
    public static function articleLD(array $post, string $lang = 'et'): array
    {
        $title = $post['title'][$lang] ?? ($post['title']['et'] ?? '');
        $desc = $post['meta_description'][$lang] ?? ($post['meta_description']['et'] ?? '');
        $image = $post['image'] ?? '';
        $published = $post['published_at'] ?? date('c');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'description' => $desc,
            'image' => $image ? (SITE_URL . '/assets/img/' . $image) : null,
            'datePublished' => $published,
            'dateModified' => $post['updated_at'] ?? $published,
            'author' => [
                '@type' => 'Person',
                'name' => CONTACT_NAME,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Kalavork.ee',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => SITE_URL . '/assets/favicon.ico',
                ],
            ],
        ];
    }

    /**
     * Combine all schema data into one script tag
     */
    public static function jsonLD(array ...$schemas): string
    {
        $combined = [];
        foreach ($schemas as $schema) {
            if (!empty($schema)) {
                $combined[] = $schema;
            }
        }

        $all = count($combined) === 1 ? $combined[0] : $combined;
        return '<script type="application/ld+json">' . json_encode($all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate sitemap.xml
     */
    public static function generateSitemap(): void
    {
        $urls = [];
        $urls[] = ['loc' => SITE_URL . '/', 'priority' => '1.00'];

        foreach (AVAILABLE_LANGUAGES as $lang) {
            if ($lang !== 'et') {
                $urls[] = ['loc' => SITE_URL . '/' . $lang . '/', 'priority' => '0.80'];
            }
        }

        // Blog posts
        $posts = Storage::read('blog');
        foreach ($posts as $post) {
            if ($post['status'] === 'published') {
                $urls[] = ['loc' => SITE_URL . '/blog/' . ($post['slug'] ?? $post['id']), 'priority' => '0.70'];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('c') . '</lastmod>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>';

        file_put_contents(BASE_PATH . '/sitemap.xml', $xml);
    }
}
