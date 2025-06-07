<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Bottle Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            position: relative;
            padding: 100px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            overflow: hidden;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding: 2rem;
            max-width: 600px;
        }

        .hero-image {
            position: relative;
            z-index: 1;
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.5s ease;
        }

        .hero-image:hover {
            transform: perspective(1000px) rotateY(0deg);
        }

        .hero-image img {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            display: block;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: all 0.5s ease;
        }

        .hero-section h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: #1a1a1a;
            line-height: 1.2;
            background: linear-gradient(45deg, #1a1a1a, #4a4a4a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-section .lead {
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            color: #666;
            line-height: 1.6;
        }

        .hero-content .btn-primary {
            padding: 1.2rem 2.5rem;
            font-size: 1.2rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            background: linear-gradient(45deg, #1a1a1a, #4a4a4a);
            border: none;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .hero-content .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            background: linear-gradient(45deg, #4a4a4a, #1a1a1a);
        }

        .hero-decoration {
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.1), rgba(74,74,74,0.1));
            border-radius: 50%;
            z-index: 0;
        }

        .hero-decoration-1 {
            top: -150px;
            right: -150px;
        }

        .hero-decoration-2 {
            bottom: -150px;
            left: -150px;
        }

        @media (max-width: 991px) {
            .hero-section {
                padding: 60px 0;
                text-align: center;
            }
            
            .hero-content {
                margin: 0 auto;
                padding: 1rem;
            }
            
            .hero-section h1 {
                font-size: 3rem;
            }
            
            .hero-section .lead {
                font-size: 1.2rem;
            }
            
            .hero-image {
                margin-top: 3rem;
                transform: none;
            }
            
            .hero-image:hover {
                transform: none;
            }
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .hero-section .lead {
                font-size: 1.1rem;
            }
            
            .hero-content .btn-primary {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }

        .hero-features {
            display: flex;
            gap: 2rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }

        .hero-feature {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 1rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .hero-feature:hover {
            transform: translateY(-3px);
        }

        .hero-feature i {
            font-size: 1.5rem;
            color: #1a1a1a;
            background: #f8f9fa;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .hero-feature span {
            font-weight: 500;
            color: #1a1a1a;
        }

        @media (max-width: 768px) {
            .hero-features {
                justify-content: center;
                gap: 1rem;
            }

            .hero-feature {
                padding: 0.8rem 1.2rem;
            }
        }

        .featured-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: relative;
            overflow: hidden;
        }

        .featured-section::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.05), rgba(74,74,74,0.05));
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }

        .featured-section::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.05), rgba(74,74,74,0.05));
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .featured-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            height: 100%;
        }

        .featured-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .featured-image-wrapper {
            position: relative;
            overflow: hidden;
            padding-top: 100%;
        }

        .featured-image-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .featured-card:hover .featured-image-wrapper img {
            transform: scale(1.1);
        }

        .featured-actions {
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

        .featured-card:hover .featured-actions {
            opacity: 1;
            transform: translateX(0);
        }

        .featured-actions button {
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

        .featured-actions button:hover {
            background: #f8f9fa;
            color: #1a1a1a;
            transform: scale(1.1);
        }

        .featured-content {
            padding: 1.5rem;
        }

        .featured-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1a1a1a;
        }

        .featured-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .featured-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .featured-price .currency {
            font-size: 0.9rem;
            margin-right: 2px;
        }

        .featured-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-size-guide {
            border: 2px solid #e9ecef;
            color: #1a1a1a;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .btn-size-guide:hover {
            background: #f8f9fa;
            color: #1a1a1a;
            border-color: #e9ecef;
        }

        .btn-add-to-cart {
            background: linear-gradient(45deg, #ffffff, #f8f9fa);
            border: 2px solid #e9ecef;
            color: #1a1a1a;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-add-to-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: linear-gradient(45deg, #f8f9fa, #ffffff);
        }

        .view-all-btn {
            display: inline-block;
            margin-top: 3rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            background: linear-gradient(45deg, #ffffff, #f8f9fa);
            border: 2px solid #e9ecef;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .view-all-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: linear-gradient(45deg, #f8f9fa, #ffffff);
            color: #1a1a1a;
        }

        @media (max-width: 768px) {
            .featured-section {
                padding: 60px 0;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .section-title p {
                font-size: 1rem;
            }

            .featured-card {
                margin-bottom: 2rem;
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

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-decoration hero-decoration-1"></div>
        <div class="hero-decoration hero-decoration-2"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1>Stay Hydrated in Style</h1>
                        <p class="lead">Discover our premium collection of custom water bottles, designed for every lifestyle. Quality meets elegance in every sip.</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Shop Now
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <img src="assets/images/cover.png" alt="Premium Water Bottles" class="img-fluid">
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="hero-features">
                <div class="hero-feature">
                    <i class="fas fa-temperature-low"></i>
                    <span>Keeps Cold for 24 Hours</span>
                </div>
                <div class="hero-feature">
                    <i class="fas fa-leaf"></i>
                    <span>Eco-Friendly Materials</span>
                </div>
                <div class="hero-feature">
                    <i class="fas fa-award"></i>
                    <span>Premium Quality</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Products Section -->
    <section class="featured-section">
        <div class="container">
            <div class="section-title">
                <h2>Featured Products</h2>
                <p>Discover our most popular water bottles, crafted with premium materials and innovative designs.</p>
            </div>
            <div class="row">
                <?php
                // Get latest products
                $featured_sql = "SELECT * FROM products ORDER BY id DESC LIMIT 3";
                $featured_result = mysqli_query($conn, $featured_sql);

                while ($product = mysqli_fetch_assoc($featured_result)) {
                    echo '<div class="col-md-4 mb-4">';
                    echo '<div class="featured-card">';
                    echo '<div class="featured-image-wrapper">';
                    echo '<img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['name']) . '">';
                    echo '<div class="featured-actions">';
                    echo '<button class="btn btn-link text-dark quick-view" data-product-id="' . $product['id'] . '"><i class="fas fa-eye"></i></button>';
                    echo '<button class="btn btn-link text-dark add-to-wishlist" data-product-id="' . $product['id'] . '"><i class="far fa-heart"></i></button>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="featured-content">';
                    echo '<h3 class="featured-title">' . htmlspecialchars($product['name']) . '</h3>';
                    echo '<p class="featured-description">' . htmlspecialchars($product['description']) . '</p>';
                    echo '<div class="featured-price">';
                    echo '<span class="currency">₱</span>';
                    echo '<span class="amount">' . number_format($product['price'], 2, '.', ',') . '</span>';
                    echo '</div>';
                    echo '<div class="featured-buttons">';
                    echo '<button type="button" class="btn btn-size-guide" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">';
                    echo '<i class="fas fa-ruler me-1"></i>Size Guide</button>';
                    echo '<form action="product.php" method="GET" class="d-inline">';
                    echo '<input type="hidden" name="id" value="' . $product['id'] . '">';
                    echo '<button type="submit" class="btn btn-add-to-cart">';
                    echo '<i class="fas fa-shopping-cart me-1"></i>Add to Cart</button>';
                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="text-center">
                <a href="products.php" class="view-all-btn">
                    View All Products <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4">
                    <i class="fas fa-truck fa-3x mb-3"></i>
                    <h3>Free Shipping</h3>
                    <p>On orders over $50</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-undo fa-3x mb-3"></i>
                    <h3>Easy Returns</h3>
                    <p>30-day return policy</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-lock fa-3x mb-3"></i>
                    <h3>Secure Payment</h3>
                    <p>100% secure checkout</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <section class="faq-section py-5 bg-light">
        <div class="container">
            <div class="section-title">
                <h2>Frequently Asked Questions</h2>
                <p>Find answers to common questions about our products and services</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <!-- Product Questions -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What materials are your water bottles made of?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Our water bottles are made from high-quality, food-grade materials including:
                                    <ul>
                                        <li>Stainless Steel (18/8 and 18/10)</li>
                                        <li>BPA-free Tritan plastic</li>
                                        <li>Glass with protective sleeves</li>
                                    </ul>
                                    All materials are FDA-approved and safe for daily use.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    How do I clean my water bottle?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>For best results, follow these cleaning steps:</p>
                                    <ol>
                                        <li>Wash with warm, soapy water after each use</li>
                                        <li>Use a bottle brush for thorough cleaning</li>
                                        <li>For stainless steel bottles, you can use vinegar solution for deep cleaning</li>
                                        <li>Air dry completely before storing</li>
                                    </ol>
                                    <p>Note: Most of our bottles are dishwasher safe (top rack only).</p>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Questions -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How long does shipping take?
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Shipping times vary by location:</p>
                                    <ul>
                                        <li>Metro Manila: 1-2 business days</li>
                                        <li>Luzon: 2-3 business days</li>
                                        <li>Visayas: 3-4 business days</li>
                                        <li>Mindanao: 4-5 business days</li>
                                    </ul>
                                    <p>Note: These are estimated delivery times and may vary based on weather conditions and other factors.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Returns Questions -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What is your return policy?
                                </button>
                            </h3>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Our return policy:</p>
                                    <ul>
                                        <li>30-day return window for unused items</li>
                                        <li>Items must be in original packaging</li>
                                        <li>Proof of purchase required</li>
                                        <li>Return shipping costs may apply</li>
                                    </ul>
                                    <p>For defective items, please contact our customer service within 7 days of delivery.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i>Still have questions? Contact us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Size Guide Modal -->
    <div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-labelledby="sizeGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sizeGuideModalLabel">Size Guide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
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
                                    <td>Small</td>
                                    <td>350ml</td>
                                    <td>20cm</td>
                                    <td>7cm</td>
                                    <td>Kids, Short Trips</td>
                                </tr>
                                <tr>
                                    <td>Medium</td>
                                    <td>500ml</td>
                                    <td>25cm</td>
                                    <td>7.5cm</td>
                                    <td>Daily Use, Office</td>
                                </tr>
                                <tr>
                                    <td>Large</td>
                                    <td>750ml</td>
                                    <td>30cm</td>
                                    <td>8cm</td>
                                    <td>Sports, Gym</td>
                                </tr>
                                <tr>
                                    <td>X-Large</td>
                                    <td>1000ml</td>
                                    <td>35cm</td>
                                    <td>8.5cm</td>
                                    <td>Long Trips, Hiking</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <h6>How to Choose the Right Size:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check-circle text-success me-2"></i>Consider your daily water intake needs</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>Think about where you'll be using it most</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>Check if it fits in your bag or car cup holder</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>Consider the weight when full</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
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
</body>
</html> 