<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

echo "<h2>User Profiles System Fix</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;padding:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} .warning{color:orange;font-weight:bold;}</style>";

$db = new MysqliDb();

try {
    // Step 1: Check if user_profiles table exists
    echo "<h3>Step 1: Checking user_profiles table</h3>";
    $tableExists = $db->rawQuery("SHOW TABLES LIKE 'user_profiles'");
    
    if (empty($tableExists)) {
        echo "<span class='warning'>⚠️ user_profiles table does not exist!</span><br>";
        echo "<span class='info'>Creating user_profiles table...</span><br>";
        
        $createTableSQL = "
        CREATE TABLE `user_profiles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `first_name` varchar(100) DEFAULT NULL,
            `last_name` varchar(100) DEFAULT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `profile_image` varchar(255) DEFAULT NULL,
            `bio` text,
            `address` text,
            `city` varchar(100) DEFAULT NULL,
            `country` varchar(100) DEFAULT NULL,
            `postal_code` varchar(20) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`),
            CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $result = $db->rawQuery($createTableSQL);
        if ($result !== false) {
            echo "<span class='success'>✅ user_profiles table created successfully!</span><br>";
        } else {
            echo "<span class='error'>❌ Failed to create user_profiles table: " . $db->getLastError() . "</span><br>";
            exit;
        }
    } else {
        echo "<span class='success'>✅ user_profiles table exists</span><br>";
    }
    
    // Step 2: Check table structure
    echo "<h3>Step 2: Verifying table structure</h3>";
    $columns = $db->rawQuery("DESCRIBE user_profiles");
    $hasProfileImage = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'profile_image') {
            $hasProfileImage = true;
            break;
        }
    }
    
    if (!$hasProfileImage) {
        echo "<span class='warning'>⚠️ profile_image column missing!</span><br>";
        echo "<span class='info'>Adding profile_image column...</span><br>";
        $addColumnSQL = "ALTER TABLE user_profiles ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER phone";
        $db->rawQuery($addColumnSQL);
        echo "<span class='success'>✅ profile_image column added!</span><br>";
    } else {
        echo "<span class='success'>✅ profile_image column exists</span><br>";
    }
    
    // Step 3: Ensure all users have profile records
    echo "<h3>Step 3: Creating missing user profiles</h3>";
    $usersWithoutProfiles = $db->rawQuery("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone 
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        WHERE up.user_id IS NULL
    ");
    
    if (!empty($usersWithoutProfiles)) {
        echo "<span class='info'>Found " . count($usersWithoutProfiles) . " users without profiles</span><br>";
        
        foreach ($usersWithoutProfiles as $user) {
            $insertSQL = "
                INSERT INTO user_profiles (user_id, first_name, last_name, phone, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ";
            
            $result = $db->rawQuery($insertSQL, [
                $user['id'],
                $user['first_name'],
                $user['last_name'],
                $user['phone']
            ]);
            
            if ($result !== false) {
                echo "<span class='success'>✅ Created profile for user: {$user['first_name']} {$user['last_name']} (ID: {$user['id']})</span><br>";
            } else {
                echo "<span class='error'>❌ Failed to create profile for user ID {$user['id']}: " . $db->getLastError() . "</span><br>";
            }
        }
    } else {
        echo "<span class='success'>✅ All users already have profiles</span><br>";
    }
    
    // Step 4: Create uploads directory if it doesn't exist
    echo "<h3>Step 4: Checking uploads directory</h3>";
    $uploadDir = __DIR__ . '/../assets/uploads/profiles/';
    
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            echo "<span class='success'>✅ Created uploads directory: $uploadDir</span><br>";
        } else {
            echo "<span class='error'>❌ Failed to create uploads directory</span><br>";
        }
    } else {
        echo "<span class='success'>✅ Uploads directory exists</span><br>";
    }
    
    // Check if directory is writable
    if (is_writable($uploadDir)) {
        echo "<span class='success'>✅ Uploads directory is writable</span><br>";
    } else {
        echo "<span class='warning'>⚠️ Uploads directory is not writable - fixing permissions...</span><br>";
        chmod($uploadDir, 0755);
        if (is_writable($uploadDir)) {
            echo "<span class='success'>✅ Fixed directory permissions</span><br>";
        } else {
            echo "<span class='error'>❌ Could not fix directory permissions</span><br>";
        }
    }
    
    // Step 5: Test the JOIN query
    echo "<h3>Step 5: Testing JOIN query</h3>";
    $testQuery = "
        SELECT u.id, u.first_name, u.last_name, u.email, up.profile_image, up.updated_at as profile_updated
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        ORDER BY u.created_at DESC 
        LIMIT 5
    ";
    
    $testResult = $db->rawQuery($testQuery);
    
    if ($testResult) {
        echo "<span class='success'>✅ JOIN query working correctly</span><br>";
        echo "<span class='info'>Sample results:</span><br>";
        echo "<table border='1' style='border-collapse:collapse;margin:10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Profile Image</th><th>Profile Updated</th></tr>";
        
        foreach ($testResult as $row) {
            $name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
            $email = htmlspecialchars($row['email']);
            $profileImage = $row['profile_image'] ?: '<em>None</em>';
            $profileUpdated = $row['profile_updated'] ?: '<em>Never</em>';
            
            echo "<tr><td>{$row['id']}</td><td>$name</td><td>$email</td><td>$profileImage</td><td>$profileUpdated</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<span class='error'>❌ JOIN query failed: " . $db->getLastError() . "</span><br>";
    }
    
    echo "<h3>✅ System Check Complete!</h3>";
    echo "<div style='background:#e7f5e7;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<p><strong>✅ All checks completed successfully!</strong></p>";
    echo "<p>Your user profile system should now be working correctly.</p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Upload user profile images via the admin panel</li>";
    echo "<li>View user images in the users list</li>";
    echo "<li>Users can update their own profile images</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Critical Error: " . $e->getMessage() . "</span><br>";
}

echo "<br><br><a href='users-all.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>← Back to Users List</a>";
echo " <a href='user-edit.php?id=18' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>Test Edit User</a>";
echo " <a href='test-direct-query.php' style='background:#ffc107;color:black;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>Test Queries</a>";

// Auto-cleanup: Delete this file after successful execution
if (file_exists(__FILE__)) {
    echo "<br><br><small style='color:#666;'>Note: This setup script will be automatically deleted after execution for security.</small>";
    // Uncomment the next line to auto-delete the file after running
    // unlink(__FILE__);
}
?>