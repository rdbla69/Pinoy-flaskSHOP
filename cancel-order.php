<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order_id is provided
if (!isset($_POST['order_id'])) {
    $_SESSION['error'] = "Invalid request.";
    header('Location: account.php');
    exit();
}

$order_id = $_POST['order_id'];
$user_id = $_SESSION['user_id'];

// Verify that the order belongs to the user and is in pending status
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND order_status = 'pending'");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Order cannot be cancelled.";
    header('Location: account.php');
    exit();
}

// Update order status to cancelled
$stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Order has been cancelled successfully.";
} else {
    $_SESSION['error'] = "Failed to cancel order. Please try again.";
}

header('Location: account.php');
exit(); 