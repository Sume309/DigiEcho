<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'delete':
                deleteProduct($db);
                break;
            case 'bulk_delete':
                bulkDeleteProducts($db);
                break;
            default:
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

function deleteProduct($db) {
    try {
        $productId = intval($_POST['product_id'] ?? 0);
        
        if (!$productId) {
            throw new Exception('Invalid product ID');
        }
        
        // Get product details before deletion
        $product = $db->where('id', $productId)->getOne('products', 'id, name, image, gallery_images');
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Delete product images from filesystem
        $uploadDir = __DIR__ . '/../assets/products/';
        
        // Delete main image
        if (!empty($product['image'])) {
            $imagePath = $uploadDir . $product['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Delete gallery images
        if (!empty($product['gallery_images'])) {
            $galleryImages = json_decode($product['gallery_images'], true);
            if (is_array($galleryImages)) {
                foreach ($galleryImages as $image) {
                    $imagePath = $uploadDir . $image;
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }
        }
        
        // Delete product from database
        $db->where('id', $productId);
        if ($db->delete('products')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Product "' . htmlspecialchars($product['name']) . '" deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete product: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function bulkDeleteProducts($db) {
    try {
        $productIds = $_POST['product_ids'] ?? [];
        
        if (empty($productIds) || !is_array($productIds)) {
            throw new Exception('No products selected for deletion');
        }
        
        // Convert to integers for security
        $ids = array_map('intval', $productIds);
        
        // Get products to delete images
        $db->where('id', $ids, 'IN');
        $products = $db->get('products', null, 'id, name, image, gallery_images');
        
        if (empty($products)) {
            throw new Exception('No products found for deletion');
        }
        
        // Delete images from filesystem
        $uploadDir = __DIR__ . '/../assets/products/';
        
        foreach ($products as $product) {
            // Delete main image
            if (!empty($product['image'])) {
                $imagePath = $uploadDir . $product['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            // Delete gallery images
            if (!empty($product['gallery_images'])) {
                $galleryImages = json_decode($product['gallery_images'], true);
                if (is_array($galleryImages)) {
                    foreach ($galleryImages as $image) {
                        $imagePath = $uploadDir . $image;
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                }
            }
        }
        
        // Delete products from database
        $db->where('id', $ids, 'IN');
        if ($db->delete('products')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => count($ids) . ' product(s) deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete products: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>