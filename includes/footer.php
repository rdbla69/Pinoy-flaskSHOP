<!-- Footer -->
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <!-- About Section -->
            <div class="col-lg-4 col-md-6">
                <h5 class="text-uppercase mb-4 fw-bold text-white">
                    <i class="fa-solid fa-droplet me-2"></i>Pinoyflask
                </h5>
                <p class="text-white-50">Your trusted source for high-quality custom water bottles. We provide premium products with excellent customer service.</p>
                <div class="social-links mt-4">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase mb-4 fw-bold text-white">Quick Links</h6>
                <ul class="list-unstyled footer-links">
                    <li class="mb-2"><a href="about.php" class="text-white text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="products.php" class="text-white text-decoration-none">Products</a></li>
                    <li class="mb-2"><a href="contact.php" class="text-white text-decoration-none">Contact</a></li>
                    <li class="mb-2"><a href="faq.php" class="text-white text-decoration-none">FAQ</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase mb-4 fw-bold text-white">Customer Service</h6>
                <ul class="list-unstyled footer-links">
                    <li class="mb-2"><a href="shipping.php" class="text-white text-decoration-none">Shipping Info</a></li>
                    <li class="mb-2"><a href="returns.php" class="text-white text-decoration-none">Returns</a></li>
                    <li class="mb-2"><a href="privacy.php" class="text-white text-decoration-none">Privacy Policy</a></li>
                    <li class="mb-2"><a href="terms.php" class="text-white text-decoration-none">Terms & Conditions</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-uppercase mb-4 fw-bold text-white">Contact Us</h6>
                <ul class="list-unstyled footer-contact">
                    <li class="mb-3 text-white">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        123 Water Street, Manila, Philippines
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-phone me-2"></i>
                        <a href="tel:+631234567890" class="text-white text-decoration-none">+63 123 456 7890</a>
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-envelope me-2"></i>
                        <a href="mailto:info@pinoyflask.com" class="text-white text-decoration-none">info@pinoyflask.com</a>
                    </li>
                    <li class="text-white">
                        <i class="fas fa-clock me-2"></i>
                        Mon - Fri: 9:00 AM - 6:00 PM
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Bottom Footer -->
<div class="bg-darker text-white-50 py-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Pinoyflask. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                <div class="payment-methods">
                    <img src="assets/images/GCash_2019.svg" alt="GCash" class="gcash-logo" style="height: 35px;">
                    <span class="ms-2">We accept GCash payments</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
footer {
    margin-top: auto;
}

footer h5, footer h6 {
    position: relative;
    padding-bottom: 10px;
}

footer h5:after, footer h6:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 50px;
    height: 2px;
    background-color: #0d6efd;
}

.footer-links a, .footer-contact a {
    transition: all 0.3s ease;
    opacity: 0.8;
}

.footer-links a:hover, .footer-contact a:hover {
    color: #0d6efd !important;
    padding-left: 5px;
    opacity: 1;
}

.social-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    opacity: 0.8;
}

.social-links a:hover {
    background-color: #0d6efd;
    transform: translateY(-3px);
    opacity: 1;
}

.payment-methods {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.gcash-logo {
    filter: brightness(0) invert(1);
    transition: all 0.3s ease;
}

.gcash-logo:hover {
    transform: scale(1.1);
}

.bg-darker {
    background-color: #1a1a1a;
}

@media (max-width: 768px) {
    footer {
        text-align: center;
    }
    
    footer h5:after, footer h6:after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    .social-links {
        justify-content: center;
    }

    .payment-methods {
        justify-content: center;
        margin-top: 1rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html> 