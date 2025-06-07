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
    $address_id = $_POST['address_id'];

    try {
        // Verify that the address belongs to the user
        $stmt = $conn->prepare("SELECT id, is_default FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Address not found or unauthorized access.");
        }

        $address = $result->fetch_assoc();

        // Delete the address
        $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting address: " . $stmt->error);
        }

        // If the deleted address was the default, set another address as default
        if ($address['is_default']) {
            $stmt = $conn->prepare("
                UPDATE addresses 
                SET is_default = 1 
                WHERE user_id = ? 
                ORDER BY id ASC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }

        $_SESSION['success'] = "Address deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: account.php#addresses');
    exit();
} else {
    header('Location: account.php');
    exit();
}
?> 