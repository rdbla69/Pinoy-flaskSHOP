<?php
session_start();
require_once 'config/database.php';
require_once 'config/email.php';

$error = '';
$success = '';
$show_otp_form = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle email submission
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Generate OTP
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Store OTP in database
                $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expires_at = ? WHERE email = ?");
                $stmt->bind_param("sss", $otp, $otp_expires, $email);
                
                if ($stmt->execute()) {
                    // Send OTP email
                    if (sendOTPEmail($email, $otp)) {
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['otp_expires'] = strtotime($otp_expires);
                        $show_otp_form = true;
                        $success = 'Verification code has been sent to your email.';
                    } else {
                        $error = 'Failed to send verification code. Please try again.';
                    }
                } else {
                    $error = 'An error occurred. Please try again.';
                }
            } else {
                // Don't reveal if email exists or not for security
                $error = 'If your email is registered, you will receive a verification code.';
            }
        }
    }
    // Handle OTP verification
    elseif (isset($_POST['otp'])) {
        $email = $_SESSION['reset_email'];
        $otp = is_array($_POST['otp']) ? implode('', $_POST['otp']) : trim($_POST['otp']);
        
        if (empty($otp)) {
            $error = 'Please enter the verification code.';
        } else {
            // Check if OTP is valid and not expired
            $sql = "SELECT id, otp_expires_at FROM users WHERE email = ? AND otp = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $email, $otp);
                
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($row = mysqli_fetch_assoc($result)) {
                        // Check expiration time
                        $expires_at = strtotime($row['otp_expires_at']);
                        $now = time();
                        
                        if ($now <= $expires_at) {
                            // Store user ID in session for password reset
                            $_SESSION['reset_user_id'] = $row['id'];
                            // Clear OTP
                            $sql = "UPDATE users SET otp = NULL, otp_expires_at = NULL WHERE email = ?";
                            if ($stmt = mysqli_prepare($conn, $sql)) {
                                mysqli_stmt_bind_param($stmt, "s", $email);
                                mysqli_stmt_execute($stmt);
                            }
                            // Redirect to reset password page
                            header("Location: reset-password.php");
                            exit;
                        } else {
                            $error = 'Verification code has expired. Please request a new one.';
                        }
                    } else {
                        $error = 'Invalid verification code.';
                    }
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    // Handle resend OTP
    elseif (isset($_POST['resend'])) {
        $email = $_SESSION['reset_email'];
        
        // Check if last OTP was sent less than 1 minute ago
        $sql = "SELECT otp_expires_at FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $last_otp_time = strtotime($row['otp_expires_at']) - 600; // Subtract 10 minutes to get sent time
                $time_diff = time() - $last_otp_time;
                
                if ($time_diff < 60) { // 60 seconds = 1 minute
                    $wait_time = 60 - $time_diff;
                    $error = "Please wait {$wait_time} seconds before requesting a new code.";
                } else {
                    // Generate new OTP
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Update OTP in database
                    $sql = "UPDATE users SET otp = ?, otp_expires_at = ? WHERE email = ?";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sss", $otp, $otp_expires, $email);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            if (sendOTPEmail($email, $otp)) {
                                $success = "New verification code has been sent to your email.";
                                $_SESSION['otp_expires'] = strtotime($otp_expires);
                            } else {
                                $error = "Failed to send verification code. Please try again.";
                            }
                        } else {
                            $error = "Something went wrong. Please try again later.";
                        }
                    }
                }
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Pinoy Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .forgot-password-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .forgot-password-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .forgot-password-header i {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 5px;
        }
        .btn-reset {
            padding: 0.75rem 1rem;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
        }
        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }
        .alert {
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        .otp-input {
            width: 50px !important;
            font-size: 24px !important;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="forgot-password-container">
            <div class="forgot-password-header">
                <i class="fas fa-lock-open"></i>
                <h2>Forgot Password?</h2>
                <p class="text-muted">
                    <?php if ($show_otp_form): ?>
                        Enter the 6-digit verification code sent to your email.
                    <?php else: ?>
                        Enter your email address and we'll send you a verification code.
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($show_otp_form): ?>
                <!-- OTP Verification Form -->
                <form method="POST" action="" id="otpForm">
                    <div class="mb-4">
                        <div class="d-flex justify-content-center gap-2">
                            <?php for($i = 1; $i <= 6; $i++): ?>
                                <input type="text" class="form-control text-center otp-input" 
                                       maxlength="1" pattern="[0-9]" inputmode="numeric"
                                       name="otp[]" required>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Verify Code</button>
                        <button type="submit" name="resend" class="btn btn-outline-primary" id="resendBtn">
                            Resend Code
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Email Form -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter your registered email" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-reset">
                        <i class="fas fa-paper-plane me-2"></i>Send Verification Code
                    </button>
                </form>
            <?php endif; ?>

            <div class="back-to-login">
                <p class="mb-0">Remember your password? 
                    <a href="login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Back to Login
                    </a>
                </p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($show_otp_form): ?>
        // Auto-focus next input when a digit is entered
        document.querySelectorAll('.otp-input').forEach((input, index) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1) {
                    if (index < 5) {
                        this.nextElementSibling.focus();
                    }
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    this.previousElementSibling.focus();
                }
            });
        });
        
        // Handle form submission
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            const submitter = e.submitter;
            
            if (submitter && submitter.name === 'resend') {
                // Let the form submit normally for resend
                return true;
            } else {
                e.preventDefault();
                const otpInputs = document.querySelectorAll('.otp-input');
                const otp = Array.from(otpInputs).map(input => input.value).join('');
                
                // Create hidden input for combined OTP
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'otp';
                hiddenInput.value = otp;
                
                this.appendChild(hiddenInput);
                this.submit();
            }
        });

        // Countdown timer
        function updateCountdown() {
            const countdownElement = document.createElement('div');
            countdownElement.className = 'text-center text-muted mt-2';
            document.getElementById('otpForm').insertBefore(countdownElement, document.getElementById('resendBtn'));
            
            const resendButton = document.getElementById('resendBtn');
            const expirationTime = <?php echo $_SESSION['otp_expires'] ?? 0; ?> * 1000; // Convert to milliseconds
            
            function update() {
                const now = new Date().getTime();
                const distance = expirationTime - now;
                
                if (distance <= 0) {
                    countdownElement.innerHTML = "Code has expired. Please request a new one.";
                    resendButton.disabled = false;
                    return;
                }
                
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                countdownElement.innerHTML = `Code expires in: ${minutes}:${seconds.toString().padStart(2, '0')}`;
                resendButton.disabled = true;
                
                setTimeout(update, 1000);
            }
            
            update();
        }

        updateCountdown();
        <?php endif; ?>
    </script>
</body>
</html> 