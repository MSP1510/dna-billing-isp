<?php
$f_rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
$w_rt = $f_rid > 0 ? "WHERE id=$f_rid" : "ORDER BY id ASC";
 cekAkses(['owner','admin','teknisi']);
function durasiKeDetik($str) {
    $d = 0;
    if (preg_match('/(\d+)w/', $str, $m)) $d += (int)$m[1] * 604800;
    if (preg_match('/(\d+)d/', $str, $m)) $d += (int)$m[1] * 86400;
    if (preg_match('/(\d+)h/', $str, $m)) $d += (int)$m[1] * 3600;
    if (preg_match('/(\d+)m/', $str, $m)) $d += (int)$m[1] * 60;
    if (preg_match('/(\d+)s/', $str, $m)) $d += (int)$m[1];
    return $d;
}

// Kick manual user (PPPoE atau Hotspot)
if (isset($_GET['kick_ppp']) && isset($_GET['rid'])) {
    $rid = (int)$_GET['rid']; $usr = $_GET['kick_ppp'];
    $rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers ".$w_rt." WHERE id=$rid"));
    if ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        $act = $api->comm('/ppp/active/print'); $cur = [];
        foreach ($act as $l) {
            if ($l == '!re') { if (($cur['name']??'') == $usr && !empty($cur['id'])) $api->comm('/ppp/active/remove', ['.id' => $cur['id']]); $cur = []; }
            elseif (strpos($l,'=.id=')===0) $cur['id'] = substr($l,5);
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
        }
        if (($cur['name']??'') == $usr && !empty($cur['id'])) $api->comm('/ppp/active/remove', ['.id' => $cur['id']]);
        $api->disconnect();
    }
    echo "<script>location.href='?page=session&rid='.$f_rid;</script>"; exit;
}
if (isset($_GET['kick_hs']) && isset($_GET['rid'])) {
    $rid = (int)$_GET['rid']; $usr = $_GET['kick_hs'];
    $rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=$rid"));
    if ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        $act = $api->comm('/ip/hotspot/active/print'); $cur = [];
        foreach ($act as $l) {
            if ($l == '!re') { if (($cur['user']??'') == $usr && !empty($cur['id'])) $api->comm('/ip/hotspot/active/remove', ['.id' => $cur['id']]); $cur = []; }
            elseif (strpos($l,'=.id=')===0) $cur['id'] = substr($l,5);
            elseif (strpos($l,'=user=')===0) $cur['user'] = substr($l,6);
        }
        if (($cur['user']??'') == $usr && !empty($cur['id'])) $api->comm('/ip/hotspot/active/remove', ['.id' => $cur['id']]);
        $api->disconnect();
    }
    echo "<script>location.href='?page=session&rid='.$f_rid;</script>"; exit;
}
// Pembersihan otomatis user Hotspot yang sudah melewati batas durasi (RADIUS radacct & MikroTik)
$hapus_cnt = 0;
$q_exp = mysqli_query($conn, "SELECT v.id, v.kode, COALESCE(p.durasi_detik, 0) as batas, COALESCE((SELECT SUM(acctsessiontime) FROM radacct r WHERE r.username=v.kode), 0) as terpakai FROM voucher v LEFT JOIN paket p ON v.profil=p.nama_paket WHERE v.status='terjual'");
$exp_codes = [];
while ($vx = mysqli_fetch_assoc($q_exp)) {
    if ($vx['batas'] > 0 && $vx['terpakai'] >= $vx['batas']) {
        $kd = mysqli_real_escape_string($conn, $vx['kode']);
        mysqli_query($conn, "DELETE FROM radcheck WHERE username='$kd'");
        mysqli_query($conn, "DELETE FROM radreply WHERE username='$kd'");
        mysqli_query($conn, "DELETE FROM radusergroup WHERE username='$kd'");
        mysqli_query($conn, "DELETE FROM voucher WHERE id=".(int)$vx['id']);
        $exp_codes[$vx['kode']] = true;
        $hapus_cnt++;
    }
}

$ses_ppp = []; $ses_hs = [];
$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    $api->timeout = 1; if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        // Ambil PPPoE Active
        $act = $api->comm('/ppp/active/print'); $cur = [];
        foreach ($act as $l) {
            if ($l == '!re') { if(!empty($cur['name'])){ $cur['router']=$rt['nama_nas']; $cur['rid']=$rt['id']; $ses_ppp[]=$cur; } $cur=[]; }
            elseif (strpos($l,'=name=')===0) $cur['name']=substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip']=substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up']=substr($l,8);
            elseif (strpos($l,'=caller-id=')===0) $cur['mac']=substr($l,11);
        }
        if(!empty($cur['name'])){ $cur['router']=$rt['nama_nas']; $cur['rid']=$rt['id']; $ses_ppp[]=$cur; }

        // Hapus user hotspot lokal di MikroTik yang sudah mencapai limit-uptime / expired
        $hsu = $api->comm('/ip/hotspot/user/print'); $cur = [];
        foreach ($hsu as $l) {
            if ($l == '!re') {
                $lim = durasiKeDetik($cur['lim'] ?? ''); $up = durasiKeDetik($cur['up'] ?? '');
                if (!empty($cur['name']) && (isset($exp_codes[$cur['name']]) || ($lim > 0 && $up >= $lim))) {
                    if (!empty($cur['id'])) $api->comm('/ip/hotspot/user/remove', ['.id' => $cur['id']]);
                    $exp_codes[$cur['name']] = true; $hapus_cnt++;
                }
                $cur = [];
            }
            elseif (strpos($l,'=.id=')===0) $cur['id']=substr($l,5);
            elseif (strpos($l,'=name=')===0) $cur['name']=substr($l,6);
            elseif (strpos($l,'=limit-uptime=')===0) $cur['lim']=substr($l,14);
            elseif (strpos($l,'=uptime=')===0) $cur['up']=substr($l,8);
        }

        // Ambil Hotspot Active & putuskan jika termasuk yang expired
        $hsa = $api->comm('/ip/hotspot/active/print'); $cur = [];
        foreach ($hsa as $l) {
            if ($l == '!re') {
                if (!empty($cur['user'])) {
                    if (isset($exp_codes[$cur['user']]) && !empty($cur['id'])) { $api->comm('/ip/hotspot/active/remove', ['.id' => $cur['id']]); }
                    else { $cur['router']=$rt['nama_nas']; $cur['rid']=$rt['id']; $ses_hs[]=$cur; }
                }
                $cur = [];
            }
            elseif (strpos($l,'=.id=')===0) $cur['id']=substr($l,5);
            elseif (strpos($l,'=user=')===0) $cur['user']=substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip']=substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up']=substr($l,8);
            elseif (strpos($l,'=session-time-left=')===0) $cur['left']=substr($l,19);
            elseif (strpos($l,'=mac-address=')===0) $cur['mac']=substr($l,13);
        }
        if (!empty($cur['user'])) {
            if (isset($exp_codes[$cur['user']]) && !empty($cur['id'])) { $api->comm('/ip/hotspot/active/remove', ['.id' => $cur['id']]); }
            else { $cur['router']=$rt['nama_nas']; $cur['rid']=$rt['id']; $ses_hs[]=$cur; }
        }
        $api->disconnect();
    }
}
?>
<?php if(isset($_GET['refresh']) && $hapus_cnt == 0): ?><div class="card" style="background:#ecfdf5;border-left:5px solid #10b981;color:#065f46;font-size:12px;margin-bottom:12px;"><b>Sinkronisasi Selesai:</b> Sesi PPPoE & Hotspot seluruh router berhasil diperbarui (0 voucher expired).</div><?php endif; ?><?php if($hapus_cnt > 0): ?>
<div class="card" style="background:#fef2f2;border-left:5px solid #ef4444;color:#991b1b;font-size:12px">
  <b>Pembersihan Otomatis:</b> <?= $hapus_cnt ?> voucher/user Hotspot yang telah melewati batas durasi berhasil dihapus otomatis dari MikroTik & database!
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;"><b style="font-size:13px;color:#0f172a;">Filter Router MikroTik</b><select onchange="location.href='?page=session&rid='+this.value" style="padding:8px 12px;border-radius:8px;border:1px solid #cbd5e1;font-weight:700;font-size:13px;background:#fff;color:#0f172a;"><option value="0">Semua Router (Total Gabungan)</option><?php $qr_f=mysqli_query($conn,"SELECT id,nama_nas FROM routers ORDER BY id ASC");while($rf=mysqli_fetch_assoc($qr_f)){ $sl=($f_rid==(int)$rf["id"])?"selected":""; echo "<option value='{$rf['id']}' $sl>MikroTik: ".htmlspecialchars($rf["nama_nas"])."</option>"; } ?></select></div>
<div class="card" style="border-top:4px solid #0284c7">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
    <h4>1. Session PPPoE Online (<?= count($ses_ppp) ?> Aktif)</h4>
    <a href="?page=session&rid=<?= $f_rid ?>&refresh=1" class="btn" style="padding:4px 8px;font-size:11px">Refresh & Bersihkan Expired</a>
  </div>
  <div style="overflow-x:auto"><table>
    <tr><th>Router</th><th>User PPPoE</th><th>IP Address</th><th>Uptime</th><th>MAC</th><th>Aksi</th></tr>
    <?php foreach($ses_ppp as $s): ?>
    <tr><td><?= htmlspecialchars($s['router']) ?></td><td><b><?= htmlspecialchars($s['name']) ?></b></td><td><?= htmlspecialchars($s['ip']??'-') ?></td><td><span style="color:#00a65a;font-weight:700"><?= htmlspecialchars($s['up']??'-') ?></span></td><td><small><?= htmlspecialchars($s['mac']??'-') ?></small></td><td><a href="?page=session&kick_ppp=<?= urlencode($s['name']) ?>&rid=<?= $s['rid'] ?>" onclick="return confirm('Putuskan koneksi PPPoE ini?')" class="btn btn-r" style="padding:3px 7px;font-size:10px">Kick</a></td></tr>
    <?php endforeach; ?>
    <?php if(empty($ses_ppp)) echo "<tr><td colspan='6' style='text-align:center;color:#64748b'>Tidak ada session PPPoE aktif</td></tr>"; ?>
  </table></div>
</div>

<div class="card" style="border-top:4px solid #f59e0b">
  <h4>2. Session Voucher Hotspot Online (<?= count($ses_hs) ?> Aktif)</h4>
  <small style="color:#64748b">User Hotspot yang telah mencapai batas durasi otomatis dihapus dari MikroTik dan RADIUS saat halaman dimuat.</small>
  <div style="overflow-x:auto;margin-top:8px"><table>
    <tr><th>Router</th><th>Kode / User Hotspot</th><th>IP Address</th><th>Uptime</th><th>Sisa Waktu</th><th>Aksi</th></tr>
    <?php foreach($ses_hs as $h): ?>
    <tr><td><?= htmlspecialchars($h['router']) ?></td><td><b><?= htmlspecialchars($h['user']) ?></b></td><td><?= htmlspecialchars($h['ip']??'-') ?></td><td><?= htmlspecialchars($h['up']??'-') ?></td><td><span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= htmlspecialchars($h['left']??'Unlimited') ?></span></td><td><a href="?page=session&kick_hs=<?= urlencode($h['user']) ?>&rid=<?= $h['rid'] ?>" onclick="return confirm('Putuskan user Hotspot ini?')" class="btn btn-r" style="padding:3px 7px;font-size:10px">Kick</a></td></tr>
    <?php endforeach; ?>
    <?php if(empty($ses_hs)) echo "<tr><td colspan='6' style='text-align:center;color:#64748b'>Tidak ada user Hotspot aktif</td></tr>"; ?>
  </table></div>
</div>
