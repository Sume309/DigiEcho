<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

echo "<h2>Running Team Members Migration</h2>";

try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute the migration file
    $migration_file = __DIR__ . '/../migrations/20250928_create_team_members_table.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: " . $migration_file);
    }
    
    $sql = file_get_contents($migration_file);
    
    // Split SQL statements by semicolon and execute each one
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "<p>Executing: " . substr($statement, 0, 50) . "...</p>";
            $pdo->exec($statement);
        }
    }
    
    echo "<div class='alert alert-success'>Migration completed successfully!</div>";
    echo "<p><a href='team-all.php' class='btn btn-primary'>Go to Team Management</a></p>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . $e->getMessage() . "</div>";
} catch(Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<style>
.alert {
    padding: 15px;
    margin: 20px 0;
    border: 1px solid transparent;
    border-radius: 4px;
}
.alert-success {
    color: #3c763d;
    background-color: #dff0d8;
    border-color: #d6e9c6;
}
.alert-danger {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
}
.btn {
    display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    background-image: none;
    border: 1px solid transparent;
    border-radius: 4px;
    text-decoration: none;
}
.btn-primary {
    color: #fff;
    background-color: #337ab7;
    border-color: #2e6da4;
}
</style>
