<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Not authorized']));
}

// Database connection
$conn = new mysqli("localhost", "root", "", "grocery_shopping_system");
if ($conn->connect_error) {
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Get data from the AJAX request
$data = json_decode(file_get_contents('php://input'), true);

$productIds = isset($data['product_ids']) ? $data['product_ids'] : [];
$increaseAmount = isset($data['increase_amount']) ? (int)$data['increase_amount'] : 50;

// Validate input
if (empty($productIds) || !is_array($productIds)) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Invalid or empty product IDs provided.']));
}

// Prepare for batch update
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$types = str_repeat('i', count($productIds)); // 'i' for integer

// SQL query to update stock
$sql = "UPDATE inventory SET stock = stock + ? WHERE product_id IN ($placeholders)";

// Prepare and bind parameters
$stmt = $conn->prepare($sql);

// We need to dynamically bind the increaseAmount and productIds
// The bind_param expects parameters by reference, so we use call_user_func_array
$params = [$increaseAmount];
foreach ($productIds as $id) {
    $params[] = &$id; // Pass by reference
}

// The types string needs to include the type for increaseAmount ('i')
$bind_types = 'i' . $types;

// Use call_user_func_array to bind parameters
call_user_func_array([$stmt, 'bind_param'], array_merge([$bind_types], $params));

// Execute the update query
if ($stmt->execute()) {
    $rowsAffected = $stmt->affected_rows;
    $message = "Successfully updated stock for " . $rowsAffected . " product(s).";
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error updating inventory: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?> 