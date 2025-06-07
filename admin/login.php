<?php
session_start();
require_once '../config/database.php';

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $stmt = $conn->prepare("SELECT id, email, password FROM admin_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Pinoy Flask</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #000000;
            --secondary-color: #333333;
            --success-color: #1a1a1a;
            --danger-color: #262626;
            --warning-color: #404040;
            --info-color: #4d4d4d;
            --light-color: #f5f5f5;
            --dark-color: #1a1a1a;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--gray-100);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 3rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .login-header p {
            color: var(--gray-600);
            margin: 1rem 0 0;
            font-size: 1rem;
        }

        .form-control {
            border: 1px solid var(--gray-300);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            height: auto;
        }

        .form-control:focus {
            border-color: var(--gray-500);
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.15);
        }

        .form-label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--gray-900);
            border-color: var(--gray-900);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 500;
            font-size: 1rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-primary:hover {
            background-color: var(--gray-800);
            border-color: var(--gray-800);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 12px;
            font-size: 1rem;
            padding: 1.25rem;
            margin-bottom: 2rem;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .input-group-text {
            background-color: var(--gray-100);
            border: 1px solid var(--gray-300);
            color: var(--gray-600);
            padding: 1rem 1.25rem;
            font-size: 1rem;
        }

        .password-toggle {
            cursor: pointer;
            transition: color 0.2s ease;
            font-size: 1.1rem;
        }

        .password-toggle:hover {
            color: var(--gray-800);
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating > .form-control {
            height: calc(3.5rem + 2px);
            padding: 1rem 1.25rem;
        }

        .form-floating > label {
            padding: 1rem 1.25rem;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="brand">
                    <i class="bi bi-droplet-fill"></i>
                    <span class="brand-name">Pinoy Flask</span>
                </div>
                <h1>Admin Login</h1>
                <p>Enter your credentials to access the admin panel</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                    <label for="email">Email address</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Sign In
                </button>
            </form>

            <div class="back-to-site">
                <a href="../index.php">
                    <i class="bi bi-arrow-left"></i>
                    Back to Website
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 