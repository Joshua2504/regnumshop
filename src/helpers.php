<?php

require_once __DIR__ . '/Cart.php';

/**
 * Escape HTML output
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect($url) {
    // Check if headers already sent
    if (headers_sent($filename, $linenum)) {
        error_log("Cannot redirect - headers already sent in $filename on line $linenum");
        echo "<script>window.location.href = '$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit;
    }
    
    error_log("Redirecting to: $url");
    header('Location: ' . $url);
    exit;
}

/**
 * Format price
 */
function formatPrice($price) {
    return '€' . number_format($price, 2, ',', '.');
}

/**
 * Format date
 */
function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

/**
 * Get payment method label
 */
function getPaymentMethodLabel($method) {
    $labels = [
        'paypal' => 'PayPal',
        'bank_transfer' => 'Bank Transfer'
    ];
    return $labels[$method] ?? $method;
}

/**
 * Get payment status label
 */
function getPaymentStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed'
    ];
    return $labels[$status] ?? $status;
}

/**
 * Get payment status badge class
 */
function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'paid' => 'success',
        'failed' => 'danger'
    ];
    return $badges[$status] ?? 'secondary';
}

/**
 * Get order status label
 */
function getOrderStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
    return $labels[$status] ?? $status;
}

/**
 * Get order status badge class
 */
function getOrderStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'processing' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    return $badges[$status] ?? 'secondary';
}

/**
 * Render page header
 */
function renderHeader($title = '', $session = null) {
    $pageTitle = $title ? e($title) . ' - ' . APP_NAME : APP_NAME;
    $user = $session ? $session->getUser() : null;
    $cart = new Cart();
    $cartCount = $cart->getItemCount();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $pageTitle; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="/assets/css/style.css">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="/"><?php echo e(APP_NAME); ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/">Shop</a>
                        </li>
                        <?php if ($user): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/orders.php">My Orders</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/cart.php">
                                    Cart
                                    <?php if ($cartCount > 0): ?>
                                        <span class="badge bg-danger"><?php echo $cartCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <span class="nav-link">Welcome, <?php echo e($user['username']); ?></span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/logout.php">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/login.php">Login</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
        <main class="container my-4">
    <?php
}

/**
 * Render page footer
 */
function renderFooter() {
    ?>
        </main>
        <footer class="bg-dark text-white text-center py-3 mt-5">
            <div class="container">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo e(APP_NAME); ?>. All rights reserved.</p>
            </div>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="/assets/js/main.js"></script>
    </body>
    </html>
    <?php
}
