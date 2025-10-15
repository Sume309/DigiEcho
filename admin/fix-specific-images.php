<?php
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

echo "<h2>Fix Specific Missing Images</h2>";

$uploadDir = __DIR__ . '/../assets/products/';
$placeholderImage = 'placeholder.jpg';

// Create placeholder image if it doesn't exist
$placeholderPath = $uploadDir . $placeholderImage;
if (!file_exists($placeholderPath)) {
    // Create a simple placeholder image
    $img = imagecreate(200, 200);
    $bg = imagecolorallocate($img, 240, 240, 240);
    $text = imagecolorallocate($img, 100, 100, 100);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 3, 50, 90, 'No Image', $text);
    imagestring($img, 3, 45, 110, 'Available', $text);
    imagejpeg($img, $placeholderPath, 90);
    imagedestroy($img);
    echo "<p style='color: green;'>✅ Created placeholder image: $placeholderImage</p>";
}

// Fix the specific missing images mentioned in the error
$missingImageNames = [
    '68bbf99ac7ce6_1757149594.webp',
    '68b1e30c98917_1756488460.webp', 
    '68b1e9b9db746_1756490169.png'
];

$fixedCount = 0;

foreach ($missingImageNames as $imageName) {
    $imagePath = $uploadDir . $imageName;
    
    if (!file_exists($imagePath)) {
        // Find products using this image
        $products = $db->rawQuery("SELECT id, name, image, gallery_images FROM products WHERE image = ? OR gallery_images LIKE ?", 
                                [$imageName, '%' . $imageName . '%']);
        
        foreach ($products as $product) {
            $updateData = [];
            $needsUpdate = false;
            
            // Fix main image
            if ($product['image'] === $imageName) {
                $updateData['image'] = $placeholderImage;
                $needsUpdate = true;
                echo "<p>🔧 Fixed main image for Product ID {$product['id']}: {$product['name']}</p>";
            }
            
            // Fix gallery images
            if (!empty($product['gallery_images'])) {
                $galleryImages = json_decode($product['gallery_images'], true);
                if (is_array($galleryImages)) {
                    $newGalleryImages = array_filter($galleryImages, function($img) use ($imageName) {
                        return $img !== $imageName;
                    });
                    
                    if (count($newGalleryImages) !== count($galleryImages)) {
                        $updateData['gallery_images'] = !empty($newGalleryImages) ? json_encode(array_values($newGalleryImages)) : null;
                        $needsUpdate = true;
                        echo "<p>🔧 Removed missing gallery image from Product ID {$product['id']}: {$product['name']}</p>";
                    }
                }
            }
            
            // Update the product
            if ($needsUpdate) {
                $db->where('id', $product['id']);
                if ($db->update('products', $updateData)) {
                    $fixedCount++;
                } else {
                    echo "<p style='color: red;'>❌ Failed to update Product ID {$product['id']}</p>";
                }
            }
        }
    } else {
        echo "<p style='color: green;'>✅ Image exists: $imageName</p>";
    }
}

echo "<h3>Summary:</h3>";
echo "<p>Fixed $fixedCount product record(s)</p>";
echo "<p>Placeholder image: " . (file_exists($placeholderPath) ? '✅ Available' : '❌ Failed to create') . "</p>";

echo "<hr>";
echo "<p><a href='quick-image-check.php'>Check Status Again</a></p>";
echo "<p><a href='product-management.php'>Back to Product Management</a></p>";
?>