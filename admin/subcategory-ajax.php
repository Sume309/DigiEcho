<?php
session_start();
require_once '../vendor/autoload.php';
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

header('Content-Type: application/json');

// Database configuration - Update these with your actual database credentials
$host = settings()['hostname'];
$username = settings()['user'];
$password = settings()['password'];
$database = settings()['database'];

try {
    $db = new MysqliDb($host, $username, $password, $database);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check if this is a DataTables server-side request
if (isset($_POST['draw'])) {
    handleDataTableRequest($db);
    exit;
}

// Handle other AJAX actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'fetch':
        fetchSubcategories($db);
        break;
    case 'create':
        createSubcategory($db);
        break;
    case 'update':
        updateSubcategory($db);
        break;
    case 'delete':
        deleteSubcategory($db);
        break;
    case 'get_single':
        getSingleSubcategory($db);
        break;
    case 'get_categories':
        getCategories($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Handle DataTables server-side processing
 */
function handleDataTableRequest($db) {
    // Get request parameters
    $draw = intval($_POST['draw']);
    $start = intval($_POST['start']);
    $length = intval($_POST['length']);
    $search = $_POST['search']['value'] ?? '';
    $orderColumn = $_POST['order'][0]['column'] ?? 0;
    $orderDir = $_POST['order'][0]['dir'] ?? 'asc';
    $status = $_POST['status'] ?? '';
    $categoryId = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    // Column mapping for ordering
    $columns = [
        0 => 's.id',
        1 => 's.name',
        2 => 'c.name',
        3 => 's.is_active',
        4 => 's.sort_order',
        5 => 's.updated_at'
    ];
    
    $orderBy = $columns[$orderColumn] ?? 's.id';
    $orderDir = in_array(strtolower($orderDir), ['asc', 'desc']) ? $orderDir : 'asc';
    
    // Build the query
    $db->join("categories c", "c.id = s.category_id", "LEFT");
    
    // Apply search filter
    if (!empty($search)) {
        $db->where('(s.name LIKE ? OR s.description LIKE ? OR c.name LIKE ?)', 
                  ["%$search%", "%$search%", "%$search%"]);
    }
    
    // Apply status filter
    if ($status !== '') {
        $db->where('s.is_active', intval($status));
    }
    
    // Apply category filter
    if ($categoryId > 0) {
        $db->where('s.category_id', $categoryId);
    }
    
    // Get total records count (without pagination)
    $db->withTotalCount();
    $totalRecords = $db->getValue('subcategories s', 'COUNT(DISTINCT s.id)');
    
    // Get filtered records count (with search/filter)
    $filteredCount = $db->count;
    
    // Apply ordering and pagination
    $db->orderBy($orderBy, $orderDir);
    $db->pageLimit = $length;
    $page = ($start / $length) + 1;
    
    // Get the data
    $subcategories = $db->arrayBuilder()->paginate('subcategories s', $page, [
        's.id',
        's.name',
        's.slug',
        's.description',
        's.image',
        's.is_active',
        's.sort_order',
        's.created_at',
        's.updated_at',
        'c.name as category_name',
        'c.id as category_id',
        '(SELECT COUNT(*) FROM products p WHERE p.subcategory_id = s.id) as product_count'
    ]);
    
    // Prepare response data
    $data = [];
    foreach ($subcategories as $subcategory) {
        $data[] = [
            'id' => $subcategory['id'],
            'name' => $subcategory['name'],
            'slug' => $subcategory['slug'],
            'category_name' => $subcategory['category_name'] ?? 'Uncategorized',
            'category_id' => $subcategory['category_id'],
            'product_count' => (int)$subcategory['product_count'],
            'is_active' => (int)$subcategory['is_active'],
            'sort_order' => (int)$subcategory['sort_order'],
            'created_at' => $subcategory['created_at'],
            'updated_at' => $subcategory['updated_at']
        ];
    }
    
    // Return JSON response
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredCount,
        'data' => $data
    ];
    
    echo json_encode($response);
    exit;
}

function fetchSubcategories($db) {
    try {
        $db->join("categories c", "s.category_id = c.id", "LEFT");
        $db->orderBy("s.id", "DESC");
        $subcategories = $db->get("subcategories s", null, "s.*, c.name as category_name");
        
        if ($db->getLastErrno() === 0) {
            echo json_encode(['data' => $subcategories]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error fetching subcategories: ' . $db->getLastError()]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching subcategories: ' . $e->getMessage()]);
    }
}

function createSubcategory($db) {
    try {
        // Validate required fields
        if (empty($_POST['category_id']) || empty($_POST['name']) || empty($_POST['slug'])) {
            echo json_encode(['success' => false, 'message' => 'Category, name, and slug are required']);
            return;
        }
        
        // Check for duplicate name within the same category (proper scope)
        $duplicateNameCheck = $db->rawQueryOne(
            'SELECT s.id, s.name, c.name as category_name FROM subcategories s LEFT JOIN categories c ON s.category_id = c.id WHERE s.name = ? AND s.category_id = ?', 
            [$_POST['name'], $_POST['category_id']]
        );
        if ($duplicateNameCheck) {
            echo json_encode(['success' => false, 'message' => "A subcategory with name '{$_POST['name']}' already exists in the '{$duplicateNameCheck['category_name']}' category."]);
            return;
        }
        
        // Check if slug already exists
        $db->where("slug", $_POST['slug']);
        $existing = $db->getOne("subcategories", "id");
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Slug already exists']);
            return;
        }
        
        // Handle image upload
        $imageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageName = handleImageUpload($_FILES['image']);
            if (!$imageName) {
                echo json_encode(['success' => false, 'message' => 'Error uploading image']);
                return;
            }
        }
        
        // Prepare data for insertion
        $data = [
            'category_id' => $_POST['category_id'],
            'name' => $_POST['name'],
            'slug' => $_POST['slug'],
            'description' => $_POST['description'] ?? null,
            'image' => $imageName,
            'is_active' => $_POST['is_active'] ?? 1,
            'sort_order' => $_POST['sort_order'] ?? 0
        ];
        
        // Insert subcategory
        $id = $db->insert('subcategories', $data);
        
        if ($id) {
            echo json_encode(['success' => true, 'message' => 'Subcategory created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating subcategory: ' . $db->getLastError()]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error creating subcategory: ' . $e->getMessage()]);
    }
}

function updateSubcategory($db) {
    try {
        $id = $_POST['subcategory_id'];
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Subcategory ID is required']);
            return;
        }
        
        // Validate required fields
        if (empty($_POST['category_id']) || empty($_POST['name']) || empty($_POST['slug'])) {
            echo json_encode(['success' => false, 'message' => 'Category, name, and slug are required']);
            return;
        }
        
        // Check for duplicate name within the same category (exclude current subcategory)
        $duplicateNameCheck = $db->rawQueryOne(
            'SELECT s.id, s.name, c.name as category_name FROM subcategories s LEFT JOIN categories c ON s.category_id = c.id WHERE s.name = ? AND s.category_id = ? AND s.id != ?', 
            [$_POST['name'], $_POST['category_id'], $id]
        );
        if ($duplicateNameCheck) {
            echo json_encode(['success' => false, 'message' => "A subcategory with name '{$_POST['name']}' already exists in the '{$duplicateNameCheck['category_name']}' category."]);
            return;
        }
        
        // Check if slug already exists (excluding current record)
        $db->where("slug", $_POST['slug']);
        $db->where("id", $id, "!=");
        $existing = $db->getOne("subcategories", "id");
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Slug already exists']);
            return;
        }
        
        // Get current image
        $db->where("id", $id);
        $currentSubcategory = $db->getOne("subcategories", "image");
        $currentImage = $currentSubcategory['image'] ?? null;
        
        // Handle image upload
        $imageName = $currentImage;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $newImageName = handleImageUpload($_FILES['image']);
            if ($newImageName) {
                // Delete old image if it exists
                $path = settings()['physical_path'] . "assets/subcategories/$currentImage";
                if ($currentImage && file_exists($path)) {
                    unlink($path);
                }
                $imageName = $newImageName;
            }
        }
        
        // Prepare data for update
        $data = [
            'category_id' => $_POST['category_id'],
            'name' => $_POST['name'],
            'slug' => $_POST['slug'],
            'description' => $_POST['description'] ?? null,
            'image' => $imageName,
            'is_active' => $_POST['is_active'] ?? 1,
            'sort_order' => $_POST['sort_order'] ?? 0,
            'updated_at' => $db->now()
        ];
        
        // Update subcategory
        $db->where('id', $id);
        $result = $db->update('subcategories', $data);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Subcategory updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating subcategory: ' . $db->getLastError()]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating subcategory: ' . $e->getMessage()]);
    }
}

function deleteSubcategory($db) {
    try {
        $id = $_POST['id'];
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Subcategory ID is required']);
            return;
        }
        
        // Get image name before deletion
        $db->where("id", $id);
        $subcategory = $db->getOne("subcategories", "image");
        
        // Delete subcategory
        $db->where('id', $id);
        $result = $db->delete('subcategories');
        
        if ($result) {
            $path = settings()['physical_path'] . "assets/subcategories/{$subcategory['image']}";
            // Delete associated image file
            if ($subcategory && $subcategory['image'] && file_exists($path)) {
                unlink($path);
            }
            echo json_encode(['success' => true, 'message' => 'Subcategory deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting subcategory: ' . $db->getLastError()]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting subcategory: ' . $e->getMessage()]);
    }
}

function getSingleSubcategory($db) {
    try {
        $id = $_POST['id'];
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Subcategory ID is required']);
            return;
        }
        
        $db->where("id", $id);
        $subcategory = $db->getOne("subcategories");
        
        if ($subcategory) {
            echo json_encode(['success' => true, 'data' => $subcategory]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Subcategory not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching subcategory: ' . $e->getMessage()]);
    }
}

function getCategories($db) {
    try {
        $db->where("is_active", 1);
        $db->orderBy("name", "ASC");
        $categories = $db->get("categories", null, "id, name");
        
        if ($db->getLastErrno() === 0) {
            echo json_encode(['success' => true, 'data' => $categories]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error fetching categories: ' . $db->getLastError()]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching categories: ' . $e->getMessage()]);
    }
}

function handleImageUpload($file) {
    
    $uploadDir = settings()['physical_path'] . '/assets/subcategories/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5242880) {
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        //resize image        
        $manager = new ImageManager(new Driver());
        $filepath = realpath($filepath);
        $image = $manager->read($filepath);
        $image->scale(width: 400);


    // Apply watermark
    $watermarkPath = realpath(settings()['physical_path'] . '\admin\assets\watermark.png');
    if (file_exists($watermarkPath)) {
        $image->place($watermarkPath, 'center', 0, 0, 30); // Position: bottom-right with 10px offset
    }

    // Save the image with compression (quality: 85%)
    $image->save($filepath, 85);
        return $filename;
    }
    
    return false;
}
?>