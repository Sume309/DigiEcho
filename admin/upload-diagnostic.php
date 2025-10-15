<?php
session_start();

// Simple authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Test directory permissions and PHP upload settings
$diagnostics = [];

// 1. Check upload directory
$uploadDir = __DIR__ . '/../assets/products/';
$diagnostics['upload_dir'] = [
    'path' => $uploadDir,
    'exists' => is_dir($uploadDir),
    'writable' => is_writable($uploadDir),
    'real_path' => realpath($uploadDir)
];

// 2. Check PHP upload settings
$diagnostics['php_settings'] = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'upload_tmp_dir' => ini_get('upload_tmp_dir')
];

// 3. Test actual upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    $response = ['success' => false, 'message' => '', 'debug' => []];
    
    try {
        $file = $_FILES['test_image'];
        $response['debug']['file_info'] = [
            'name' => $file['name'],
            'type' => $file['type'],
            'size' => $file['size'],
            'error' => $file['error'],
            'tmp_name' => $file['tmp_name']
        ];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($file['type'], $allowedTypes)) {
                if ($file['size'] <= 5 * 1024 * 1024) {
                    $fileName = 'diagnostic_' . uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $response['success'] = true;
                        $response['message'] = 'Upload successful!';
                        $response['filename'] = $fileName;
                        $response['target_path'] = $targetPath;
                    } else {
                        $response['message'] = 'Failed to move uploaded file';
                    }
                } else {
                    $response['message'] = 'File too large';
                }
            } else {
                $response['message'] = 'Invalid file type';
            }
        } else {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $response['message'] = $uploadErrors[$file['error']] ?? 'Unknown upload error';
        }
    } catch (Exception $e) {
        $response['message'] = 'Exception: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h2>Upload System Diagnostics</h2>
        
        <!-- Diagnostics Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>System Status</h5>
            </div>
            <div class="card-body">
                <h6>Upload Directory:</h6>
                <ul>
                    <li>Path: <code><?php echo $diagnostics['upload_dir']['path']; ?></code></li>
                    <li>Exists: <span class="badge <?php echo $diagnostics['upload_dir']['exists'] ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $diagnostics['upload_dir']['exists'] ? 'Yes' : 'No'; ?></span></li>
                    <li>Writable: <span class="badge <?php echo $diagnostics['upload_dir']['writable'] ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $diagnostics['upload_dir']['writable'] ? 'Yes' : 'No'; ?></span></li>
                    <li>Real Path: <code><?php echo $diagnostics['upload_dir']['real_path'] ?: 'N/A'; ?></code></li>
                </ul>
                
                <h6>PHP Settings:</h6>
                <ul>
                    <?php foreach ($diagnostics['php_settings'] as $setting => $value): ?>
                        <li><?php echo $setting; ?>: <code><?php echo $value ?: 'Not set'; ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- Upload Test -->
        <div class="card">
            <div class="card-header">
                <h5>Upload Test</h5>
            </div>
            <div class="card-body">
                <form id="diagnosticForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="test_image" class="form-label">Select Test Image</label>
                        <input type="file" class="form-control" id="test_image" name="test_image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Test Upload</button>
                </form>
                
                <div id="result" class="mt-3"></div>
            </div>
        </div>
    </div>
    
    <script>
    $('#diagnosticForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'upload-diagnostic.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                let html = '';
                if (response.success) {
                    html = `<div class="alert alert-success">
                        <strong>Success!</strong> ${response.message}<br>
                        <small>File: ${response.filename}</small>
                    </div>`;
                } else {
                    html = `<div class="alert alert-danger">
                        <strong>Failed!</strong> ${response.message}
                    </div>`;
                }
                
                if (response.debug) {
                    html += `<div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        <pre>${JSON.stringify(response.debug, null, 2)}</pre>
                    </div>`;
                }
                
                $('#result').html(html);
            },
            error: function(xhr, status, error) {
                $('#result').html(`<div class="alert alert-danger">
                    <strong>AJAX Error!</strong> ${error}<br>
                    <small>Status: ${status}</small>
                </div>`);
            }
        });
    });
    </script>
</body>
</html>