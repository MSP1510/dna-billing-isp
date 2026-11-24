<?php
$z = (int)($_GET['z'] ?? 15);
$x = (int)($_GET['x'] ?? 0);
$y = (int)($_GET['y'] ?? 0);
$m = ($_GET['m'] ?? 'y') === 'm' ? 'm' : 'y';
$url = "https://google.com{$m}&x={$x}&y={$y}&z={$z}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
$img = curl_exec($ch);
curl_close($ch);
header("Content-Type: image/jpeg");
header("Cache-Control: public, max-age=86400");
echo $img;
