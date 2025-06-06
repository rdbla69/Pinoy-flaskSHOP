<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    exit('Order ID is required');
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    exit('Order not found');
}

// Get order items
$stmt = $conn->prepare("SELECT oi.*, p.name, p.image, p.description 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="order-details">
    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="mb-2">Order Information</h6>
            <p class="mb-1"><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
            <p class="mb-1"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($order['created_at'])); ?></p>
            <p class="mb-1"><strong>Status:</strong> 
                <span class="order-status status-<?php echo $order['order_status']; ?>">
                    <?php echo ucfirst($order['order_status']); ?>
                </span>
            </p>
        </div>
        <div class="col-md-6">
            <h6 class="mb-2">Payment Information</h6>
            <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
            <p class="mb-1"><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
        </div>
    </div>

    <h6 class="mb-3">Order Items</h6>
    <div class="order-items">
        <?php foreach ($order_items as $item): ?>
            <div class="order-item mb-3">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="img-fluid rounded">
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($item['description']); ?></p>
                        <p class="text-muted small mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="mb-0">
                            <span class="text-muted">Price:</span>
                            ₱<?php echo number_format($item['price'], 2); ?>
                        </p>
                        <p class="mb-0">
                            <span class="text-muted">Subtotal:</span>
                            <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="order-summary mt-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">Order Summary</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="order-total">Total</span>
                    <span class="order-total">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</div> 