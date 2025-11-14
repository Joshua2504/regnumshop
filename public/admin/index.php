<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Session.php';
require_once __DIR__ . '/../../src/Order.php';
require_once __DIR__ . '/../../src/Email.php';
require_once __DIR__ . '/../../src/helpers.php';

$session = new Session();
$email = new Email();

// Check admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth = new Auth();
    if ($auth->adminLogin($username, $password)) {
        $session->set('is_admin', true);
        redirect('/admin/index.php');
    } else {
        $loginError = 'Invalid admin credentials.';
    }
}

if (!$session->isAdmin()) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - <?php echo e(APP_NAME); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title text-center mb-4">Admin Login</h3>
                            <?php if (isset($loginError)): ?>
                                <div class="alert alert-danger"><?php echo e($loginError); ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" name="admin_login" class="btn btn-primary w-100">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$orderModel = new Order();

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_payment_status'])) {
        $orderId = $_POST['order_id'] ?? 0;
        $paymentStatus = $_POST['payment_status'] ?? '';
        $orderModel->updatePaymentStatus($orderId, $paymentStatus);
        redirect('/admin/index.php');
    } elseif (isset($_POST['update_order_status'])) {
        $orderId = $_POST['order_id'] ?? 0;
        $orderStatus = $_POST['order_status'] ?? '';

        // Get order and user info for email
        $order = $orderModel->getById($orderId);
        if ($order) {
            $orderModel->updateOrderStatus($orderId, $orderStatus);

            // Send status update email if status changed to processing or completed
            if (in_array($orderStatus, ['processing', 'completed']) && $order['order_status'] !== $orderStatus) {
                $db = Database::getInstance();
                $stmt = $db->query('SELECT email FROM users WHERE id = ?', [$order['user_id']]);
                $user = $stmt->fetch();

                if ($user && $user['email']) {
                    $email->sendOrderStatusUpdate(
                        $user['email'],
                        $order['username'],
                        $orderId,
                        $orderStatus
                    );
                }
            }
        }

        redirect('/admin/index.php');
    } elseif (isset($_POST['add_notes'])) {
        $orderId = $_POST['order_id'] ?? 0;
        $notes = $_POST['notes'] ?? '';
        $orderModel->addNotes($orderId, $notes);
        redirect('/admin/index.php');
    }
}

$orders = $orderModel->getAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/index.php">Admin Panel</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/admin/index.php">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/items.php">Items</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/analytics.php">Analytics</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/">View Shop</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container my-4">
        <h1 class="mb-4">Order Management</h1>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">No orders yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
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
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo e($order['username']); ?></td>
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
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                        Manage
                                    </button>
                                </td>
                            </tr>

                            <!-- Order Management Modal -->
                            <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Manage Order #<?php echo $order['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Customer:</strong> <?php echo e($order['username']); ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Order Date:</strong> <?php echo formatDate($order['created_at']); ?>
                                                </div>
                                            </div>

                                            <h6>Items:</h6>
                                            <table class="table table-sm mb-4">
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

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <form method="POST" class="mb-3">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <label class="form-label">Payment Status</label>
                                                        <div class="input-group">
                                                            <select name="payment_status" class="form-select">
                                                                <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                                <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                            </select>
                                                            <button type="submit" name="update_payment_status" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="col-md-6">
                                                    <form method="POST" class="mb-3">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <label class="form-label">Order Status</label>
                                                        <div class="input-group">
                                                            <select name="order_status" class="form-select">
                                                                <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                                <option value="completed" <?php echo $order['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                            </select>
                                                            <button type="submit" name="update_order_status" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <label class="form-label">Notes</label>
                                                <textarea name="notes" class="form-control mb-2" rows="3"><?php echo e($order['notes'] ?? ''); ?></textarea>
                                                <button type="submit" name="add_notes" class="btn btn-primary">Save Notes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
