<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "grocery_shopping_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Check if passwords match
if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
    exit();
}

// Check if email already exists
$sql = "SELECT * FROM admin WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo "<script>alert('Email already registered as admin!'); window.history.back();</script>";
    exit();
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into admin table
$sql = "INSERT INTO admin (name, email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $name, $email, $hashed_password);

if ($stmt->execute()) {
    echo "<script>alert('Admin registered successfully!'); window.location.href='index.html';</script>";
} else {
    echo "<script>alert('Error registering admin.'); window.history.back();</script>";
}

$stmt->close();
$conn->close();

error_log("Trying to log in as admin: $name");
?>