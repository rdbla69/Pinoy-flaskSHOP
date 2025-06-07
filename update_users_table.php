<?php
require_once 'config/database.php';

// Add is_admin column to users table
$sql = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0";

if ($conn->query($sql)) {
    echo "Successfully added is_admin column to users table.\n";
} else {
    echo "Error adding is_admin column: " . $conn->error . "\n";
}

$conn->close();
?> 