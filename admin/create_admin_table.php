<?php
require_once '../config/database.php';

// Create admin_users table
$sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Admin users table created successfully\n";
} else {
    echo "Error creating admin users table: " . $conn->error . "\n";
}

// Create default admin user
$email = "admin@example.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$full_name = "Admin User";

// Check if admin user already exists
$stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Insert default admin user
    $stmt = $conn->prepare("INSERT INTO admin_users (email, password, full_name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $password, $full_name);
    
    if ($stmt->execute()) {
        echo "Default admin user created successfully\n";
        echo "Email: admin@example.com\n";
        echo "Password: admin123\n";
    } else {
        echo "Error creating default admin user: " . $stmt->error . "\n";
    }
} else {
    echo "Default admin user already exists\n";
}

$stmt->close();
$conn->close();
?> 