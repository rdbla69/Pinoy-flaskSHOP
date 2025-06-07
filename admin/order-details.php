<?php
require_once '../config/database.php';
require_once 'auth_check.php';

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details with customer information
$stmt = $conn->prepare("
    SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
           o.shipping_address as customer_address
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Order not found";
    header("Location: orders.php");
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Order status updated successfully";
        header("Location: order-details.php?id=" . $order_id);
        exit();
    } else {
        $_SESSION['error'] = "Error updating order status";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin Dashboard</title>
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

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-packed { background-color: #cce5ff; color: #004085; }
        .status-shipped { background-color: #d4edda; color: #155724; }
        .status-delivered { background-color: #d1e7dd; color: #0f5132; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fa-solid fa-droplet" style="color: #000000;"></i> Pinoy Flask Admin</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
            <a class="nav-link active" href="orders.php">
                <i class="bi bi-cart"></i>
                Orders
            </a>
            <a class="nav-link" href="products.php">
                <i class="bi bi-box"></i>
                Products
            </a>
            <a class="nav-link" href="users.php">
                <i class="bi bi-people"></i>
                Users
            </a>
            <a class="nav-link" href="settings.php">
                <i class="bi bi-gear"></i>
                Settings
            </a>
            <a class="nav-link" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1>Order Details #<?php echo $order_id; ?></h1>
            <a href="orders.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Order Information -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order Information</h5>
                        <form method="POST" class="d-flex align-items-center">
                            <select name="status" class="form-select me-2" style="width: auto;">
                                <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="packed" <?php echo $order['order_status'] === 'packed' ? 'selected' : ''; ?>>Packed</option>
                                <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-primary">
                                Update Status
                            </button>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Order Status</h6>
                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Order Date</h6>
                                <p class="mb-0"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo '../' . htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                         class="product-image me-3">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                        <td class="text-end"><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Name</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Email</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Phone</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Shipping Address</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 