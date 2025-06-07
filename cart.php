<?php
session_start();
require_once 'config/database.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                if (isset($_POST['item_id']) && isset($_POST['quantity'])) {
                    $item_id = $_POST['item_id'];
                    $quantity = (int)$_POST['quantity'];
                    
                    if ($quantity > 0) {
                        $_SESSION['cart'][$item_id]['quantity'] = $quantity;
                    } else {
                        unset($_SESSION['cart'][$item_id]);
                    }
                }
                break;
                
            case 'remove':
                if (isset($_POST['item_id'])) {
                    unset($_SESSION['cart'][$_POST['item_id']]);
                }
                break;
                
            case 'clear':
                $_SESSION['cart'] = array();
                break;
        }
    }
}

// Calculate cart totals
$subtotal = 0;
$shipping = 0;
$total = 0;

// Get cart items with product details
$cart_items = array();
if (!empty($_SESSION['cart'])) {
    $item_ids = array_keys($_SESSION['cart']);
    $ids_string = implode(',', $item_ids);
    
    $sql = "SELECT p.*, pc.option_name, pc.option_value, pc.price_adjustment 
            FROM products p 
            LEFT JOIN product_customization pc ON p.id = pc.product_id 
            WHERE p.id IN ($ids_string)";
    
    $result = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $item_id = $row['id'];
        if (!isset($cart_items[$item_id])) {
            $cart_items[$item_id] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'image_url' => $row['image_url'],
                'quantity' => $_SESSION['cart'][$item_id]['quantity'],
                'customizations' => array()
            );
        }
        
        if ($row['option_name']) {
            $cart_items[$item_id]['customizations'][] = array(
                'name' => $row['option_name'],
                'value' => $row['option_value'],
                'price_adjustment' => $row['price_adjustment']
            );
        }
    }
    
    // Calculate totals
    foreach ($cart_items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        foreach ($item['customizations'] as $customization) {
            $item_total += $customization['price_adjustment'] * $item['quantity'];
        }
        $subtotal += $item_total;
    }
    
    // Calculate shipping (example: free shipping over $50)
    $shipping = $subtotal >= 50 ? 0 : 10;
    $total = $subtotal + $shipping;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Water Bottle Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cart-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            min-height: calc(100vh - 200px);
        }

        .cart-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 2rem;
            text-align: center;
        }

        .cart-empty {
            text-align: center;
            padding: 80px 0;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin: 2rem 0;
        }

        .cart-empty i {
            font-size: 5rem;
            color: #e9ecef;
            margin-bottom: 1.5rem;
        }

        .cart-empty h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .cart-empty p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .empty-cart-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .empty-cart-btn {
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .empty-cart-btn-primary {
            background: #000;
            color: #fff;
            border: 1px solid #000;
        }

        .empty-cart-btn-primary:hover {
            background: #333;
            border-color: #333;
            transform: translateY(-1px);
        }

        .empty-cart-btn-outline {
            background: transparent;
            color: #000;
            border: 1px solid #000;
        }

        .empty-cart-btn-outline:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
        }

        .empty-cart-btn i {
            font-size: 0.9rem;
        }

        .cart-item {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .cart-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .cart-item:hover .cart-item-image {
            transform: scale(1.05);
        }

        .cart-item-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .cart-item-customization {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            color: #1a1a1a;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #e9ecef;
            transform: scale(1.1);
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.5rem;
            font-weight: 600;
        }

        .cart-item-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .remove-item {
            color: #dc3545;
            background: none;
            border: none;
            padding: 0;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .remove-item:hover {
            color: #c82333;
            transform: scale(1.1);
        }

        .cart-summary {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
        }

        .cart-summary-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a1a1a;
            padding-top: 1rem;
            border-top: 2px solid #f8f9fa;
            margin-top: 1rem;
        }

        .checkout-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            background: linear-gradient(45deg, #1a1a1a, #4a4a4a);
            border: none;
            color: white;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }

        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: linear-gradient(45deg, #4a4a4a, #1a1a1a);
        }

        .continue-shopping {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            color: #1a1a1a;
            transition: all 0.3s ease;
        }

        .continue-shopping:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: #ffffff;
        }

        .clear-cart {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            background: #ffffff;
            border: 2px solid #dc3545;
            color: #dc3545;
            transition: all 0.3s ease;
        }

        .clear-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: #dc3545;
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .cart-section {
                padding: 30px 0;
            }

            .cart-title {
                font-size: 2rem;
            }

            .cart-item {
                margin-bottom: 1rem;
            }

            .cart-summary {
                margin-top: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fa-solid fa-droplet" style="color: #000000;"></i>Pinoyflask</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <form class="d-flex me-3" action="search.php" method="GET">
                        <input class="form-control me-2" type="search" name="q" placeholder="Search products...">
                        <button class="btn btn-link text-dark" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <a href="cart.php" class="btn btn-link text-dark me-3 position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                <?php echo count($_SESSION['cart']); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-link text-dark dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="account.php"><i class="fas fa-user-circle me-2"></i>My Account</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-link text-dark me-2">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                        <a href="register.php" class="btn btn-link text-dark">
                            <i class="fas fa-user-plus"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <h1 class="cart-title">Shopping Cart</h1>
            
            <?php if (empty($cart_items)): ?>
                <div class="cart-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your Cart is Empty</h3>
                    <p>Looks like you haven't added any items to your cart yet. Start shopping to fill it up with amazing products!</p>
                    <div class="empty-cart-buttons">
                        <a href="products.php" class="empty-cart-btn empty-cart-btn-primary">
                            <i class="fas fa-shopping-bag"></i>
                            Shop Now
                        </a>
                        <a href="index.php" class="empty-cart-btn empty-cart-btn-outline">
                            <i class="fas fa-home"></i>
                            Home
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Cart Items -->
                    <div class="col-lg-8">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 class="cart-item-image" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <h5 class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <?php if (!empty($item['customizations'])): ?>
                                                <?php foreach ($item['customizations'] as $customization): ?>
                                                    <div class="cart-item-customization">
                                                        <?php echo htmlspecialchars($customization['name']); ?>: 
                                                        <?php echo htmlspecialchars($customization['value']); ?>
                                                        <?php if ($customization['price_adjustment'] != 0): ?>
                                                            (<?php echo $customization['price_adjustment'] > 0 ? '+' : ''; ?>₱<?php echo number_format($customization['price_adjustment'], 2); ?>)
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="quantity-control">
                                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="quantity-input" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" 
                                                       onchange="updateQuantity(<?php echo $item['id']; ?>, 'set', this.value)">
                                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <div class="cart-item-price">
                                                ₱<?php 
                                                    $item_total = $item['price'] * $item['quantity'];
                                                    foreach ($item['customizations'] as $customization) {
                                                        $item_total += $customization['price_adjustment'] * $item['quantity'];
                                                    }
                                                    echo number_format($item_total, 2);
                                                ?>
                                            </div>
                                            <button class="remove-item" onclick="removeItem(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-trash me-1"></i>Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="products.php" class="continue-shopping">
                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                            </a>
                            <button class="clear-cart" onclick="clearCart()">
                                <i class="fas fa-trash me-2"></i>Clear Cart
                            </button>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <h5 class="cart-summary-title">Order Summary</h5>
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span><?php echo $shipping == 0 ? 'Free' : '₱' . number_format($shipping, 2); ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>₱<?php echo number_format($total, 2); ?></span>
                            </div>
                            <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                                <i class="fas fa-lock me-2"></i>Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Your trusted source for custom water bottles.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-light">About Us</a></li>
                        <li><a href="contact.php" class="text-light">Contact</a></li>
                        <li><a href="faq.php" class="text-light">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                        <li><i class="fas fa-envelope"></i> info@waterbottleshop.com</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function updateQuantity(itemId, action, value = null) {
            let quantity;
            if (action === 'set') {
                quantity = parseInt(value);
            } else {
                const input = document.querySelector(`input[onchange*="${itemId}"]`);
                const currentValue = parseInt(input.value);
                quantity = action === 'increase' ? currentValue + 1 : currentValue - 1;
            }

            if (quantity < 1) {
                if (confirm('Do you want to remove this item from your cart?')) {
                    removeItem(itemId);
                } else {
                    // Reset to 1 if user cancels
                    const input = document.querySelector(`input[onchange*="${itemId}"]`);
                    input.value = 1;
                }
                return;
            }

            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="item_id" value="${itemId}">
                <input type="hidden" name="quantity" value="${quantity}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function removeItem(itemId) {
            if (confirm('Are you sure you want to remove this item?')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="item_id" value="${itemId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function clearCart() {
            if (confirm('Are you sure you want to clear your cart?')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="clear">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Prevent negative numbers in quantity input
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value < 1) {
                    this.value = 1;
                }
            });
        });
    </script>
</body>
</html> 
