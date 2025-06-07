<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get some basic statistics
$stats = [
    'total_orders' => 0,
    'total_products' => 0,
    'total_users' => 0,
    'total_revenue' => 0
];

// Get total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result) {
    $stats['total_orders'] = $result->fetch_assoc()['count'];
}

// Get total products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result) {
    $stats['total_products'] = $result->fetch_assoc()['count'];
}

// Get total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $stats['total_users'] = $result->fetch_assoc()['count'];
}

// Get total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'");
if ($result) {
    $stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pinoy Flask</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #000000;
            --secondary-color: #333333;
            --success-color: #1a1a1a;
            --danger-color: #262626;
            --warning-color: #404040;
            --info-color: #4d4d4d;
            --light-color: #f5f5f5;
            --dark-color: #1a1a1a;
            --sidebar-width: 250px;
            --header-height: 60px;
            --border-radius: 8px;
            --box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--gray-100);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background-color: var(--gray-900);
            padding: 1rem;
            color: white;
            box-shadow: var(--box-shadow);
        }

        .sidebar-header {
            padding: 1rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--gray-800);
        }

        .sidebar-header h4 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .nav-link {
            color: var(--gray-400);
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            margin-bottom: 0.5rem;
        }

        .nav-link:hover {
            color: white;
            background-color: var(--gray-800);
        }

        .nav-link.active {
            color: white;
            background-color: var(--gray-800);
        }

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            border: 1px solid var(--gray-200);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .stat-card i {
            font-size: 1.5rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: var(--gray-700);
            margin: 0;
            font-size: 0.875rem;
        }

        /* Table Styles */
        .card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 1.25rem;
            font-weight: 500;
            color: var(--gray-900);
        }

        .card-header h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .table {
            margin: 0;
        }

        .table th {
            font-weight: 500;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            color: var(--gray-800);
            border-bottom: 1px solid var(--gray-200);
        }

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .badge.bg-success { background-color: #e8f5e9 !important; color: #2e7d32 !important; }
        .badge.bg-warning { background-color: #fff3e0 !important; color: #ef6c00 !important; }
        .badge.bg-danger { background-color: #ffebee !important; color: #c62828 !important; }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 50rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: var(--gray-200);
            color: var(--gray-700);
        }

        .status-packed {
            background-color: var(--gray-300);
            color: var(--gray-800);
        }

        .status-shipped {
            background-color: var(--gray-400);
            color: var(--gray-900);
        }

        .status-delivered {
            background-color: var(--gray-800);
            color: white;
        }

        .status-cancelled {
            background-color: var(--gray-100);
            color: var(--gray-600);
        }

        .btn-primary {
            background-color: var(--gray-900);
            border-color: var(--gray-900);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--gray-800);
            border-color: var(--gray-800);
        }

        .btn-outline-primary {
            color: var(--gray-900);
            border-color: var(--gray-900);
        }

        .btn-outline-primary:hover {
            background-color: var(--gray-900);
            border-color: var(--gray-900);
            color: white;
        }

        .status-dropdown .btn {
            background-color: var(--gray-100);
            border: 1px solid var(--gray-300);
            color: var(--gray-800);
        }

        .status-dropdown .btn:hover {
            background-color: var(--gray-200);
            border-color: var(--gray-400);
        }

        .status-dropdown-menu {
            background-color: white;
            border: 1px solid var(--gray-200);
            box-shadow: var(--box-shadow);
        }

        .status-dropdown-item {
            color: var(--gray-800);
        }

        .status-dropdown-item:hover {
            background-color: var(--gray-100);
            color: var(--gray-900);
        }

        .status-dropdown-item.active {
            background-color: var(--gray-200);
            color: var(--gray-900);
        }

        .status-dropdown-item i {
            color: var(--gray-600);
        }

        .status-dropdown-item:hover i {
            color: var(--gray-800);
        }

        .status-dropdown-item.active i {
            color: var(--gray-900);
        }

        .search-box {
            background-color: white;
            border: 1px solid var(--gray-300);
            color: var(--gray-800);
        }

        .search-box:focus {
            border-color: var(--gray-500);
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.15);
        }

        .form-select {
            border-color: var(--gray-300);
            color: var(--gray-800);
        }

        .form-select:focus {
            border-color: var(--gray-500);
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.15);
        }

        .cancelled-orders {
            background-color: var(--gray-100);
        }

        .cancelled-orders .card-header {
            background-color: var(--gray-100);
            border-bottom: 1px solid var(--gray-200);
        }

        .cancelled-orders .table th,
        .cancelled-orders .table td {
            border-color: var(--gray-200);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
         
            <h4><i class="fa-solid fa-droplet" style="color:rgb(255, 255, 255);"></i> Pinoy Flask Admin</h4>
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="bi bi-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="bi bi-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h2>Dashboard</h2>
        </div>
        
        <div class="row g-4 mb-4">
            <!-- Statistics Cards -->
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-cart"></i>
                    <h3><?php echo number_format($stats['total_orders']); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-box"></i>
                    <h3><?php echo number_format($stats['total_products']); ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-people"></i>
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-currency-dollar"></i>
                    <h3>₱<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Recent Orders</h5>
                <a href="orders.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("
                            SELECT o.*, u.email 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            ORDER BY o.created_at DESC 
                            LIMIT 5
                        ");
                        while ($order = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['order_status'] == 'completed' ? 'success' : 
                                        ($order['order_status'] == 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 