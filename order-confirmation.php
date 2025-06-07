<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order_id is set
if (!isset($_GET['order_id'])) {
    header('Location: account.php');
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header('Location: account.php');
    exit();
}

// Get order items
$sql = "SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format date
$order_date = date('F j, Y g:i A', strtotime($order['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Pinoy-flaskSHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <!-- Success Message -->
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h2 class="mb-2">Order Confirmed!</h2>
                            <p class="text-muted">Thank you for your purchase. Your order has been received.</p>
                        </div>

                        <!-- Order Details -->
                        <div class="border rounded p-3 mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Order Information</h6>
                                    <p class="mb-1"><strong>Order Number:</strong> #<?php echo $order['id']; ?></p>
                                    <p class="mb-1"><strong>Date:</strong> <?php echo $order_date; ?></p>
                                    <p class="mb-1"><strong>Status:</strong> 
                                        <span class="badge bg-<?php 
                                            echo $order['order_status'] === 'pending' ? 'warning' : 
                                                ($order['order_status'] === 'processing' ? 'info' : 
                                                ($order['order_status'] === 'shipped' ? 'primary' : 'success')); 
                                        ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </p>
                                    <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Shipping Address</h6>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <h6 class="mb-3">Order Items</h6>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" 
                                                     class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                <span><?php echo $item['name']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-end">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                        <td class="text-end">₱<?php echo number_format($order['shipping_fee'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>₱<?php echo number_format($order['total_amount'] + $order['shipping_fee'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="products.php" class="btn btn-outline-dark">
                                <i class="fas fa-shopping-cart me-2"></i>Continue Shopping
                            </a>
                            <a href="account.php?tab=orders" class="btn btn-dark">
                                <i class="fas fa-list me-2"></i>View Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 