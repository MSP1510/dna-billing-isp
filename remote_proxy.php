<?php
require 'core/config.php';
if (empty($_SESSION['user_id'])) die("Silakan login ke D.N.A_Billing terlebih dahulu.");

if (isset($_GET['set_target'])) {
    $_SESSION['remote_target'] = trim($_GET['set_target']);
    $_SESSION['remote_port']   = (int)($_GET['port'] ?? 80);
    header("Location: /remote_proxy.php/");
    exit;
}

$target = $_SESSION['remote_target'] ?? '';
$port   = $_SESSION['remote_port'] ?? 80;
if (empty($target)) die("Target IP Modem belum dipilih.");

$uri  = $_SERVER['REQUEST_URI'];
$pos  = strpos($uri, 'remote_proxy.php');
$path = ($pos !== false) ? substr($uri, $pos + 16) : '/';
if ($path === '' || $path === false || strpos($path, '/?page=') === 0) $path = '/';

$proto = ($port == 443 || $port == 8443) ? 'https' : 'http';
$url   = "{$proto}://{$target}:{$port}{$path}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/ont_ck_" . md5($target) . ".txt");
curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/ont_ck_" . md5($target) . ".txt");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
    if (!empty($_SERVER['CONTENT_TYPE'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . $_SERVER['CONTENT_TYPE']]);
    }
}

$resp = curl_exec($ch);
if ($resp === false) {
    $err = curl_error($ch);
    curl_close($ch);
    die("<div style='font-family:sans-serif;padding:20px;background:#fef2f2;border:1px solid #ef4444;border-radius:8px;max-width:480px;margin:30px auto'>
        <h3 style='color:#dc2626;margin-top:0'>Modem ({$target}:{$port}) Tidak Merespons</h3>
        <p style='font-size:13px;color:#334155'>Error: <code>{$err}</code></p>
        <p style='font-size:12px;color:#475569'>Pastikan Remote Web WAN di dalam modem ONT aktif, atau coba port lain:
        <a href='/remote_proxy.php?set_target={$target}&port=80'>Port 80</a> |
        <a href='/remote_proxy.php?set_target={$target}&port=8080'>Port 8080</a> |
        <a href='/remote_proxy.php?set_target={$target}&port=443'>Port 443</a></p>
    </div>");
}

$h_size  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$ctype   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$headers = substr($resp, 0, $h_size);
$body    = substr($resp, $h_size);
curl_close($ch);

foreach (explode("\r\n", $headers) as $h) {
    if (stripos($h, 'Location:') === 0) {
        $loc = trim(substr($h, 9));
        $loc = preg_replace("#^https?://[^/]+#", "", $loc);
        if (strpos($loc, '/') !== 0) $loc = '/' . $loc;
        header("Location: /remote_proxy.php" . $loc);
        exit;
    }
}
if ($ctype) header("Content-Type: " . $ctype);
if (stripos($ctype, 'text/html') !== false) {
    $body = preg_replace('#(href|src|action)=["\']/(?!remote_proxy\.php)#i', '$1="/remote_proxy.php/', $body);
}
echo $body;
