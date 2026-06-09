<?php
/**
 * Email sender using PHPMailer
 */
class Mailer
{
    /**
     * Send an email
     */
    public static function send(string $to, string $subject, string $htmlBody, string $plainBody = '', string $replyTo = '', string $replyName = ''): bool
    {
        require_once LIB_PATH . '/PHPMailer/PHPMailer.php';
        require_once LIB_PATH . '/PHPMailer/SMTP.php';
        require_once LIB_PATH . '/PHPMailer/Exception.php';

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);

            if ($replyTo) {
                $mail->addReplyTo($replyTo, $replyName);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            if ($plainBody) {
                $mail->AltBody = $plainBody;
            } else {
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            }

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send contact form notification to admin
     */
    public static function sendContactNotification(string $name, string $email, string $message): bool
    {
        $subject = 'Contact form: ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $html = sprintf(
            '<h2>New Contact Form Message</h2>
            <p><strong>Name:</strong> %s</p>
            <p><strong>Email:</strong> %s</p>
            <p><strong>Message:</strong></p>
            <p>%s</p>',
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
            nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'))
        );
        $plain = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";

        return self::send(ADMIN_ORDER_EMAIL, $subject, $html, $plain, $email, $name);
    }

    /**
     * Send order confirmation to admin
     */
    public static function sendOrderNotification(array $order): bool
    {
        $subject = 'New Order #' . htmlspecialchars($order['id'] ?? 'unknown', ENT_QUOTES, 'UTF-8');

        $itemsHtml = '';
        $total = 0;
        foreach ($order['items'] ?? [] as $item) {
            $itemPrice = (float)($item['unit_price'] ?? 0) * (int)($item['quantity'] ?? 0);
            $total += $itemPrice;
            $itemName = self::translationText($item['name'] ?? 'Unknown');
            $itemsHtml .= sprintf(
                '<tr><td>%s</td><td>%d</td><td>%.2f€</td><td>%.2f€</td></tr>',
                htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8'),
                (int)($item['quantity'] ?? 0),
                (float)($item['unit_price'] ?? 0),
                $itemPrice
            );
        }

        $fulfillment = ($order['fulfillment'] ?? 'delivery') === 'pickup' ? 'Pickup at location' : 'Delivery';
        $address = '';
        if ($fulfillment === 'Delivery') {
            $addr = $order['address'] ?? [];
            $address = sprintf(
                '<p><strong>Address:</strong> %s %s, %s %s, %s</p>',
                htmlspecialchars($addr['street'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($addr['city'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($addr['postal'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($addr['county'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($addr['country'] ?? 'Estonia', ENT_QUOTES, 'UTF-8')
            );
        }

        $html = sprintf(
            '<h2>New Order Received</h2>
            <p><strong>Order ID:</strong> %s</p>
            <p><strong>Customer:</strong> %s (%s)</p>
            <p><strong>Phone:</strong> %s</p>
            <p><strong>Fulfillment:</strong> %s</p>
            %s
            <p><strong>Notes:</strong> %s</p>
            <h3>Items</h3>
            <table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse">
                <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr>
                %s
                <tr><td colspan="3"><strong>Total</strong></td><td><strong>%.2f€</strong></td></tr>
            </table>',
            htmlspecialchars($order['id'] ?? '', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($order['name'] ?? '', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($order['email'] ?? '', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($fulfillment, ENT_QUOTES, 'UTF-8'),
            $address,
            htmlspecialchars($order['notes'] ?? '', ENT_QUOTES, 'UTF-8'),
            $itemsHtml,
            $total
        );

        return self::send(ADMIN_ORDER_EMAIL, $subject, $html, '', $order['email'] ?? '', $order['name'] ?? '');
    }

    private static function translationText($value): string
    {
        if (!is_array($value)) {
            return (string)($value ?? '');
        }

        $fallback = $value[DEFAULT_LANGUAGE] ?? reset($value);
        return is_scalar($fallback) ? (string)$fallback : '';
    }
}
