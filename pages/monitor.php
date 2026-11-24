<?php cekAkses(['owner','admin','teknisi']);
function durasiDetik($str) {
    $d = 0;
    if (preg_match('/(\d+)w/', $str, $m)) $d += (int)$m * 604800;
    if (preg_match('/(\d+)d/', $str, $m)) $d += (int)$m * 86400;
    if (preg_match('/(\d+)h/', $str, $m)) $d += (int)$m * 3600;
    if (preg_match('/(\d+)m/', $str, $m)) $d += (int)$m * 60;
    if (preg_match('/(\d+)s/', $str, $m)) $d += (int)$m;
    return $d;
}

// 1. Pembersihan otomatis voucher Hotspot yang sudah habis durasinya di RADIUS & database
$hapus_hs = 0; $exp_codes = [];
$q_exp = mysqli_query($conn, "SELECT v.id, v.kode, COALESCE(p.durasi_detik, 0) as batas, COALESCE((SELECT SUM(acctsessiontime) FROM radacct r WHERE r.username=v.kode), 0) as terpakai FROM voucher v LEFT JOIN paket p ON v.profil=p.nama_paket WHERE v.status='terjual'");
while ($vx = mysqli_fetch_assoc($q_exp)) {
    if ($vx['batas'] > 0 && $vx['terpakai'] >= $vx['batas']) {
        $kd = mysqli_real_escape_string($conn, $vx['kode']);
        mysqli_query($conn, "DELETE FROM radcheck WHERE username='$kd'");
        mysqli_query($conn, "DELETE FROM radreply WHERE username='$kd'");
        mysqli_query($conn, "DELETE FROM radusergroup WHERE username='$kd'");
        mysqli_query($conn, "DELETE FROM voucher WHERE id=".(int)$vx['id']);
        $exp_codes[$vx['kode']] = true;
        $hapus_hs++;
    }
}

$online_ppp = []; $online_arp = []; $hs_online = [];
$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        // PPPoE Active
        $cur = [];
        foreach ($api->comm('/ppp/active/print') as $l) {
            if ($l == '!re') { if(!empty($cur['name'])) $online_ppp[trim($cur['name'])] = $cur; $cur = []; }
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
        }
        if (!empty($cur['name'])) $online_ppp[trim($cur['name'])] = $cur;

        // Static IP ARP Active
        $cur = [];
        foreach ($api->comm('/ip/arp/print') as $l) {
            if ($l == '!re') { if(!empty($cur['ip']) && ($cur['comp']??'false')=='true') $online_arp[trim($cur['ip'])] = $cur; $cur = []; }
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=complete=')===0) $cur['comp'] = substr($l,10);
        }
        if (!empty($cur['ip']) && ($cur['comp']??'false')=='true') $online_arp[trim($cur['ip'])] = $cur;

        // Hapus user Hotspot lokal MikroTik yang sudah habis limit-uptime
        $cur = [];
        foreach ($api->comm('/ip/hotspot/user/print') as $l) {
            if ($l == '!re') {
                $lim = durasiDetik($cur['lim'] ?? ''); $up = durasiDetik($cur['up'] ?? '');
                if (!empty($cur['name']) && (isset($exp_codes[$cur['name']]) || ($lim > 0 && $up >= $lim))) {
                    if (!empty($cur['id'])) $api->comm('/ip/hotspot/user/remove', ['=.id='.$cur['id']]);
                    $exp_codes[$cur['name']] = true; $hapus_hs++;
                }
                $cur = [];
            }
            elseif (strpos($l,'=.id=')===0) $cur['id'] = substr($l,5);
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
            elseif (strpos($l,'=limit-uptime=')===0) $cur['lim'] = substr($l,14);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
        }

        // Ambil Hotspot Active & putuskan jika sudah habis durasi
        $cur = [];
        foreach ($api->comm('/ip/hotspot/active/print') as $l) {
            if ($l == '!re') {
                if (!empty($cur['user'])) {
                    if (isset($exp_codes[$cur['user']]) && !empty($cur['id'])) { $api->comm('/ip/hotspot/active/remove', ['=.id='.$cur['id']]); }
                    else { $cur['router'] = $rt['nama_router']; $hs_online[] = $cur; }
                }
                $cur = [];
            }
            elseif (strpos($l,'=.id=')===0) $cur['id'] = substr($l,5);
            elseif (strpos($l,'=user=')===0) $cur['user'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
            elseif (strpos($l,'=session-time-left=')===0) $cur['left'] = substr($l,19);
        }
        if (!empty($cur['user'])) {
            if (isset($exp_codes[$cur['user']]) && !empty($cur['id'])) { $api->comm('/ip/hotspot/active/remove', ['=.id='.$cur['id']]); }
            else { $cur['router'] = $rt['nama_router']; $hs_online[] = $cur; }
        }
        $api->disconnect();
    }
}
$list_ppp = []; $list_st = [];
$qp = mysqli_query($conn, "SELECT p.*, o.nama_odp FROM pelanggan p LEFT JOIN odp o ON p.odp_id=o.id WHERE p.nama NOT LIKE '<hotspot-%' ORDER BY p.nama ASC");
while ($p = mysqli_fetch_assoc($qp)) {
    if (($p['tipe_layanan'] ?? 'pppoe') == 'static') {
        $ip = trim($p['ip_static'] ?? '');
        $p['is_on'] = ($ip !== '' && isset($online_arp[$ip]));
        $p['detail_net'] = $p['is_on'] ? ($online_arp[$ip]['mac'] ?? 'ARP Active') : 'Tidak Terhubung';
        $list_st[] = $p;
    } else {
        $usr = trim($p['pppoe_user'] ?? '');
        $p['is_on'] = ($usr !== '' && isset($online_ppp[$usr]));
        $p['detail_net'] = $p['is_on'] ? (($online_ppp[$usr]['ip'] ?? '-').' | '.($online_ppp[$usr]['up'] ?? '-')) : 'Offline';
        $list_ppp[] = $p;
    }
}
?>
<?php if($hapus_hs > 0): ?>
<div class="card" style="background:#fef2f2;border-left:5px solid #ef4444;color:#991b1b;font-size:12px">
  <b>Auto-Clean Hotspot:</b> <?= $hapus_hs ?> voucher Hotspot yang telah habis durasinya berhasil dihapus otomatis dari MikroTik & database!
</div>
<?php endif; ?>

<div class="card" style="border-top:4px solid #0284c7">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
    <h4>1. Pelanggan PPPoE (<?= count($list_ppp) ?> Total)</h4>
    <a href="?page=monitor" class="btn" style="padding:4px 8px;font-size:11px">Refresh & Bersihkan Expired</a>
  </div>
  <div style="overflow-x:auto"><table>
    <tr><th>Status</th><th>Nama Pelanggan</th><th>Akun PPPoE</th><th>IP & Uptime</th><th>Jalur ODP</th></tr>
    <?php foreach($list_ppp as $p): $bg = ($p['status']=='isolir')?'#ef4444':($p['is_on']?'#22c55e':'#f59e0b'); $st = ($p['status']=='isolir')?'ISOLIR':($p['is_on']?'ONLINE':'OFFLINE'); ?>
    <tr><td><span style="background:<?= $bg ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= $st ?></span></td><td><b><?= htmlspecialchars($p['nama']) ?></b></td><td><?= htmlspecialchars($p['pppoe_user']??'-') ?></td><td><small><?= htmlspecialchars($p['detail_net']) ?></small></td><td><?= htmlspecialchars($p['nama_odp']??'-') ?></td></tr>
    <?php endforeach; ?>
    <?php if(empty($list_ppp)) echo "<tr><td colspan='5' style='text-align:center;color:#64748b'>Belum ada pelanggan PPPoE</td></tr>"; ?>
  </table></div>
</div>

<div class="card" style="border-top:4px solid #8b5cf6">
  <h4>2. Pelanggan Static IP (<?= count($list_st) ?> Total)</h4>
  <div style="overflow-x:auto;margin-top:8px"><table>
    <tr><th>Status</th><th>Nama Pelanggan</th><th>IP Static</th><th>MAC / Info ARP</th><th>Jalur ODP</th></tr>
    <?php foreach($list_st as $p): $bg = ($p['status']=='isolir')?'#ef4444':($p['is_on']?'#22c55e':'#f59e0b'); $st = ($p['status']=='isolir')?'ISOLIR':($p['is_on']?'ONLINE':'OFFLINE'); ?>
    <tr><td><span style="background:<?= $bg ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= $st ?></span></td><td><b><?= htmlspecialchars($p['nama']) ?></b></td><td><?= htmlspecialchars($p['ip_static']??'-') ?></td><td><small><?= htmlspecialchars($p['detail_net']) ?></small></td><td><?= htmlspecialchars($p['nama_odp']??'-') ?></td></tr>
    <?php endforeach; ?>
    <?php if(empty($list_st)) echo "<tr><td colspan='5' style='text-align:center;color:#64748b'>Belum ada pelanggan Static IP</td></tr>"; ?>
  </table></div>
</div>

<div class="card" style="border-top:4px solid #f59e0b">
  <h4>3. Voucher Hotspot Online (<?= count($hs_online) ?> Aktif)</h4>
  <small style="color:#64748b">User Hotspot yang sudah habis durasinya otomatis dihapus dari MikroTik dan database saat halaman dimuat.</small>
  <div style="overflow-x:auto;margin-top:8px"><table>
    <tr><th>Router</th><th>Kode / User Hotspot</th><th>IP Address</th><th>Uptime</th><th>Sisa Durasi</th></tr>
    <?php foreach($hs_online as $h): ?>
    <tr><td><?= htmlspecialchars($h['router']) ?></td><td><b><?= htmlspecialchars($h['user']) ?></b></td><td><?= htmlspecialchars($h['ip']??'-') ?></td><td><?= htmlspecialchars($h['up']??'-') ?></td><td><span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= htmlspecialchars($h['left']??'Unlimited') ?></span></td></tr>
    <?php endforeach; ?>
    <?php if(empty($hs_online)) echo "<tr><td colspan='5' style='text-align:center;color:#64748b'>Tidak ada voucher Hotspot aktif</td></tr>"; ?>
  </table></div>
</div>
