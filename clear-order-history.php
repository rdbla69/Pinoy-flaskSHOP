<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // First, delete all order items
        $stmt = $conn->prepare("
            DELETE oi FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Then, delete all orders
        $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success'] = "Order history has been cleared successfully.";
        } else {
            throw new Exception("Error clearing order history.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "An error occurred while clearing order history. Please try again.";
    }
}

header('Location: account.php#orders');
exit();
?> 