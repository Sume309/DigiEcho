<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => '', 'debug' => []];
    
    try {
        // Log all received data
        error_log('POST: ' . json_encode($_POST));
        error_log('FILES: ' . json_encode($_FILES));
        
        $response['debug']['post_data'] = $_POST;
        $response['debug']['files_data'] = $_FILES;
        
        // Check required fields
        if (empty($_POST['name']) || empty($_POST['sku']) || empty($_POST['selling_price'])) {
            throw new Exception('Missing required fields');
        }
        
        // Handle image upload
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileName = 'simplified_' . uniqid() . '.' . pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $targetPath)) {
                $response['success'] = true;
                $response['message'] = 'Product and image uploaded successfully!';
                $response['image_filename'] = $fileName;
            } else {
                throw new Exception('Failed to upload image');
            }
        } else {
            $response['success'] = true;
            $response['message'] = 'Product created successfully (no image)';
            if (isset($_FILES['main_image'])) {
                $response['debug']['image_error'] = $_FILES['main_image']['error'];
            }
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log('Error: ' . $e->getMessage());
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
    <title>Simplified Product Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .image-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .image-upload-area:hover {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Simplified Product Form Test</h2>
        
        <form id="simpleProductForm" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU *</label>
                                <input type="text" class="form-control" id="sku" name="sku" required>
                            </div>
                            <div class="mb-3">
                                <label for="selling_price" class="form-label">Price *</label>
                                <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Product Image</label>
                                <div class="image-upload-area" id="imageUploadArea">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                    <p>Click to upload image</p>
                                    <input type="file" id="main_image" name="main_image" accept="image/*" style="display: none;">
                                </div>
                                <div id="imagePreview" class="mt-2" style="display: none;">
                                    <img id="previewImg" class="image-preview" alt="Preview">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Create Product</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
    $(document).ready(function() {
        let selectedFile = null;
        
        // Upload area click
        $('#imageUploadArea').on('click', function() {
            console.log('Upload area clicked');
            $('#main_image').click();
        });
        
        // File input change
        $('#main_image').on('change', function(e) {
            console.log('File input changed');
            const file = e.target.files[0];
            console.log('Selected file:', file);
            
            if (file) {
                selectedFile = file;
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#previewImg').attr('src', e.target.result);
                    $('#imagePreview').show();
                };
                reader.readAsDataURL(file);
                
                console.log('File stored and preview shown');
            }
        });
        
        // Form submission
        $('#simpleProductForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            const formData = new FormData(this);
            
            // Manually add the file if it exists
            if (selectedFile) {
                console.log('Adding selected file to FormData:', selectedFile.name);
                formData.set('main_image', selectedFile);
            }
            
            // Debug FormData
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(key, ':', value.name, '(', value.size, 'bytes)');
                } else {
                    console.log(key, ':', value);
                }
            }
            
            $.ajax({
                url: 'simplified-product-form.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Success response:', response);
                    
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', xhr, status, error);
                    Swal.fire('Error!', 'AJAX request failed: ' + error, 'error');
                }
            });
        });
    });
    </script>
    
    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
</body>
</html>