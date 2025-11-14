<?php

class Email {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;

    public function __construct() {
        $this->smtpHost = SMTP_HOST;
        $this->smtpPort = SMTP_PORT;
        $this->smtpUsername = SMTP_USERNAME;
        $this->smtpPassword = SMTP_PASSWORD;
        $this->fromEmail = SMTP_FROM_EMAIL;
        $this->fromName = SMTP_FROM_NAME;
    }

    /**
     * Send email using SMTP
     */
    public function send($to, $subject, $body, $isHtml = true) {
        try {
            // Create SMTP connection
            $socket = $this->smtpConnect();

            if (!$socket) {
                error_log('Failed to connect to SMTP server');
                return false;
            }

            // SMTP conversation
            $this->smtpCommand($socket, "EHLO " . $_SERVER['SERVER_NAME'] ?? 'localhost');
            $this->smtpCommand($socket, "AUTH LOGIN");
            $this->smtpCommand($socket, base64_encode($this->smtpUsername));
            $this->smtpCommand($socket, base64_encode($this->smtpPassword));
            $this->smtpCommand($socket, "MAIL FROM: <{$this->fromEmail}>");
            $this->smtpCommand($socket, "RCPT TO: <{$to}>");
            $this->smtpCommand($socket, "DATA");

            // Build email headers
            $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $headers .= "Reply-To: {$this->fromEmail}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";

            if ($isHtml) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }

            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

            // Send email
            $message = $headers . "Subject: {$subject}\r\n\r\n{$body}\r\n.\r\n";
            fwrite($socket, $message);
            $this->smtpResponse($socket);

            // Close connection
            $this->smtpCommand($socket, "QUIT");
            fclose($socket);

            return true;

        } catch (Exception $e) {
            error_log('Email send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Connect to SMTP server
     */
    private function smtpConnect() {
        $socket = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);

        if (!$socket) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }

        $this->smtpResponse($socket);
        return $socket;
    }

    /**
     * Send SMTP command
     */
    private function smtpCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        return $this->smtpResponse($socket);
    }

    /**
     * Get SMTP response
     */
    private function smtpResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($userEmail, $username, $orderId, $orderTotal, $paymentMethod) {
        $subject = "Order Confirmation #$orderId - " . APP_NAME;

        $paymentMethodLabel = $paymentMethod === 'paypal' ? 'PayPal' : 'Bank Transfer';

        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f8f9fa; padding: 20px; margin-top: 20px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .info-box { background-color: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 15px 0; }
                .button { display: inline-block; padding: 12px 24px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Order Confirmed!</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>$username</strong>,</p>

                    <p>Thank you for your order! We have received your order and it will be processed once we confirm your payment.</p>

                    <h3>Order Details:</h3>
                    <ul>
                        <li><strong>Order Number:</strong> #$orderId</li>
                        <li><strong>Total Amount:</strong> " . formatPrice($orderTotal) . "</li>
                        <li><strong>Payment Method:</strong> $paymentMethodLabel</li>
                    </ul>

                    <div class='info-box'>
                        <h4>⚡ Delivery Time: 6-12 Hours</h4>
                        <p>Your items will be delivered within 6-12 hours after payment confirmation.</p>
                    </div>

                    <h3>Next Steps:</h3>
                    " . ($paymentMethod === 'paypal' ? "
                    <p>Please send your payment to: <strong>" . PAYPAL_EMAIL . "</strong></p>
                    <p>Include order number <strong>#$orderId</strong> in the payment note.</p>
                    " : "
                    <p>Please transfer the amount to our bank account:</p>
                    <ul>
                        <li><strong>Bank:</strong> " . BANK_NAME . "</li>
                        <li><strong>IBAN:</strong> " . BANK_IBAN . "</li>
                        <li><strong>BIC:</strong> " . BANK_BIC . "</li>
                        <li><strong>Reference:</strong> Order #$orderId</li>
                    </ul>
                    ") . "

                    <p>You can track your order status at any time by logging into your account.</p>

                    <p style='text-align: center;'>
                        <a href='" . APP_URL . "/orders.php' class='button'>View Order Status</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>" . APP_NAME . " - Fast & Secure Delivery</p>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->send($userEmail, $subject, $body, true);
    }

    /**
     * Send admin notification email for new order
     */
    public function sendAdminOrderNotification($orderId, $username, $orderTotal, $paymentMethod) {
        $subject = "New Order Received #$orderId - " . APP_NAME;

        $paymentMethodLabel = $paymentMethod === 'paypal' ? 'PayPal' : 'Bank Transfer';

        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #198754; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f8f9fa; padding: 20px; margin-top: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛒 New Order Received</h1>
                </div>
                <div class='content'>
                    <h3>Order Details:</h3>
                    <ul>
                        <li><strong>Order Number:</strong> #$orderId</li>
                        <li><strong>Customer:</strong> $username</li>
                        <li><strong>Total Amount:</strong> " . formatPrice($orderTotal) . "</li>
                        <li><strong>Payment Method:</strong> $paymentMethodLabel</li>
                        <li><strong>Time:</strong> " . date('d.m.Y H:i:s') . "</li>
                    </ul>

                    <p style='text-align: center;'>
                        <a href='" . APP_URL . "/admin/index.php' class='button'>View Order in Admin Panel</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->send($this->fromEmail, $subject, $body, true);
    }

    /**
     * Send order status update email
     */
    public function sendOrderStatusUpdate($userEmail, $username, $orderId, $newStatus) {
        $subject = "Order #$orderId Status Update - " . APP_NAME;

        $statusMessages = [
            'processing' => 'Your order is being processed and will be delivered soon!',
            'completed' => 'Your order has been completed and delivered!',
            'cancelled' => 'Your order has been cancelled.'
        ];

        $message = $statusMessages[$newStatus] ?? 'Your order status has been updated.';

        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f8f9fa; padding: 20px; margin-top: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Order Status Updated</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>$username</strong>,</p>

                    <p>$message</p>

                    <p><strong>Order Number:</strong> #$orderId</p>
                    <p><strong>New Status:</strong> " . ucfirst($newStatus) . "</p>

                    <p style='text-align: center;'>
                        <a href='" . APP_URL . "/orders.php' class='button'>View Order Details</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->send($userEmail, $subject, $body, true);
    }
}
