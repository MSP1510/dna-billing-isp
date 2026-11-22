<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "isp_billing_v2";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

require_once __DIR__ . '/routeros_api.class.php';

$set = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings WHERE id = 1"));

function connectMikrotik($set) {
    $API = new RouterosAPI();
    $API->port = (int)$set['mikrotik_port'];
    if ($API->connect($set['mikrotik_ip'], $set['mikrotik_user'], $set['mikrotik_pass'])) {
        return $API;
    }
    return false;
}

function mikrotikTimeToSeconds($str) {
    if (empty($str)) return 0;
    $seconds = 0;
    if (preg_match('/(\d+)w/', $str, $m)) $seconds += $m[1] * 604800;
    if (preg_match('/(\d+)d/', $str, $m)) $seconds += $m[1] * 86400;
    if (preg_match('/(\d+)h/', $str, $m)) $seconds += $m[1] * 3600;
    if (preg_match('/(\d+)m/', $str, $m)) $seconds += $m[1] * 60;
    if (preg_match('/(\d+)s/', $str, $m)) $seconds += $m[1];
    return $seconds;
}

function kirimFonnte($target, $pesan, $token) {
    if (empty($token) || empty($target)) return false;
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://fonnte.com',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['target' => $target, 'message' => $pesan],
        CURLOPT_HTTPHEADER => ["Authorization: $token"],
    ]);
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}
?>
