<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$valid_session = false;

// Check if user has verified their email through OTP
if (isset($_SESSION['reset_user_id'])) {
    $valid_session = true;
} else {
    $error = "Please verify your email first.";
    header("Location: forgot-password.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_session) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['reset_user_id'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must have at least 6 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } else {
        // First check if the user's email is verified
        $check_sql = "SELECT email_verified FROM users WHERE id = ?";
        if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "i", $user_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $email_verified);
            mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt);

            // Update password while maintaining email verification status
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Clear session variables
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['otp_expires']);
                    
                    $success = "Password has been reset successfully. You can now login with your new password.";
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Pinoy Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .reset-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .reset-header i {
            font-size: 3.5rem;
            color: #0d6efd;
            margin-bottom: 1rem;
            background: #e7f1ff;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
        }
        .password-field {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .btn-reset {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        .strength-weak { background-color: #dc3545; width: 33.33%; }
        .strength-medium { background-color: #ffc107; width: 66.66%; }
        .strength-strong { background-color: #198754; width: 100%; }
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }
        .requirement i {
            margin-right: 0.5rem;
            font-size: 0.75rem;
        }
        .requirement.met {
            color: #198754;
        }
        .requirement.unmet {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="reset-container">
            <div class="reset-header">
                <i class="fas fa-lock"></i>
                <h2>Reset Your Password</h2>
                <p class="text-muted">Please enter your new password below.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                        </a>
                    </div>
                </div>
            <?php elseif ($valid_session): ?>
                <form method="POST" action="" id="resetForm">
                    <div class="password-field">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-strength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <div class="password-requirements">
                            <div class="requirement" id="length">
                                <i class="fas fa-circle"></i>
                                At least 6 characters
                            </div>
                            <div class="requirement" id="uppercase">
                                <i class="fas fa-circle"></i>
                                One uppercase letter
                            </div>
                            <div class="requirement" id="lowercase">
                                <i class="fas fa-circle"></i>
                                One lowercase letter
                            </div>
                            <div class="requirement" id="number">
                                <i class="fas fa-circle"></i>
                                One number
                            </div>
                        </div>
                    </div>
                    
                    <div class="password-field">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-reset">
                            <i class="fas fa-key me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <?php if (!$valid_session && !$success): ?>
                <div class="text-center mt-3">
                    <a href="forgot-password.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Forgot Password
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        const password = document.getElementById('password');
        const strengthBar = document.querySelector('.password-strength-bar');
        const requirements = {
            length: { regex: /.{6,}/, element: document.getElementById('length') },
            uppercase: { regex: /[A-Z]/, element: document.getElementById('uppercase') },
            lowercase: { regex: /[a-z]/, element: document.getElementById('lowercase') },
            number: { regex: /[0-9]/, element: document.getElementById('number') }
        };

        password.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;
            let metRequirements = 0;

            // Check each requirement
            for (const [key, req] of Object.entries(requirements)) {
                if (req.regex.test(value)) {
                    req.element.classList.add('met');
                    req.element.classList.remove('unmet');
                    req.element.querySelector('i').className = 'fas fa-check-circle';
                    metRequirements++;
                } else {
                    req.element.classList.remove('met');
                    req.element.classList.add('unmet');
                    req.element.querySelector('i').className = 'fas fa-circle';
                }
            }

            // Calculate strength
            if (value.length >= 6) strength++;
            if (/[A-Z]/.test(value)) strength++;
            if (/[a-z]/.test(value)) strength++;
            if (/[0-9]/.test(value)) strength++;

            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (strength <= 1) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            let isValid = true;
            
            // Check password requirements
            for (const [key, req] of Object.entries(requirements)) {
                if (!req.regex.test(password)) {
                    isValid = false;
                    break;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please meet all password requirements.');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }

            // If all validations pass, allow form submission
            return true;
        });
    </script>
</body>
</html> 