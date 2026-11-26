<?php
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Jakarta');

$conn = mysqli_connect("localhost", "dna_admin", "DnaBilling2026", "dna_billing");
if (!$conn) die("Koneksi database gagal: " . mysqli_connect_error());

$cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan WHERE id=1"));
if (!$cfg) $cfg = ['wa_endpoint'=>'http://127.0.0', 'wa_api_key'=>'dna_secret_2026'];

if (!function_exists('punyaAkses')) {
    function punyaAkses($m) {
        $r = $_SESSION['role'] ?? '';
        if ($r === 'owner') return true;
        $ak = explode(',', $_SESSION['akses_menu'] ?? '');
        return in_array($m, $ak);
    }
}
if (!function_exists('cekAkses')) {
    function cekAkses($roles = []) {
        if (empty($_SESSION['user_id'])) { header("Location: index.php"); exit; }
    }
}

require_once __DIR__ . '/mikrotik.php';
$api = new RouterosAPI();

// Auto-deteksi URL Tunnel aktif untuk link portal WhatsApp
$url_cache = __DIR__ . '/active_url.txt';
if (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false && strpos($_SERVER['HTTP_HOST'], '10.20.30.') === false) {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $BASE_PORTAL_URL = $proto . '://' . $_SERVER['HTTP_HOST'];
    @file_put_contents($url_cache, $BASE_PORTAL_URL);
} else {
    $BASE_PORTAL_URL = file_exists($url_cache) ? trim(file_get_contents($url_cache)) : 'https://liabilities-compiled-nickel-paintings.trycloudflare.com';
}
