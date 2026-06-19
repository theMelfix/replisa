<?php

/**
 * Genera l'icona app Replisa (1024x1024 PNG) richiesta da Meta.
 * Sfondo verde brand con gradiente verticale + "R" bianca centrata.
 *
 * Uso:  php brand/generate-icon.php
 * Output: brand/replisa-icon-1024.png
 */
$S = 1024;
$im = imagecreatetruecolor($S, $S);
imagesavealpha($im, true);

// Gradiente verticale: brand green #16a34a -> #0f7a37
$top = [0x16, 0xA3, 0x4A];
$bot = [0x0F, 0x7A, 0x37];
for ($y = 0; $y < $S; $y++) {
    $t = $y / ($S - 1);
    $r = (int) round($top[0] + ($bot[0] - $top[0]) * $t);
    $g = (int) round($top[1] + ($bot[1] - $top[1]) * $t);
    $b = (int) round($top[2] + ($bot[2] - $top[2]) * $t);
    $c = imagecolorallocate($im, $r, $g, $b);
    imagefilledrectangle($im, 0, $y, $S, $y, $c);
}

$white = imagecolorallocate($im, 255, 255, 255);
$font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

// "R" grande centrata
$size = 620;
$bbox = imagettfbbox($size, 0, $font, 'R');
$tw = $bbox[2] - $bbox[0];
$th = $bbox[1] - $bbox[7];
$x = (int) (($S - $tw) / 2 - $bbox[0]);
$y = (int) (($S - $th) / 2 - $bbox[7]) - 20; // leggermente alzata
imagettftext($im, $size, 0, $x, $y, $white, $font, 'R');

$out = __DIR__.'/replisa-icon-1024.png';
imagepng($im, $out);

$info = getimagesize($out);
echo "Creato: {$info[0]}x{$info[1]}  ".round(filesize($out) / 1024).' KB'.PHP_EOL;
