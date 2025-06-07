<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $address_id = $_POST['address_id'];
    $address_name = trim($_POST['address_name']);
    $street_address = trim($_POST['street_address']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postal_code = trim($_POST['postal_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    try {
        // Verify that the address belongs to the user
        $stmt = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Address not found or unauthorized access.");
        }

        // If this is set as default, unset any existing default address
        if ($is_default) {
            $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }

        // Update the address
        $stmt = $conn->prepare("
            UPDATE addresses 
            SET address_name = ?, 
                street_address = ?, 
                barangay = ?,
                city = ?, 
                state = ?, 
                postal_code = ?, 
                is_default = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->bind_param("ssssssiii", 
            $address_name,
            $street_address,
            $barangay,
            $city,
            $state,
            $postal_code,
            $is_default,
            $address_id,
            $user_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Address updated successfully!";
        } else {
            throw new Exception("Error updating address: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: account.php#addresses');
    exit();
} else {
    header('Location: account.php');
    exit();
} 