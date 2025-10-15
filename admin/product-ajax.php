<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/auth/admin.php';

// Include notification helper
require_once __DIR__ . '/components/notification_helper.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createProduct($db);
            break;
        case 'update':
            updateProduct($db);
            break;
        case 'delete':
            deleteProduct($db);
            break;
        case 'get_categories':
            getCategories($db);
            break;
        case 'get_subcategories':
            getSubcategories($db);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    // Log detailed error information
    error_log("Product AJAX Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'action' => $action
        ]
    ]);
}

function createProduct($db) {
    try {
        error_log('=== Starting product creation ===');
        error_log('POST data: ' . json_encode($_POST));
        error_log('FILES data: ' . json_encode(array_map(function($file) {
            return [
                'name' => $file['name'] ?? 'N/A',
                'size' => $file['size'] ?? 'N/A',
                'error' => $file['error'] ?? 'N/A',
                'type' => $file['type'] ?? 'N/A'
            ];
        }, $_FILES)));
        
        // Validate required fields
        $requiredFields = ['name', 'sku', 'category_id', 'selling_price', 'stock_quantity'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Check if SKU already exists
        $db->where('sku', $_POST['sku']);
        if ($db->has('products')) {
            throw new Exception('A product with this SKU already exists');
        }
        
        // Auto-generate slug if not provided
        $slug = !empty($_POST['slug']) ? $_POST['slug'] : 
                strtolower(preg_replace('/[^a-zA-Z0-9\s-]/', '', 
                    preg_replace('/\s+/', '-', trim($_POST['name']))));
        
        // Ensure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (true) {
            $db->where('slug', $slug);
            if (!$db->has('products')) {
                break;
            }
            $slug = $originalSlug . '-' . $counter++;
        }
        
        // Prepare product data
        $productData = [
            'name' => $_POST['name'],
            'slug' => $slug,
            'sku' => $_POST['sku'],
            'category_id' => intval($_POST['category_id']),
            'subcategory_id' => !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null,
            'brand' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
            'short_description' => $_POST['short_description'] ?? null,
            'description' => $_POST['description'] ?? null,
            'selling_price' => floatval($_POST['selling_price']),
            'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
            'markup_percentage' => !empty($_POST['markup_percentage']) ? floatval($_POST['markup_percentage']) : 0,
            'discount_price' => !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null,
            'discount_start_date' => !empty($_POST['discount_start_date']) ? $_POST['discount_start_date'] : null,
            'discount_end_date' => !empty($_POST['discount_end_date']) ? $_POST['discount_end_date'] : null,
            'stock_quantity' => intval($_POST['stock_quantity']),
            'min_stock_level' => !empty($_POST['min_stock_level']) ? intval($_POST['min_stock_level']) : 5,
            'barcode' => $_POST['barcode'] ?? null,
            'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
            'dimensions' => $_POST['dimensions'] ?? null,
            'meta_title' => $_POST['meta_title'] ?? null,
            'meta_description' => $_POST['meta_description'] ?? null,
            'meta_keywords' => $_POST['meta_keywords'] ?? null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_hot_item' => isset($_POST['is_hot_item']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'tags' => $_POST['tags'] ?? null,
            'status' => $_POST['status'] ?? 'draft',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle image uploads
        $uploadDir = realpath(__DIR__ . '/../assets/products/');
        if (!$uploadDir || !is_dir($uploadDir)) {
            $uploadDir = __DIR__ . '/../assets/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $uploadDir = realpath($uploadDir);
        }
        $uploadDir = $uploadDir . DIRECTORY_SEPARATOR;
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Handle main image
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            error_log('Processing main image upload: ' . $_FILES['main_image']['name']);
            $fileExtension = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // Check file size (5MB limit)
                if ($_FILES['main_image']['size'] <= 5 * 1024 * 1024) {
                    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
                    $filePath = $uploadDir . $fileName;
                    error_log('Attempting to save main image to: ' . $filePath);
                    
                    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $filePath)) {
                        $productData['image'] = $fileName;
                        error_log('Main image uploaded successfully: ' . $fileName);
                    } else {
                        error_log('Failed to move uploaded main image: ' . error_get_last()['message']);
                        throw new Exception('Failed to upload main image: ' . error_get_last()['message']);
                    }
                } else {
                    error_log('Main image file size too large: ' . $_FILES['main_image']['size']);
                    throw new Exception('Main image file size exceeds 5MB limit');
                }
            } else {
                error_log('Invalid main image file type: ' . $fileExtension);
                throw new Exception('Invalid main image file type. Allowed: ' . implode(', ', $allowedExtensions));
            }
        } else {
            if (isset($_FILES['main_image'])) {
                error_log('Main image upload error: ' . $_FILES['main_image']['error']);
            } else {
                error_log('No main image file received in $_FILES');
            }
        }
        
        // Handle gallery images
        if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
            $galleryImages = [];
            $galleryCount = count($_FILES['gallery_images']['name']);
            
            for ($i = 0; $i < $galleryCount; $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileExtension = strtolower(pathinfo($_FILES['gallery_images']['name'][$i], PATHINFO_EXTENSION));
                    
                    if (in_array($fileExtension, $allowedExtensions)) {
                        // Check file size (5MB limit)
                        if ($_FILES['gallery_images']['size'][$i] <= 5 * 1024 * 1024) {
                            $fileName = uniqid() . '_' . time() . '_' . $i . '.' . $fileExtension;
                            $filePath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $filePath)) {
                                $galleryImages[] = $fileName;
                            } else {
                                error_log("Failed to upload gallery image $i: " . error_get_last()['message']);
                            }
                        } else {
                            error_log("Gallery image $i exceeds 5MB limit");
                        }
                    } else {
                        error_log("Invalid gallery image $i file type: $fileExtension");
                    }
                }
            }
            
            if (!empty($galleryImages)) {
                $productData['gallery_images'] = json_encode($galleryImages);
            }
        }
        
        // Insert product
        $productId = $db->insert('products', $productData);
        
        if ($productId) {
            // Notify about new product creation
            notifyProductActivity(
                $productId, 
                $productData['name'], 
                'created',
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Product created successfully',
                'product_id' => $productId
            ]);
        } else {
            throw new Exception('Failed to create product: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateProduct($db) {
    try {
        $productId = intval($_POST['product_id'] ?? 0);
        
        if (!$productId) {
            throw new Exception('Invalid product ID');
        }
        
        // Get existing product
        $existingProduct = $db->where('id', $productId)->getOne('products');
        if (!$existingProduct) {
            throw new Exception('Product not found');
        }
        
        // Validate required fields
        $requiredFields = ['name', 'sku', 'category_id', 'selling_price', 'stock_quantity'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Check if SKU already exists (excluding current product)
        $db->where('sku', $_POST['sku']);
        $db->where('id', $productId, '!=');
        if ($db->has('products')) {
            throw new Exception('A product with this SKU already exists');
        }
        
        // Auto-generate slug if not provided
        $slug = !empty($_POST['slug']) ? $_POST['slug'] : 
                strtolower(preg_replace('/[^a-zA-Z0-9\s-]/', '', 
                    preg_replace('/\s+/', '-', trim($_POST['name']))));
        
        // Ensure slug is unique (excluding current product)
        $originalSlug = $slug;
        $counter = 1;
        while (true) {
            $db->where('slug', $slug);
            $db->where('id', $productId, '!=');
            if (!$db->has('products')) {
                break;
            }
            $slug = $originalSlug . '-' . $counter++;
        }
        
        // Prepare product data
        $productData = [
            'name' => $_POST['name'],
            'slug' => $slug,
            'sku' => $_POST['sku'],
            'category_id' => intval($_POST['category_id']),
            'subcategory_id' => !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null,
            'brand' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
            'short_description' => $_POST['short_description'] ?? null,
            'description' => $_POST['description'] ?? null,
            'selling_price' => floatval($_POST['selling_price']),
            'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
            'markup_percentage' => !empty($_POST['markup_percentage']) ? floatval($_POST['markup_percentage']) : 0,
            'discount_price' => !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null,
            'discount_start_date' => !empty($_POST['discount_start_date']) ? $_POST['discount_start_date'] : null,
            'discount_end_date' => !empty($_POST['discount_end_date']) ? $_POST['discount_end_date'] : null,
            'stock_quantity' => intval($_POST['stock_quantity']),
            'min_stock_level' => !empty($_POST['min_stock_level']) ? intval($_POST['min_stock_level']) : 5,
            'barcode' => $_POST['barcode'] ?? null,
            'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
            'dimensions' => $_POST['dimensions'] ?? null,
            'meta_title' => $_POST['meta_title'] ?? null,
            'meta_description' => $_POST['meta_description'] ?? null,
            'meta_keywords' => $_POST['meta_keywords'] ?? null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_hot_item' => isset($_POST['is_hot_item']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'tags' => $_POST['tags'] ?? null,
            'status' => $_POST['status'] ?? 'draft',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle image uploads
        $uploadDir = realpath(__DIR__ . '/../assets/products/');
        if (!$uploadDir || !is_dir($uploadDir)) {
            $uploadDir = __DIR__ . '/../assets/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $uploadDir = realpath($uploadDir);
        }
        $uploadDir = $uploadDir . DIRECTORY_SEPARATOR;
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Handle main image
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // Check file size (5MB limit)
                if ($_FILES['main_image']['size'] <= 5 * 1024 * 1024) {
                    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $filePath)) {
                        // Delete old image if exists
                        if (!empty($existingProduct['image'])) {
                            $oldImagePath = $uploadDir . $existingProduct['image'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        $productData['image'] = $fileName;
                    } else {
                        throw new Exception('Failed to upload main image: ' . error_get_last()['message']);
                    }
                } else {
                    throw new Exception('Main image file size exceeds 5MB limit');
                }
            } else {
                throw new Exception('Invalid main image file type. Allowed: ' . implode(', ', $allowedExtensions));
            }
        }
        
        // Handle gallery images
        $galleryImages = [];
        
        // Preserve existing gallery images that weren't marked for removal
        if (!empty($existingProduct['gallery_images'])) {
            $existingGallery = json_decode($existingProduct['gallery_images'], true);
            if (is_array($existingGallery)) {
                // Check if any images were marked for removal
                $removeImages = $_POST['remove_gallery_images'] ?? [];
                foreach ($existingGallery as $index => $image) {
                    // If this image wasn't marked for removal, keep it
                    if (!in_array(strval($index), $removeImages)) {
                        $galleryImages[] = $image;
                    } else {
                        // Delete the removed image from filesystem
                        $imagePath = $uploadDir . $image;
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                }
            }
        }
        
        // Add new uploaded gallery images
        if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
            $galleryCount = count($_FILES['gallery_images']['name']);
            
            for ($i = 0; $i < $galleryCount; $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileExtension = strtolower(pathinfo($_FILES['gallery_images']['name'][$i], PATHINFO_EXTENSION));
                    
                    if (in_array($fileExtension, $allowedExtensions)) {
                        // Check file size (5MB limit)
                        if ($_FILES['gallery_images']['size'][$i] <= 5 * 1024 * 1024) {
                            $fileName = uniqid() . '_' . time() . '_' . $i . '.' . $fileExtension;
                            $filePath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $filePath)) {
                                $galleryImages[] = $fileName;
                            } else {
                                error_log("Failed to upload gallery image $i: " . error_get_last()['message']);
                            }
                        } else {
                            error_log("Gallery image $i exceeds 5MB limit");
                        }
                    } else {
                        error_log("Invalid gallery image $i file type: $fileExtension");
                    }
                }
            }
        }
        
        // Update gallery images
        if (!empty($galleryImages)) {
            $productData['gallery_images'] = json_encode($galleryImages);
        } else if (isset($_FILES['gallery_images']) || isset($_POST['remove_gallery_images'])) {
            // If gallery images were processed (either added or removed), explicitly set to null if empty
            $productData['gallery_images'] = null;
        }
        
        // Update product
        $db->where('id', $productId);
        if ($db->update('products', $productData)) {
            // Notify about product update
            notifyProductActivity(
                $productId, 
                $productData['name'], 
                'updated',
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Product updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update product: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteProduct($db) {
    try {
        $productId = intval($_POST['product_id'] ?? 0);
        
        if (!$productId) {
            throw new Exception('Invalid product ID');
        }
        
        // Get product to delete images
        $product = $db->where('id', $productId)->getOne('products');
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Delete product images
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
        
        // Delete product
        $db->where('id', $productId);
        if ($db->delete('products')) {
            // Notify about product deletion
            notifyProductActivity(
                $productId, 
                $product['name'], 
                'deleted',
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? null
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Product deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete product: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getCategories($db) {
    try {
        $categories = $db->get('categories', null, ['id', 'name']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $categories]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getSubcategories($db) {
    try {
        $categoryId = intval($_POST['category_id'] ?? 0);
        if (!$categoryId) {
            throw new Exception('Category ID is required');
        }
        
        $db->where('category_id', $categoryId);
        $db->where('is_active', 1);
        $subcategories = $db->get('subcategories', null, ['id', 'name']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $subcategories]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>