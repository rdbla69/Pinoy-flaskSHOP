<?php
require_once '../config/database.php';
require_once 'auth_check.php';

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['status']) && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param('si', $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
    header('Location: orders.php?success=1');
    exit;
}

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = [];
$params = [];
$types = '';

if ($status_filter) {
    $where[] = 'o.order_status = ?';
    $params[] = $status_filter;
    $types .= 's';
}
if ($search) {
    $where[] = '(o.id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id $where_sql ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin Dashboard</title>
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

        /* Card Styles */
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

        /* Table Styles */
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

        /* Search Box Styles */
        .search-box {
            background-color: white;
            border: 1px solid var(--gray-300);
            color: var(--gray-800);
        }

        .search-box:focus {
            border-color: var(--gray-500);
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.15);
        }

        /* Filter Styles */
        .form-select {
            border-color: var(--gray-300);
            color: var(--gray-800);
        }

        .form-select:focus {
            border-color: var(--gray-500);
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.15);
        }

        /* Button Styles */
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

        /* Status Colors */
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

        .btn-icon {
            padding: 0.25rem 0.5rem;
            color: var(--dark-color);
            transition: all 0.2s;
        }

        .btn-icon:hover {
            color: var(--accent-color);
            transform: translateY(-1px);
        }

        .status-dropdown {
            position: relative;
            display: inline-block;
        }

        .status-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 160px;
            justify-content: space-between;
            background-color: var(--gray-100);
            color: var(--gray-800);
        }

        .status-dropdown-toggle:hover {
            background-color: var(--gray-200);
        }

        .status-dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            z-index: 1000;
            min-width: 220px;
            padding: 0.5rem;
            margin: 0;
            background-color: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            max-height: 300px;
            overflow-y: auto;
        }

        .status-dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .status-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.75rem 1rem;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: var(--border-radius);
            color: var(--gray-800);
        }

        .status-dropdown-item:hover {
            background-color: var(--gray-100);
            color: var(--gray-900);
        }

        .status-dropdown-item.active {
            background-color: var(--gray-200);
            color: var(--gray-900);
            font-weight: 500;
        }

        .status-dropdown-item i {
            font-size: 1rem;
            color: var(--gray-600);
        }

        .status-dropdown-item:hover i {
            color: var(--gray-800);
        }

        .status-dropdown-item.active i {
            color: var(--gray-900);
        }

        /* Status Colors */
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

        .status-on_the_way {
            background-color: var(--gray-400);
            color: var(--gray-900);
        }

        .status-out_for_delivery {
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fa-solid fa-droplet" style="color:rgb(255, 255, 255);"></i> Pinoy Flask Admin</h4>
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
        <div class="page-header">
            <h2>Order Management</h2>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="packed" <?php echo $status_filter === 'packed' ? 'selected' : ''; ?>>Packed</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="on_the_way" <?php echo $status_filter === 'on_the_way' ? 'selected' : ''; ?>>On the Way</option>
                            <option value="out_for_delivery" <?php echo $status_filter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($order = $result->fetch_assoc()) {
                                $status_class = 'status-' . strtolower($order['order_status']);
                                echo "<tr>";
                                echo "<td>#" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . "</td>";
                                echo "<td>" . htmlspecialchars($order['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($order['email']) . "</td>";
                                echo "<td>₱" . number_format($order['total_amount'], 2) . "</td>";
                                echo "<td>
                                        <div class='status-dropdown'>
                                            <form method='post' class='status-form'>
                                                <input type='hidden' name='order_id' value='" . $order['id'] . "'>
                                                <input type='hidden' name='update_status' value='1'>
                                                <input type='hidden' name='status' value='" . $order['order_status'] . "'>
                                                <button type='button' class='btn btn-sm status-dropdown-toggle " . $status_class . "' onclick='toggleDropdown(this)'>
                                                    <i class='bi bi-" . getStatusIcon($order['order_status']) . "'></i>
                                                    " . getStatusLabel($order['order_status']) . "
                                                    <i class='bi bi-chevron-down ms-2'></i>
                                                </button>
                                                <div class='status-dropdown-menu'>
                                                    <button type='submit' name='status' value='pending' class='status-dropdown-item" . ($order['order_status'] === 'pending' ? ' active' : '') . "'>
                                                        <i class='bi bi-hourglass-split'></i> Pending
                                                    </button>
                                                    <button type='submit' name='status' value='packed' class='status-dropdown-item" . ($order['order_status'] === 'packed' ? ' active' : '') . "'>
                                                        <i class='bi bi-box-seam'></i> Packed
                                                    </button>
                                                    <button type='submit' name='status' value='shipped' class='status-dropdown-item" . ($order['order_status'] === 'shipped' ? ' active' : '') . "'>
                                                        <i class='bi bi-truck'></i> Shipped
                                                    </button>
                                                    <button type='submit' name='status' value='on_the_way' class='status-dropdown-item" . ($order['order_status'] === 'on_the_way' ? ' active' : '') . "'>
                                                        <i class='bi bi-truck'></i> On the Way
                                                    </button>
                                                    <button type='submit' name='status' value='out_for_delivery' class='status-dropdown-item" . ($order['order_status'] === 'out_for_delivery' ? ' active' : '') . "'>
                                                        <i class='bi bi-truck'></i> Out for Delivery
                                                    </button>
                                                    <button type='submit' name='status' value='delivered' class='status-dropdown-item" . ($order['order_status'] === 'delivered' ? ' active' : '') . "'>
                                                        <i class='bi bi-check-circle'></i> Delivered
                                                    </button>
                                                    <button type='submit' name='status' value='cancelled' class='status-dropdown-item" . ($order['order_status'] === 'cancelled' ? ' active' : '') . "'>
                                                        <i class='bi bi-x-circle'></i> Cancelled
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </td>";
                                echo "<td>" . date('M d, Y H:i', strtotime($order['created_at'])) . "</td>";
                                echo "<td>
                                        <div class='table-actions'>
                                            <a href='order-details.php?id=" . $order['id'] . "' class='btn-icon' title='View Details'>
                                                <i class='bi bi-eye'></i>
                                            </a>
                                        </div>
                                    </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4'>No orders found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDropdown(button) {
            const dropdown = button.nextElementSibling;
            const isOpen = dropdown.classList.contains('show');
            
            // Close all other dropdowns
            document.querySelectorAll('.status-dropdown-menu.show').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('show');
            
            // Update button state
            button.classList.toggle('active', !isOpen);
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.status-dropdown')) {
                document.querySelectorAll('.status-dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                document.querySelectorAll('.status-dropdown-toggle.active').forEach(button => {
                    button.classList.remove('active');
                });
            }
        });

        // Handle form submission
        document.querySelectorAll('.status-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const dropdown = this.querySelector('.status-dropdown-menu');
                if (dropdown.classList.contains('show')) {
                    e.preventDefault();
                    const status = e.submitter.value;
                    this.querySelector('input[name="status"]').value = status;
                    this.submit();
                }
            });
        });
    </script>

    <?php
    function getStatusIcon($status) {
        $status_icons = [
            'pending' => 'hourglass-split',
            'packed' => 'box-seam',
            'shipped' => 'truck',
            'on_the_way' => 'truck',
            'out_for_delivery' => 'truck',
            'delivered' => 'check-circle',
            'cancelled' => 'x-circle'
        ];
        return $status_icons[$status] ?? 'question-circle';
    }

    function getStatusLabel($status) {
        $status_labels = [
            'pending' => 'Pending',
            'packed' => 'Packed',
            'shipped' => 'Shipped',
            'on_the_way' => 'On the Way',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled'
        ];
        return $status_labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }
    ?>
</body>
</html> 