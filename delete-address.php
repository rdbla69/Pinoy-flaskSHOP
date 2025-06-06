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
        // Delete the address
        $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Address deleted successfully.";
        } else {
            throw new Exception("Failed to delete address.");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred while deleting the address. Please try again.";
    }
}

header('Location: account.php');
exit();
?> 