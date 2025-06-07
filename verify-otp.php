<?php
session_start();
require_once 'config/database.php';
require_once 'config/email.php';

$error = '';
$success = '';
$show_login = false;

// Check if user is coming from registration
if (!isset($_SESSION['verify_email'])) {
    header("location: register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle resend OTP
    if (isset($_POST['resend'])) {
        $email = $_SESSION['verify_email'];
        
        // Check if last OTP was sent less than 1 minute ago
        $sql = "SELECT otp_expires_at FROM users WHERE email = ? AND email_verified = 0";
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
                    $sql = "UPDATE users SET otp = ?, otp_expires_at = ? WHERE email = ? AND email_verified = 0";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sss", $otp, $otp_expires, $email);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            if (sendOTPEmail($email, $otp)) {
                                $success = "New verification code has been sent to your email.";
                                // Store expiration time in session for countdown
                                $_SESSION['otp_expires'] = strtotime($otp_expires);
                            } else {
                                $error = "Failed to send verification code. Please try again.";
                            }
                        } else {
                            $error = "Something went wrong. Please try again later.";
                        }
                    }
                }
            } else {
                $error = "User not found. Please register again.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    // Handle OTP verification
    elseif (isset($_POST['otp'])) {
        // Combine OTP digits if it's an array
        $otp = is_array($_POST['otp']) ? implode('', $_POST['otp']) : trim($_POST['otp']);
        $email = $_SESSION['verify_email'];
        
        if (empty($otp)) {
            $error = "Please enter the verification code.";
        } else {
            // First get the current OTP from database
            $sql = "SELECT id, otp, otp_expires_at FROM users WHERE email = ? AND email_verified = 0";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($row = mysqli_fetch_assoc($result)) {
                        // Check expiration time
                        $expires_at = strtotime($row['otp_expires_at']);
                        $now = time();
                        
                        if ($now <= $expires_at) {
                            // Compare OTPs
                            if ($row['otp'] === $otp) {
                                // Update user as verified
                                $sql = "UPDATE users SET email_verified = 1, otp = NULL, otp_expires_at = NULL WHERE email = ?";
                                if ($stmt = mysqli_prepare($conn, $sql)) {
                                    mysqli_stmt_bind_param($stmt, "s", $email);
                                    
                                    if (mysqli_stmt_execute($stmt)) {
                                        unset($_SESSION['verify_email']);
                                        $success = "Email verified successfully! You can now login.";
                                        $show_login = true;
                                    } else {
                                        $error = "Something went wrong. Please try again later.";
                                    }
                                }
                            } else {
                                $error = "Invalid verification code. Please try again.";
                            }
                        } else {
                            $error = "Verification code has expired. Please request a new one.";
                        }
                    } else {
                        $error = "User not found. Please register again.";
                    }
                } else {
                    $error = "Oops! Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Get OTP expiration time if not set in session
if (!isset($_SESSION['otp_expires'])) {
    $sql = "SELECT otp_expires_at FROM users WHERE email = ? AND email_verified = 0";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $_SESSION['verify_email']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['otp_expires'] = strtotime($row['otp_expires_at']);
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Pinoy Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Verify Your Email</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <?php if ($show_login): ?>
                                    <div class="mt-3">
                                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$success || !$show_login): ?>
                            <p class="text-center mb-4">Please enter the 6-digit verification code sent to your email.</p>
                            
                            <div class="text-center mb-4">
                                <div id="countdown" class="text-muted"></div>
                            </div>
                            
                            <!-- Verification Form -->
                            <form method="POST" action="" id="verifyForm">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php for($i = 1; $i <= 6; $i++): ?>
                                            <input type="text" class="form-control text-center otp-input" 
                                                   style="width: 50px; font-size: 24px;" 
                                                   maxlength="1" pattern="[0-9]" inputmode="numeric"
                                                   name="otp[]" required>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Verify Email</button>
                                </div>
                            </form>

                            <!-- Separate Resend Form -->
                            <form method="POST" action="" id="resendForm" class="mt-3">
                                <div class="d-grid">
                                    <button type="submit" name="resend" class="btn btn-outline-primary" id="resendBtn">
                                        Resend Code
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // OTP Input Handling
        const otpInputs = document.querySelectorAll('.otp-input');
        
        otpInputs.forEach((input, index) => {
            // Handle input
            input.addEventListener('input', function() {
                if (this.value.length === 1) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                }
            });
            
            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
            
            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, 6);
                if (/^\d+$/.test(pastedData)) {
                    pastedData.split('').forEach((digit, i) => {
                        if (otpInputs[i]) {
                            otpInputs[i].value = digit;
                        }
                    });
                    if (otpInputs[pastedData.length]) {
                        otpInputs[pastedData.length].focus();
                    }
                }
            });
        });

        // Countdown Timer
        <?php if (isset($_SESSION['otp_expires'])): ?>
        const expiryTime = <?php echo $_SESSION['otp_expires']; ?> * 1000;
        const countdownElement = document.getElementById('countdown');
        const resendButton = document.getElementById('resendBtn');
        
        function updateCountdown() {
            const now = new Date().getTime();
            const timeLeft = expiryTime - now;
            
            if (timeLeft <= 0) {
                countdownElement.innerHTML = "Code expired";
                resendButton.disabled = false;
                return;
            }
            
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            countdownElement.innerHTML = `Code expires in: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            resendButton.disabled = true;
            
            setTimeout(updateCountdown, 1000);
        }
        
        updateCountdown();
        <?php endif; ?>
    </script>
</body>
</html> 