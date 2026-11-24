<?php
require '/var/www/html/core/config.php';
require '/var/www/html/core/mikrotik.php';
$api = new RouterosAPI();

$qv = mysqli_query($conn, "SELECT v.*, p.durasi_detik FROM voucher v LEFT JOIN paket p ON v.profil = p.nama_paket");
while ($v = mysqli_fetch_assoc($qv)) {
    $kode = $v['kode'];
    $limit = (int)($v['durasi_detik'] ?? 14400);
    $acc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(radacctid) as logins, SUM(acctsessiontime) as used FROM radacct WHERE username='$kode'"));
    $logins = (int)($acc['logins'] ?? 0);
    $used   = (int)($acc['used'] ?? 0);

    // 1. Jika voucher baru dipakai login (status masih stok), otomatis catat ke Keuangan & ubah ke Terjual
    if ($v['status'] == 'stok' && $logins > 0) {
        mysqli_query($conn, "UPDATE voucher SET status='terjual' WHERE id=".(int)$v['id']);
        mysqli_query($conn, "INSERT INTO keuangan (tipe,kategori,keterangan,jumlah) VALUES ('masuk','Voucher Hotspot','Terpakai Otomatis: $kode ($v[profil])',$v[harga])");
    }

    // 2. Jika durasi sudah habis, hapus otomatis dari STB, RADIUS & MikroTik
    if ($used >= $limit && $limit > 0) {
        mysqli_query($conn, "DELETE FROM radcheck WHERE username='$kode'");
        mysqli_query($conn, "DELETE FROM radreply WHERE username='$kode'");
        mysqli_query($conn, "DELETE FROM voucher WHERE id=".(int)$v['id']);
        $rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=".(int)$v['router_id']));
        if ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
            $u = $api->comm('/ip/hotspot/user/print', ['?name'=>$kode]);
            foreach($u as $l) { if(strpos($l,'=.id=')===0) $api->comm('/ip/hotspot/user/remove', ['.id'=>substr($l,5)]); }
            $api->disconnect();
        }
    }
}
?>
