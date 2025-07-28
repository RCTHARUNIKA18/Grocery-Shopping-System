<?php
session_start();

// Database connection details (adjust if necessary)
$servername = "localhost";
$username = "root";
$password = ""; // Your database password
$dbname = "grocery_shopping_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error and return a JSON response
    error_log("Database Connection failed: " . $conn->connect_error);
    die(json_encode(['success' => false, 'message' => 'Database connection failed. Please check server logs for details.']));
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data from the request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Check if order_ids are provided and is an array
    if (isset($data['order_ids']) && is_array($data['order_ids']) && !empty($data['order_ids'])) {
        $orderIds = $data['order_ids'];
        $deleted_orders_count = 0;
        $deleted_payments_count = 0;
        $errors = [];

        // Disable foreign key checks temporarily to avoid issues during deletion
        $conn->query("SET foreign_key_checks = 0");

        // Prepare statement for deleting payments
        $stmt_payments = $conn->prepare("DELETE FROM payment WHERE order_id = ?");
        
        // Prepare statement for deleting orders
        $stmt_orders = $conn->prepare("DELETE FROM orders WHERE order_id = ?");

        if ($stmt_payments === false || $stmt_orders === false) {
             $response = ['success' => false, 'message' => 'Error preparing statements: ' . $conn->error];
        } else {
            $stmt_payments->bind_param("i", $orderId);
            $stmt_orders->bind_param("i", $orderId);

            foreach ($orderIds as $id) {
                // Sanitize the ID and ensure it's an integer
                $orderId = (int)$id;

                // 1. Delete related payments first
                if ($stmt_payments->execute()) {
                    $deleted_payments_count += $stmt_payments->affected_rows;
                } else {
                    error_log("Error deleting payments for order ID {$id}: " . $stmt_payments->error);
                    $errors[] = "Error deleting payments for order ID {$id}. Check server logs.";
                }

                // 2. Delete the order
                if ($stmt_orders->execute()) {
                    if ($stmt_orders->affected_rows > 0) {
                        $deleted_orders_count++;
                    } else {
                        $errors[] = "Order ID {$id} not found after attempting to delete payments.";
                    }
                } else {
                    // Log the specific execution error for other issues
                    error_log("Error deleting order ID {$id}: " . $stmt_orders->error);
                    $errors[] = "Error deleting order ID {$id}. Check server logs for details.";
                }
            }

            $stmt_payments->close();
            $stmt_orders->close();
            
            // Re-enable foreign key checks
            $conn->query("SET foreign_key_checks = 1");

            if ($deleted_orders_count > 0) {
                $response = ['success' => true, 'message' => "Successfully deleted {$deleted_orders_count} order(s) and {$deleted_payments_count} associated payment(s)."];
                if (!empty($errors)) {
                    $response['message'] .= " However, some issues occurred: " . implode(", ", $errors);
                }
            } else {
                 // Re-enable foreign key checks in case of early exit
                 $conn->query("SET foreign_key_checks = 1");
                $response = ['success' => false, 'message' => "No orders were deleted. " . implode(", ", $errors)];
            }
        }

    } else {
         // Re-enable foreign key checks in case of early exit
         $conn->query("SET foreign_key_checks = 1");
        $response = ['success' => false, 'message' => 'No order IDs provided or invalid format.'];
    }

} else {
     // Re-enable foreign key checks in case of early exit
     $conn->query("SET foreign_key_checks = 1");
    $response = ['success' => false, 'message' => 'Invalid request method.'];
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?> 