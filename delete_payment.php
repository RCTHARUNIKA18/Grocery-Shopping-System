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
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data from the request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Check if payment_ids are provided and is an array
    if (isset($data['payment_ids']) && is_array($data['payment_ids']) && !empty($data['payment_ids'])) {
        $paymentIds = $data['payment_ids'];
        $deleted_count = 0;
        $errors = [];

        // Prepare for deletion
        $stmt = $conn->prepare("DELETE FROM payment WHERE payment_id = ?");
        $stmt->bind_param("i", $paymentId);

        foreach ($paymentIds as $id) {
            // Sanitize the ID and ensure it's an integer
            $paymentId = (int)$id;

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $deleted_count++;
                } else {
                    $errors[] = "Payment ID {$id} not found.";
                }
            } else {
                $errors[] = "Error deleting payment ID {$id}: " . $stmt->error;
            }
        }

        $stmt->close();

        if ($deleted_count > 0) {
            $response = ['success' => true, 'message' => "Successfully deleted {$deleted_count} payment(s)."];
            if (!empty($errors)) {
                $response['message'] .= " Some errors occurred: " . implode(", ", $errors);
            }
        } else {
            $response = ['success' => false, 'message' => "No payments were deleted. " . implode(", ", $errors)];
        }

    } else {
        $response = ['success' => false, 'message' => 'No payment IDs provided or invalid format.'];
    }

} else {
    $response = ['success' => false, 'message' => 'Invalid request method.'];
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?> 