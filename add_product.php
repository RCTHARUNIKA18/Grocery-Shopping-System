<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "grocery_shopping_system");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get form data
$category = $_POST['category'] ?? '';
$product_name = $_POST['product_name'] ?? '';
$price = $_POST['price'] ?? 0;
$availability = $_POST['availability'] ?? 'In Stock';

// Validate input
if (empty($category) || empty($product_name) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'All fields are required and price must be greater than 0']);
    exit();
}

// Prepare and execute the insert statement
$stmt = $conn->prepare("INSERT INTO products (category, product_name, price, availability) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssds", $category, $product_name, $price, $availability);

if ($stmt->execute()) {
    // Get the new product ID
    $new_product_id = $conn->insert_id;
    
    // Also add an entry to the inventory table
    $inventory_stmt = $conn->prepare("INSERT INTO inventory (product_id, stock, product_name) VALUES (?, 50, ?)");
    $inventory_stmt->bind_param("is", $new_product_id, $product_name);
    
    if ($inventory_stmt->execute()) {
        ob_clean(); // Clean output buffer before echoing JSON
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } else {
        ob_clean(); // Clean output buffer before echoing JSON
        echo json_encode(['success' => false, 'message' => 'Error adding to inventory: ' . $inventory_stmt->error]);
    }
    
    $inventory_stmt->close();
    
} else {
    ob_clean(); // Clean output buffer before echoing JSON
    echo json_encode(['success' => false, 'message' => 'Error adding product: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 