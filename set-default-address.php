<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['address_id'])) {
    $user_id = $_SESSION['user_id'];
    $address_id = (int)$_POST['address_id'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Unset any existing default address
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Set new default address
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success'] = "Default address updated successfully.";
        } else {
            throw new Exception("Failed to update default address.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "An error occurred while updating the default address. Please try again.";
    }
}

header('Location: account.php');
exit();
?> 