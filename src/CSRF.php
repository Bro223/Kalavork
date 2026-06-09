<?php
/**
 * CSRF Protection
 */
class CSRF
{
    /**
     * Generate a CSRF token and store in session
     */
    public static function generate(): string
    {
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }

    /**
     * Verify CSRF token from request
     */
    public static function verify(?string $token = null): bool
    {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        $valid = hash_equals($_SESSION['csrf_token'], $token);

        // Token can only be used once
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);

        return $valid;
    }

    /**
     * Get the current token (for forms)
     */
    public static function token(): string
    {
        return $_SESSION['csrf_token'] ?? self::generate();
    }

    /**
     * Hidden input field for forms
     */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    /**
     * Check if token exists and is fresh
     */
    public static function hasToken(): bool
    {
        return !empty($_SESSION['csrf_token']);
    }
}
