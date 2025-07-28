<?php
// cart_count.php - Returns the number of items in the user's cart

// Start session to access user ID
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Database connection parameters
$servername = "localhost";
$username = "root"; // Replace with your actual database username
$password = ""; // Replace with your actual database password
$dbname = "grocery_shopping_system"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['count' => 0, 'error' => 'Database connection failed']);
    exit;
}

// Prepare and execute query to get cart count
$stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Return count (or 0 if no items)
echo json_encode(['count' => $row['total'] ?? 0]);

// Close statement and connection
$stmt->close();
$conn->close();
?>