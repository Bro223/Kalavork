<?php
/**
 * Simple Template Engine
 * Renders PHP templates with data, auto-escapes output
 */
class Template
{
    private string $templateDir;
    private string $layout = 'layout';

    public function __construct()
    {
        $this->templateDir = TEMPLATES_PATH;
    }

    /**
     * Render a template with data.
     * Pass $options['no_layout'] => true to skip layout wrapping (e.g. login page).
     */
    public function render(string $template, array $data = [], array $options = []): string
    {
        $file = $this->templateDir . '/' . $template . '.php';

        if (!file_exists($file)) {
            return '<p>Template not found: ' . htmlspecialchars($template) . '</p>';
        }

        extract($data, EXTR_SKIP);
        $lang = $data['lang'] ?? DEFAULT_LANGUAGE;
        $t = $data['t'] ?? function(string $key, string $default = '') { return $default; };

        ob_start();
        include $file;
        $content = ob_get_clean();

        // Skip layout when requested
        if (!empty($options['no_layout'])) {
            return $content;
        }

        // Wrap in layout
        $isAdmin = strpos($template, 'admin/') === 0;
        $layoutFile = $isAdmin
            ? $this->templateDir . '/admin/layout.php'
            : $this->templateDir . '/' . $this->layout . '.php';

        if (file_exists($layoutFile)) {
            $layoutData = $data;
            $layoutData['content'] = $content;
            extract($layoutData, EXTR_SKIP);
            ob_start();
            include $layoutFile;
            return ob_get_clean();
        }

        return $content;
    }

    /**
     * Escape HTML output
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Local date format
     */
    public static function date(string $isoDate, string $format = 'd.m.Y'): string
    {
        $ts = strtotime($isoDate);
        return $ts ? date($format, $ts) : $isoDate;
    }
}
