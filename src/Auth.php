<?php
/**
 * Authentication - Single admin user
 */
class Auth
{
    /**
     * Check if the current session is authenticated as admin
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Attempt login with username and password
     */
    public static function login(string $username, string $password): bool
    {
        if ($username !== ADMIN_USERNAME) {
            return false;
        }

        if (!password_verify($password, ADMIN_PASSWORD_HASH)) {
            return false;
        }

        // Regenerate session ID on login
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return true;
    }

    /**
     * Logout
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /**
     * Require admin authentication - redirects to login if not authed
     */
    public static function requireAdmin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /manage/login');
            exit;
        }

        // Session fixation check
        if (($_SESSION['admin_ip'] ?? '') !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            self::logout();
            header('Location: /manage/login');
            exit;
        }
    }
}
