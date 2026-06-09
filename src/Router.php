<?php
/**
 * Front Controller / Router
 */
class Router
{
    private array $routes = [];
    private string $basePath = '';

    public function get(string $pattern, callable $handler): void
    {
        $this->routes['GET'][] = ['pattern' => $this->compile($pattern), 'handler' => $handler];
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->routes['POST'][] = ['pattern' => $this->compile($pattern), 'handler' => $handler];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        if ($this->basePath && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath)) ?: '/';
        }

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) $params[$k] = $v;
                }
                call_user_func($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        $lang = self::detectLanguage();
        $t = self::translator($lang);
        try {
            $template = new Template();
            echo $template->render('404', [
                'lang' => $lang,
                't' => $t,
                'page_title' => '404 - ' . SITE_NAME,
                'canonical' => SITE_URL . '/404',
                'meta_description' => $t('meta_description'),
                'is_home' => false,
            ]);
        } catch (\Throwable $e) {
            echo '<h1>404 - Page Not Found</h1>';
        }
    }

    private function compile(string $pattern): string
    {
        return '#^' . preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern) . '$#';
    }

    public static function detectLanguage(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $firstSegment = explode('/', trim($uri, '/'))[0] ?? '';
        if (in_array($firstSegment, AVAILABLE_LANGUAGES, true)) {
            return $firstSegment;
        }
        return DEFAULT_LANGUAGE;
    }

    public static function normalizeLanguage(?string $lang): string
    {
        return in_array($lang, AVAILABLE_LANGUAGES, true) ? $lang : DEFAULT_LANGUAGE;
    }

    public static function stripLanguagePrefix(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        $segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

        if ($segments && in_array($segments[0], AVAILABLE_LANGUAGES, true)) {
            array_shift($segments);
        }

        return $segments ? '/' . implode('/', $segments) : '/';
    }

    public static function url(string $path = '/', ?string $lang = null): string
    {
        $lang = self::normalizeLanguage($lang);
        $path = self::stripLanguagePrefix($path);

        if ($path === '/') {
            return $lang === DEFAULT_LANGUAGE ? '/' : '/' . $lang . '/';
        }

        return $lang === DEFAULT_LANGUAGE ? $path : '/' . $lang . $path;
    }

    public static function currentUrlForLanguage(string $lang): string
    {
        return self::url($_SERVER['REQUEST_URI'] ?? '/', $lang);
    }

    public static function translator(string $lang): callable
    {
        $translations = Storage::read('translations');
        return function(string $key) use ($translations, $lang) {
            return $translations[$key][$lang] ?? ($translations[$key][DEFAULT_LANGUAGE] ?? $key);
        };
    }
}
