<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Session.php';
require_once __DIR__ . '/../src/Order.php';
require_once __DIR__ . '/../src/helpers.php';

$session = new Session();

if (!$session->isLoggedIn()) {
    redirect('/login.php');
}

$user = $session->getUser();
$orderModel = new Order();
$orders = $orderModel->getByUserId($user['id']);

renderHeader('My Orders', $session);
?>

<h1 class="mb-4">My Orders</h1>

<?php if (empty($orders)): ?>
    <div class="alert alert-info">
        You haven't placed any orders yet. <a href="/">Start shopping</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php $orderNumber = $order['order_number'] ?? str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT); ?>
                    <tr>
                        <td>#<?php echo e($orderNumber); ?></td>
                        <td><?php echo formatDate($order['created_at']); ?></td>
                        <td><?php echo formatPrice($order['total_amount']); ?></td>
                        <td><?php echo getPaymentMethodLabel($order['payment_method']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo getPaymentStatusBadge($order['payment_status']); ?>">
                                <?php echo getPaymentStatusLabel($order['payment_status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo getOrderStatusBadge($order['order_status']); ?>">
                                <?php echo getOrderStatusLabel($order['order_status']); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                View Details
                            </button>
                        </td>
                    </tr>

                    <!-- Order Details Modal -->
                    <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Order #<?php echo e($orderNumber); ?> Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Order Date:</strong> <?php echo formatDate($order['created_at']); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Payment Method:</strong> <?php echo getPaymentMethodLabel($order['payment_method']); ?>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Payment Status:</strong>
                                            <span class="badge bg-<?php echo getPaymentStatusBadge($order['payment_status']); ?>">
                                                <?php echo getPaymentStatusLabel($order['payment_status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Order Status:</strong>
                                            <span class="badge bg-<?php echo getOrderStatusBadge($order['order_status']); ?>">
                                                <?php echo getOrderStatusLabel($order['order_status']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Payment Details Section -->
                                    <div class="mb-4">
                                        <h6>Payment Details:</h6>
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Payment Method:</strong> <?php echo getPaymentMethodLabel($order['payment_method']); ?><br>
                                                        <strong>Total Amount:</strong> <?php echo formatPrice($order['total_amount']); ?><br>
                                                        <strong>Order Reference:</strong> #<?php echo e($orderNumber); ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <?php if ($order['payment_status'] === 'pending'): ?>
                                                            <div class="alert alert-warning alert-permanent mb-0">
                                                                <small><strong>⏳ Payment Required</strong><br>
                                                                Please complete your payment to process this order.</small>
                                                            </div>
                                                        <?php elseif ($order['payment_status'] === 'paid'): ?>
                                                            <div class="alert alert-success alert-permanent mb-0">
                                                                <small><strong>✅ Payment Confirmed</strong><br>
                                                                Your payment has been received and confirmed.</small>
                                                            </div>
                                                        <?php elseif ($order['payment_status'] === 'failed'): ?>
                                                            <div class="alert alert-danger alert-permanent mb-0">
                                                                <small><strong>❌ Payment Failed</strong><br>
                                                                There was an issue with your payment. Please contact support.</small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <hr>
                                                <div class="payment-instructions">
                                                    <?php if ($order['payment_status'] === 'pending'): ?>
                                                        <strong>Payment Instructions:</strong>
                                                        <?php if ($order['payment_method'] === 'paypal'): ?>
                                                            <div class="mt-2">
                                                                <p class="mb-1">💳 Send <strong><?php echo formatPrice($order['total_amount']); ?></strong> to our PayPal account:</p>
                                                                <div class="alert alert-info alert-permanent mb-2">
                                                                    <strong>📧 PayPal Email:</strong> <?php echo e(PAYPAL_EMAIL); ?>
                                                                </div>
                                                                <p class="text-muted mb-0">
                                                                    <small>⚠️ <strong>Important:</strong> Please include order number <strong>#<?php echo e($orderNumber); ?></strong> in the payment note/reference.</small>
                                                                </p>
                                                            </div>
                                                        <?php elseif ($order['payment_method'] === 'bank_transfer'): ?>
                                                            <div class="mt-2">
                                                                <p class="mb-1">🏦 Transfer <strong><?php echo formatPrice($order['total_amount']); ?></strong> to our bank account:</p>
                                                                <div class="alert alert-info alert-permanent mb-2">
                                                                    <strong>🏛️ Bank:</strong> <?php echo e(BANK_NAME); ?><br>
                                                                    <strong>👤 Account Holder:</strong> <?php echo e(BANK_ACCOUNT_HOLDER); ?><br>
                                                                    <strong>💳 IBAN:</strong> <?php echo e(BANK_IBAN); ?><br>
                                                                    <strong>🔗 BIC:</strong> <?php echo e(BANK_BIC); ?><br>
                                                                    <strong>📝 Reference:</strong> Order #<?php echo e($orderNumber); ?>
                                                                </div>
                                                                <p class="text-muted mb-0">
                                                                    <small>⚠️ <strong>Important:</strong> Please use order number <strong>#<?php echo e($orderNumber); ?></strong> as the payment reference to ensure fast processing.</small>
                                                                </p>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="mt-2">
                                                            <small class="text-muted">
                                                                <strong>⚡ Delivery Time:</strong> Your items will be delivered within 6-12 hours after payment confirmation.
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <strong>Payment Information:</strong>
                                                        <div class="mt-2">
                                                            <?php if ($order['payment_method'] === 'paypal'): ?>
                                                                <p class="mb-1">💳 Payment was made via PayPal</p>
                                                                <div class="alert alert-secondary alert-permanent mb-2">
                                                                    <small>
                                                                        <strong>📧 PayPal Email:</strong> <?php echo e(PAYPAL_EMAIL); ?><br>
                                                                        <strong>📝 Reference Used:</strong> Order #<?php echo e($orderNumber); ?>
                                                                    </small>
                                                                </div>
                                                            <?php elseif ($order['payment_method'] === 'bank_transfer'): ?>
                                                                <p class="mb-1">🏦 Payment was made via bank transfer</p>
                                                                <div class="alert alert-secondary alert-permanent mb-2">
                                                                    <small>
                                                                        <strong>🏛️ Bank:</strong> <?php echo e(BANK_NAME); ?><br>
                                                                        <strong>💳 IBAN:</strong> <?php echo e(BANK_IBAN); ?><br>
                                                                        <strong>📝 Reference Used:</strong> Order #<?php echo e($orderNumber); ?>
                                                                    </small>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($order['payment_status'] === 'paid'): ?>
                                                                <div class="mt-2">
                                                                    <small class="text-success">
                                                                        <strong>✅ Payment Confirmed:</strong> Your items are being processed for delivery.
                                                                    </small>
                                                                </div>
                                                            <?php elseif ($order['payment_status'] === 'failed'): ?>
                                                                <div class="mt-2">
                                                                    <small class="text-danger">
                                                                        <strong>❌ Payment Failed:</strong> Please contact support for assistance.
                                                                    </small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h6>Items:</h6>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $orderItems = $orderModel->getOrderItems($order['id']);
                                            if (empty($orderItems)) {
                                                echo '<tr><td colspan="4" class="text-center text-muted"><em>No items found for this order</em></td></tr>';
                                            }
                                            foreach ($orderItems as $item):
                                            ?>
                                                <tr>
                                                    <td><?php echo e($item['item_name']); ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td><?php echo formatPrice($item['price']); ?></td>
                                                    <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    <?php if ($order['notes']): ?>
                                        <div class="alert alert-info">
                                            <strong>Notes:</strong> <?php echo e($order['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php renderFooter(); ?>
