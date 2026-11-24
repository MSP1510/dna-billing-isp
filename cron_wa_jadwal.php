<?php
require "/var/www/html/core/config.php";
$now = date('Y-m-d H:i:s');
$q = mysqli_query($conn, "SELECT * FROM wa_jadwal WHERE status='menunggu' AND jadwal_kirim <= '$now'");
while ($j = mysqli_fetch_assoc($q)) {
    $where = "p.no_wa IS NOT NULL AND p.no_wa != '' AND p.nama NOT LIKE '<hotspot-%'";
    if ($j['target_tipe'] == 'odp' && !empty($j['odp_id'])) {
        $where .= " AND p.odp_id=".(int)$j['odp_id'];
    } elseif ($j['target_tipe'] == 'tunggakan') {
        $where .= " AND p.id IN (SELECT pelanggan_id FROM tagihan WHERE status='belum_bayar')";
    } else {
        $where .= " AND p.status != 'disable'";
    }
    $qp = mysqli_query($conn, "SELECT p.nama, p.no_wa, o.nama_odp FROM pelanggan p LEFT JOIN odp o ON p.odp_id=o.id WHERE $where");
    $cnt = 0;
    while ($p = mysqli_fetch_assoc($qp)) {
        $msg = str_replace(['{nama}', '{odp}'], [$p['nama'], $p['nama_odp'] ?? '-'], $j['pesan']);
        kirimWA($conn, $p['no_wa'], $msg);
        $cnt++;
    }
    mysqli_query($conn, "UPDATE wa_jadwal SET status='terkirim', total_terkirim=$cnt WHERE id=".(int)$j['id']);
}
