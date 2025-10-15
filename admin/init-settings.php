<?php
require_once __DIR__ . '/../vendor/autoload.php';

echo "<h2>Initializing Settings System</h2>";

try {
    $db = new MysqliDb();
    
    // Create site_settings table
    $db->rawQuery("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "<p>✅ Created site_settings table</p>";
    
    // Create banners table
    $db->rawQuery("CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(255),
        description TEXT,
        image VARCHAR(255) NOT NULL,
        button_text VARCHAR(100),
        button_link VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        sort_order INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "<p>✅ Created banners table</p>";
    
    // Insert default settings
    $defaultSettings = [
        'site_name' => 'DigiEcho',
        'site_description' => 'Your trusted online marketplace for quality products',
        'contact_email' => 'info@digiecho.com',
        'contact_phone' => '+880 1234567890',
        'address' => 'Dhaka, Bangladesh',
        'logo' => 'assets/images/logo.png',
        'notice_enabled' => '1',
        'notice_text' => 'Welcome to DigiEcho! 🎉 Get 20% off on your first order. Use code: WELCOME20',
        'notice_type' => 'success'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        $existing = $db->where('setting_key', $key)->getOne('site_settings');
        if (!$existing) {
            $db->insert('site_settings', [
                'setting_key' => $key,
                'setting_value' => $value
            ]);
            echo "<p>✅ Added setting: {$key}</p>";
        } else {
            echo "<p>ℹ️ Setting already exists: {$key}</p>";
        }
    }
    
    // Create banners directory if it doesn't exist
    $bannersDir = '../assets/images/banners/';
    if (!is_dir($bannersDir)) {
        mkdir($bannersDir, 0755, true);
        echo "<p>✅ Created banners directory</p>";
    }
    
    // Insert sample banner if none exist
    $existingBanners = $db->get('banners');
    if (empty($existingBanners)) {
        // Copy a sample banner image (you can replace this with your own)
        $sampleBanner = [
            'title' => 'Welcome to DigiEcho',
            'subtitle' => 'Your Trusted Online Marketplace',
            'description' => 'Discover amazing products with great deals, quality assurance, and fast delivery. Shop with confidence!',
            'image' => 'assets/images/banners/sample-banner.jpg',
            'button_text' => 'Shop Now',
            'button_link' => 'index.php#products',
            'status' => 'active',
            'sort_order' => 1
        ];
        
        // Use existing logo as banner placeholder if no banner image exists
        $bannerImagePath = '../assets/images/banners/sample-banner.jpg';
        if (!file_exists($bannerImagePath)) {
            // Copy logo as placeholder banner
            $logoPath = '../assets/images/logo.png';
            if (file_exists($logoPath)) {
                copy($logoPath, $bannerImagePath);
                echo "<p>✅ Created sample banner using logo</p>";
            } else {
                // Create a simple HTML file as placeholder
                $htmlContent = '<!-- Placeholder banner image -->';
                file_put_contents($bannerImagePath, $htmlContent);
                echo "<p>⚠️ Created placeholder banner file (please upload a proper banner image)</p>";
            }
        }
        
        $db->insert('banners', $sampleBanner);
        echo "<p>✅ Added sample banner</p>";
    } else {
        echo "<p>ℹ️ Banners already exist</p>";
    }
    
    echo "<h3>🎉 Settings system initialized successfully!</h3>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Go to <a href='settings.php'>Admin Settings</a> to manage your site</li>";
    echo "<li>Upload your own logo and banners</li>";
    echo "<li>Customize the notice bar message</li>";
    echo "<li>Visit the <a href='../index.php'>homepage</a> to see the changes</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
