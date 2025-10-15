<?php
session_start();
require_once '../config/database.php';

// Check admin authentication
require_once __DIR__ . '/../vendor/autoload.php';
use App\auth\Admin;

if(!Admin::Check()){
    header('Location: ../login.php?message=Please login to access reports');
    exit();
}

echo "<h2>Reports System Auto-Initialization</h2>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .info { color: blue; } .warning { color: orange; }</style>";

try {
    // Check if reports tables exist and have data
    $needsInit = false;
    $issues = [];
    
    // Check report_categories
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM report_categories");
        $categoryCount = $stmt->fetchColumn();
        if ($categoryCount == 0) {
            $needsInit = true;
            $issues[] = "No report categories found";
        } else {
            echo "<p class='success'>✓ Report categories: $categoryCount found</p>";
        }
    } catch (Exception $e) {
        $needsInit = true;
        $issues[] = "report_categories table missing or inaccessible";
    }
    
    // Check report_templates
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM report_templates");
        $templateCount = $stmt->fetchColumn();
        if ($templateCount == 0) {
            $needsInit = true;
            $issues[] = "No report templates found";
        } else {
            echo "<p class='success'>✓ Report templates: $templateCount found</p>";
        }
    } catch (Exception $e) {
        $needsInit = true;
        $issues[] = "report_templates table missing or inaccessible";
    }
    
    // Check if template ID 14 exists
    try {
        $stmt = $pdo->prepare("SELECT name FROM report_templates WHERE id = 14");
        $stmt->execute();
        $template14 = $stmt->fetch();
        if ($template14) {
            echo "<p class='success'>✓ Template ID 14 exists: " . $template14['name'] . "</p>";
        } else {
            echo "<p class='warning'>⚠ Template ID 14 not found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error checking template 14: " . $e->getMessage() . "</p>";
    }
    
    if ($needsInit) {
        echo "<h3 class='warning'>Issues Found:</h3>";
        foreach ($issues as $issue) {
            echo "<p class='error'>• $issue</p>";
        }
        
        echo "<h3>Auto-Initialization</h3>";
        echo "<p>The reports system needs to be initialized. This will create:</p>";
        echo "<ul>";
        echo "<li>Report categories (Sales, Customer Analytics, Product Performance, etc.)</li>";
        echo "<li>19+ professional report templates</li>";
        echo "<li>Default dashboard configuration</li>";
        echo "<li>All necessary database tables</li>";
        echo "</ul>";
        
        if (isset($_POST['initialize'])) {
            echo "<p class='info'>Starting initialization...</p>";
            
            // Include and run the initialization
            require_once 'init-reports.php';
            
            try {
                initializeReportsSystem($pdo);
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h4>✅ Reports System Initialized Successfully!</h4>";
                echo "<p>All report categories, templates, and database structures have been created.</p>";
                echo "<p><strong>Available Reports:</strong></p>";
                
                // List created templates
                $stmt = $pdo->query("SELECT id, name, chart_type FROM report_templates ORDER BY id");
                $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<ul>";
                foreach ($templates as $template) {
                    echo "<li>ID {$template['id']}: {$template['name']} ({$template['chart_type']})</li>";
                }
                echo "</ul>";
                
                echo "<p><a href='reports-list.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Reports List</a></p>";
                echo "<p><a href='reports-view.php?template_id=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Report Viewer</a></p>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<p class='error'>Initialization failed: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<form method='post' style='margin: 20px 0;'>";
            echo "<button type='submit' name='initialize' style='background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>Initialize Reports System Now</button>";
            echo "</form>";
        }
    } else {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Reports System is Ready!</h3>";
        echo "<p>The reports system appears to be properly initialized.</p>";
        echo "<p><a href='reports-list.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Reports</a></p>";
        echo "<p><a href='debug-reports.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Diagnostics</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Database connection error: " . $e->getMessage() . "</p>";
}
?>

<p><a href="index.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Back to Dashboard</a></p>
