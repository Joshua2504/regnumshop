<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Session.php';
require_once __DIR__ . '/../../src/Analytics.php';
require_once __DIR__ . '/../../src/helpers.php';

$session = new Session();

if (!$session->isAdmin()) {
    redirect('/admin/index.php');
}

$analytics = new Analytics();
$todayStats = $analytics->getTodayStats();
$totalStats = $analytics->getTotalStats();
$topPages = $analytics->getTopPages(10);
$recentEvents = $analytics->getRecentEvents(20);
$summary = $analytics->getSummary(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin</title>
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
                    <a class="nav-link active" href="/admin/analytics.php">Analytics</a>
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
        <h1 class="mb-4">Analytics Dashboard</h1>

        <!-- Today's Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Today's Page Views</h5>
                        <h2 class="mb-0"><?php echo $todayStats['page_views'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Unique Visitors</h5>
                        <h2 class="mb-0"><?php echo $todayStats['unique_visitors'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Today's Logins</h5>
                        <h2 class="mb-0"><?php echo $todayStats['logins'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Today's Orders</h5>
                        <h2 class="mb-0"><?php echo $todayStats['orders'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Stats -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All-Time Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Total Page Views:</strong> <?php echo number_format($totalStats['total_page_views'] ?? 0); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Total Logins:</strong> <?php echo number_format($totalStats['total_logins'] ?? 0); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Total Orders:</strong> <?php echo number_format($totalStats['total_orders'] ?? 0); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Total Revenue:</strong> <?php echo formatPrice($totalStats['total_revenue'] ?? 0); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 30-Day Summary -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Last 30 Days Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Page Views</th>
                                        <th>Visitors</th>
                                        <th>Logins</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($summary as $day): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($day['date'])); ?></td>
                                            <td><?php echo $day['page_views']; ?></td>
                                            <td><?php echo $day['unique_visitors']; ?></td>
                                            <td><?php echo $day['logins']; ?></td>
                                            <td><?php echo $day['orders']; ?></td>
                                            <td><?php echo formatPrice($day['revenue']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Pages -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Pages (Last 30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topPages as $page): ?>
                                    <tr>
                                        <td><?php echo e($page['page_url']); ?></td>
                                        <td><?php echo $page['views']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Events -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEvents as $event): ?>
                                    <tr>
                                        <td><?php echo date('H:i:s', strtotime($event['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo $event['event_type'] === 'order' ? 'success' :
                                                    ($event['event_type'] === 'login' ? 'info' :
                                                    ($event['event_type'] === 'add_to_cart' ? 'warning' : 'secondary'));
                                            ?>">
                                                <?php echo str_replace('_', ' ', $event['event_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo e($event['username'] ?? 'Guest'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
