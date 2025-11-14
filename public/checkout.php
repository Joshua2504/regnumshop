<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Session.php';
require_once __DIR__ . '/../src/Item.php';
require_once __DIR__ . '/../src/Cart.php';
require_once __DIR__ . '/../src/Order.php';
require_once __DIR__ . '/../src/Analytics.php';
require_once __DIR__ . '/../src/Email.php';
require_once __DIR__ . '/../src/helpers.php';

$session = new Session();
$analytics = new Analytics();
$email = new Email();

// Track page view
$analytics->trackPageView();

if (!$session->isLoggedIn()) {
    redirect('/login.php');
}

$cart = new Cart();
$cartItems = $cart->getItems();

// Preserve success info (post/redirect/get)
$storedSuccess = $session->get('checkout_success');
if ($storedSuccess) {
    $success = true;
    $orderId = $storedSuccess['order_id'];
    $orderNumber = $storedSuccess['order_number'];
    $paymentMethod = $storedSuccess['payment_method'];
    $total = $storedSuccess['total'];
    $session->remove('checkout_success');
} else {
    if (empty($cartItems)) {
        redirect('/cart.php');
    }
    $total = $cart->getTotal();
}
$error = '';
$success = $success ?? false;
$orderId = $orderId ?? null;
$orderNumber = $orderNumber ?? null;
$paymentMethod = $paymentMethod ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$session->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $paymentMethod = $_POST['payment_method'] ?? '';

        if (!in_array($paymentMethod, ['paypal', 'bank_transfer'])) {
            $error = 'Please select a valid payment method.';
        } else {
            // Validate cart
            if (!$cart->validate()) {
                $error = 'Some items in your cart are no longer available or out of stock.';
            } else {
                $orderModel = new Order();
                $user = $session->getUser();

                $orderResult = $orderModel->create($user['id'], $cartItems, $paymentMethod);

                if ($orderResult) {
                    $orderId = $orderResult['id'];
                    $orderNumber = $orderResult['order_number'];
                    // Track order
                    $analytics->trackOrder($orderNumber, $total);

                    // Send email notifications
                    if ($user['email']) {
                        $email->sendOrderConfirmation(
                            $user['email'],
                            $user['username'],
                            $orderNumber,
                            $total,
                            $paymentMethod
                        );
                    }

                    // Send admin notification
                    $email->sendAdminOrderNotification(
                        $orderNumber,
                        $user['username'],
                        $total,
                        $paymentMethod
                    );

                    $cart->clear();
                    $session->set('checkout_success', [
                        'order_id' => $orderId,
                        'order_number' => $orderNumber,
                        'payment_method' => $paymentMethod,
                        'total' => $total
                    ]);
                    redirect('/checkout.php?success=1');
                } else {
                    $error = 'Failed to create order. Please try again.';
                }
            }
        }
    }
}

renderHeader('Checkout', $session);
?>

<?php if ($success && $orderNumber): ?>
    <div class="alert alert-success">
        <h4 class="alert-heading">Order Placed Successfully!</h4>
        <p>Your order #<?php echo e($orderNumber); ?> has been placed successfully.</p>
    </div>

    <!-- Delivery Time Notice -->
    <div class="alert alert-primary border-primary" style="border-width: 2px;">
        <h5 class="alert-heading">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16" style="vertical-align: text-bottom;">
                <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.615.789a6.996 6.996 0 0 0-.418-.302zm1.834 1.79a6.99 6.99 0 0 0-.653-.796l.724-.69c.27.285.52.59.747.91l-.818.576zm.744 1.352a7.08 7.08 0 0 0-.214-.468l.893-.45a7.976 7.976 0 0 1 .45 1.088l-.95.313a7.023 7.023 0 0 0-.179-.483zm.53 2.507a6.991 6.991 0 0 0-.1-1.025l.985-.17c.067.386.106.778.116 1.17l-1 .025zm-.131 1.538c.033-.17.06-.339.081-.51l.993.123a7.957 7.957 0 0 1-.23 1.155l-.964-.267c.046-.165.086-.332.12-.501zm-.952 2.379c.184-.29.346-.594.486-.908l.914.405c-.16.36-.345.706-.555 1.038l-.845-.535zm-.964 1.205c.122-.122.239-.248.35-.378l.758.653a8.073 8.073 0 0 1-.401.432l-.707-.707z"/>
                <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0v1z"/>
                <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/>
            </svg>
            Delivery Timeframe
        </h5>
        <p class="mb-0">
            <strong>Your items will be delivered within 6-12 hours</strong> after payment confirmation. Our team processes orders quickly to ensure you receive your in-game items as soon as possible.
        </p>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Payment Instructions</h5>

            <?php
            $order = (new Order())->getById($orderId);
            $displayOrderNumber = $order['order_number'] ?? $orderNumber;
            if ($order['payment_method'] === 'paypal'):
            ?>
                <p>Please send <strong><?php echo formatPrice($total); ?></strong> to our PayPal account:</p>
                <div class="alert alert-info">
                    <strong>PayPal Email:</strong> <?php echo e(PAYPAL_EMAIL); ?>
                </div>
                <p class="text-muted">Please include your order number <strong>#<?php echo e($displayOrderNumber); ?></strong> in the payment note.</p>
            <?php else: ?>
                <p>Please transfer <strong><?php echo formatPrice($total); ?></strong> to our bank account:</p>
                <div class="alert alert-info">
                    <strong>Bank:</strong> <?php echo e(BANK_NAME); ?><br>
                    <strong>Account Holder:</strong> <?php echo e(BANK_ACCOUNT_HOLDER); ?><br>
                    <strong>IBAN:</strong> <?php echo e(BANK_IBAN); ?><br>
                    <strong>BIC:</strong> <?php echo e(BANK_BIC); ?><br>
                    <strong>Reference:</strong> Order #<?php echo e($displayOrderNumber); ?>
                </div>
            <?php endif; ?>

            <p>Your order will be processed once we receive the payment. You can check the status in <a href="/orders.php">My Orders</a>.</p>
        </div>
    </div>

    <div class="mt-4">
        <a href="/" class="btn btn-primary">Back to Shop</a>
        <a href="/orders.php" class="btn btn-outline-primary">View My Orders</a>
    </div>

<?php else: ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <h1 class="mb-4">Checkout</h1>

    <!-- Delivery Time Notice -->
    <div class="alert alert-success mb-4">
        <h6 class="alert-heading mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-truck" viewBox="0 0 16 16" style="vertical-align: text-bottom;">
                <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5v-7zm1.294 7.456A1.999 1.999 0 0 1 4.732 11h5.536a2.01 2.01 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456zM12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12v4zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
            </svg>
            Fast Delivery
        </h6>
        <p class="mb-0">
            <strong>Delivery Time: 6-12 hours</strong> after payment confirmation
        </p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td><?php echo e($item['name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                <td><strong><?php echo formatPrice($total); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Payment Method</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $session->getCsrfToken(); ?>">

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal" required>
                                <label class="form-check-label" for="paypal">
                                    <strong>PayPal</strong><br>
                                    <small class="text-muted">Pay via PayPal</small>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer" required>
                                <label class="form-check-label" for="bank_transfer">
                                    <strong>Bank Transfer</strong><br>
                                    <small class="text-muted">Pay via bank transfer</small>
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <p class="mb-1">
                                After placing your order you can optionally submit your Discord handle or character name so we can deliver even faster.
                            </p>
                            <p class="mb-1">
                                Prefer to skip? No problem – we will reach out via your account email instead.
                            </p>
                            <p class="mb-0 text-muted">
                                Once the order is fulfilled we immediately obfuscate this contact data for your privacy.
                            </p>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Place Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php renderFooter(); ?>
