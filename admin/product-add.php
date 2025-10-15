<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

// Handle form submission
if ($_POST && isset($_POST['add_product'])) {
    try {
        // Use the same database connection as the rest of the application
        $db = new MysqliDb();
        $pdo = new PDO(
            "mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'] . ";charset=utf8mb4", 
            settings()['user'], 
            settings()['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        
        // Handle image upload without GD dependency
        $imageName = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/products/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Failed to create upload directory: $uploadDir");
                }
            }
            
            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $imageInfo = pathinfo($_FILES['image']['name']);
            $fileExtension = strtolower($imageInfo['extension']);
            
            if (!in_array($fileExtension, $allowedTypes)) {
                throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowedTypes));
            }
            
            // Validate file size (5MB limit)
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size exceeds 5MB limit");
            }
            
            $imageName = uniqid() . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $imageName;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $lastError = error_get_last();
                throw new Exception("Failed to upload image: " . ($lastError['message'] ?? 'Unknown error'));
            }
        }
        
        // Generate slug from name
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
        
        // Check if slug already exists and make it unique if needed
        $originalSlug = $slug;
        $counter = 1;
        while (true) {
            $existing = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
            $existing->execute([$slug]);
            if (!$existing->fetch()) {
                break; // Slug is unique
            }
            $slug = $originalSlug . '-' . $counter++;
        }
        
        // Prepare the SQL query with all possible fields
        $sql = "INSERT INTO products SET 
            category_id = :category_id,
            name = :name,
            slug = :slug,
            description = :description,
            short_description = :short_description,
            sku = :sku,
            selling_price = :selling_price,
            stock_quantity = :stock_quantity,
            min_stock_level = :min_stock_level,
            image = :image,
            created_at = NOW()";
            
        // Add optional fields if they exist
        $params = [
            ':category_id' => $_POST['category_id'],
            ':name' => trim($_POST['name']),
            ':slug' => $slug,
            ':description' => $_POST['description'] ?? '',
            ':short_description' => $_POST['short_description'] ?? '',
            ':sku' => trim($_POST['sku']),
            ':selling_price' => floatval($_POST['selling_price']),
            ':stock_quantity' => intval($_POST['stock_quantity'] ?? 0),
            ':min_stock_level' => intval($_POST['min_stock_level'] ?? 5),
            ':image' => $imageName
        ];
        
        // Add optional fields
        if (!empty($_POST['subcategory_id'])) {
            $sql .= ", subcategory_id = :subcategory_id";
            $params[':subcategory_id'] = intval($_POST['subcategory_id']);
        }
        
        if (!empty($_POST['brand_id'])) {
            $sql .= ", brand = :brand";
            $params[':brand'] = intval($_POST['brand_id']);
        }
        
        if (isset($_POST['cost_price']) && is_numeric($_POST['cost_price'])) {
            $sql .= ", cost_price = :cost_price";
            $params[':cost_price'] = floatval($_POST['cost_price']);
        }
        
        if (isset($_POST['is_hot_item'])) {
            $sql .= ", is_hot_item = :is_hot_item";
            $params[':is_hot_item'] = 1;
        }
        
        // Prepare and execute the statement
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $productId = $pdo->lastInsertId();
        
        // Handle additional product attributes if needed
        
        $_SESSION['success'] = "Product added successfully!";
        header('Location: products.php');
        exit;
        
    } catch (Exception $e) {
        // Log the error with full details
        error_log("Product Add Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        // Show detailed error in development
        $error = "Error creating product: " . $e->getMessage();
        
        // Log the POST data for debugging
        error_log("POST Data: " . print_r($_POST, true));
    }
}

// Fetch categories, subcategories, and brands for dropdowns
try {
    $pdo = new PDO(
        "mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'] . ";charset=utf8mb4", 
        settings()['user'], 
        settings()['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    $categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
    $subcategories = $pdo->query("SELECT * FROM subcategories WHERE is_active = 1 ORDER BY name")->fetchAll();
    $brands = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<?php require __DIR__.'/components/header.php'; ?>
    </head>
    <body class="sb-nav-fixed">
    <?php require __DIR__.'/components/navbar.php'; ?>
        <div id="layoutSidenav">
        <?php require __DIR__.'/components/sidebar.php'; ?>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Add New Product</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                            <li class="breadcrumb-item active">Add Product</li>
                        </ol>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-plus me-1"></i>
                                Product Information
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Product Name *</label>
                                            <input type="text" class="form-control" name="name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">SKU *</label>
                                            <input type="text" class="form-control" name="sku" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Category *</label>
                                            <select class="form-select" name="category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Subcategory</label>
                                            <select class="form-select" name="subcategory_id">
                                                <option value="">Select Subcategory</option>
                                                <?php foreach ($subcategories as $subcategory): ?>
                                                    <option value="<?= $subcategory['id'] ?>"><?= htmlspecialchars($subcategory['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Brand</label>
                                            <select class="form-select" name="brand_id">
                                                <option value="">Select Brand</option>
                                                <?php foreach ($brands as $brand): ?>
                                                    <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Short Description</label>
                                            <textarea class="form-control" name="short_description" rows="2"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="2"></textarea>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Selling Price *</label>
                                            <input type="number" step="0.01" class="form-control" name="selling_price" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Cost Price</label>
                                            <input type="number" step="0.01" class="form-control" name="cost_price">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Markup %</label>
                                            <input type="number" step="0.01" class="form-control" name="markup_percentage" value="0">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Pricing Method</label>
                                            <select class="form-select" name="pricing_method">
                                                <option value="manual">Manual</option>
                                                <option value="cost_plus">Cost Plus</option>
                                                <option value="market_based">Market Based</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Stock Quantity</label>
                                            <input type="number" class="form-control" name="stock_quantity" value="0">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Min Stock Level</label>
                                            <input type="number" class="form-control" name="min_stock_level" value="5">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Weight (kg)</label>
                                            <input type="number" step="0.01" class="form-control" name="weight">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Dimensions</label>
                                            <input type="text" class="form-control" name="dimensions" placeholder="L x W x H">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Barcode</label>
                                            <input type="text" class="form-control" name="barcode">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Product Image</label>
                                            <input type="file" class="form-control" name="image" accept="image/*">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_hot_item" id="is_hot_item">
                                                <label class="form-check-label" for="is_hot_item">
                                                    Hot Item
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="auto_update_price" id="auto_update_price">
                                                <label class="form-check-label" for="auto_update_price">
                                                    Auto Update Price
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="submit" name="add_product" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Add Product
                                            </button>
                                            <a href="products.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left me-1"></i> Back to Products
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </main>
                <!-- footer -->
                <?php require __DIR__.'/components/footer.php'; ?>
            </div>
        </div>
        <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="<?= settings()['adminpage'] ?>assets/demo/chart-area-demo.js"></script>
        <script src="<?= settings()['adminpage'] ?>assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
        <script src="<?= settings()['adminpage'] ?>assets/js/datatables-simple-demo.js"></script>

        <script>
        // Dynamic subcategory loading based on category selection
        document.querySelector('select[name="category_id"]').addEventListener('change', function() {
            const categoryId = this.value;
            const subcategorySelect = document.querySelector('select[name="subcategory_id"]');
            
            // Clear existing options
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            
            if (categoryId) {
                // Filter subcategories based on selected category
                <?php 
                echo 'const subcategories = ' . json_encode($subcategories) . ';';
                ?>
                
                subcategories.forEach(function(subcategory) {
                    if (subcategory.category_id == categoryId) {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        subcategorySelect.appendChild(option);
                    }
                });
            }
        });

        // Auto-calculate selling price based on cost price and markup
        const costPriceInput = document.querySelector('input[name="cost_price"]');
        const markupInput = document.querySelector('input[name="markup_percentage"]');
        const sellingPriceInput = document.querySelector('input[name="selling_price"]');

        function calculateSellingPrice() {
            const costPrice = parseFloat(costPriceInput.value) || 0;
            const markup = parseFloat(markupInput.value) || 0;
            
            if (costPrice > 0 && markup > 0) {
                const sellingPrice = costPrice * (1 + markup / 100);
                sellingPriceInput.value = sellingPrice.toFixed(2);
            }
        }

        if (costPriceInput && markupInput) {
            costPriceInput.addEventListener('input', calculateSellingPrice);
            markupInput.addEventListener('input', calculateSellingPrice);
        }
        </script>
    </body>
</html>