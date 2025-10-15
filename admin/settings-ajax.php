<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

use App\auth\Admin;

// Check admin authentication using the same method as other admin pages
if(!Admin::Check()){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new MysqliDb();
    
    // Skip table creation in AJAX calls for better performance
    // Tables should already exist from main settings page
    
    // Initialize default settings if they don't exist
    $defaultSettings = [
        'site_name' => 'DigiEcho',
        'site_description' => 'Your trusted online marketplace',
        'contact_email' => 'info@digiecho.com',
        'contact_phone' => '+880 1234567890',
        'address' => 'Dhaka, Bangladesh',
        'logo' => 'assets/images/logo.png',
        'notice_enabled' => '0',
        'notice_text' => '',
        'notice_type' => 'info'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        $existing = $db->where('setting_key', $key)->getOne('site_settings');
        if (!$existing) {
            $db->insert('site_settings', [
                'setting_key' => $key,
                'setting_value' => $value
            ]);
        }
    }
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Debug logging
    error_log("Action received: " . $action);
    error_log("POST data: " . print_r($_POST, true));
    
    switch ($action) {
        case 'get_banners':
            $pageType = $_GET['page_type'] ?? 'homepage';
            $banners = $db->where('page_type', $pageType)->orderBy('sort_order', 'ASC')->get('banners');
            echo json_encode([
                'success' => true,
                'banners' => $banners
            ]);
            break;
            
        case 'get_banner':
            $id = (int)($_GET['id'] ?? 0);
            $banner = $db->where('id', $id)->getOne('banners');
            
            if ($banner) {
                echo json_encode([
                    'success' => true,
                    'banner' => $banner
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Banner not found'
                ]);
            }
            break;
            
        case 'add_banner':
            $title = $_POST['title'] ?? '';
            $subtitle = $_POST['subtitle'] ?? '';
            $description = $_POST['description'] ?? '';
            $buttonText = $_POST['button_text'] ?? '';
            $buttonLink = $_POST['button_link'] ?? '';
            $pageType = $_POST['page_type'] ?? 'homepage';
            $status = $_POST['status'] ?? 'active';
            $sortOrder = (int)($_POST['sort_order'] ?? 1);
            
            if (empty($title)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Title is required'
                ]);
                break;
            }
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/banners/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = 'banner_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $imagePath = 'assets/images/banners/' . $fileName;
                    
                    $result = $db->insert('banners', [
                        'title' => $title,
                        'subtitle' => $subtitle,
                        'description' => $description,
                        'image' => $imagePath,
                        'button_text' => $buttonText,
                        'button_link' => $buttonLink,
                        'page_type' => $pageType,
                        'status' => $status,
                        'sort_order' => $sortOrder,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Banner added successfully'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Failed to save banner to database'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to upload image'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Image is required'
                ]);
            }
            break;
            
        case 'update_banner':
            $id = (int)($_POST['banner_id'] ?? 0);
            $title = $_POST['title'] ?? '';
            $subtitle = $_POST['subtitle'] ?? '';
            $description = $_POST['description'] ?? '';
            $buttonText = $_POST['button_text'] ?? '';
            $buttonLink = $_POST['button_link'] ?? '';
            $status = $_POST['status'] ?? 'active';
            $sortOrder = (int)($_POST['sort_order'] ?? 1);
            
            if (empty($title) || $id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid data provided'
                ]);
                break;
            }
            
            $updateData = [
                'title' => $title,
                'subtitle' => $subtitle,
                'description' => $description,
                'button_text' => $buttonText,
                'button_link' => $buttonLink,
                'status' => $status,
                'sort_order' => $sortOrder,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Handle image upload if provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/banners/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = 'banner_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    // Delete old image
                    $oldBanner = $db->where('id', $id)->getOne('banners', 'image');
                    if ($oldBanner && file_exists('../' . $oldBanner['image'])) {
                        unlink('../' . $oldBanner['image']);
                    }
                    
                    $updateData['image'] = 'assets/images/banners/' . $fileName;
                }
            }
            
            $result = $db->where('id', $id)->update('banners', $updateData);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Banner updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update banner'
                ]);
            }
            break;
            
        case 'delete_banner':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid banner ID'
                ]);
                break;
            }
            
            // Get banner to delete image file
            $banner = $db->where('id', $id)->getOne('banners', 'image');
            
            if ($banner) {
                // Delete image file
                if (file_exists('../' . $banner['image'])) {
                    unlink('../' . $banner['image']);
                }
                
                // Delete from database
                $result = $db->where('id', $id)->delete('banners');
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Banner deleted successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete banner'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Banner not found'
                ]);
            }
            break;
            
        case 'update_appearance':
            $appearanceSettings = [
                'theme_mode' => $_POST['theme_mode'] ?? 'auto',
                'day_start_time' => $_POST['day_start_time'] ?? '06:00',
                'night_start_time' => $_POST['night_start_time'] ?? '18:00',
                'default_language' => $_POST['default_language'] ?? 'en',
                'currency_format' => $_POST['currency_format'] ?? 'BDT',
                'date_format' => $_POST['date_format'] ?? 'Y-m-d',
                'timezone' => $_POST['timezone'] ?? 'Asia/Dhaka'
            ];
            
            $success = true;
            foreach ($appearanceSettings as $key => $value) {
                $existing = $db->where('setting_key', $key)->getOne('site_settings');
                
                if ($existing) {
                    $result = $db->where('setting_key', $key)->update('site_settings', [
                        'setting_value' => $value,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    $result = $db->insert('site_settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                if (!$result) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Appearance settings updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update appearance settings'
                ]);
            }
            break;
            
        case 'get_settings':
            $settings = [];
            $settingsData = $db->get('site_settings');
            foreach ($settingsData as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
            
            echo json_encode([
                'success' => true,
                'settings' => $settings
            ]);
            break;
            
        case 'edit_banner':
            $id = (int)($_POST['id'] ?? 0);
            $title = $_POST['title'] ?? '';
            $subtitle = $_POST['subtitle'] ?? '';
            $description = $_POST['description'] ?? '';
            $buttonText = $_POST['button_text'] ?? '';
            $buttonLink = $_POST['button_link'] ?? '';
            $pageType = $_POST['page_type'] ?? 'homepage';
            $status = $_POST['status'] ?? 'active';
            $sortOrder = (int)($_POST['sort_order'] ?? 1);
            
            if (empty($title) || $id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Title and valid ID are required'
                ]);
                break;
            }
            
            // Check if banner exists
            $existingBanner = $db->where('id', $id)->getOne('banners');
            if (!$existingBanner) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Banner not found'
                ]);
                break;
            }
            
            $updateData = [
                'title' => $title,
                'subtitle' => $subtitle,
                'description' => $description,
                'button_text' => $buttonText,
                'button_link' => $buttonLink,
                'page_type' => $pageType,
                'status' => $status,
                'sort_order' => $sortOrder,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Handle image upload if new image is provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/banners/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    // Delete old image if it exists
                    if (!empty($existingBanner['image']) && file_exists('../' . $existingBanner['image'])) {
                        unlink('../' . $existingBanner['image']);
                    }
                    $updateData['image'] = 'assets/images/banners/' . $fileName;
                }
            }
            
            $result = $db->where('id', $id)->update('banners', $updateData);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Banner updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update banner'
                ]);
            }
            break;
            
        case 'delete_banner':
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Valid ID is required'
                ]);
                break;
            }
            
            // Get banner to delete image file
            $banner = $db->where('id', $id)->getOne('banners');
            if (!$banner) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Banner not found'
                ]);
                break;
            }
            
            // Delete the banner
            $result = $db->where('id', $id)->delete('banners');
            
            if ($result) {
                // Delete image file if it exists
                if (!empty($banner['image']) && file_exists('../' . $banner['image'])) {
                    unlink('../' . $banner['image']);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Banner deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete banner'
                ]);
            }
            break;
            
        case 'test_action':
            echo json_encode([
                'success' => true,
                'message' => 'Test action received successfully',
                'received_action' => $action,
                'post_data' => $_POST
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$db->disconnect();
?>
