<?php
header('Location: category-delete-enhanced.php' . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));
exit;
?>

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$category_id) {
    header('Location: category-all.php');
    exit;
}

// Check if category exists
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();

if (!$category) {
    $_SESSION['error'] = "Category not found.";
    header('Location: category-all.php');
    exit;
}

// Check if category has products (direct and through subcategories)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$direct_product_count = $result->fetch_assoc()['count'];

// Check for subcategories and their products
$stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$subcategories = [];
$subcategory_product_count = 0;
while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
    
    // Count products in this subcategory
    $stmt2 = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE subcategory_id = ?");
    $stmt2->bind_param("i", $row['id']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $subcategory_product_count += $result2->fetch_assoc()['count'];
}

$total_product_count = $direct_product_count + $subcategory_product_count;

// Get associated products (both direct and from subcategories)
$associated_products = [];
if ($direct_product_count > 0) {
    $stmt = $pdo->prepare("SELECT id, name, image, 'direct' as source FROM products WHERE category_id = ? LIMIT 5");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $associated_products[] = $row;
    }
}

if ($subcategory_product_count > 0) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.image, s.name as subcategory_name, 'subcategory' as source 
        FROM products p 
        JOIN subcategories s ON p.subcategory_id = s.id 
        WHERE s.category_id = ? 
        LIMIT 5
    ");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $associated_products[] = $row;
    }
}

// Get other categories for moving products
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id != ? AND is_active = 1");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$other_categories = [];
while ($row = $result->fetch_assoc()) {
    $other_categories[] = $row;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'move_products' && isset($_POST['target_category'])) {
        // Move products to another category
        $target_category_id = (int)$_POST['target_category'];
        
        try {
            // Start transaction
            $pdo->autocommit(false);
            
            // Move direct products
            $stmt = $pdo->prepare("UPDATE products SET category_id = ? WHERE category_id = ?");
            $stmt->bind_param("ii", $target_category_id, $category_id);
            $stmt->execute();
            
            // Move products from subcategories and delete subcategories
            $stmt = $pdo->prepare("SELECT id FROM subcategories WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($subcategory = $result->fetch_assoc()) {
                // Move products from subcategory to target category
                $stmt2 = $pdo->prepare("UPDATE products SET category_id = ?, subcategory_id = NULL WHERE subcategory_id = ?");
                $stmt2->bind_param("ii", $target_category_id, $subcategory['id']);
                $stmt2->execute();
            }
            
            // Delete all subcategories
            $stmt = $pdo->prepare("DELETE FROM subcategories WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            
            // Commit transaction
            $pdo->commit();
            $pdo->autocommit(true);
            
            $_SESSION['success'] = "All products and subcategories have been moved/deleted. You can now delete this category.";
            header('Location: category-delete.php?id=' . $category_id);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction
            $pdo->rollback();
            $pdo->autocommit(true);
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'delete_category') {
        // Check again if category has products or subcategories (in case something changed)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_direct_products = $result->fetch_assoc()['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subcategories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_subcategories = $result->fetch_assoc()['count'];
        
        if ($current_direct_products > 0 || $current_subcategories > 0) {
            $_SESSION['error'] = "Cannot delete category. It still has products or subcategories associated with it.";
            header('Location: category-all.php');
            exit;
        }
        
        try {
            // Delete the category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $category_id);
            
            if ($stmt->execute()) {
                // Delete associated image file if it exists
                if ($category['image']) {
                    $image_path = settings()['physical_path'] . '/assets/categories/' . $category['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                $_SESSION['success'] = "Category '{$category['name']}' has been deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete category.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
        
        header('Location: category-all.php');
        exit;
    }
}
?>
<?php require __DIR__ . '/components/header.php'; ?>

</head>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_SESSION['success']); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-exclamation-triangle"></i> Delete Category
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($total_product_count > 0 || count($subcategories) > 0): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-warning"></i>
                                            <strong>Cannot Delete!</strong> This category has:
                                            <?php if ($direct_product_count > 0): ?>
                                                <br>• <?= $direct_product_count ?> direct product(s)
                                            <?php endif; ?>
                                            <?php if ($subcategory_product_count > 0): ?>
                                                <br>• <?= $subcategory_product_count ?> product(s) in subcategories
                                            <?php endif; ?>
                                            <?php if (count($subcategories) > 0): ?>
                                                <br>• <?= count($subcategories) ?> subcategory(ies)
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (count($subcategories) > 0): ?>
                                            <h6>Subcategories:</h6>
                                            <div class="row mb-3">
                                                <?php foreach ($subcategories as $subcategory): ?>
                                                    <div class="col-md-6 mb-2">
                                                        <div class="card bg-info text-white">
                                                            <div class="card-body p-2">
                                                                <small><i class="fas fa-folder"></i> <?= htmlspecialchars($subcategory['name']) ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (count($associated_products) > 0): ?>
                                            <h6>Associated Products:</h6>
                                            <div class="row mb-3">
                                                <?php foreach ($associated_products as $product): ?>
                                                    <div class="col-md-6 mb-2">
                                                        <div class="card">
                                                            <div class="card-body p-2">
                                                                <div class="d-flex align-items-center">
                                                                    <?php if ($product['image']): ?>
                                                                        <<img src="<?= settings()['root'] ?>/assets/products/<?= $product['image'] ?>"
                                                                             width="40" height="40" class="me-2">
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <small><?= htmlspecialchars($product['name']) ?></small>
                                                                        <?php if ($product['source'] == 'subcategory'): ?>
                                                                            <br><small class="text-muted">from: <?= htmlspecialchars($product['subcategory_name']) ?></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if ($total_product_count > 10): ?>
                                                    <div class="col-12">
                                                        <small class="text-muted">... and <?= $total_product_count - 10 ?> more products</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($other_categories)): ?>
                                            <h6>Move Products to Another Category:</h6>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="move_products">
                                                <div class="form-group">
                                                    <select name="target_category" class="form-control" required>
                                                        <option value="">Select a category to move products to...</option>
                                                        <?php foreach ($other_categories as $cat): ?>
                                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-arrows-alt"></i> Move All Products & Delete Subcategories
                                                </button>
                                            </form>
                                            <div class="alert alert-info mt-2">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Note:</strong> This will move all products to the selected category and delete all subcategories. Products from subcategories will lose their subcategory association.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                No other active categories available to move products to. Please create another category first or manually delete/move the products.
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-warning"></i>
                                            <strong>Warning!</strong> This action cannot be undone.
                                        </div>
                                        
                                        <p>Are you sure you want to delete the following category?</p>
                                        
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <?php if ($category['image']): ?>
                                                            <img src="<?= settings()['root'] ?>/assets/categories/<?= $category['image'] ?>" 
                                                                 class="img-thumbnail" width="80" height="80">
                                                        <?php else: ?>
                                                            <div class="bg-secondary d-flex align-items-center justify-content-center" 
                                                                 style="width: 80px; height: 80px;">
                                                                <i class="fas fa-image text-white"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <h6 class="mb-1"><?= htmlspecialchars($category['name']) ?></h6>
                                                        <p class="text-muted mb-1">
                                                            <small>Slug: <?= htmlspecialchars($category['slug']) ?></small>
                                                        </p>
                                                        <?php if ($category['description']): ?>
                                                            <p class="mb-1">
                                                                <small><?= htmlspecialchars($category['description']) ?></small>
                                                            </p>
                                                        <?php endif; ?>
                                                        <span class="badge <?= $category['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                                                            <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_category">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Yes, Delete Category
                                                </button>
                                            </form>
                                            <a href="category-all.php" class="btn btn-secondary ml-2">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- footer -->
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
</body>

</html>
