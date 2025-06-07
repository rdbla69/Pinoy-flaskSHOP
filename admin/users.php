<?php
require_once '../config/database.php';
require_once 'auth_check.php';

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $user_id = (int)$_POST['user_id'];
        $new_status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE users SET email_verified = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User status updated successfully";
        } else {
            $_SESSION['error'] = "Error updating user status";
        }
    }
    // Handle user deletion
    elseif (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First delete order items (due to foreign key constraint with orders)
            $stmt = $conn->prepare("DELETE oi FROM order_items oi 
                                   INNER JOIN orders o ON oi.order_id = o.id 
                                   WHERE o.user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Then delete orders
            $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Delete user's addresses
            $stmt = $conn->prepare("DELETE FROM addresses WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Finally delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            $_SESSION['success'] = "User deleted successfully";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
        }
    }
    // Handle user deactivation
    elseif (isset($_POST['deactivate_user'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE users SET email_verified = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User deactivated successfully";
        } else {
            $_SESSION['error'] = "Error deactivating user";
        }
    }
    
    header("Location: users.php");
    exit();
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$where_clause = "";
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause .= " WHERE (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($status_filter)) {
    $where_clause .= empty($where_clause) ? " WHERE" : " AND";
    $where_clause .= " u.email_verified = ?";
    $params[] = $status_filter;
    $types .= "i";
}

// Get total users count
$count_sql = "SELECT COUNT(*) as total FROM users u" . $where_clause;
if (!empty($params)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_result = $stmt->get_result()->fetch_assoc();
    $total_users = $total_result['total'];
} else {
    $total_result = $conn->query($count_sql)->fetch_assoc();
    $total_users = $total_result['total'];
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$total_pages = ceil($total_users / $per_page);
$offset = ($page - 1) * $per_page;

// Get users with pagination
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
        (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND order_status != 'cancelled') as total_spent
        FROM users u" . $where_clause . " 
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .status-active { background-color: #d1e7dd; color: #0f5132; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }

        /* Add styles for delete confirmation modal */
        .modal-header {
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 1.25rem;
        }
        
        .modal-footer {
            border-top: 1px solid var(--gray-200);
            padding: 1rem 1.25rem;
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
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
            <a class="nav-link" href="orders.php">
                <i class="bi bi-cart"></i>
                Orders
            </a>
            <a class="nav-link" href="products.php">
                <i class="bi bi-box"></i>
                Products
            </a>
            <a class="nav-link active" href="users.php">
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
            <h1>User Management</h1>
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

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control search-box" name="search" 
                                   placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Verified</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Unverified</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="users.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-lg"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        No users found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo '../' . htmlspecialchars($user['profile_picture']); ?>" 
                                                     alt="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                     class="user-avatar me-3">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo $user['total_orders']; ?></td>
                                        <td>₱<?php echo number_format($user['total_spent'] ?? 0, 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['email_verified'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['email_verified'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                        data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="user-details.php?id=<?php echo $user['id']; ?>">
                                                            <i class="bi bi-eye"></i> View Details
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $user['email_verified'] ? '0' : '1'; ?>">
                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                <i class="bi bi-<?php echo $user['email_verified'] ? 'person-x' : 'person-check'; ?>"></i>
                                                                <?php echo $user['email_verified'] ? 'Unverify' : 'Verify'; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="deactivate_user" class="dropdown-item text-warning" 
                                                                    <?php echo !$user['email_verified'] ? 'disabled' : ''; ?>>
                                                                <i class="bi bi-person-x"></i> Deactivate
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirm Delete</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                                                            <p><strong>User:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" name="delete_user" class="btn btn-danger">
                                                                    <i class="bi bi-trash"></i> Delete User
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 