<?php
// Start session to access session variables
session_start();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Delete admin cookie (this might not be set for regular users, but it's safe to keep)
// setcookie("adminLoggedIn", "", time() - 3600, "/"); // Commenting this out as it's specific to admin

// Destroy the session
session_destroy();

// Determine redirect location
$redirect_page = 'admin_login.html?logout=success'; // Default redirect for admin

if (isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'index.html') {
    $redirect_page = 'index.html?logout=success'; // Redirect for regular users
}

// Redirect to the determined page
header("Location: " . $redirect_page);
exit();
?>