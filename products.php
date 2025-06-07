<?php
session_start();
require_once 'config/database.php';

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Build the SQL query
$sql = "SELECT * FROM products WHERE 1=1";
$params = array();
$types = "";

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " AND price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

// Add sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY name DESC";
        break;
    default:
        $sql .= " ORDER BY name ASC";
}

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get categories for filter
$categories_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL";
$categories_result = mysqli_query($conn, $categories_sql);
$categories = array();
while ($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row['category'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Water Bottle Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <style>
        .products-hero {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 60px 0;
            position: relative;
            overflow: hidden;
        }

        .products-hero::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.05), rgba(74,74,74,0.05));
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }

        .products-hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.05), rgba(74,74,74,0.05));
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
        }

        .filters-sidebar {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .filters-sidebar:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .form-control, .form-select {
            border: 2px solid #f8f9fa;
            padding: 0.8rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .form-control:focus, .form-select:focus {
            border-color: #e9ecef;
            box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.05);
            background-color: #ffffff;
        }

        .category-list {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .category-list::-webkit-scrollbar {
            width: 5px;
        }

        .category-list::-webkit-scrollbar-track {
            background: #f8f9fa;
            border-radius: 10px;
        }

        .category-list::-webkit-scrollbar-thumb {
            background: #e9ecef;
            border-radius: 10px;
        }

        .product-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background-color: #ffffff;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .product-image-wrapper {
            position: relative;
            overflow: hidden;
            padding-top: 100%;
            background-color: #ffffff;
        }

        .product-image-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image-wrapper img {
            transform: scale(1.1);
        }

        .product-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s ease;
        }

        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateX(0);
        }

        .product-actions button {
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .product-actions button:hover {
            background: #f8f9fa;
            color: #1a1a1a;
            transform: scale(1.1);
        }

        .card-body {
            padding: 1.5rem;
            background-color: #ffffff;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1a1a1a;
        }

        .card-text {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .price-tag {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .price-tag .currency {
            font-size: 0.9rem;
            margin-right: 2px;
        }

        .btn-outline-primary {
            border: 2px solid #e9ecef;
            color: #1a1a1a;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .btn-outline-primary:hover {
            background: #f8f9fa;
            color: #1a1a1a;
            border-color: #e9ecef;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ffffff, #f8f9fa);
            border: 2px solid #e9ecef;
            color: #1a1a1a;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: linear-gradient(45deg, #f8f9fa, #ffffff);
        }

        .view-options button {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .view-options button.active {
            background: #f8f9fa;
            color: #1a1a1a;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .view-options button:hover:not(.active) {
            background: #f8f9fa;
        }

        .alert-info {
            background-color: #ffffff;
            border-color: #e9ecef;
            color: #1a1a1a;
        }

        .table-light {
            background-color: #ffffff;
        }

        .table-bordered {
            border-color: #e9ecef;
        }

        @media (max-width: 768px) {
            .products-hero {
                padding: 40px 0;
                text-align: center;
            }

            .filters-sidebar {
                margin-bottom: 2rem;
            }

            .product-card {
                margin-bottom: 2rem;
            }
        }
    </style>

    <!-- Products Hero -->
    <div class="products-hero">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Our Products</h1>
            <p class="lead text-muted">Discover our collection of high-quality water bottles</p>
        </div>
    </div>

    <div class="container-fluid py-5">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-md-3">
                <div class="filters-sidebar p-4">
                    <h5 class="mb-4 fw-bold">Filters</h5>
                    <form action="products.php" method="GET" id="filterForm">
                        <!-- Search -->
                        <div class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control border-end-0" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                                <button type="submit" class="btn btn-link text-dark border-start-0">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Category</label>
                            <div class="category-list">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="category" id="all" value="" <?php echo empty($category) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="all">All Categories</label>
                                </div>
                                <?php foreach ($categories as $cat): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="category" id="<?php echo htmlspecialchars($cat); ?>" value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Price Range</label>
                            <div class="price-range">
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="min_price" placeholder="Min" value="<?php echo $min_price; ?>" min="0">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="max_price" placeholder="Max" value="<?php echo $max_price; ?>" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sort -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Sort By</label>
                            <select class="form-select" id="sort" name="sort" onchange="document.getElementById('filterForm').submit();">
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-md-9">
                <div class="products-header d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><?php echo mysqli_num_rows($result); ?> Products</h4>
                    <div class="view-options">
                        <button class="btn btn-link text-dark active" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="btn btn-link text-dark" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
                <div class="row products-grid">
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<div class="col-md-4 mb-4">';
                            echo '<div class="card product-card h-100">';
                            echo '<div class="product-image-wrapper">';
                            echo '<img src="' . htmlspecialchars($row['image_url']) . '" class="card-img-top" alt="' . htmlspecialchars($row['name']) . '">';
                            echo '<div class="product-actions">';
                            echo '<button class="btn btn-link text-dark quick-view" data-product-id="' . $row['id'] . '"><i class="fas fa-eye"></i></button>';
                            echo '<button class="btn btn-link text-dark add-to-wishlist" data-product-id="' . $row['id'] . '"><i class="far fa-heart"></i></button>';
                            echo '</div>';
                            echo '</div>';
                            echo '<div class="card-body">';
                            echo '<h5 class="card-title">' . htmlspecialchars($row['name']) . '</h5>';
                            echo '<p class="card-text text-muted">' . htmlspecialchars($row['description']) . '</p>';
                            echo '<div class="d-flex justify-content-between align-items-center">';
                            echo '<div class="price-tag">';
                            echo '<span class="currency">₱</span>';
                            echo '<span class="amount">' . number_format($row['price'], 2, '.', ',') . '</span>';
                            echo '</div>';
                            echo '<div class="d-flex gap-2">';
                            echo '<button type="button" class="btn btn-outline-primary size-guide-btn" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">';
                            echo '<i class="fas fa-ruler me-1"></i>Size</button>';
                            echo '<form action="product.php" method="GET" class="d-inline">';
                            echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                            echo '<button type="submit" class="btn btn-primary add-to-cart-btn">';
                            echo '<i class="fas fa-shopping-cart me-1"></i>Add to Cart</button>';
                            echo '</form>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div></div></div>';
                        }
                    } else {
                        echo '<div class="col-12"><div class="alert alert-info">No products found matching your criteria.</div></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Size Guide Modal -->
    <div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-labelledby="sizeGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sizeGuideModalLabel">Water Bottle Size Guide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
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
                                    <td>12" (30.5cm)</td>
                                    <td>3" (7.6cm)</td>
                                    <td>Sports, Long trips</td>
                                </tr>
                                <tr>
                                    <td><strong>Extra Large</strong></td>
                                    <td>64 oz (1.9L)</td>
                                    <td>14" (35.6cm)</td>
                                    <td>3.5" (8.9cm)</td>
                                    <td>Family, Camping</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // View switching functionality
        document.querySelectorAll('.view-options button').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.view-options button').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const view = this.dataset.view;
                const productsGrid = document.querySelector('.products-grid');
                
                if (view === 'list') {
                    productsGrid.classList.add('list-view');
                    document.querySelectorAll('.product-card').forEach(card => {
                        card.classList.add('list-view');
                    });
                } else {
                    productsGrid.classList.remove('list-view');
                    document.querySelectorAll('.product-card').forEach(card => {
                        card.classList.remove('list-view');
                    });
                }
            });
        });

        // Quick view functionality
        document.querySelectorAll('.quick-view').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                // Here you would typically load the product details via AJAX
                // For now, we'll just show the modal
                const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
                modal.show();
            });
        });

        // Wishlist functionality
        document.querySelectorAll('.add-to-wishlist').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                // Here you would typically add the product to the wishlist via AJAX
                this.querySelector('i').classList.toggle('far');
                this.querySelector('i').classList.toggle('fas');
            });
        });
    </script>

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
    <script src="assets/js/main.js"></script>
</body>
</html> 