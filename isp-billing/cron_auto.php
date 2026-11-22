<?php
require_once __DIR__ . '/config.php';

$API = connectMikrotik($set);
if (!$API) {
    echo "[" . date('Y-m-d H:i:s') . "] Gagal terhubung ke MikroTik.\n";
    exit;
}

// 1. AUTO-REMOVE VOUCHER HOTSPOT JIKA PEMAKAIAN SUDAH MELEBIHI WAKTU
$hsUsers = $API->comm('/ip/hotspot/user/print');
$deletedVouchers = 0;

foreach ($hsUsers as $u) {
    if (!isset($u['name']) || $u['name'] === 'default-trial') continue;
    $uname = $u['name'];
    $limitUptime = isset($u['limit-uptime']) ? mikrotikTimeToSeconds($u['limit-uptime']) : 0;
    $usedUptime  = isset($u['uptime']) ? mikrotikTimeToSeconds($u['uptime']) : 0;

    if ($limitUptime > 0 && $usedUptime >= $limitUptime) {
        $actives = $API->comm('/ip/hotspot/active/print', ['?user' => $uname]);
        foreach ($actives as $act) {
            $API->comm('/ip/hotspot/active/remove', ['.id' => $act['.id']]);
        }
        $API->comm('/ip/hotspot/user/remove', ['.id' => $u['.id']]);
        $safeUname = mysqli_real_escape_string($conn, $uname);
        mysqli_query($conn, "DELETE FROM voucher_hotspot WHERE kode_voucher = '$safeUname'");
        $deletedVouchers++;
    }
}

// 2. AUTO-ISOLIR PELANGGAN PPPOE JIKA JATUH TEMPO & BELUM LUNAS
$hariIni = (int)date('j');
$qTempo = mysqli_query($conn, "SELECT * FROM pelanggan_pppoe WHERE status_bayar = 'BELUM LUNAS' AND status_koneksi = 'AKTIF' AND tgl_tempo < $hariIni");

while ($p = mysqli_fetch_assoc($qTempo)) {
    $pppoeUser = $p['username_pppoe'];
    $secrets = $API->comm('/ppp/secret/print', ['?name' => $pppoeUser]);
    foreach ($secrets as $s) {
        $API->comm('/ppp/secret/set', ['.id' => $s['.id'], 'profile' => $set['isolir_profile']]);
    }
    $actives = $API->comm('/ppp/active/print', ['?name' => $pppoeUser]);
    foreach ($actives as $a) {
        $API->comm('/ppp/active/remove', ['.id' => $a['.id']]);
    }
    mysqli_query($conn, "UPDATE pelanggan_pppoe SET status_koneksi = 'TERISOLIR' WHERE id = " . $p['id']);
}

$API->disconnect();
echo "[" . date('Y-m-d H:i:s') . "] Selesai. Voucher dihapus: $deletedVouchers\n";
?>
