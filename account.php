<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get order statistics
$total_orders = count($orders);
$pending_orders = 0;
$cancelled_orders = 0;
$total_spent = 0;
foreach ($orders as $order) {
    if ($order['order_status'] === 'pending') {
        $pending_orders++;
    }
    if ($order['order_status'] === 'cancelled') {
        $cancelled_orders++;
    }
    if ($order['order_status'] !== 'cancelled') {
        $total_spent += $order['total_amount'];
    }
}

// Calculate active orders (excluding cancelled)
$active_orders = $total_orders - $cancelled_orders;

$success_message = '';
$error_message = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
        $error_message = "Only JPG, PNG and GIF files are allowed.";
    } elseif ($_FILES['profile_picture']['size'] > $max_size) {
        $error_message = "File size must be less than 5MB.";
    } else {
        $upload_dir = 'assets/images/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            // Delete old profile picture if it exists and is not the default
            if ($user['profile_picture'] && $user['profile_picture'] !== 'assets/images/default-avatar.png') {
                @unlink($user['profile_picture']);
            }
            
            // Update database
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $upload_path, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Profile picture updated successfully.";
                $user['profile_picture'] = $upload_path;
            } else {
                $error_message = "Failed to update profile picture in database.";
                @unlink($upload_path);
            }
        } else {
            $error_message = "Failed to upload profile picture.";
        }
    }
}

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validate input
    $errors = [];

    if (empty($username)) {
        $errors[] = "Username is required";
    }

    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Email is already taken";
        }
    }

    // Password change validation
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }

        if (empty($new_password)) {
            $errors[] = "New password is required when changing password";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }

    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Update user information
            $update_sql = "UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?";
            $params = [$username, $full_name, $email, $phone];
            $types = "ssss";

            // If password is being changed
            if (!empty($current_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql .= ", password = ?";
                $params[] = $hashed_password;
                $types .= "s";
            }

            $update_sql .= " WHERE id = ?";
            $params[] = $user_id;
            $types .= "i";

            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, $types, ...$params);
            
            if (mysqli_stmt_execute($update_stmt)) {
                mysqli_commit($conn);
                $success_message = "Profile updated successfully";
                
                // Update session data
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                throw new Exception("Error updating profile");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Water Bottle Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <style>
        .dashboard-header {
            background: #1a1a1a;
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #1a1a1a;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e0e0e0;
        }

        .dashboard-card .card-header {
            background: none;
            border-bottom: 1px solid #e0e0e0;
            padding: 1.5rem;
        }

        .dashboard-card .card-body {
            padding: 1.5rem;
        }

        /* Enhanced Side Navigation */
        .side-nav {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        .side-nav .nav-link {
            color: #666;
            padding: 1.2rem 1.5rem;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .side-nav .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .side-nav .nav-link:hover {
            background: #f8f8f8;
            color: #1a1a1a;
            border-left-color: #1a1a1a;
        }

        .side-nav .nav-link.active {
            background: #f8f8f8;
            color: #1a1a1a;
            border-left-color: #1a1a1a;
            font-weight: 500;
        }

        /* Tab Content Styling */
        .tab-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .tab-pane {
            padding: 2rem;
        }

        .tab-pane .card-header {
            background: none;
            border-bottom: 1px solid #e0e0e0;
            padding: 1.5rem 2rem;
        }

        .tab-pane .card-body {
            padding: 2rem;
        }

        /* Form Styling */
        .form-control {
            border: 1px solid #e0e0e0;
            padding: 0.8rem 1rem;
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: #1a1a1a;
            box-shadow: 0 0 0 0.2rem rgba(26, 26, 26, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
        }

        /* Button Styling */
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #1a1a1a;
            border-color: #1a1a1a;
            color: white;
        }

        .btn-primary:hover {
            background: #333;
            border-color: #333;
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            color: #1a1a1a;
            border-color: #1a1a1a;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: #1a1a1a;
            border-color: #1a1a1a;
            color: white;
        }

        .btn-light {
            background: #f5f5f5;
            border-color: #e0e0e0;
            color: #1a1a1a;
        }

        .btn-light:hover {
            background: #e0e0e0;
            border-color: #d0d0d0;
            color: #1a1a1a;
        }

        /* Table Styling */
        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending { background: #f5f5f5; color: #666; }
        .status-processing { background: #e0e0e0; color: #333; }
        .status-shipped { background: #d0d0d0; color: #1a1a1a; }
        .status-delivered { background: #c0c0c0; color: #000; }
        .status-cancelled { background: #f0f0f0; color: #666; }

        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        /* Alert Styling */
        .alert-success {
            background: #f5f5f5;
            border-color: #e0e0e0;
            color: #1a1a1a;
        }

        .alert-danger {
            background: #f5f5f5;
            border-color: #e0e0e0;
            color: #1a1a1a;
        }

        /* Links */
        a {
            color: #1a1a1a;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #333;
        }

        /* Address Card Styling */
        .address-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .address-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .address-info {
            color: #495057;
        }

        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
        }

        .dropdown-item i {
            width: 1rem;
        }

        .profile-picture-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 2rem;
        }

        .profile-picture {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .profile-picture-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #0d6efd;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-picture-upload:hover {
            background: #0b5ed7;
            transform: scale(1.1);
        }

        .profile-picture-upload input {
            display: none;
        }

        .cropper-container {
            max-height: 400px;
        }

        .modal-body {
            padding: 0;
        }

        .img-container {
            max-height: 400px;
            width: 100%;
        }

        .img-container img {
            max-width: 100%;
            max-height: 400px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                    <p class="mb-0 mt-2">Manage your account and track your orders</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="products.php" class="btn btn-light">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-value"><?php echo $active_orders; ?></div>
                    <div class="stat-label">Active Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $pending_orders; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="stat-value"><?php echo $cancelled_orders; ?></div>
                    <div class="stat-label">Cancelled Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($total_spent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3">
                <div class="side-nav">
                    <div class="nav flex-column nav-pills" role="tablist">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#profile">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#orders">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Orders</span>
                        </button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#security">
                            <i class="fas fa-lock"></i>
                            <span>Security</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($success_message): ?>
                                    <div class="alert alert-success">
                                        <?php echo $success_message; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($error_message): ?>
                                    <div class="alert alert-danger">
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="profile-picture-container">
                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                         alt="Profile Picture" 
                                         class="profile-picture"
                                         id="profile-preview">
                                    <label class="profile-picture-upload" for="profile-picture-input">
                                        <i class="fas fa-camera"></i>
                                        <input type="file" 
                                               id="profile-picture-input" 
                                               accept="image/*"
                                               onchange="handleImageSelect(event)">
                                    </label>
                                </div>

                                <form method="POST" action="" id="profile-form">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="username" 
                                                   name="username" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="full_name" class="form-label">Full Name</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="full_name" 
                                                   name="full_name" 
                                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                                   required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="email" 
                                                   name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="tel" 
                                                   class="form-control" 
                                                   id="phone" 
                                                   name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <h5 class="mb-3">Change Password</h5>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="current_password" 
                                                   name="current_password">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="new_password" 
                                                   name="new_password">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirm_password" 
                                                   name="confirm_password">
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Order History</h5>
                                <?php if (!empty($orders)): ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelAllOrdersModal">
                                            <i class="fas fa-ban me-2"></i>Cancel All Orders
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearHistoryModal">
                                            <i class="fas fa-trash me-2"></i>Clear History
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (empty($orders)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-shopping-bag"></i>
                                        <p>No orders found.</p>
                                        <a href="products.php" class="btn btn-primary">
                                            <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Date</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="order-status status-<?php echo $order['order_status']; ?>">
                                                                <?php echo ucfirst($order['order_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
                                                                <?php if ($order['order_status'] === 'pending'): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#cancelOrderModal" 
                                                                            data-order-id="<?php echo $order['id']; ?>"
                                                                            data-order-number="#<?php echo $order['id']; ?>">
                                                                        <i class="fas fa-times"></i> Cancel
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['password_error'])): ?>
                                    <div class="alert alert-danger">
                                        <?php 
                                        echo $_SESSION['password_error'];
                                        unset($_SESSION['password_error']);
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <form action="update-password.php" method="POST">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Password must be at least 6 characters long</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel order <span id="orderNumber"></span>?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <form action="cancel-order.php" method="POST" class="d-inline">
                        <input type="hidden" name="order_id" id="orderId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Cancel Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel All Orders Modal -->
    <div class="modal fade" id="cancelAllOrdersModal" tabindex="-1" aria-labelledby="cancelAllOrdersModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelAllOrdersModalLabel">Cancel All Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel all pending orders?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <form action="cancel-all-orders.php" method="POST" class="d-inline">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban me-2"></i>Cancel All Orders
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear History Modal -->
    <div class="modal fade" id="clearHistoryModal" tabindex="-1" aria-labelledby="clearHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clearHistoryModalLabel">Clear Order History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to clear your entire order history?</p>
                    <p class="text-muted">This action cannot be undone and will permanently delete all your order records.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <form action="clear-order-history.php" method="POST" class="d-inline">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Clear History
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Cropper Modal -->
    <div class="modal fade" id="cropperModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crop Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="img-container">
                        <img id="cropper-image" src="" alt="Image to crop">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="crop-button">Crop & Save</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        let cropper;
        const modal = new bootstrap.Modal(document.getElementById('cropperModal'));

        function handleImageSelect(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const image = document.getElementById('cropper-image');
                    image.src = e.target.result;
                    modal.show();
                    
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(image, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                };
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('crop-button').addEventListener('click', function() {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 300
            });

            canvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('profile_picture', blob, 'profile.jpg');

                fetch('account.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    document.documentElement.innerHTML = html;
                    modal.hide();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to upload profile picture');
                });
            }, 'image/jpeg', 0.9);
        });

        // Password validation
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (currentPassword || newPassword || confirmPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Please enter your current password');
                    return;
                }
                if (!newPassword) {
                    e.preventDefault();
                    alert('Please enter a new password');
                    return;
                }
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match');
                    return;
                }
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('New password must be at least 6 characters long');
                    return;
                }
            }
        });

        // Password toggle functionality
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleButton = passwordInput.nextElementSibling;
            const icon = toggleButton.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html> 