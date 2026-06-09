<?php
/**
 * reCAPTCHA v2 verification
 */
class Captcha
{
    /**
     * Verify a reCAPTCHA token
     */
    public static function verify(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'secret' => RECAPTCHA_SECRET_KEY,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]),
                'timeout' => 10,
            ],
        ]);

        $result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if ($result === false) {
            return false;
        }

        $response = json_decode($result, true);
        if (!is_array($response)) {
            return false;
        }

        return !empty($response['success']) && ($response['score'] ?? 1) >= 0.5;
    }

    /**
     * Output the reCAPTCHA widget HTML
     */
    public static function widget(): string
    {
        return '<div class="g-recaptcha" data-sitekey="' . RECAPTCHA_SITE_KEY . '"></div>';
    }

    /**
     * Output the reCAPTCHA script tag
     */
    public static function script(): string
    {
        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }
}
