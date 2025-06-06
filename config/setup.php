<?php
// Add full_name column to users table
$sql = "ALTER TABLE users ADD COLUMN full_name VARCHAR(255) AFTER username";
if ($conn->query($sql) === TRUE) {
    echo "Added full_name column to users table successfully<br>";
} else {
    echo "Error adding full_name column: " . $conn->error . "<br>";
} 