<?php
session_start();

// Simple authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];
    
    // Check if files are received
    if (isset($_FILES['main_image'])) {
        $file = $_FILES['main_image'];
        
        // Basic validation
        if ($file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileExtension, $allowedExtensions)) {
                if ($file['size'] <= 5 * 1024 * 1024) {
                    $fileName = 'simple_test_' . uniqid() . '.' . $fileExtension;
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        echo json_encode(['success' => true, 'message' => 'Image uploaded successfully!', 'filename' => $fileName]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'File too large']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid file type']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No image file received']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Image Upload Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Simple Image Upload Test</h5>
                    </div>
                    <div class="card-body">
                        <form id="simpleForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="main_image" class="form-label">Select Image</label>
                                <input type="file" class="form-control" id="main_image" name="main_image" accept="image/*" required>
                                <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                            </div>
                            <div class="mb-3">
                                <img id="preview" src="" alt="Preview" class="img-fluid d-none" style="max-height: 200px;">
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Image</button>
                        </form>
                        
                        <div id="result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Simple preview
    $('#main_image').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview').attr('src', e.target.result).removeClass('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Simple form submission
    $('#simpleForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Debug: Log FormData contents
        console.log('FormData contents:');
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }
        
        $.ajax({
            url: 'simple-image-test.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Success response:', response);
                if (response.success) {
                    $('#result').html('<div class="alert alert-success">' + response.message + '</div>');
                    Swal.fire('Success!', response.message, 'success');
                } else {
                    $('#result').html('<div class="alert alert-danger">' + response.message + '</div>');
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.log('Error response:', xhr, status, error);
                $('#result').html('<div class="alert alert-danger">AJAX Error: ' + error + '</div>');
                Swal.fire('Error!', 'Upload failed: ' + error, 'error');
            }
        });
    });
    </script>
</body>
</html>