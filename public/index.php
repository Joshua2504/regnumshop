<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Session.php';
require_once __DIR__ . '/../src/Item.php';
require_once __DIR__ . '/../src/Cart.php';
require_once __DIR__ . '/../src/Analytics.php';
require_once __DIR__ . '/../src/helpers.php';

$session = new Session();
$itemModel = new Item();
$cart = new Cart();
$analytics = new Analytics();

// Track page view
$analytics->trackPageView();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$session->isLoggedIn()) {
        redirect('/login.php');
    }

    $itemId = $_POST['item_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;

    if ($cart->addItem($itemId, $quantity)) {
        // Track add to cart event
        $item = $itemModel->getById($itemId);
        if ($item) {
            $analytics->trackAddToCart($itemId, $item['name'], $quantity);
        }

        $message = 'Item added to cart successfully!';
        $messageType = 'success';
    } else {
        $message = 'Failed to add item to cart. Item may be out of stock.';
        $messageType = 'danger';
    }
}

$items = $itemModel->getActiveItems();

renderHeader('Shop', $session);
?>

<div class="text-center mb-4">
    <h1 class="mb-3">Champions of Regnum Shop</h1>
    <p class="text-muted">
        Pick a pack, pay, and get your Magnanit delivered. No fluff—just quick trading like back in the day.
    </p>
    <?php if (!$session->isLoggedIn()): ?>
        <a href="/login.php" class="btn btn-primary btn-sm">Login with COR account</a>
    <?php endif; ?>
</div>

<?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo e($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<section id="shop-items">

<!-- Delivery Time Banner -->
<div class="alert alert-success alert-permanent mb-4">
    <div class="row align-items-center">
        <div class="col-md-9">
            <h5 class="mb-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-lightning-charge" viewBox="0 0 16 16" style="vertical-align: text-bottom;">
                    <path d="M11.251.068a.5.5 0 0 1 .227.58L9.677 6.5H13a.5.5 0 0 1 .364.843l-8 8.5a.5.5 0 0 1-.842-.49L6.323 9.5H3a.5.5 0 0 1-.364-.843l8-8.5a.5.5 0 0 1 .615-.09z"/>
                </svg>
                <strong>Fast Delivery: 6-12 hours</strong> after payment confirmation | Secure Payment via PayPal or Bank Transfer
            </h5>
        </div>
        <div class="col-md-3 text-md-end mt-2 mt-md-0">
            <?php if (!$session->isLoggedIn()): ?>
                <a href="/login.php" class="btn btn-outline-primary btn-sm">Login to Purchase</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="alert alert-info">
        No items available at the moment. Please check back later.
    </div>
<?php else: ?>
    <div class="container">
        <?php foreach ($items as $item): ?>
            <div class="row mb-3 border-bottom pb-3 align-items-center">
                <div class="col-md-3">
                    <?php if ($item['image_url']): ?>
                        <img src="<?php echo e($item['image_url']); ?>" class="img-fluid rounded" alt="<?php echo e($item['name']); ?>" style="max-height: 150px;">
                    <?php else: ?>
                        <div class="bg-secondary d-flex align-items-center justify-content-center rounded" style="height: 150px;">
                            <span class="text-white">No Image</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5><?php echo e($item['name']); ?></h5>
                    <p class="text-muted"><?php echo e($item['description']); ?></p>
                </div>
                <div class="col-md-3 text-end">
                    <p class="mb-2">
                        <span class="fs-4 fw-bold text-warning"><?php echo $session->isLoggedIn() ? formatPrice($item['price']) : '?,??€'; ?></span>
                    </p>
                    <?php if ($session->isLoggedIn()): ?>
                        <form method="POST" class="d-flex gap-2 justify-content-end">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $item['stock']; ?>" class="form-control" style="width: 80px;">
                            <button type="submit" name="add_to_cart" class="btn btn-primary" <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                <?php echo $item['stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-outline-primary">Login to Purchase</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</section>

<?php renderFooter(); ?>
