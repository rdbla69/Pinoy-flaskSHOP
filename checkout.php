<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the current URL in the session
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("Location: login.php?redirect=checkout.php");
    exit;
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get user's default address
$sql = "SELECT * FROM addresses WHERE user_id = ? AND is_default = 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$default_address = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get all user's addresses
$sql = "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$addresses = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get cart items with product details
$cart_items = array();
$subtotal = 0;
$shipping = 0;
$total = 0;

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = (int)$_POST['shipping_address'];
    $payment_method = $_POST['payment_method'];
    $card_number = isset($_POST['card_number']) ? trim($_POST['card_number']) : '';
    $card_expiry = isset($_POST['card_expiry']) ? trim($_POST['card_expiry']) : '';
    $card_cvv = isset($_POST['card_cvv']) ? trim($_POST['card_cvv']) : '';
    
    // Validate input
    $errors = array();
    
    if (empty($address_id)) {
        $errors[] = "Please select a shipping address.";
    } else {
        // Verify that the address belongs to the user
        $stmt = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $errors[] = "Invalid shipping address selected.";
        }
    }
    
    if (empty($payment_method)) {
        $errors[] = "Payment method is required.";
    }
    
    if ($payment_method === 'credit_card') {
        if (empty($card_number) || !preg_match('/^\d{16}$/', $card_number)) {
            $errors[] = "Valid card number is required.";
        }
        if (empty($card_expiry) || !preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
            $errors[] = "Valid expiry date (MM/YY) is required.";
        }
        if (empty($card_cvv) || !preg_match('/^\d{3,4}$/', $card_cvv)) {
            $errors[] = "Valid CVV is required.";
        }
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Get the selected address
            $stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $address_id, $user_id);
            $stmt->execute();
            $address = $stmt->get_result()->fetch_assoc();
            
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, order_status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            
            $shipping_address = sprintf(
                "%s, %s, %s, %s %s",
                $address['street_address'],
                $address['barangay'],
                $address['city'],
                $address['state'],
                $address['postal_code']
            );
            
            $stmt->bind_param("idss", $user_id, $total, $shipping_address, $payment_method);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Add order items
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                $stmt->execute();
            }
            
            // Clear cart
            unset($_SESSION['cart']);
            
            $conn->commit();
            
            // Redirect to order confirmation
            header("Location: order-confirmation.php?order_id=" . $order_id);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "An error occurred while processing your order. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Water Bottle Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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

    <!-- Checkout Section -->
    <div class="container my-5">
        <h1 class="mb-4">Checkout</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Shipping Information</h5>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="checkoutForm">
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address</label>
                                <select class="form-select" id="shipping_address" name="shipping_address" required>
                                    <?php if (!empty($addresses)): ?>
                                        <?php foreach ($addresses as $address): ?>
                                            <option value="<?php echo htmlspecialchars($address['id']); ?>" <?php echo $address['is_default'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($address['address_name']); ?> - 
                                                <?php echo htmlspecialchars($address['street_address']); ?>, 
                                                <?php echo htmlspecialchars($address['barangay']); ?>, 
                                                <?php echo htmlspecialchars($address['city']); ?>, 
                                                <?php echo htmlspecialchars($address['state']); ?> 
                                                <?php echo htmlspecialchars($address['postal_code']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No addresses found</option>
                                    <?php endif; ?>
                                </select>
                                <div class="mt-2">
                                    <a href="account.php#addresses" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i>Add New Address
                                    </a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="region" class="form-label">Region</label>
                                        <select class="form-select" id="region" name="region" required>
                                            <option value="">Select Region</option>
                                            <option value="NCR">National Capital Region (NCR)</option>
                                            <option value="Region I">Ilocos Region</option>
                                            <option value="Region II">Cagayan Valley</option>
                                            <option value="Region III">Central Luzon</option>
                                            <option value="Region IV-A">CALABARZON</option>
                                            <option value="Region IV-B">MIMAROPA</option>
                                            <option value="Region V">Bicol Region</option>
                                            <option value="Region VI">Western Visayas</option>
                                            <option value="Region VII">Central Visayas</option>
                                            <option value="Region VIII">Eastern Visayas</option>
                                            <option value="Region IX">Zamboanga Peninsula</option>
                                            <option value="Region X">Northern Mindanao</option>
                                            <option value="Region XI">Davao Region</option>
                                            <option value="Region XII">SOCCSKSARGEN</option>
                                            <option value="Region XIII">Caraga</option>
                                            <option value="BARMM">Bangsamoro Autonomous Region</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">City/Municipality</label>
                                        <select class="form-select" id="city" name="city" required disabled>
                                            <option value="">Select City/Municipality</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="delivery_time" class="form-label">Preferred Delivery Time</label>
                                <select class="form-select" id="delivery_time" name="delivery_time" required>
                                    <option value="">Select Preferred Time</option>
                                    <option value="morning">Morning (8:00 AM - 12:00 PM)</option>
                                    <option value="afternoon">Afternoon (1:00 PM - 5:00 PM)</option>
                                    <option value="evening">Evening (6:00 PM - 9:00 PM)</option>
                                </select>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-truck me-2"></i>
                                <strong>Estimated Delivery:</strong>
                                <span id="deliveryEstimate">Select your region to see delivery estimate</span>
                            </div>
                            
                            <h5 class="card-title mt-4">Payment Method</h5>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                    <label class="form-check-label" for="credit_card">
                                        Credit Card
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                    <label class="form-check-label" for="paypal">
                                        PayPal
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod">
                                    <label class="form-check-label" for="cod">
                                        Cash on Delivery
                                    </label>
                                </div>
                            </div>
                            
                            <div id="creditCardDetails">
                                <div class="mb-3">
                                    <label for="card_number" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="card_expiry" class="form-label">Expiry Date</label>
                                            <input type="text" class="form-control" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="card_cvv" class="form-label">CVV</label>
                                            <input type="text" class="form-control" id="card_cvv" name="card_cvv" placeholder="123" maxlength="4">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="codDetails" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Cash on Delivery Information:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>You will pay $<?php echo number_format($total, 2); ?> when your order arrives</li>
                                        <li>Please have the exact amount ready</li>
                                        <li>Our delivery personnel will provide a receipt</li>
                                    </ul>
                                </div>
                                <div class="mb-3">
                                    <label for="delivery_instructions" class="form-label">Delivery Instructions (Optional)</label>
                                    <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="2" placeholder="Any specific instructions for delivery? (e.g., gate code, landmarks, preferred delivery time)"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_number" class="form-label">Contact Number for Delivery</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" placeholder="Enter your mobile number" required>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Place Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                </div>
                                <span>$<?php 
                                    $item_total = $item['price'] * $item['quantity'];
                                    foreach ($item['customizations'] as $customization) {
                                        $item_total += $customization['price_adjustment'] * $item['quantity'];
                                    }
                                    echo number_format($item_total, 2);
                                ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span><?php echo $shipping === 0 ? 'Free' : '$' . number_format($shipping, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong>$<?php echo number_format($total, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
        // Philippine cities by region
        const citiesByRegion = {
            'NCR': ['Manila', 'Quezon City', 'Caloocan', 'Las Piñas', 'Makati', 'Malabon', 'Mandaluyong', 'Marikina', 'Muntinlupa', 'Navotas', 'Parañaque', 'Pasay', 'Pasig', 'Pateros', 'San Juan', 'Taguig', 'Valenzuela'],
            'Region I': ['San Fernando', 'Laoag', 'Vigan', 'Dagupan', 'San Carlos', 'Alaminos', 'Urdaneta'],
            'Region II': ['Tuguegarao', 'Ilagan', 'Santiago', 'Cauayan', 'Tuguegarao'],
            'Region III': ['San Fernando', 'Angeles', 'Olongapo', 'Malolos', 'Meycauayan', 'San Jose del Monte'],
            'Region IV-A': ['Calamba', 'Batangas City', 'Lipa', 'San Pablo', 'Santa Rosa', 'Biñan', 'Cabuyao', 'San Pedro'],
            'Region IV-B': ['Puerto Princesa', 'Calapan', 'San Jose', 'Romblon', 'Boac'],
            'Region V': ['Legazpi', 'Naga', 'Iriga', 'Tabaco', 'Ligao', 'Sorsogon City'],
            'Region VI': ['Iloilo City', 'Bacolod', 'Roxas', 'San Carlos', 'Silay', 'Talisay'],
            'Region VII': ['Cebu City', 'Mandaue', 'Lapu-Lapu', 'Talisay', 'Danao', 'Toledo'],
            'Region VIII': ['Tacloban', 'Ormoc', 'Calbayog', 'Catbalogan', 'Maasin'],
            'Region IX': ['Zamboanga City', 'Dipolog', 'Pagadian', 'Isabela'],
            'Region X': ['Cagayan de Oro', 'Iligan', 'Oroquieta', 'Ozamiz', 'Tangub'],
            'Region XI': ['Davao City', 'Digos', 'Mati', 'Panabo', 'Tagum'],
            'Region XII': ['Koronadal', 'General Santos', 'Kidapawan', 'Cotabato City', 'Tacurong'],
            'Region XIII': ['Butuan', 'Surigao City', 'Tandag', 'Bislig', 'Bayugan'],
            'BARMM': ['Cotabato City', 'Marawi', 'Lamitan']
        };

        // Delivery estimates by region (in days)
        const deliveryEstimates = {
            'NCR': '1-2 business days',
            'Region I': '3-4 business days',
            'Region II': '3-4 business days',
            'Region III': '2-3 business days',
            'Region IV-A': '2-3 business days',
            'Region IV-B': '4-5 business days',
            'Region V': '3-4 business days',
            'Region VI': '3-4 business days',
            'Region VII': '3-4 business days',
            'Region VIII': '4-5 business days',
            'Region IX': '4-5 business days',
            'Region X': '4-5 business days',
            'Region XI': '4-5 business days',
            'Region XII': '4-5 business days',
            'Region XIII': '4-5 business days',
            'BARMM': '5-6 business days'
        };

        // Update cities when region is selected
        document.getElementById('region').addEventListener('change', function() {
            const citySelect = document.getElementById('city');
            const selectedRegion = this.value;
            
            // Clear and disable city select
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            
            if (selectedRegion) {
                // Enable city select
                citySelect.disabled = false;
                
                // Add cities for selected region
                citiesByRegion[selectedRegion].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });

                // Update delivery estimate
                document.getElementById('deliveryEstimate').textContent = deliveryEstimates[selectedRegion];
            } else {
                citySelect.disabled = true;
                document.getElementById('deliveryEstimate').textContent = 'Select your region to see delivery estimate';
            }
        });

        // Toggle payment details based on payment method
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const creditCardDetails = document.getElementById('creditCardDetails');
                const codDetails = document.getElementById('codDetails');
                const contactNumber = document.getElementById('contact_number');
                
                if (this.value === 'credit_card') {
                    creditCardDetails.style.display = 'block';
                    codDetails.style.display = 'none';
                    contactNumber.required = false;
                } else if (this.value === 'cod') {
                    creditCardDetails.style.display = 'none';
                    codDetails.style.display = 'block';
                    contactNumber.required = true;
                } else {
                    creditCardDetails.style.display = 'none';
                    codDetails.style.display = 'none';
                    contactNumber.required = false;
                }
            });
        });
        
        // Format phone number
        document.getElementById('contact_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.slice(0, 3) + '-' + value.slice(3);
                } else {
                    value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                }
            }
            e.target.value = value;
        });
        
        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            e.target.value = formattedValue;
        });
        
        // Format expiry date
        document.getElementById('card_expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });
        
        // Validate form before submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            if (paymentMethod === 'credit_card') {
                const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
                const cardExpiry = document.getElementById('card_expiry').value;
                const cardCvv = document.getElementById('card_cvv').value;
                
                if (cardNumber.length !== 16) {
                    e.preventDefault();
                    alert('Please enter a valid 16-digit card number.');
                    return;
                }
                
                if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                    e.preventDefault();
                    alert('Please enter a valid expiry date (MM/YY).');
                    return;
                }
                
                if (!/^\d{3,4}$/.test(cardCvv)) {
                    e.preventDefault();
                    alert('Please enter a valid CVV (3 or 4 digits).');
                    return;
                }
            } else if (paymentMethod === 'cod') {
                const contactNumber = document.getElementById('contact_number').value.replace(/\D/g, '');
                if (contactNumber.length < 10) {
                    e.preventDefault();
                    alert('Please enter a valid contact number for delivery.');
                    return;
                }
            }
        });
    </script>
</body>
</html> 