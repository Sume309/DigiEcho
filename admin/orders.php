<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user_id parameter if provided
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Redirect to order management with user filter if user_id is provided
if ($user_id) {
    header("Location: order-management.php?user_id=" . $user_id);
} else {
    header("Location: order-management.php");
}
exit();
?>