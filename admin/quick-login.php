<?php
session_start();

// Include required files
require_once __DIR__ . '/../vendor/autoload.php';

// Simulate login process using the default admin credentials
$db = new MysqliDb();
$db->where("email", "admin@gmail.com");
$row = $db->getOne("users");

if ($row && password_verify('12345', $row['password'])) {
    // Set session variables like the login form does
    $_SESSION['loggedin'] = true;
    $_SESSION['userid'] = $row['id'];
    $_SESSION['username'] = $row['first_name'];
    $_SESSION['role'] = $row['role'];
    
    // Show success message and redirect
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Quick Login</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>
        Swal.fire({
            icon: "success",
            title: "Login Successful!",
            text: "You have been logged in as admin. Redirecting to category management...",
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.location.href = "category-management.php";
        });
    </script>
</body>
</html>';
} else {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Quick Login Error</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>
        Swal.fire({
            icon: "error",
            title: "Login Failed!",
            text: "Please check credentials. Redirecting to login page...",
            timer: 3000,
            showConfirmButton: false
        }).then(() => {
            window.location.href = "../login.php";
        });
    </script>
</body>
</html>';
}
?>