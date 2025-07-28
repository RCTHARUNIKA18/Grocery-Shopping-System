<?php
// add_to_cart.php - Handles adding products to cart

// Start session to access user ID
session_start();

// Log the received POST data for debugging
error_log("add_to_cart.php received POST data: " . print_r($_POST, true));

// Database connection parameters
$host = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$database = "grocery_shopping_system";     // Replace with your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Check if required parameters are set
if (!isset($_POST['product_id']) || !isset($_POST['user_id']) || !isset($_POST['quantity'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]));
}

// Get parameters
$product_id = $_POST['product_id'];
$user_id = $_POST['user_id'];
$quantity = $_POST['quantity'];

// Validate parameters
if (!is_numeric($product_id) || !is_numeric($user_id) || !is_numeric($quantity)) {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]));
}

// Validate that user_id matches the logged-in user
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized user'
    ]));
}

// Check if product already exists in cart
$stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Product exists in cart, update quantity
    $row = $result->fetch_assoc();
    $new_quantity = $row['quantity'] + $quantity;
    
    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, last_updated = NOW() WHERE user_id = ? AND product_id = ?");
    $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update cart: ' . $conn->error
        ]);
    }
    
    $update_stmt->close();
} else {
    // Product doesn't exist in cart, insert new record
    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, last_updated) VALUES (?, ?, ?, NOW())");
    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add product to cart: ' . $conn->error
        ]);
    }
    
    $insert_stmt->close();
}

// Close statement and connection
$stmt->close();
$conn->close();
?>