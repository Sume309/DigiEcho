<?php
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

echo "<h2>Fix Missing Product Images</h2>";

$uploadDir = __DIR__ . '/../assets/products/';
$placeholderImage = 'placeholder.jpg';

// Create a simple placeholder image if it doesn't exist
$placeholderPath = $uploadDir . $placeholderImage;
if (!file_exists($placeholderPath)) {
    // Create a simple 1x1 pixel transparent PNG
    $img = imagecreate(200, 200);
    $bg = imagecolorallocate($img, 240, 240, 240);
    $text = imagecolorallocate($img, 100, 100, 100);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 3, 50, 90, 'No Image', $text);
    imagestring($img, 3, 45, 110, 'Available', $text);
    imagejpeg($img, $placeholderPath, 90);
    imagedestroy($img);
    echo "<p style='color: green;'>Created placeholder image: $placeholderImage</p>";
}

// Get all products
$products = $db->get('products', null, ['id', 'name', 'image', 'gallery_images']);

$fixedMainImages = 0;
$fixedGalleryImages = 0;

foreach ($products as $product) {
    $needsUpdate = false;
    $updateData = [];
    
    // Check and fix main image
    if (!empty($product['image'])) {
        $mainImagePath = $uploadDir . $product['image'];
        if (!file_exists($mainImagePath)) {
            $updateData['image'] = $placeholderImage;
            $needsUpdate = true;
            $fixedMainImages++;
            echo "<p>Fixed main image for Product ID {$product['id']}: {$product['name']}</p>";
        }
    }
    
    // Check and fix gallery images
    if (!empty($product['gallery_images'])) {
        $galleryImages = json_decode($product['gallery_images'], true);
        if (is_array($galleryImages)) {
            $validGalleryImages = [];
            $galleryFixed = false;
            
            foreach ($galleryImages as $image) {
                $imagePath = $uploadDir . $image;
                if (file_exists($imagePath)) {
                    $validGalleryImages[] = $image;
                } else {
                    $galleryFixed = true;
                    echo "<p>Removed missing gallery image: {$image} from Product ID {$product['id']}</p>";
                }
            }
            
            if ($galleryFixed) {
                $updateData['gallery_images'] = !empty($validGalleryImages) ? json_encode($validGalleryImages) : null;
                $needsUpdate = true;
                $fixedGalleryImages++;
            }
        }
    }
    
    // Update the product if needed
    if ($needsUpdate) {
        $db->where('id', $product['id']);
        if ($db->update('products', $updateData)) {
            echo "<p style='color: green;'>Updated Product ID {$product['id']}</p>";
        } else {
            echo "<p style='color: red;'>Failed to update Product ID {$product['id']}: " . $db->getLastError() . "</p>";
        }
    }
}

echo "<h3>Summary:</h3>";
echo "<ul>";
echo "<li>Fixed main images: $fixedMainImages</li>";
echo "<li>Fixed gallery images: $fixedGalleryImages</li>";
echo "<li>Placeholder image created: " . (file_exists($placeholderPath) ? 'Yes' : 'No') . "</li>";
echo "</ul>";

echo "<p><a href='debug-missing-images.php'>Check Image Status Again</a></p>";
echo "<p><a href='product-management.php'>Back to Product Management</a></p>";
?>