<?php
// Start session for login management
session_start();

// Connect to database
$conn = new mysqli("localhost", "root", "", "grocery_shopping_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$name = $_POST['admin_name'];
$password = $_POST['admin_password'];

// Prepare SQL statement to prevent SQL injection
$sql = "SELECT * FROM admin WHERE name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    // Check if password matches
    if (password_verify($password, $admin['password'])) {
        // Successful login - set session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['name'];
        
        // Set cookie as additional authentication method
        setcookie("adminLoggedIn", "true", time() + 3600, "/", "", false, true);
        
        // Redirect to dashboard
        header("Location: admin_dashboard.php?success=logged_in");
        exit();
    } else {
        // Wrong password
        header("Location: admin_login.html?error=invalid_password");
        exit();
    }
} else {
    // Admin not found
    header("Location: admin_login.html?error=admin_not_found");
    exit();
}

$stmt->close();
$conn->close();
?>