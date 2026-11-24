<?php
// scripts/generate_icons.php
// Génération des icônes PWA (192x192 et 512x512) aux couleurs du thème (Bleu Nuit #0F172A & Or #D97706)

$sizes = [192, 512];
$iconsDir = __DIR__ . '/../assets/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0777, true);
}

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);

    // Fond Bleu Nuit #0F172A
    $bg = imagecolorallocate($img, 15, 23, 42);
    imagefill($img, 0, 0, $bg);

    // Or Sacré #D97706
    $gold = imagecolorallocate($img, 217, 119, 6);
    $goldLight = imagecolorallocate($img, 245, 158, 11);

    // Dessin d'un cercle avec bordure or
    $center = $size / 2;
    $radius = (int)($size * 0.42);
    imagefilledellipse($img, (int)$center, (int)$center, $radius * 2, $radius * 2, imagecolorallocate($img, 21, 31, 50));
    imagesetthickness($img, (int)max(2, $size * 0.02));
    imageellipse($img, (int)$center, (int)$center, $radius * 2, $radius * 2, $gold);

    // Dessin d'une croix latine dorée épurée
    // Barre verticale
    $vWidth = (int)max(4, $size * 0.08);
    $vHeight = (int)($size * 0.52);
    $vX = (int)($center - ($vWidth / 2));
    $vY = (int)($center - ($vHeight * 0.48));
    imagefilledrectangle($img, $vX, $vY, $vX + $vWidth, $vY + $vHeight, $goldLight);

    // Barre horizontale
    $hWidth = (int)($size * 0.36);
    $hHeight = (int)max(4, $size * 0.08);
    $hX = (int)($center - ($hWidth / 2));
    $hY = (int)($vY + ($vHeight * 0.25));
    imagefilledrectangle($img, $hX, $hY, $hX + $hWidth, $hY + $hHeight, $goldLight);

    $outPath = $iconsDir . "/icon-{$size}.png";
    imagepng($img, $outPath);
    imagedestroy($img);
    echo "Icône générée : $outPath\n";
}
