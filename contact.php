<?php
session_start();
require_once 'config/database.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Here you would typically send the email or store in database
        // For now, we'll just show a success message
        $success_message = 'Thank you for your message. We will get back to you soon!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Pinoyflask</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .contact-hero {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.1), rgba(74,74,74,0.1));
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }

        .contact-hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26,26,26,0.1), rgba(74,74,74,0.1));
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
        }

        .contact-hero h1 {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(45deg, #1a1a1a, #4a4a4a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .contact-hero .lead {
            font-size: 1.4rem;
            color: #666;
            line-height: 1.6;
        }

        .contact-hero img {
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.5s ease;
        }

        .contact-hero img:hover {
            transform: translateY(-10px);
        }

        .contact-info-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            height: 100%;
            transition: transform 0.3s ease;
            text-align: center;
        }

        .contact-info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .contact-info-card i {
            font-size: 2.5rem;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
        }

        .contact-info-card h4 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1a1a1a;
        }

        .contact-info-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 0;
        }

        .contact-form-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .contact-form-card h3 {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #1a1a1a;
        }

        .form-control {
            border: 2px solid #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #1a1a1a;
            box-shadow: none;
        }

        .form-label {
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .btn-submit {
            background: linear-gradient(45deg, #1a1a1a, #4a4a4a);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: linear-gradient(45deg, #4a4a4a, #1a1a1a);
        }

        .map-card {
            border-radius: 20px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .business-hours {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 100px 0;
        }

        .business-hours-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .business-hours h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #1a1a1a;
        }

        .business-hours h5 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1a1a1a;
        }

        .business-hours p {
            color: #666;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .contact-hero {
                padding: 60px 0;
                text-align: center;
            }

            .contact-hero h1 {
                font-size: 3rem;
            }

            .contact-hero .lead {
                font-size: 1.2rem;
            }

            .contact-hero img {
                margin-top: 2rem;
            }

            .business-hours-card {
                padding: 2rem;
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
                        <a class="nav-link active" href="contact.php">Contact</a>
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

    <!-- Contact Hero -->
    <div class="contact-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Get in Touch</h1>
                    <p class="lead">We'd love to hear from you. Send us a message and we'll respond as soon as possible. Your feedback and questions are important to us.</p>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/contact.jpeg" alt="Contact Us" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="contact-info-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h4>Our Location</h4>
                    <p>123 Main Street<br>Manila, Philippines</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-info-card">
                    <i class="fas fa-phone"></i>
                    <h4>Phone Number</h4>
                    <p>(123) 456-7890<br>(123) 456-7891</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-info-card">
                    <i class="fas fa-envelope"></i>
                    <h4>Email Address</h4>
                    <p>info@pinoyflask.com<br>support@pinoyflask.com</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Form and Map -->
    <div class="container my-5">
        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-md-6">
                <div class="contact-form-card">
                    <h3>Send us a Message</h3>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form action="contact.php" method="POST">
                        <div class="mb-4">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-4">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-submit">Send Message</button>
                    </form>
                </div>
            </div>
            <!-- Map -->
            <div class="col-md-6">
                <div class="map-card">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.6504901687!2d120.9813!3d14.5995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTTCsDM1JzU4LjIiTiAxMjDCsDU4JzUyLjciRQ!5e0!3m2!1sen!2sph!4v1234567890!5m2!1sen!2sph" 
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Hours -->
    <div class="business-hours">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="business-hours-card text-center">
                        <h2>Business Hours</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Weekdays</h5>
                                <p>Monday - Friday<br>9:00 AM - 6:00 PM</p>
                            </div>
                            <div class="col-md-6">
                                <h5>Weekend</h5>
                                <p>Saturday - Sunday<br>10:00 AM - 4:00 PM</p>
                            </div>
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