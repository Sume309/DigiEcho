<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Ensure this script is accessed via POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get the raw POST data
$input = file_get_contents('php://input');
parse_str($input, $data);

$text = trim($data['text'] ?? '');
$table = trim($data['table'] ?? '');

if (empty($text) || empty($table)) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Function to create a URL-friendly slug
function createSlug($string, $separator = '-') {
    // Convert all dashes/underscores to the specified separator
    $flip = $separator === '-' ? '_' : '-';
    $string = preg_replace('!['.preg_quote($flip).']+!u', $separator, $string);
    
    // Replace @ with the word 'at'
    $string = str_replace('@', $separator.'at'.$separator, $string);
    
    // Remove all characters that are not the separator, letters, numbers, or whitespace
    $string = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($string));
    
    // Replace all separator characters and whitespace by a single separator
    $string = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $string);
    
    return trim($string, $separator);
}

// Generate the initial slug
$slug = createSlug($text);

// Check if the slug already exists in the database
$db = new MysqliDb([
    'host' => settings()['hostname'],
    'username' => settings()['user'],
    'password' => settings()['password'],
    'db' => settings()['database'],
    'port' => 3306,
    'prefix' => '',
    'charset' => 'utf8mb4'
]);

// Make sure the slug is unique
$originalSlug = $slug;
$counter = 1;

while (true) {
    $db->where('slug', $slug);
    $exists = $db->has($table);
    
    if (!$exists) {
        break;
    }
    
    // If the slug exists, append a number and try again
    $slug = $originalSlug . '-' . $counter;
    $counter++;
}

// Return the unique slug
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'slug' => $slug,
    'original' => $originalSlug
]);

// Close the database connection
$db->disconnect();
