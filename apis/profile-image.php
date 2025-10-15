<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
$db = new MysqliDb();

$userId = $_SESSION['userid'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'upload') {
        // Handle image upload
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
            exit;
        }
        
        $file = $_FILES['profile_image'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
            exit;
        }
        
        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 2MB.']);
            exit;
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../assets/uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        // Get current profile to delete old image
        $currentProfile = $db->where('user_id', $userId)->getOne('user_profiles');
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Update database
            $updateData = [
                'profile_image' => $filename,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->where('user_id', $userId)->update('user_profiles', $updateData);
            
            // Delete old image if exists
            if (!empty($currentProfile['profile_image'])) {
                $oldImagePath = $uploadDir . $currentProfile['profile_image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile image updated successfully',
                'filename' => $filename
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        }
        
    } elseif ($action === 'remove') {
        // Handle image removal
        $currentProfile = $db->where('user_id', $userId)->getOne('user_profiles');
        
        if (!empty($currentProfile['profile_image'])) {
            // Delete file
            $imagePath = __DIR__ . '/../assets/uploads/profiles/' . $currentProfile['profile_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            
            // Update database
            $updateData = [
                'profile_image' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->where('user_id', $userId)->update('user_profiles', $updateData);
            
            echo json_encode(['success' => true, 'message' => 'Profile image removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No profile image to remove']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Profile image error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing the request']);
}

$db->disconnect();
?>
