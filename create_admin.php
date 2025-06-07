<?php
require_once 'config/database.php';

// Admin credentials
$admin_email = 'admin@pinoyflask.com';
$admin_username = 'admin';
$admin_password = 'password';
$is_admin = 1;

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$stmt->bind_param("ss", $admin_email, $admin_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Admin user already exists!\n";
    echo "Email: " . $admin_email . "\n";
    echo "Username: " . $admin_username . "\n";
    echo "Password: " . $admin_password . "\n";
} else {
    // Insert admin user
    $stmt = $conn->prepare("INSERT INTO users (email, username, password, is_admin) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $admin_email, $admin_username, $hashed_password, $is_admin);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!\n";
        echo "Email: " . $admin_email . "\n";
        echo "Username: " . $admin_username . "\n";
        echo "Password: " . $admin_password . "\n";
    } else {
        echo "Error creating admin user: " . $stmt->error . "\n";
    }
}

$stmt->close();
$conn->close();
?> 