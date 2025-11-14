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
