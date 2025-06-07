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
        
        // Update all pending orders to cancelled status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET order_status = 'cancelled', 
                updated_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND order_status = 'pending'
        ");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success'] = "All pending orders have been cancelled successfully.";
        } else {
            throw new Exception("Error cancelling orders.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "An error occurred while cancelling orders. Please try again.";
    }
}

header('Location: account.php#orders');
exit();
?> 