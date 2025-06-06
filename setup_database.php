<?php
require_once 'config/database.php';

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email_verified BOOLEAN DEFAULT FALSE,
    otp VARCHAR(6),
    otp_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create password reset tokens table
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Password resets table created successfully<br>";
} else {
    echo "Error creating password resets table: " . $conn->error . "<br>";
}

// Check if full_name column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
if ($result->num_rows == 0) {
    // Add full_name column if it doesn't exist
    $sql = "ALTER TABLE users ADD COLUMN full_name VARCHAR(255) NOT NULL AFTER username";
    if ($conn->query($sql) === TRUE) {
        echo "Added full_name column successfully<br>";
    } else {
        echo "Error adding full_name column: " . $conn->error . "<br>";
    }
} else {
    echo "full_name column already exists<br>";
}

// Check if phone column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
if ($result->num_rows == 0) {
    // Add phone column if it doesn't exist
    $sql = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER password";
    if ($conn->query($sql) === TRUE) {
        echo "Added phone column successfully<br>";
    } else {
        echo "Error adding phone column: " . $conn->error . "<br>";
    }
} else {
    echo "phone column already exists<br>";
}

// Check if OTP columns exist
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'otp'");
if ($result->num_rows == 0) {
    // Add OTP columns if they don't exist
    $sql = "ALTER TABLE users 
            ADD COLUMN otp VARCHAR(6) AFTER email_verified,
            ADD COLUMN otp_expires_at TIMESTAMP NULL AFTER otp";
    if ($conn->query($sql) === TRUE) {
        echo "Added OTP columns successfully<br>";
    } else {
        echo "Error adding OTP columns: " . $conn->error . "<br>";
    }
} else {
    echo "OTP columns already exist<br>";
}

// Close the connection
$conn->close();
?> 