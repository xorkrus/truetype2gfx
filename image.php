<?php
// Set the enviroment variable for GD
putenv('GDFONTPATH=' . realpath('./fonts'));

// Set the content-type
header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');

// Create the image
$im = imagecreatetruecolor(320, 240);

$dpi = 141;
if (isset($_GET["dpi"]) && is_numeric($_GET["dpi"]) && $_GET["dpi"] > 0 && $_GET["dpi"] <= 300) {
    $dpi = intval($_GET["dpi"]);
}

$text = 'Testing 123...';
if (isset($_GET["text"])) {
    $text = htmlspecialchars($_GET["text"], ENT_QUOTES, 'UTF-8');
    if (strlen($text) > 100) $text = substr($text, 0, 100);
}

$size = (20 * $dpi) / 96;
if (isset($_GET["size"]) && is_numeric($_GET["size"]) && $_GET["size"] >= 3 && $_GET["size"] <= 200) {
    $size = (intval($_GET["size"]) * $dpi) / 96;
}

$font = 'FreeSans.ttf';
if (isset($_GET["font"])) {
    $font = basename($_GET["font"]);
    // Only allow specific font files
    $allowed_fonts = [
        'FreeSans.ttf', 'FreeSansBold.ttf', 'FreeSansBoldOblique.ttf', 'FreeSansOblique.ttf',
        'FreeSerif.ttf', 'FreeSerifBold.ttf', 'FreeSerifBoldItalic.ttf', 'FreeSerifItalic.ttf',
        'FreeMono.ttf', 'FreeMonoBold.ttf', 'FreeMonoBoldOblique.ttf', 'FreeMonoOblique.ttf'
    ];
    
    // Check if it's a user font (starts with 'user/')
    if (strpos($font, 'user/') === 0) {
        $user_font = basename(substr($font, 5));
        if (preg_match('/^[a-zA-Z0-9._-]+\\.ttf$/i', $user_font)) {
            $font = 'user/' . $user_font;
        } else {
            $font = 'FreeSans.ttf';
        }
    } elseif (!in_array($font, $allowed_fonts)) {
        $font = 'FreeSans.ttf';
    }
}

// Create some colors
$white = imagecolorallocate($im, 240, 240, 240);
$black = imagecolorallocate($im, 0, 0, 0);
imagefilledrectangle($im, 0, 0, 319, 239, $white);

// First we create our bounding box for the first text
$bbox = imagettfbbox($size, 0, $font, $text);

// Center text
$w = abs($bbox[4] - $bbox[0]);

$cx = (imagesx($im) / 2) - ($w / 2) - ($bbox[0] / 2);
$cy = (imagesy($im) / 2) - ($bbox[5] / 2) - ($bbox[1] / 2);

// Add the text
imagettftext($im, $size, 0, $cx, $cy, $black, $font, $text);

// Using imagepng() results in clearer text compared with imagejpeg()
imagepng($im);
imagedestroy($im);
?>
