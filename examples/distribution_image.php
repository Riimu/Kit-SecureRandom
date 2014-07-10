<?php

require __DIR__ . '/../vendor/autoload.php';

$rng = new \Riimu\Kit\SecureRandom\SecureRandom();

/**
 * Credits for the distribution image goes to:
 * http://boallen.com/random-numbers.html
 */

header("Content-type: image/png");

$im = imagecreatetruecolor(512, 512);
$white = imagecolorallocate($im, 255, 255, 255);
$width = 512;
$height = 512;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        if ($rng->getInteger(0, 1)) {
            imagesetpixel($im, $x, $y, $white);
        }
    }
}

imagepng($im);
imagedestroy($im);
