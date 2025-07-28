<?php
header('Content-Type: application/json');

// Database connection (Assuming the same credentials as your other files)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "grocery_shopping_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Return error as JSON
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database Connection failed: ' . $conn->connect_error]);
    exit;
}

// Get search query from the frontend (expecting it via GET request, e.g., ?query=apple)
$search_query = isset($_GET['query']) ? $_GET['query'] : '';

$products = [];

if (!empty($search_query)) {
    // Prepare SQL query to search for products by name
    // Using LIKE for partial matches, and % wildcards
    $sql = "SELECT product_id, category, product_name, price, availability
            FROM products
            WHERE product_name LIKE ?";

    $stmt = $conn->prepare($sql);

    // Bind the search query parameter, adding % wildcards for partial matching
    $search_term = "%" . $search_query . "%";
    $stmt->bind_param("s", $search_term);

    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
}

$conn->close();

// Return results as JSON
echo json_encode($products);
?>
