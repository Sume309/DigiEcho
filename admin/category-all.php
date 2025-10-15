<?php
// Redirect to the new enhanced category management page
header('Location: category-management.php');
exit;
?>

<?php require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .category-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    .action-btns .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .search-box {
        max-width: 300px;
    }
</style>
</head>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Category Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Categories</li>
                    </ol>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-tags me-1"></i>
                                    All Categories
                                </div>
                                <a href="category-add.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Add New Category
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Search and Filter Form -->
                            <form action="" method="get" class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>">
                                            <button class="btn btn-outline-secondary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="status" class="form-select" onchange="this.form.submit()">
                                            <option value="">All Status</option>
                                            <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                                            <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <?php if (!empty($search) || $status !== ''): ?>
                                    <div class="col-md-2">
                                        <a href="category-all.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Clear Filters
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <!-- Categories Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered" id="categoriesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Products</th>
                                            <th>Status</th>
                                            <th>Sort Order</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($categories)): ?>
                                            <?php $counter = $offset + 1; ?>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td>
                                                        <?php if (!empty($category['image']) && file_exists("../" . $category['image'])): ?>
                                                            <img src="../<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 4px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($category['slug']); ?></small>
                                                    </td>
                                                    <td><?php echo !empty($category['description']) ? htmlspecialchars(substr($category['description'], 0, 50)) . (strlen($category['description']) > 50 ? '...' : '') : 'N/A'; ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary"><?php echo (int)$category['product_count']; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge rounded-pill bg-<?php echo $category['is_active'] ? 'success' : 'secondary'; ?> status-badge">
                                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo (int)$category['sort_order']; ?></td>
                                                    <td><?php echo !empty($category['updated_at']) ? date('M d, Y', strtotime($category['updated_at'])) : date('M d, Y', strtotime($category['created_at'])); ?></td>
                                                    <td class="action-btns">
                                                        <a href="category-edit.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-category" data-id="<?php echo $category['id']; ?>" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                        <a href="../category/<?php echo $category['slug']; ?>" target="_blank" class="btn btn-sm btn-outline-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                                        <p class="mb-0">No categories found. <a href="category-add.php">Add your first category</a></p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo; Previous</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?>" aria-label="Next">
                                                    <span aria-hidden="true">Next &raquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this category? This action cannot be undone.</p>
                            <p class="text-muted">Note: If there are products in this category, they will be moved to 'Uncategorized'.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="#" id="confirmDelete" class="btn btn-danger">Delete Category</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php require __DIR__ . '/components/footer.php'; ?>
            
            <!-- Include DataTables JS -->
            <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            
            <script>
                $(document).ready(function() {
                    // Initialize DataTable
                    $('#categoriesTable').DataTable({
                        paging: false,
                        searching: false,
                        info: false,
                        order: [[6, 'asc']], // Default sort by sort_order
                        columnDefs: [
                            { orderable: false, targets: [0, 1, 4, 8] } // Disable sorting on these columns
                        ]
                    });

                    // Delete category confirmation
                    $('.delete-category').on('click', function(e) {
                        e.preventDefault();
                        const categoryId = $(this).data('id');
                        const deleteUrl = `category-delete.php?id=${categoryId}`;
                        
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = deleteUrl;
                            }
                        });
                    });

                    // Show success/error messages with auto-hide
                    $('.alert').delay(3000).fadeOut('slow');
                });
            </script>
        </div>
    </div>
</body>
</html>
                    <h3>All Categories</h3>
                    <a href="category-add.php" class="btn btn-primary">Add Category</a>
                </div>

                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th>Sort Order</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= $category['id'] ?></td>
                                <td><?= htmlspecialchars($category['name']) ?></td>
                                <td><?= htmlspecialchars($category['slug']) ?></td>
                                <td><?= htmlspecialchars($category['description']) ?></td>
                                <td><img src="<?= settings()['root'] ?>/assets/categories/<?= $category['image'] ?>" width="50" height="50" /></td>
                                <td><?= $category['is_active'] ? 'Active' : 'Inactive' ?></td>
                                <td><?= $category['sort_order'] ?></td>
                                <td><?= $category['created_at'] ?></td>
                                <td><?= $category['updated_at'] ?></td>
                                <td>
                                    <a href="category-edit.php?id=<?= $category['id'] ?>" class="btn btn-primary">Edit</a>
                                    <a href="category-delete.php?id=<?= $category['id'] ?>" class="btn btn-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- changed content  ends-->
            </main>
            <!-- footer -->
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/demo/chart-area-demo.js"></script>
    <script src="<?= settings()['adminpage'] ?>assets/demo/chart-bar-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/datatables-simple-demo.js"></script>
</body>

</html>