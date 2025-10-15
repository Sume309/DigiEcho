<?php
// Simple placeholder image generator
$width = intval($_GET['w'] ?? 150);
$height = intval($_GET['h'] ?? 150);
$text = $_GET['text'] ?? '?';
$bg_color = $_GET['bg'] ?? 'cccccc';
$text_color = $_GET['color'] ?? '666666';

// Limit dimensions for security
$width = min(max($width, 50), 500);
$height = min(max($height, 50), 500);

// Create image
$image = imagecreate($width, $height);

// Convert hex colors to RGB
function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}

$bg_rgb = hex2rgb($bg_color);
$text_rgb = hex2rgb($text_color);

// Allocate colors
$bg = imagecolorallocate($image, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2]);
$text_col = imagecolorallocate($image, $text_rgb[0], $text_rgb[1], $text_rgb[2]);

// Fill background
imagefill($image, 0, 0, $bg);

// Add text using built-in fonts (no external font files needed)
$font_size = 5; // Built-in font size (1-5)
$text = strtoupper(substr($text, 0, 2)); // Show first 2 characters, uppercase

// Calculate text position to center it
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_col);

// Output image
header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
imagepng($image);
imagedestroy($image);
?>
