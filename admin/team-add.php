<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$page = "Add Team Member";

// Database connection
try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $twitter = trim($_POST['twitter'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($position)) {
        $errors[] = "Position is required.";
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/team/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid('team_') . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image_path = 'uploads/team/' . $file_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image format. Only JPG, JPEG, PNG, GIF, and WebP are allowed.";
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO team_members (name, position, description, image, email, phone, linkedin, twitter, facebook, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $position, $description, $image_path, $email, $phone, $linkedin, $twitter, $facebook, $sort_order, $is_active]);
            
            $success_message = "Team member added successfully!";
            
            // Clear form data
            $name = $position = $description = $email = $phone = $linkedin = $twitter = $facebook = '';
            $sort_order = 0;
            $is_active = 1;
            
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mt-4">Add Team Member</h1>
                        <a href="team-all.php" class="btn btn-secondary mt-4">
                            <i class="fas fa-arrow-left me-1"></i>Back to Team List
                        </a>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="team-all.php">Team Management</a></li>
                        <li class="breadcrumb-item active">Add Team Member</li>
                    </ol>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-user-plus me-1"></i>
                                    Team Member Information
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?= htmlspecialchars($name ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="position" name="position" 
                                                       value="<?= htmlspecialchars($position ?? '') ?>" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="4"
                                                      placeholder="Brief description about the team member..."><?= htmlspecialchars($description ?? '') ?></textarea>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?= htmlspecialchars($email ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Phone</label>
                                                <input type="text" class="form-control" id="phone" name="phone" 
                                                       value="<?= htmlspecialchars($phone ?? '') ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="image" class="form-label">Profile Image</label>
                                            <input type="file" class="form-control" id="image" name="image" 
                                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                            <div class="form-text">Supported formats: JPG, JPEG, PNG, GIF, WebP. Max size: 5MB</div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="linkedin" class="form-label">LinkedIn URL</label>
                                                <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                                       value="<?= htmlspecialchars($linkedin ?? '') ?>" 
                                                       placeholder="https://linkedin.com/in/username">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="twitter" class="form-label">Twitter URL</label>
                                                <input type="url" class="form-control" id="twitter" name="twitter" 
                                                       value="<?= htmlspecialchars($twitter ?? '') ?>" 
                                                       placeholder="https://twitter.com/username">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="facebook" class="form-label">Facebook URL</label>
                                                <input type="url" class="form-control" id="facebook" name="facebook" 
                                                       value="<?= htmlspecialchars($facebook ?? '') ?>" 
                                                       placeholder="https://facebook.com/username">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="sort_order" class="form-label">Sort Order</label>
                                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                                       value="<?= htmlspecialchars($sort_order ?? 0) ?>" min="0">
                                                <div class="form-text">Lower numbers appear first</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check mt-4">
                                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                                           <?= ($is_active ?? 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_active">
                                                        Active (Show on website)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <a href="team-all.php" class="btn btn-secondary me-md-2">Cancel</a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Add Team Member
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Tips
                                </div>
                                <div class="card-body">
                                    <h6>Image Guidelines:</h6>
                                    <ul class="small">
                                        <li>Use square images (1:1 ratio) for best results</li>
                                        <li>Recommended size: 400x400 pixels</li>
                                        <li>Professional headshots work best</li>
                                        <li>Keep file size under 5MB</li>
                                    </ul>
                                    
                                    <h6 class="mt-3">Sort Order:</h6>
                                    <p class="small">Use sort order to control the display sequence. Lower numbers appear first (e.g., 1, 2, 3...).</p>
                                    
                                    <h6 class="mt-3">Social Links:</h6>
                                    <p class="small">Social media links are optional but help visitors connect with team members.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Admin JS -->
    <script src="assets/js/scripts.js"></script>

    <script>
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create preview if it doesn't exist
                    let preview = document.getElementById('imagePreview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'imagePreview';
                        preview.className = 'img-thumbnail mt-2';
                        preview.style.maxWidth = '200px';
                        preview.style.maxHeight = '200px';
                        document.getElementById('image').parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
