<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First delete all order items
    $sql = "DELETE oi FROM order_items oi 
            INNER JOIN orders o ON oi.order_id = o.id 
            WHERE o.user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    
    // Then delete all orders
    $sql = "DELETE FROM orders WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    $_SESSION['success'] = "Your order history has been cleared successfully.";
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    $_SESSION['error'] = "An error occurred while clearing order history. Please try again.";
}

// Redirect back to account page
header('Location: account.php');
exit();
?> 