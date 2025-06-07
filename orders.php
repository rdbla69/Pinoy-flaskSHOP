<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user's orders
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Water Bottle Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .order-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-body {
            padding: 1.5rem;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-price {
            font-weight: 500;
            color: #1a1a1a;
        }

        .order-total {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }

        .empty-state i {
            font-size: 4rem;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            background: #f8f9fa;
            width: 100px;
            height: 100px;
            line-height: 100px;
            border-radius: 50%;
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .empty-state:hover i {
            transform: scale(1.1);
        }

        .empty-state h3 {
            font-size: 1.8rem;
            color: #1a1a1a;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .empty-state .btn-primary {
            padding: 0.4rem 1.2rem;
            font-size: 0.85rem;
            border-radius: 3px;
            font-weight: 400;
            background: #1a1a1a;
            border: 1px solid #1a1a1a;
            color: white;
            transition: all 0.2s ease;
            text-transform: none;
            letter-spacing: normal;
            box-shadow: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .empty-state .btn-primary:hover {
            background: white;
            color: #1a1a1a;
            transform: none;
            box-shadow: none;
        }

        .empty-state .btn-primary i {
            font-size: 0.8rem;
            color: inherit;
            background: none;
            width: auto;
            height: auto;
            line-height: 1;
        }

        .modal-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">My Orders</h1>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Yet</h3>
                <p>Start your shopping journey with our amazing collection of water bottles. Find the perfect one for your needs!</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i>Shop Now
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h5 class="mb-1">Order #<?php echo $order['id']; ?></h5>
                            <p class="text-muted mb-0"><?php echo date('F d, Y', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="order-status status-<?php echo $order['order_status']; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="row">
                            <div class="col-md-8">
                                <?php
                                // Get order items
                                $stmt = $conn->prepare("SELECT oi.*, p.name FROM order_items oi 
                                                      JOIN products p ON oi.product_id = p.id 
                                                      WHERE oi.order_id = ?");
                                $stmt->bind_param("i", $order['id']);
                                $stmt->execute();
                                $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                ?>
                                <?php foreach ($order_items as $item): ?>
                                    <div class="order-item">
                                        <div class="order-item-details">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p class="text-muted mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                                        </div>
                                        <div class="order-item-price">
                                            ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="col-md-4">
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
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
</body>
</html> 