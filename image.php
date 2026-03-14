<?php
putenv('GDFONTPATH=' . realpath('./fonts'));
header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');

$width = 240;
$height = 240;
$dpi = 265;
if (isset($_GET["dpi"]) && is_numeric($_GET["dpi"]) && $_GET["dpi"] > 0 && $_GET["dpi"] <= 300) {
    $dpi = intval($_GET["dpi"]);
}

$text = '23:45';
if (isset($_GET["text"])) {
    $text = htmlspecialchars($_GET["text"], ENT_QUOTES, 'UTF-8');
    if (strlen($text) > 100) $text = substr($text, 0, 100);
}

$size = (20 * $dpi) / 72;
if (isset($_GET["size"]) && is_numeric($_GET["size"]) && $_GET["size"] >= 3 && $_GET["size"] <= 200) {
    $size = (intval($_GET["size"]) * $dpi) / 72;
}

// --- Определение пути к шрифту (как в предыдущей версии) ---
$font_file = __DIR__ . '/fonts/FreeSans.ttf';
if (isset($_GET["font"])) {
    $font_param = $_GET["font"];
    if (strpos($font_param, 'user/') === 0) {
        $user_font = basename(substr($font_param, 5));
        if (preg_match('/^[a-zA-Z0-9._-]+\\.ttf$/i', $user_font)) {
            $full_path = __DIR__ . '/fonts/user/' . $user_font;
            if (file_exists($full_path)) $font_file = $full_path;
        }
    } else {
        $sys_font = basename($font_param);
        if (preg_match('/^[a-zA-Z0-9._-]+\\.ttf$/i', $sys_font)) {
            $full_path = __DIR__ . '/fonts/' . $sys_font;
            if (file_exists($full_path)) $font_file = $full_path;
        }
    }
}
if (!file_exists($font_file)) $font_file = __DIR__ . '/fonts/FreeSans.ttf';

// --- Создаём изображение с прозрачным фоном ---
$im = imagecreatetruecolor($width, $height);
imagealphablending($im, false);
imagesavealpha($im, true);
$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127); // полностью прозрачный
imagefill($im, 0, 0, $transparent);

// Рисуем белый круг (экран)
$white = imagecolorallocate($im, 255, 255, 255);
imagefilledellipse($im, 120, 120, 240, 240, $white);

// Рисуем текст чёрным поверх круга
$black = imagecolorallocate($im, 0, 0, 0);
$bbox = imagettfbbox($size, 0, $font_file, $text);
if ($bbox === false) {
    $font_file = __DIR__ . '/fonts/FreeSans.ttf';
    $bbox = imagettfbbox($size, 0, $font_file, $text);
}
$w = abs($bbox[4] - $bbox[0]);
$cx = ($width / 2) - ($w / 2) - ($bbox[0] / 2);
$cy = ($height / 2) - ($bbox[5] / 2) - ($bbox[1] / 2);
imagettftext($im, $size, 0, $cx, $cy, $black, $font_file, $text);

// Выводим PNG с прозрачностью
imagepng($im);
imagedestroy($im);
?>
