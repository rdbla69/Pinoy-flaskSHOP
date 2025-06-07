<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Pinoyflask</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .about-hero::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.1), rgba(74,74,74,0.1));
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }

        .about-hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.1), rgba(74,74,74,0.1));
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
        }

        .about-hero h1 {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(45deg, #1a1a1a, #4a4a4a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .about-hero .lead {
            font-size: 1.4rem;
            color: #666;
            line-height: 1.6;
        }

        .about-hero img {
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.5s ease;
        }

        .about-hero img:hover {
            transform: translateY(-10px);
        }

        .mission-vision-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            height: 100%;
            transition: transform 0.3s ease;
        }

        .mission-vision-card:hover {
            transform: translateY(-5px);
        }

        .mission-vision-card i {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #1a1a1a;
        }

        .mission-vision-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #1a1a1a;
        }

        .mission-vision-card p {
            color: #666;
            line-height: 1.6;
        }

        .values-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 100px 0;
        }

        .value-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }

        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .value-card i {
            font-size: 2.5rem;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
        }

        .value-card h4 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1a1a1a;
        }

        .value-card p {
            color: #666;
            line-height: 1.6;
        }

        .team-section {
            padding: 100px 0;
        }

        .team-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .team-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .team-card .card-body {
            padding: 2rem;
            text-align: center;
        }

        .team-card h5 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #1a1a1a;
        }

        .team-card p {
            color: #666;
            margin-bottom: 1rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            color: #1a1a1a;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #1a1a1a;
            color: white;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .about-hero {
                padding: 60px 0;
                text-align: center;
            }

            .about-hero h1 {
                font-size: 3rem;
            }

            .about-hero .lead {
                font-size: 1.2rem;
            }

            .about-hero img {
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
                        <a class="nav-link active" href="about.php">About</a>
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
    <div class="about-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Our Story</h1>
                    <p class="lead">Crafting quality water bottles with Filipino pride since 2024. We combine traditional craftsmanship with modern innovation to create products that make a difference.</p>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/about-us.jpg" alt="About Us" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="mission-vision-card">
                    <i class="fas fa-bullseye"></i>
                    <h3>Our Mission</h3>
                    <p>To provide high-quality, customizable water bottles that promote sustainability while supporting local craftsmanship and Filipino ingenuity. We strive to make a positive impact on both our customers and the environment.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mission-vision-card">
                    <i class="fas fa-eye"></i>
                    <h3>Our Vision</h3>
                    <p>To become the leading provider of custom water bottles in the Philippines, known for quality, innovation, and customer satisfaction. We aim to inspire sustainable living through our products and practices.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Values -->
    <div class="values-section">
        <div class="container">
            <h2 class="text-center mb-5 display-4 fw-bold">Our Values</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="value-card">
                        <i class="fas fa-heart"></i>
                        <h4>Quality</h4>
                        <p>We never compromise on the quality of our products, ensuring each bottle meets our high standards.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="value-card">
                        <i class="fas fa-leaf"></i>
                        <h4>Sustainability</h4>
                        <p>Eco-friendly materials and practices in everything we do, from production to packaging.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="value-card">
                        <i class="fas fa-handshake"></i>
                        <h4>Integrity</h4>
                        <p>Honest and transparent in all our business dealings, building trust with our customers.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="value-card">
                        <i class="fas fa-users"></i>
                        <h4>Community</h4>
                        <p>Supporting local artisans and Filipino craftsmanship, creating opportunities for growth.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Section -->
    <div class="team-section">
        <div class="container">
            <h2 class="text-center mb-5 display-4 fw-bold">Meet Our Team</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="assets/images/Mark-Miasis.jpg" alt="Mark Miasis">
                        <div class="card-body">
                            <h5>Mark Miasis</h5>
                            <p class="text-muted">Founder & CEO</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="assets/images/Mark-Miasis.jpg" alt="Mark Miasis">
                        <div class="card-body">
                            <h5>Mark Miasis</h5>
                            <p class="text-muted">Creative Director</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="assets/images/Mark-Miasis.jpg" alt="Mark Miasis">
                        <div class="card-body">
                            <h5>Mark Miasis</h5>
                            <p class="text-muted">Production Manager</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
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
    <script src="assets/js/main.js"></script>
</body>
</html> 