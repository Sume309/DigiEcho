<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

if (!isset($_GET['id'])) {
    header('Location: users-all.php');
    exit;
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'Invalid user ID';
    header('Location: users-all.php');
    exit;
}

// Prevent deleting own account
if ($_SESSION['userid'] == $id) {
    $_SESSION['error'] = 'You cannot delete your own account';
    header('Location: users-all.php');
    exit;
}

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Check if user exists
    $user = $db->where('id', $id)->getOne('users');
    if (!$user) {
        $_SESSION['error'] = 'User not found';
        header('Location: users-all.php');
        exit;
    }
    
    // Delete the user
    if ($db->where('id', $id)->delete('users')) {
        $_SESSION['message'] = "User '{$user['first_name']} {$user['last_name']}' has been deleted successfully";
    } else {
        $_SESSION['error'] = 'Failed to delete user. Please try again.';
    }
    
    header('Location: users-all.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header('Location: users-all.php');
    exit;
}