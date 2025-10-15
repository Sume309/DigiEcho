<?php
// Quick PHP error log checker
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo '<h3>PHP Error Logging Test</h3>';
echo '<p>Error reporting level: ' . error_reporting() . '</p>';
echo '<p>Display errors: ' . ini_get('display_errors') . '</p>';
echo '<p>Log errors: ' . ini_get('log_errors') . '</p>';
echo '<p>Error log location: ' . (ini_get('error_log') ?: 'System default') . '</p>';

// Test error logging
error_log('TEST: This is a test error log entry from image upload debugging');

echo '<p>Test error logged. Check your error log.</p>';

// Check upload settings
echo '<h3>Upload Settings</h3>';
echo '<ul>';
echo '<li>file_uploads: ' . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . '</li>';
echo '<li>upload_max_filesize: ' . ini_get('upload_max_filesize') . '</li>';
echo '<li>post_max_size: ' . ini_get('post_max_size') . '</li>';
echo '<li>max_file_uploads: ' . ini_get('max_file_uploads') . '</li>';
echo '<li>upload_tmp_dir: ' . (ini_get('upload_tmp_dir') ?: 'System default') . '</li>';
echo '</ul>';

// Check directory permissions
$uploadDir = __DIR__ . '/../assets/products/';
echo '<h3>Directory Check</h3>';
echo '<ul>';
echo '<li>Directory: ' . $uploadDir . '</li>';
echo '<li>Exists: ' . (is_dir($uploadDir) ? 'Yes' : 'No') . '</li>';
echo '<li>Readable: ' . (is_readable($uploadDir) ? 'Yes' : 'No') . '</li>';
echo '<li>Writable: ' . (is_writable($uploadDir) ? 'Yes' : 'No') . '</li>';
echo '<li>Real path: ' . (realpath($uploadDir) ?: 'Cannot resolve') . '</li>';
echo '</ul>';
?>