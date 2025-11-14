<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Session.php';
require_once __DIR__ . '/../src/Item.php';
require_once __DIR__ . '/../src/Cart.php';
require_once __DIR__ . '/../src/helpers.php';

$session = new Session();

if (!$session->isLoggedIn()) {
    redirect('/login.php');
}

$cart = new Cart();

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $itemId = $_POST['item_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;
        $cart->updateQuantity($itemId, $quantity);
        redirect('/cart.php');
    } elseif (isset($_POST['remove_item'])) {
        $itemId = $_POST['item_id'] ?? 0;
        $cart->removeItem($itemId);
        redirect('/cart.php');
    }
}

$cartItems = $cart->getItems();
$total = $cart->getTotal();

renderHeader('Shopping Cart', $session);
?>

<h1 class="mb-4">Shopping Cart</h1>

<?php if (empty($cartItems)): ?>
    <div class="alert alert-info">
        Your cart is empty. <a href="/">Browse items</a> to get started.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                <?php endif; ?>
                                <strong><?php echo e($item['name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo formatPrice($item['price']); ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control" style="width: 80px; display: inline-block;">
                                <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary">Update</button>
                            </form>
                        </td>
                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td colspan="2"><strong><?php echo formatPrice($total); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="/" class="btn btn-outline-secondary">Continue Shopping</a>
        <a href="/checkout.php" class="btn btn-primary">Proceed to Checkout</a>
    </div>
<?php endif; ?>

<?php renderFooter(); ?>
