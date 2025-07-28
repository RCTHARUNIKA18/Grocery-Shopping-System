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

// Get form data
$product_id = $_POST['product_id'] ?? null;
$category = $_POST['category'] ?? '';
$product_name = $_POST['product_name'] ?? '';
$price = $_POST['price'] ?? 0;
$availability = $_POST['availability'] ?? 'In Stock';

// Validate input
if ($product_id === null || empty($category) || empty($product_name) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'All fields are required and price must be greater than 0']);
    exit();
}

// Prepare and execute the update statement
$stmt = $conn->prepare("UPDATE products SET category = ?, product_name = ?, price = ?, availability = ? WHERE product_id = ?");
$stmt->bind_param("ssdsi", $category, $product_name, $price, $availability, $product_id);

if ($stmt->execute()) {
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        // No rows affected, might mean the product ID wasn't found or data was the same
        echo json_encode(['success' => true, 'message' => 'Product details are the same or product not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating product: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 