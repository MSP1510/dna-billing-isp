<?php
require 'core/config.php';
cekAkses(['owner','admin','teknisi']);

if (isset($_GET['set_target'])) {
    $ip = preg_replace('/[^0-9.:]/', '', $_GET['set_target']);
    $_SESSION['remote_target'] = $ip;
    header("Location: remote_proxy.php/"); exit;
}

$target = $_SESSION['remote_target'] ?? '';
if (empty($target)) die("Target IP Modem belum dipilih.");

$uri = $_SERVER['REQUEST_URI'];
$path = preg_replace('#^/remote_proxy\.php#', '', $uri);
if (empty($path)) $path = '/';
$url = "http://" . $target . $path;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}
$hdrs = [];
foreach (getallheaders() as $k => $v) {
    if (!in_array(strtolower($k), ['host', 'accept-encoding', 'content-length'])) {
        $hdrs[] = "$k: $v";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
$resp = curl_exec($ch);
if ($resp === false) die("Gagal terhubung ke modem pelanggan ($target): " . curl_error($ch) . "<br><small>Pastikan Remote Management / WAN HTTP di modem ONT pelanggan sudah diaktifkan.</small>");

$h_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$ctype  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$raw_h  = substr($resp, 0, $h_size);
$body   = substr($resp, $h_size);
curl_close($ch);

foreach (explode("\r\n", $raw_h) as $line) {
    if (stripos($line, 'Location:') === 0) {
        $loc = trim(substr($line, 9));
        $loc = preg_replace("#^https?://[^/]+#", "", $loc);
        header("Location: /remote_proxy.php" . (strpos($loc,'/')===0 ? $loc : '/'.$loc));
        exit;
    }
    if (stripos($line, 'Set-Cookie:') === 0) header($line, false);
}
if ($ctype) header("Content-Type: $ctype");
if (stripos($ctype, 'text/html') !== false) {
    $body = preg_replace('#(src|href|action)=["\']/(?!remote_proxy\.php)#i', '$1="/remote_proxy.php/', $body);
}
echo $body;
