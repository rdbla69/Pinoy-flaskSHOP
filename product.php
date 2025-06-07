<?php
session_start();
require_once 'config/database.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

// If product not found, redirect to products page
if (!$product) {
    header('Location: products.php');
    exit();
}

// Fetch related products (same category, excluding current product)
$related_sql = "SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4";
$related_stmt = mysqli_prepare($conn, $related_sql);
mysqli_stmt_bind_param($related_stmt, "si", $product['category'], $product_id);
mysqli_stmt_execute($related_stmt);
$related_products = mysqli_stmt_get_result($related_stmt);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = array(
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'image' => $product['image_url']
        );
    }
    
    // Redirect to prevent form resubmission
    header('Location: product.php?id=' . $product_id . '&added=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Pinoyflask</title>
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

    <!-- Product Details -->
    <div class="container py-5">
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Product added to cart successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Product Images -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         class="card-img-top product-image" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         data-bs-toggle="modal"
                         data-bs-target="#imageModal"
                         style="cursor: zoom-in;">
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-md-6">
                <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-muted mb-3">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                
                <div class="mb-4">
                    <h2 class="text-primary mb-0">₱<?php echo number_format($product['price'], 2); ?></h2>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success">In Stock</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Out of Stock</span>
                    <?php endif; ?>
                </div>

                <p class="mb-4"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                <?php if ($product['stock'] > 0): ?>
                    <form action="" method="POST" class="mb-4">
                        <div class="row g-3">
                            <!-- Size Selection -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Select Size:</label>
                                <div class="size-options d-flex gap-2 mb-3">
                                    <input type="radio" class="btn-check" name="size" id="size12" value="12oz" autocomplete="off" required>
                                    <label class="btn btn-outline-dark" for="size12">12oz</label>

                                    <input type="radio" class="btn-check" name="size" id="size24" value="24oz" autocomplete="off">
                                    <label class="btn btn-outline-dark" for="size24">24oz</label>

                                    <input type="radio" class="btn-check" name="size" id="size32" value="32oz" autocomplete="off">
                                    <label class="btn btn-outline-dark" for="size32">32oz</label>

                                    <input type="radio" class="btn-check" name="size" id="size40" value="40oz" autocomplete="off">
                                    <label class="btn btn-outline-dark" for="size40">40oz</label>

                                    <input type="radio" class="btn-check" name="size" id="size64" value="64oz" autocomplete="off">
                                    <label class="btn btn-outline-dark" for="size64">64oz</label>
                                </div>
                            </div>

                            <!-- Quantity -->
                            <div class="col-12">
                                <label for="quantity" class="form-label fw-bold">Quantity:</label>
                                <div class="input-group" style="width: 150px;">
                                    <button type="button" class="btn btn-outline-dark" onclick="decrementQuantity()">-</button>
                                    <input type="number" id="quantity" name="quantity" class="form-control text-center" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                    <button type="button" class="btn btn-outline-dark" onclick="incrementQuantity()">+</button>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">
                                        <i class="fas fa-ruler me-1"></i>Size Guide
                                    </button>
                                    <button type="submit" name="add_to_cart" class="btn btn-primary flex-grow-1">
                                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Product Specifications -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Specifications</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><strong>Material:</strong> <?php echo htmlspecialchars($product['material'] ?? 'Stainless Steel'); ?></li>
                            <li class="mb-2"><strong>Capacity:</strong> <?php echo htmlspecialchars($product['capacity'] ?? '500ml'); ?></li>
                            <li class="mb-2"><strong>Color:</strong> <?php echo htmlspecialchars($product['color'] ?? 'Silver'); ?></li>
                            <li class="mb-2"><strong>Dimensions:</strong> <?php echo htmlspecialchars($product['dimensions'] ?? '7.5 x 7.5 x 25 cm'); ?></li>
                            <li class="mb-2"><strong>Weight:</strong> <?php echo htmlspecialchars($product['weight'] ?? '250g'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (mysqli_num_rows($related_products) > 0): ?>
            <div class="mt-5">
                <h3 class="mb-4">Related Products</h3>
                <div class="row g-4">
                    <?php while ($related = mysqli_fetch_assoc($related_products)): ?>
                        <div class="col-md-3">
                            <div class="card h-100 border-0 shadow-sm product-card">
                                <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($related['description']); ?></p>
                                    <p class="card-text"><strong>₱<?php echo number_format($related['price'], 2); ?></strong></p>
                                    <div class="d-grid">
                                        <a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Size Guide Modal -->
    <div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-labelledby="sizeGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="sizeGuideModalLabel">Water Bottle Size Guide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Size Comparison Visual -->
                    <div class="size-comparison mb-4">
                        <div class="d-flex justify-content-between align-items-end" style="height: 250px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                            <div class="text-center">
                                <div style="height: 60px; width: 20px; background: linear-gradient(to top, #007bff, #0056b3); margin: 0 auto; border-radius: 10px 10px 0 0;"></div>
                                <small class="d-block mt-2">12oz</small>
                            </div>
                            <div class="text-center">
                                <div style="height: 90px; width: 22px; background: linear-gradient(to top, #007bff, #0056b3); margin: 0 auto; border-radius: 10px 10px 0 0;"></div>
                                <small class="d-block mt-2">24oz</small>
                            </div>
                            <div class="text-center">
                                <div style="height: 110px; width: 24px; background: linear-gradient(to top, #007bff, #0056b3); margin: 0 auto; border-radius: 10px 10px 0 0;"></div>
                                <small class="d-block mt-2">32oz</small>
                            </div>
                            <div class="text-center">
                                <div style="height: 130px; width: 26px; background: linear-gradient(to top, #007bff, #0056b3); margin: 0 auto; border-radius: 10px 10px 0 0;"></div>
                                <small class="d-block mt-2">40oz</small>
                            </div>
                            <div class="text-center">
                                <div style="height: 160px; width: 28px; background: linear-gradient(to top, #007bff, #0056b3); margin: 0 auto; border-radius: 10px 10px 0 0;"></div>
                                <small class="d-block mt-2">64oz</small>
                            </div>
                        </div>
                    </div>

                    <!-- Size Details Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Size</th>
                                    <th>Capacity</th>
                                    <th>Height</th>
                                    <th>Diameter</th>
                                    <th>Best For</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Small</strong></td>
                                    <td>12 oz (350ml)</td>
                                    <td>7.5" (19cm)</td>
                                    <td>2.5" (6.4cm)</td>
                                    <td>Kids, Short trips</td>
                                </tr>
                                <tr>
                                    <td><strong>Medium</strong></td>
                                    <td>24 oz (710ml)</td>
                                    <td>10.5" (26.7cm)</td>
                                    <td>2.75" (7cm)</td>
                                    <td>Daily use, Office</td>
                                </tr>
                                <tr>
                                    <td><strong>Large</strong></td>
                                    <td>32 oz (946ml)</td>
                                    <td>11.5" (29.2cm)</td>
                                    <td>3" (7.6cm)</td>
                                    <td>Sports, Gym</td>
                                </tr>
                                <tr>
                                    <td><strong>X-Large</strong></td>
                                    <td>40 oz (1.2L)</td>
                                    <td>12.5" (31.8cm)</td>
                                    <td>3.25" (8.3cm)</td>
                                    <td>Long trips, Hiking</td>
                                </tr>
                                <tr>
                                    <td><strong>XX-Large</strong></td>
                                    <td>64 oz (1.9L)</td>
                                    <td>14" (35.6cm)</td>
                                    <td>3.5" (8.9cm)</td>
                                    <td>Family size, Camping</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Usage Tips -->
                    <div class="card bg-light border-0 mt-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3"><i class="fas fa-lightbulb me-2"></i>Size Recommendations</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>12oz - Perfect for kids</li>
                                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>24oz - Ideal for office use</li>
                                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>32oz - Great for gym workouts</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>40oz - Best for hiking</li>
                                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>64oz - Perfect for family trips</li>
                                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>All sizes are leak-proof</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Size Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         class="img-fluid full-size-image" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Size Options Styling */
        .size-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .size-options .btn {
            min-width: 70px;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .size-options .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }

        .size-options .btn:hover::before {
            width: 100%;
            height: 100%;
        }

        .btn-check:checked + .btn-outline-dark {
            background-color: #212529;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Quantity Input Styling */
        .quantity-input-group {
            width: 150px;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .quantity-input-group .btn {
            border: none;
            padding: 8px 15px;
            transition: all 0.3s ease;
        }

        .quantity-input-group .btn:hover {
            background-color: #f8f9fa;
        }

        .quantity-input-group input {
            border: none;
            text-align: center;
            font-weight: 500;
        }

        .quantity-input-group input:focus {
            box-shadow: none;
        }

        /* Size Guide Modal Styling */
        .size-comparison {
            position: relative;
            background: linear-gradient(to bottom, #f8f9fa, #ffffff);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .size-comparison::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, #dee2e6, #adb5bd, #dee2e6);
        }

        .bottle-visual {
            position: relative;
            transition: transform 0.3s ease;
        }

        .bottle-visual:hover {
            transform: translateY(-5px);
        }

        .bottle-visual::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 2px;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
        }

        /* Table Styling */
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: linear-gradient(to right, #f8f9fa, #ffffff);
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        /* Recommendations Card */
        .recommendations-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .recommendations-card:hover {
            transform: translateY(-5px);
        }

        .recommendations-card .card-title {
            color: #212529;
            font-weight: 600;
        }

        .recommendations-card .list-unstyled li {
            transition: transform 0.3s ease;
        }

        .recommendations-card .list-unstyled li:hover {
            transform: translateX(5px);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
        }

        .action-buttons .btn {
            border-radius: 25px;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }

        .action-buttons .btn-primary {
            background: linear-gradient(to right, #0d6efd, #0a58ca);
            border: none;
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.2);
        }

        .action-buttons .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        .action-buttons .btn-outline-dark:hover {
            background-color: #212529;
            color: white;
            transform: translateY(-2px);
        }

        /* Modal Animations */
        .modal.fade .modal-dialog {
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-dialog {
            transform: scale(1);
        }

        /* Toast Notifications */
        .toast {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .toast-header {
            border-bottom: none;
            background: linear-gradient(to right, #f8f9fa, #ffffff);
        }

        /* Product Image Styling */
        .product-image {
            transition: transform 0.3s ease;
            object-fit: contain;
            height: 400px;
            padding: 20px;
        }

        .product-image:hover {
            transform: scale(1.02);
        }

        /* Full Size Image Modal Styling */
        .modal-fullscreen .modal-content {
            background-color: rgba(0, 0, 0, 0.9);
        }

        .full-size-image {
            max-height: 90vh;
            max-width: 90vw;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .modal-body {
            position: relative;
            overflow: hidden;
        }

        /* Zoom Controls */
        .zoom-controls {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 25px;
            display: flex;
            gap: 10px;
            z-index: 1050;
        }

        .zoom-controls button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
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

        .zoom-controls button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>

    <script>
        // Enhanced quantity control
        function incrementQuantity() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.getAttribute('max'));
            const currentValue = parseInt(input.value);
            if (currentValue < max) {
                input.value = currentValue + 1;
                animateQuantityChange(input);
            }
        }

        function decrementQuantity() {
            const input = document.getElementById('quantity');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                animateQuantityChange(input);
            }
        }

        function animateQuantityChange(input) {
            input.style.transform = 'scale(1.1)';
            setTimeout(() => {
                input.style.transform = 'scale(1)';
            }, 200);
        }

        // Size selection animation
        document.querySelectorAll('.size-options .btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.size-options .btn').forEach(b => {
                    b.style.transform = 'scale(1)';
                });
                this.style.transform = 'scale(1.05)';
            });
        });

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast position-fixed bottom-0 end-0 m-3`;
            toast.innerHTML = `
                <div class="toast-header">
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const sizeSelected = document.querySelector('input[name="size"]:checked');
            if (!sizeSelected) {
                e.preventDefault();
                showToast('Please select a size', 'error');
            }
        });

        // Image Modal Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const imageModal = document.getElementById('imageModal');
            const fullSizeImage = document.querySelector('.full-size-image');
            let currentZoom = 1;
            let isDragging = false;
            let startX, startY, translateX = 0, translateY = 0;

            // Add zoom controls
            const zoomControls = document.createElement('div');
            zoomControls.className = 'zoom-controls';
            zoomControls.innerHTML = `
                <button onclick="zoomImage(0.5)"><i class="fas fa-search-minus"></i></button>
                <button onclick="resetZoom()"><i class="fas fa-sync-alt"></i></button>
                <button onclick="zoomImage(2)"><i class="fas fa-search-plus"></i></button>
            `;
            document.body.appendChild(zoomControls);

            // Zoom functions
            window.zoomImage = function(factor) {
                currentZoom = Math.min(Math.max(currentZoom * factor, 0.5), 4);
                fullSizeImage.style.transform = `scale(${currentZoom})`;
            };

            window.resetZoom = function() {
                currentZoom = 1;
                translateX = 0;
                translateY = 0;
                fullSizeImage.style.transform = `translate(${translateX}px, ${translateY}px) scale(1)`;
            };

            // Mouse wheel zoom
            fullSizeImage.addEventListener('wheel', function(e) {
                e.preventDefault();
                const factor = e.deltaY > 0 ? 0.9 : 1.1;
                zoomImage(factor);
            });

            // Drag functionality
            fullSizeImage.addEventListener('mousedown', function(e) {
                if (currentZoom > 1) {
                    isDragging = true;
                    startX = e.clientX - translateX;
                    startY = e.clientY - translateY;
                    fullSizeImage.style.cursor = 'grabbing';
                }
            });

            document.addEventListener('mousemove', function(e) {
                if (isDragging && currentZoom > 1) {
                    translateX = e.clientX - startX;
                    translateY = e.clientY - startY;
                    fullSizeImage.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoom})`;
                }
            });

            document.addEventListener('mouseup', function() {
                isDragging = false;
                fullSizeImage.style.cursor = 'grab';
            });

            // Reset on modal close
            imageModal.addEventListener('hidden.bs.modal', function() {
                resetZoom();
            });

            // Double click to reset
            fullSizeImage.addEventListener('dblclick', resetZoom);
        });
    </script>

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
                        <li><i class="fas fa-envelope"></i> info@pinoyflask.com</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 