<?php
echo "=== FIXING ALL SCRIPT PATHS ===\n\n";

// Get all PHP files in admin directory
$adminDir = __DIR__;
$phpFiles = glob($adminDir . '/*.php');

$filesFixed = 0;
$totalReplacements = 0;

foreach ($phpFiles as $file) {
    $filename = basename($file);
    
    // Skip our own fix files
    if (strpos($filename, 'fix_') === 0 || strpos($filename, 'debug_') === 0 || strpos($filename, 'test_') === 0) {
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix js/scripts.js -> assets/js/scripts.js
    $content = str_replace('src="js/scripts.js"', 'src="assets/js/scripts.js"', $content);
    
    // Fix css/styles.css -> assets/css/styles.css (if any)
    $content = str_replace('href="css/styles.css"', 'href="assets/css/styles.css"', $content);
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $replacements = substr_count($originalContent, 'js/scripts.js') + substr_count($originalContent, 'css/styles.css');
        $totalReplacements += $replacements;
        $filesFixed++;
        echo "✅ Fixed: $filename ($replacements replacements)\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files processed: " . count($phpFiles) . "\n";
echo "Files fixed: $filesFixed\n";
echo "Total replacements: $totalReplacements\n";

if ($filesFixed > 0) {
    echo "\n✅ All script paths have been fixed!\n";
    echo "The 404 errors should now be resolved.\n";
} else {
    echo "\n✅ All files already have correct paths.\n";
}

echo "\nNext steps:\n";
echo "1. Clear browser cache (Ctrl+F5)\n";
echo "2. Check browser console for any remaining 404 errors\n";
echo "3. Test the discount management page\n";
?>
