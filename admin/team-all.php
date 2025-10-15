<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$page = "Team Management";

// Database connection
try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle delete request
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $success_message = "Team member deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting team member: " . $e->getMessage();
    }
}

// Handle status toggle
if (isset($_POST['toggle_status'])) {
    try {
        $stmt = $pdo->prepare("UPDATE team_members SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$_POST['toggle_status']]);
        $success_message = "Team member status updated successfully!";
    } catch(PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Fetch all team members
try {
    $stmt = $pdo->query("SELECT * FROM team_members ORDER BY sort_order ASC, created_at DESC");
    $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching team members: " . $e->getMessage();
    $team_members = [];
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
                        <h1 class="mt-4">Team Management</h1>
                        <a href="team-add.php" class="btn btn-primary mt-4">
                            <i class="fas fa-plus me-1"></i>Add New Member
                        </a>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Team Management</li>
                    </ol>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-users me-1"></i>
                            All Team Members
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="teamTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Photo</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Sort Order</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($team_members as $member): ?>
                                        <tr>
                                            <td>
                                                <?php if ($member['image'] && file_exists('../' . $member['image'])): ?>
                                                    <img src="../<?= htmlspecialchars($member['image']) ?>" 
                                                         alt="<?= htmlspecialchars($member['name']) ?>" 
                                                         class="rounded-circle" 
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 50px; height: 50px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($member['name']) ?></td>
                                            <td><?= htmlspecialchars($member['position']) ?></td>
                                            <td><?= htmlspecialchars($member['email'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($member['phone'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($member['sort_order']) ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="toggle_status" value="<?= $member['id'] ?>">
                                                    <button type="submit" class="btn btn-sm <?= $member['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                                        <?= $member['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="team-edit.php?id=<?= $member['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $member['id'] ?>, '<?= htmlspecialchars($member['name']) ?>')"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="memberName"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/v/bs5/dt-2.3.2/datatables.min.js"></script>
    <!-- Admin JS -->
    <script src="assets/js/scripts.js"></script>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#teamTable').DataTable({
                "pageLength": 25,
                "order": [[ 5, "asc" ]], // Sort by sort_order
                "columnDefs": [
                    { "orderable": false, "targets": [0, 7] } // Disable sorting for photo and actions columns
                ]
            });
        });

        // Delete confirmation
        function confirmDelete(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('memberName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>
