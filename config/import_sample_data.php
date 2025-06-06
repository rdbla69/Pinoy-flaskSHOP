<?php
require_once 'database.php';

// Disable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Drop the table if it exists
$drop_table = "DROP TABLE IF EXISTS products";
if (mysqli_query($conn, $drop_table)) {
    echo "Existing table dropped successfully<br>";
} else {
    echo "Error dropping table: " . mysqli_error($conn) . "<br>";
    exit;
}

// Create the table
$create_table = "CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(50),
    stock INT DEFAULT 0,
    material VARCHAR(100),
    capacity VARCHAR(50),
    color VARCHAR(50),
    dimensions VARCHAR(100),
    weight VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_table)) {
    echo "Table created successfully<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
    exit;
}

// Insert sample data
$insert_data = "INSERT INTO products (name, description, price, image_url, category, stock, material, capacity, color, dimensions, weight) VALUES
('Premium Stainless Steel Flask', 'High-quality stainless steel water bottle with double-wall insulation. Keeps drinks cold for 24 hours and hot for 12 hours. Perfect for outdoor activities, gym, or daily use.', 29.99, 'assets/images/products/flask1.png', 'Stainless Steel', 50, '304 Stainless Steel', '500ml', 'Silver', '7.5 x 7.5 x 25 cm', '250g'),
('Sport Water Bottle', 'Lightweight and durable sports water bottle with a wide mouth for easy filling and cleaning. Features a leak-proof cap and comfortable grip.', 19.99, 'assets/images/products/flask2.png', 'Sports', 75, 'BPA-free Plastic', '750ml', 'Blue', '8 x 8 x 28 cm', '180g'),
('Insulated Travel Mug', 'Perfect for coffee and tea lovers. Double-wall insulation keeps beverages hot for hours. Includes a spill-proof lid and comfortable handle.', 24.99, 'assets/images/products/flask3.png', 'Travel', 30, 'Stainless Steel', '400ml', 'Black', '8 x 8 x 20 cm', '300g'),
('Kids Water Bottle', 'Colorful and fun water bottle designed for children. Features a spill-proof straw and easy-grip design. BPA-free and dishwasher safe.', 14.99, 'assets/images/products/flask4.png', 'Kids', 100, 'BPA-free Plastic', '350ml', 'Pink', '6 x 6 x 18 cm', '150g')";

if (mysqli_query($conn, $insert_data)) {
    echo "Sample data inserted successfully<br>";
} else {
    echo "Error inserting sample data: " . mysqli_error($conn) . "<br>";
    exit;
}

// Re-enable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

echo "Sample data import completed!";
?> 