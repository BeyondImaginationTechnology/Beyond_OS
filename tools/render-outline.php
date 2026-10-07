<?php
$in = 'beyond-tattoo/assets/stencils/public-domain-flash-archive/01-smithsonian-flash-sheet-a/source-original.jpg';
$out = 'beyond-tattoo/assets/stencils/public-domain-flash-archive/01-smithsonian-flash-sheet-a/stencil-outline.png';
$source = imagecreatefromjpeg($in);
$canvas = imagecreatetruecolor(imagesx($source)-300, imagesy($source)-240);
imagefill($canvas, 0, 0, imagecolorallocate($canvas,255,255,255));
imagecopy($canvas, $source, 0, 0, 150, 120, imagesx($canvas), imagesy($canvas));
imagefilter($canvas, IMG_FILTER_GRAYSCALE);
imagefilter($canvas, IMG_FILTER_CONTRAST, -100);
imagepng($canvas, $out, 9);
imagedestroy($source); imagedestroy($canvas);
