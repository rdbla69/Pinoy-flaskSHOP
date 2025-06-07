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

// Get user's addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <title>My Account - Pinoyflask</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .welcome-banner {
            background-color: #000;
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .welcome-section {
            background-color: #000;
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .welcome-section h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 500;
        }
        .welcome-section p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }
        .account-container {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 2rem 0;
        }
        .nav-pills {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .nav-pills .nav-link {
            color: #000;
            border-radius: 0.5rem;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .nav-pills .nav-link:hover {
            background-color: #CBD1CE;
            color: white;
        }
        .nav-pills .nav-link.active {
            background-color: #000;
            color: white;
        }
        .nav-pills .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 0.75rem;
        }
        .profile-picture-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
        }
        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .profile-picture-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #000;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid white;
        }
        .profile-picture-upload:hover {
            background: #333;
            transform: scale(1.1);
        }
        .profile-picture-upload input[type="file"] {
            display: none;
        }
        .card {
            border: 1px solid #e9ecef;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem;
            border-radius: 1rem 1rem 0 0 !important;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #ced4da;
        }
        .form-control:focus {
            border-color: #000;
            box-shadow: 0 0 0 0.25rem rgba(0, 0, 0, 0.25);
        }
        .btn-outline-primary {
            color: #000;
            border-color: #000;
        }
        .btn-outline-primary:hover {
            background-color: #000;
            border-color: #000;
            color: white;
        }
        .btn-primary {
            background-color: #000;
            border-color: #000;
            color: white;
        }
        .btn-primary:hover {
            background-color: #333;
            border-color: #333;
            color: white;
        }
        .text-primary {
            color: #000 !important;
        }
        .bg-primary {
            background-color: #000 !important;
        }
        .address-card {
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .address-card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .address-card .btn-group {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .address-card:hover .btn-group {
            opacity: 1;
        }
        .address-card .btn-group .btn-outline-primary:hover {
            color: white;
        }
        .address-card .btn-group .btn-outline-danger:hover {
            color: white;
        }
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .badge.bg-primary {
            background-color: #000 !important;
        }
        .badge.bg-warning {
            background-color: #333 !important;
            color: white;
        }
        .badge.bg-success {
            background-color: #000 !important;
            color: white;
        }
        .badge.bg-danger {
            background-color: #000 !important;
            color: white;
        }
        .badge.bg-info {
            background-color: #333 !important;
            color: white;
        }
        .badge.bg-secondary {
            background-color: #666 !important;
            color: white;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #000;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state i {
            font-size: 3rem;
            color: #666;
            margin-bottom: 1rem;
        }
        .tab-content {
            padding-top: 1rem;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        .text-muted {
            color: #666 !important;
        }
        .alert-info {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            color: #000;
        }
        .alert-success {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            color: #000;
        }
        .alert-danger {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            color: #000;
        }
        a {
            color: #000;
            text-decoration: none;
        }
        a:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="welcome-banner">
        <div class="container">
            <h2 style="margin: 0; font-size: 1.8rem; font-weight: 500;">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
            <p style="margin: 0.5rem 0 0; opacity: 0.9;">Manage your account settings and view your orders</p>
        </div>
    </div>

    <div class="account-container">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3">
                    <div class="card mb-4">
                        <div class="card-body p-0">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="pill" data-bs-target="#dashboard" type="button" role="tab">
                                    <i class="fas fa-tachometer-alt"></i>Dashboard
                                </button>
                                <button class="nav-link" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab">
                                    <i class="fas fa-user"></i>Profile
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#addresses">
                                    <i class="fas fa-map-marker-alt"></i>Addresses
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#orders">
                                    <i class="fas fa-shopping-bag"></i>Orders
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#security">
                                    <i class="fas fa-shield-alt"></i>Security
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-9">
                    <div class="tab-content">
                        <!-- Dashboard Tab -->
                        <div class="tab-pane fade show active" id="dashboard">
                            <div class="row">
                                <!-- Order Statistics -->
                                <div class="col-md-3 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-shopping-bag fa-2x text-primary mb-3"></i>
                                            <h3 class="mb-2"><?php echo $total_orders; ?></h3>
                                            <p class="text-muted mb-0">Total Orders</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-clock fa-2x text-primary mb-3"></i>
                                            <h3 class="mb-2"><?php echo $pending_orders; ?></h3>
                                            <p class="text-muted mb-0">Pending Orders</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-check-circle fa-2x text-primary mb-3"></i>
                                            <h3 class="mb-2"><?php echo $active_orders; ?></h3>
                                            <p class="text-muted mb-0">Active Orders</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-peso-sign fa-2x text-primary mb-3"></i>
                                            <h3 class="mb-2">₱<?php echo number_format($total_spent, 2); ?></h3>
                                            <p class="text-muted mb-0">Total Spent</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Orders -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Orders</h5>
                                    <a href="#orders" class="btn btn-primary btn-sm" data-bs-toggle="pill">
                                        View All Orders
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($orders)): ?>
                                        <div class="empty-state">
                                            <i class="fas fa-shopping-bag"></i>
                                            <h5>No Orders Yet</h5>
                                            <p class="text-muted">You haven't placed any orders yet.</p>
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
                                                    <?php 
                                                    $recent_orders = array_slice($orders, 0, 5); // Show only 5 most recent orders
                                                    foreach ($recent_orders as $order): 
                                                    ?>
                                                        <tr>
                                                            <td>#<?php echo $order['id']; ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $order['order_status'] === 'pending' ? 'warning' : 
                                                                        ($order['order_status'] === 'processing' ? 'info' : 
                                                                        ($order['order_status'] === 'completed' ? 'success' : 
                                                                        ($order['order_status'] === 'cancelled' ? 'danger' : 'secondary'))); 
                                                                ?>">
                                                                    <?php echo ucfirst($order['order_status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
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

                        <!-- Profile Tab -->
                        <div class="tab-pane fade" id="profile">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Profile Information</h5>
                                </div>
                                <div class="card-body">
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

                                    <form method="POST" action="" id="profile-form">
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

                                        <div class="d-grid gap-2">
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Addresses Tab -->
                        <div class="tab-pane fade" id="addresses">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">My Addresses</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                        <i class="fas fa-plus me-2"></i>Add New Address
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($addresses)): ?>
                                        <div class="empty-state">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <h5>No Addresses Found</h5>
                                            <p class="text-muted">Add your first delivery address to get started.</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                                <i class="fas fa-plus me-2"></i>Add New Address
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($addresses as $address): ?>
                                                <div class="col-md-6 mb-4">
                                                    <div class="address-card">
                                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($address['address_name']); ?></h6>
                                                                <p class="text-muted mb-0">
                                                                    <?php echo htmlspecialchars($address['street_address']); ?><br>
                                                                    <?php echo htmlspecialchars($address['barangay']); ?>, <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?>
                                                                </p>
                                                            </div>
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#editAddressModal" 
                                                                        data-address-id="<?php echo $address['id']; ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteAddressModal" 
                                                                        data-address-id="<?php echo $address['id']; ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <?php if ($address['is_default']): ?>
                                                            <span class="badge bg-success">Default Address</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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
                                        <button type="button" class="btn btn-link text-danger p-0 me-3" data-bs-toggle="modal" data-bs-target="#cancelAllOrdersModal">
                                            <i class="fas fa-times-circle me-1"></i>Cancel All
                                        </button>
                                        <button type="button" class="btn btn-link text-danger p-0" data-bs-toggle="modal" data-bs-target="#clearHistoryModal">
                                            <i class="fas fa-trash me-1"></i>Clear History
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($orders)): ?>
                                        <div class="empty-state">
                                            <i class="fas fa-shopping-bag"></i>
                                            <h5>No Orders Yet</h5>
                                            <p class="text-muted">You haven't placed any orders yet.</p>
                                            <a href="shop.php" class="btn btn-primary">
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
                                                                <span class="badge bg-<?php 
                                                                    echo $order['order_status'] === 'pending' ? 'warning' : 
                                                                        ($order['order_status'] === 'processing' ? 'info' : 
                                                                        ($order['order_status'] === 'completed' ? 'success' : 
                                                                        ($order['order_status'] === 'cancelled' ? 'danger' : 'secondary'))); 
                                                                ?>">
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
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php 
                                            echo $_SESSION['password_error'];
                                            unset($_SESSION['password_error']);
                                            ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form action="update-password.php" method="POST">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="current_password" class="form-label">Current Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
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
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">Password must be at least 6 characters long</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
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
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAddressForm" action="add-address.php" method="POST">
                        <div class="mb-3">
                            <label for="address_name" class="form-label">Address Name</label>
                            <input type="text" class="form-control" id="address_name" name="address_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="street_address" class="form-label">Street Address</label>
                            <input type="text" class="form-control" id="street_address" name="street_address" required>
                        </div>
                        <div class="mb-3">
                            <label for="barangay" class="form-label">Barangay</label>
                            <input type="text" class="form-control" id="barangay" name="barangay" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">Set as default address</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Add Address</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAddressForm" action="edit-address.php" method="POST">
                        <input type="hidden" name="address_id" id="edit_address_id">
                        <div class="mb-3">
                            <label for="edit_address_name" class="form-label">Address Name</label>
                            <input type="text" class="form-control" id="edit_address_name" name="address_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_street_address" class="form-label">Street Address</label>
                            <input type="text" class="form-control" id="edit_street_address" name="street_address" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_barangay" class="form-label">Barangay</label>
                            <input type="text" class="form-control" id="edit_barangay" name="barangay" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="edit_city" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_state" class="form-label">State</label>
                                <input type="text" class="form-control" id="edit_state" name="state" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="edit_postal_code" name="postal_code" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_default" name="is_default">
                            <label class="form-check-label" for="edit_is_default">Set as default address</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Address</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Address Modal -->
    <div class="modal fade" id="deleteAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this address?</p>
                    <form id="deleteAddressForm" action="delete-address.php" method="POST">
                        <input type="hidden" name="address_id" id="delete_address_id">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Address</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel order <span id="cancelOrderNumber"></span>?</p>
                    <form action="cancel-order.php" method="POST">
                        <input type="hidden" name="order_id" id="cancel_order_id">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Order</button>
                            <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel All Orders Modal -->
    <div class="modal fade" id="cancelAllOrdersModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel All Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel all pending orders? This action cannot be undone.</p>
                    <form action="cancel-all-orders.php" method="POST">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal">No, Keep Orders</button>
                            <button type="submit" class="btn btn-link text-danger">Yes, Cancel All</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear History Modal -->
    <div class="modal fade" id="clearHistoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clear Order History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to clear your entire order history? This action cannot be undone.</p>
                    <form action="clear-order-history.php" method="POST">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal">No, Keep History</button>
                            <button type="submit" class="btn btn-link text-danger">Yes, Clear All</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Your trusted source for quality products and excellent service.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="shop.php" class="text-white">Shop</a></li>
                        <li><a href="about.php" class="text-white">About</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> +63 123 456 7890</li>
                        <li><i class="fas fa-envelope me-2"></i> info@pinoyflaskshop.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Manila, Philippines</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 Pinoy Flask Shop. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script>
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

        // Handle address modals
        document.querySelectorAll('[data-bs-target="#editAddressModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const addressId = this.getAttribute('data-address-id');
                const addressCard = this.closest('.address-card');
                
                // Get address details from the card
                const addressName = addressCard.querySelector('h6').textContent;
                const streetAddress = addressCard.querySelector('p').textContent.split(',')[0].trim();
                const barangay = addressCard.querySelector('p').textContent.split(',')[1].trim();
                const cityStateZip = addressCard.querySelector('p').textContent.split(',')[2].trim();
                const city = cityStateZip.split(',')[0].trim();
                const stateZip = cityStateZip.split(',')[1].trim();
                const state = stateZip.split(' ')[0].trim();
                const postalCode = stateZip.split(' ')[1].trim();
                const isDefault = addressCard.querySelector('.badge.bg-primary') !== null;

                // Set form values
                document.getElementById('edit_address_id').value = addressId;
                document.getElementById('edit_address_name').value = addressName;
                document.getElementById('edit_street_address').value = streetAddress;
                document.getElementById('edit_barangay').value = barangay;
                document.getElementById('edit_city').value = city;
                document.getElementById('edit_state').value = state;
                document.getElementById('edit_postal_code').value = postalCode;
                document.getElementById('edit_is_default').checked = isDefault;
            });
        });

        document.querySelectorAll('[data-bs-target="#deleteAddressModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const addressId = this.getAttribute('data-address-id');
                document.getElementById('delete_address_id').value = addressId;
            });
        });

        // Handle cancel order modal
        document.querySelectorAll('[data-bs-target="#cancelOrderModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const orderNumber = this.getAttribute('data-order-number');
                document.getElementById('cancel_order_id').value = orderId;
                document.getElementById('cancelOrderNumber').textContent = orderNumber;
            });
        });

        // Profile picture preview
        function handleImageSelect(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }

        // Handle form submissions
        document.getElementById('addAddressForm').addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });

        document.getElementById('editAddressForm').addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });

        document.getElementById('deleteAddressForm').addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
    </script>
</body>
</html> 