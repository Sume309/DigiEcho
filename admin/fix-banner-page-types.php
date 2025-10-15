<?php
session_start();
require_once '../config/database.php';

// Check admin authentication
require_once __DIR__ . '/../vendor/autoload.php';
use App\auth\Admin;

if(!Admin::Check()){
    header('Location: ../login.php?message=Please login');
    exit();
}

echo "<h2>🔧 Banner Page Types Fix</h2>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

try {
    // Check if page_type column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM banners LIKE 'page_type'");
    $pageTypeExists = $stmt->fetch();
    
    if (!$pageTypeExists) {
        echo "<h3>Adding page_type column to banners table...</h3>";
        $pdo->exec("ALTER TABLE banners ADD COLUMN page_type ENUM('homepage', 'hot-deals') DEFAULT 'homepage' AFTER button_link");
        echo "<p class='success'>✅ Added page_type column successfully!</p>";
    } else {
        echo "<p class='info'>✅ page_type column already exists</p>";
    }
    
    // Update existing banners without page_type
    $stmt = $pdo->prepare("UPDATE banners SET page_type = 'homepage' WHERE page_type IS NULL OR page_type = ''");
    $result = $stmt->execute();
    $updatedRows = $stmt->rowCount();
    
    if ($updatedRows > 0) {
        echo "<p class='success'>✅ Updated $updatedRows banners to homepage type</p>";
    } else {
        echo "<p class='info'>✅ All banners already have page_type set</p>";
    }
    
    // Show current banner status
    echo "<h3>📊 Current Banner Status</h3>";
    $stmt = $pdo->query("SELECT page_type, COUNT(*) as count, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count FROM banners GROUP BY page_type");
    $bannerStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($bannerStats)) {
        echo "<p class='warning'>⚠️ No banners found in database</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr><th>Page Type</th><th>Total Banners</th><th>Active Banners</th><th>Status</th></tr>";
        foreach ($bannerStats as $stat) {
            $pageType = $stat['page_type'] ?: 'homepage';
            $icon = $pageType === 'homepage' ? '🏠' : '🔥';
            echo "<tr>";
            echo "<td>$icon " . ucfirst(str_replace('-', ' ', $pageType)) . "</td>";
            echo "<td>{$stat['count']}</td>";
            echo "<td>{$stat['active_count']}</td>";
            echo "<td class='success'>✅ Ready</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test banner loading
    echo "<h3>🧪 Testing Banner Loading</h3>";
    
    // Test homepage banners
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE page_type = 'homepage' AND status = 'active' ORDER BY sort_order ASC");
    $stmt->execute();
    $homepageBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Homepage Banners:</strong> " . count($homepageBanners) . " active banners</p>";
    if (count($homepageBanners) > 0) {
        echo "<ul>";
        foreach ($homepageBanners as $banner) {
            echo "<li>🏠 <strong>{$banner['title']}</strong> - {$banner['subtitle']} (Order: {$banner['sort_order']})</li>";
        }
        echo "</ul>";
    }
    
    // Test hot deals banners
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE page_type = 'hot-deals' AND status = 'active' ORDER BY sort_order ASC");
    $stmt->execute();
    $hotDealsBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Hot Deals Banners:</strong> " . count($hotDealsBanners) . " active banners</p>";
    if (count($hotDealsBanners) > 0) {
        echo "<ul>";
        foreach ($hotDealsBanners as $banner) {
            echo "<li>🔥 <strong>{$banner['title']}</strong> - {$banner['subtitle']} (Order: {$banner['sort_order']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠️ No hot deals banners found. You can create some in the admin settings.</p>";
    }
    
    echo "<h3>✅ Fix Complete!</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Banner System Status:</h4>";
    echo "<ul>";
    echo "<li>✅ Database structure updated with page_type support</li>";
    echo "<li>✅ Existing banners properly categorized</li>";
    echo "<li>✅ Edit and delete functions implemented</li>";
    echo "<li>✅ Hot deals banner management ready</li>";
    echo "</ul>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li>Edit existing banners by clicking the edit button</li>";
    echo "<li>Delete unwanted banners</li>";
    echo "<li>Create new hot deals banners with proper text content</li>";
    echo "<li>Manage both homepage and hot deals banners separately</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚀 Quick Actions</h3>";
    echo "<p>";
    echo "<a href='settings.php#hotdeals-banners' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Manage Hot Deals Banners</a>";
    echo "<a href='settings.php#homepage-banners' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Manage Homepage Banners</a>";
    echo "<a href='../hot-deals.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Hot Deals Page</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<p><a href="index.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Back to Dashboard</a></p>
