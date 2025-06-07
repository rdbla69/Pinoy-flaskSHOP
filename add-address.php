<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $address_name = trim($_POST['address_name']);
    $street_address = trim($_POST['street_address']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postal_code = trim($_POST['postal_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    try {
        // If this is set as default, unset any existing default address
        if ($is_default) {
            $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }

        // Insert the new address
        $stmt = $conn->prepare("
            INSERT INTO addresses (user_id, address_name, street_address, barangay, city, state, postal_code, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("issssssi", 
            $user_id,
            $address_name,
            $street_address,
            $barangay,
            $city,
            $state,
            $postal_code,
            $is_default
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Address added successfully!";
        } else {
            throw new Exception("Error adding address: " . $stmt->error);
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