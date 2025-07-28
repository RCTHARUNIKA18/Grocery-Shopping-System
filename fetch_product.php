<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "grocery_shopping_system");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Get product ID from request
$product_id = $_GET['product_id'] ?? $_POST['product_id'] ?? null;

// Validate product ID
if ($product_id === null) {
    echo json_encode(['success' => false, 'message' => 'Product ID not provided.']);
    exit();
}

// Prepare and execute the select statement
$stmt = $conn->prepare("SELECT product_id, category, product_name, price, availability FROM products WHERE product_id = ? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    echo json_encode(['success' => true, 'product' => $product]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
}

$stmt->close();
$conn->close();
?> 