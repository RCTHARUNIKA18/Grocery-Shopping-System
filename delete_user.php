<?php

// Set header for JSON response (will be set later based on success/failure)
// header('Content-Type: application/json'); // Removed from here

// Start session (if needed for authentication, although admin auth is in dashboard)
session_start();

// Include database connection details (adjust path as necessary)
$servername = "localhost";
$username = "root"; // Replace with your actual database username
$password = ""; // Replace with your actual database password
$dbname = "grocery_shopping_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Return an error response
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$deletedCount = 0;
$success = false;
$message = 'Invalid request.';

// Check content type to handle single or multiple deletions
$contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($contentType === 'application/json') {
        // Handle JSON request (multiple IDs)
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        if (isset($data['user_ids']) && is_array($data['user_ids']) && !empty($data['user_ids'])) {
            $userIdsToDelete = array_map('intval', $data['user_ids']); // Ensure IDs are integers

            // Create a string of placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($userIdsToDelete), '?'));
            
            // Prepare and execute the DELETE statement for multiple IDs
            $sql = "DELETE FROM user WHERE user_id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            
            // Dynamically bind parameters
            $types = str_repeat('i', count($userIdsToDelete)); // All IDs are integers
            $bind_params = array();
            $bind_params[] = &$types; // First element is the type string
            for ($i = 0; $i < count($userIdsToDelete); $i++) {
                $bind_params[] = &$userIdsToDelete[$i]; // Add IDs by reference
            }

            call_user_func_array(array($stmt, 'bind_param'), $bind_params);

            if ($stmt->execute()) {
                $deletedCount = $stmt->affected_rows;
                $success = true;
                $message = "Deleted {$deletedCount} user(s) successfully.";
                http_response_code(200); // OK
            } else {
                $message = 'Error deleting users: ' . $stmt->error;
                http_response_code(500); // Internal Server Error
            }

            $stmt->close();
        } else {
            $message = 'Invalid or empty user_ids array provided.';
            http_response_code(400); // Bad Request
        }

    } elseif (isset($_POST['user_id'])) {
        // Handle form data request (single ID) - Existing logic
        $userIdToDelete = $conn->real_escape_string($_POST['user_id']);

        // Prepare and execute the DELETE statement
        $sql = "DELETE FROM user WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userIdToDelete); // 'i' indicates integer type
        
        if ($stmt->execute()) {
            // Check if any rows were affected (user was actually deleted)
            if ($stmt->affected_rows > 0) {
                $deletedCount = $stmt->affected_rows;
                $success = true;
                $message = 'User deleted successfully.';
                http_response_code(200); // OK
            } else {
                // User ID not found or no rows affected
                $message = 'User with ID ' . htmlspecialchars($userIdToDelete) . ' not found.';
                http_response_code(404); // Not Found
            }
        } else {
            // Error during execution
            $message = 'Error deleting user: ' . $conn->error;
            http_response_code(500); // Internal Server Error
        }

        $stmt->close();

    } else {
        // POST request with no user_id and not JSON
        http_response_code(400); // Bad Request
        $message = 'Missing user_id parameter.';
    }

} else {
    // Not a POST request
    http_response_code(405); // Method Not Allowed
    $message = 'Method not allowed.';
}

$conn->close();

// Always return JSON response
header('Content-Type: application/json');
echo json_encode(['success' => $success, 'message' => $message, 'deleted_count' => $deletedCount]);

?> 