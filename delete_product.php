<?php
ob_start(); // Start output buffering at the very beginning
error_reporting(E_ALL); ini_set('display_errors', 1);

session_start();

header('Content-Type: application/json'); // Set content type early, though it might be reset later

// Initialize response array
$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

// Database connection
$conn = new mysqli("localhost", "root", "", "grocery_shopping_system");
if ($conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . $conn->connect_error;
    ob_clean(); // Clean output buffer
    echo json_encode($response);
    exit();
}

$deletedCount = 0;

try {
    // Check content type to handle single or multiple deletions
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($contentType === 'application/json') {
            // Handle JSON request (multiple IDs)
            $json_data = file_get_contents('php://input');
            $data = json_decode($json_data, true);

            if (isset($data['product_ids']) && is_array($data['product_ids']) && !empty($data['product_ids'])) {
                $productIdsToDelete = array_map('intval', $data['product_ids']); // Ensure IDs are integers

                // Create a string of placeholders for the IN clause
                $placeholders = implode(',', array_fill(0, count($productIdsToDelete), '?'));
                
                // Start a transaction to ensure atomicity
                $conn->begin_transaction();

                // First, delete related entries in the inventory table
                $sql_inventory = "DELETE FROM inventory WHERE product_id IN ($placeholders)";
                $stmt_inventory = $conn->prepare($sql_inventory);
                $types = str_repeat('i', count($productIdsToDelete)); // All IDs are integers
                $bind_params_inventory = array();
                $bind_params_inventory[] = &$types;
                for ($i = 0; $i < count($productIdsToDelete); $i++) {
                    $bind_params_inventory[] = &$productIdsToDelete[$i];
                }
                call_user_func_array(array($stmt_inventory, 'bind_param'), $bind_params_inventory);

                if (!$stmt_inventory->execute()) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $response['message'] = 'Error deleting related inventory: ' . $stmt_inventory->error;
                    http_response_code(500); // Internal Server Error
                    $stmt_inventory->close();
                    throw new \Exception($response['message']); // Throw exception to be caught below
                }
                $stmt_inventory->close();

                // Then, delete entries in the products table
                $sql_products = "DELETE FROM products WHERE product_id IN ($placeholders)";
                $stmt_products = $conn->prepare($sql_products);
                $types = str_repeat('i', count($productIdsToDelete)); // All IDs are integers
                $bind_params_products = array();
                $bind_params_products[] = &$types;
                for ($i = 0; $i < count($productIdsToDelete); $i++) {
                    $bind_params_products[] = &$productIdsToDelete[$i];
                }
                call_user_func_array(array($stmt_products, 'bind_param'), $bind_params_products);

                if ($stmt_products->execute()) {
                    // Commit transaction on success
                    $conn->commit();
                    $deletedCount = $stmt_products->affected_rows;
                    $response['success'] = true;
                    $response['message'] = "Deleted {$deletedCount} product(s) and related inventory successfully.";
                    $response['deleted_count'] = $deletedCount;
                    http_response_code(200); // OK
                } else {
                    // Rollback transaction on error
                    $conn->rollback();
                    $response['message'] = 'Error deleting products: ' . $stmt_products->error;
                    http_response_code(500); // Internal Server Error
                }

                $stmt_products->close();
            } else {
                $response['message'] = 'Invalid or empty product_ids array provided.';
                http_response_code(400); // Bad Request
            }

        } elseif (isset($_POST['product_id'])) {
            // Handle form data request (single ID)
            $productIdToDelete = $conn->real_escape_string($_POST['product_id']);

            // Start a transaction
            $conn->begin_transaction();

            // First, delete related entry in the inventory table
            $sql_inventory = "DELETE FROM inventory WHERE product_id = ?";
            $stmt_inventory = $conn->prepare($sql_inventory);
            $stmt_inventory->bind_param("i", $productIdToDelete);

            if (!$stmt_inventory->execute()) {
                // Rollback transaction on error
                $conn->rollback();
                $response['message'] = 'Error deleting related inventory: ' . $stmt_inventory->error;
                http_response_code(500); // Internal Server Error
                $stmt_inventory->close();
                throw new \Exception($response['message']); // Throw exception
            }
            $stmt_inventory->close();

            // Then, delete the entry in the products table
            $sql_products = "DELETE FROM products WHERE product_id = ?";
            $stmt_products = $conn->prepare($sql_products);
            $stmt_products->bind_param("i", $productIdToDelete);
            
            if ($stmt_products->execute()) {
                // Commit transaction on success
                $conn->commit();
                // Check if any rows were affected (product was actually deleted)
                if ($stmt_products->affected_rows > 0) {
                    $deletedCount = $stmt_products->affected_rows;
                    $response['success'] = true;
                    $response['message'] = 'Product deleted successfully.';
                    $response['deleted_count'] = $deletedCount;
                    http_response_code(200); // OK
                } else {
                    // No rows affected, might mean the product ID wasn't found
                    $response['message'] = 'Product with ID ' . htmlspecialchars($productIdToDelete) . ' not found.';
                    http_response_code(404); // Not Found
                }
            } else {
                // Rollback transaction on error
                $conn->rollback();
                $response['message'] = 'Error deleting product: ' . $conn->error;
                http_response_code(500); // Internal Server Error
            }

            $stmt_products->close();

        } else {
            // POST request with no product_id and not JSON
            http_response_code(400); // Bad Request
            $response['message'] = 'Missing product_id parameter.';
        }

    } else {
        // Not a POST request
        http_response_code(405); // Method Not Allowed
        $response['message'] = 'Method not allowed.';
    }

} catch (\Exception $e) {
    // Catch any exceptions and return a server error
    // Transaction will be implicitly rolled back on exception if not committed
    $response['success'] = false; // Ensure success is false on exception
    $response['message'] = 'Server error during deletion: ' . $e->getMessage();
    http_response_code(500); // Internal Server Error
} finally {
    $conn->close();
    ob_clean(); // Clean output buffer one last time
    echo json_encode($response); // Output the final JSON response
    exit(); // Ensure no further output
}

?> 