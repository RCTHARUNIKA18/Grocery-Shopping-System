<?php
session_start();

// Include necessary database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "grocery_shopping_system";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']); // true if admin checkbox checked

    if ($is_admin) {
        // Admin login: check in admin table
        $sql = "SELECT * FROM admin WHERE name = ?";
    } else {
        // User login: check in user table
    $sql = "SELECT * FROM user WHERE username = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            // Successful login
            if ($is_admin) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_name'] = $user['name']; // Store admin name in session
                header("Location: admin_dashboard.php");
            } else {
                $_SESSION['user_id'] = $user['user_id']; // Store user ID in session
                $_SESSION['username'] = $user['username']; // Optionally store username
                header("Location: products.php");
            }
            exit();
        } else {
            // Password incorrect
            header("Location: index.html?error=invalid_password");
            exit();
        }
    } else {
        // User/Admin not found
        header("Location: index.html?error=user_not_found");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
