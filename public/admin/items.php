<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Session.php';
require_once __DIR__ . '/../../src/Item.php';
require_once __DIR__ . '/../../src/helpers.php';

$session = new Session();

if (!$session->isAdmin()) {
    redirect('/admin/index.php');
}

$itemModel = new Item();
$message = '';
$messageType = '';

// Handle item operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_item'])) {
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'image_url' => $_POST['image_url'] ?? '',
            'stock' => $_POST['stock'] ?? 0,
            'active' => isset($_POST['active']) ? 1 : 0
        ];

        if ($itemModel->create($data)) {
            $message = 'Item created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create item.';
            $messageType = 'danger';
        }
    } elseif (isset($_POST['update_item'])) {
        $itemId = $_POST['item_id'] ?? 0;
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'image_url' => $_POST['image_url'] ?? '',
            'stock' => $_POST['stock'] ?? 0,
            'active' => isset($_POST['active']) ? 1 : 0
        ];

        if ($itemModel->update($itemId, $data)) {
            $message = 'Item updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update item.';
            $messageType = 'danger';
        }
    } elseif (isset($_POST['delete_item'])) {
        $itemId = $_POST['item_id'] ?? 0;

        if ($itemModel->delete($itemId)) {
            $message = 'Item deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete item.';
            $messageType = 'danger';
        }
    }
}

$items = $itemModel->getAllItems();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Management - Admin</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Item Management</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createItemModal">
                Add New Item
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td>
                                <?php if ($item['image_url']): ?>
                                    <img src="<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary d-inline-block" style="width: 50px; height: 50px;"></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($item['name']); ?></td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td><?php echo $item['stock']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $item['active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $item['active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editItemModal<?php echo $item['id']; ?>">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteItemModal<?php echo $item['id']; ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Item Modal -->
                        <div class="modal fade" id="editItemModal<?php echo $item['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Item</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" name="name" class="form-control" value="<?php echo e($item['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="3"><?php echo e($item['description']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Price (€)</label>
                                                <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $item['price']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Image URL</label>
                                                <input type="url" name="image_url" class="form-control" value="<?php echo e($item['image_url']); ?>">
                                                <small class="text-muted">Enter a full URL to an image</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Stock</label>
                                                <input type="number" name="stock" class="form-control" value="<?php echo $item['stock']; ?>" required>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" name="active" class="form-check-input" id="active<?php echo $item['id']; ?>" <?php echo $item['active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="active<?php echo $item['id']; ?>">Active</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_item" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Item Modal -->
                        <div class="modal fade" id="deleteItemModal<?php echo $item['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete Item</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to delete <strong><?php echo e($item['name']); ?></strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <form method="POST">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="delete_item" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Item Modal -->
    <div class="modal fade" id="createItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (€)</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" name="image_url" class="form-control">
                            <small class="text-muted">Enter a full URL to an image</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control" value="0" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="active" class="form-check-input" id="activeNew" checked>
                            <label class="form-check-label" for="activeNew">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_item" class="btn btn-primary">Create Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
