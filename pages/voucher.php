<?php cekAkses(['owner','admin','kasir']);
function acakKodeVoucher($panjang = 8, $tipe = 'angka') {
    if ($tipe == 'huruf') $kar = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    elseif ($tipe == 'campur') $kar = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    else $kar = '0123456789';
    $res = ''; $max = strlen($kar) - 1;
    for ($i = 0; $i < $panjang; $i++) $res .= $kar[random_int(0, $max)];
    return $res;
}
function waktuKeDetik($str) {
    $d = 0;
    if (preg_match('/(\d+)w/', $str, $m)) $d += (int)$m * 604800;
    if (preg_match('/(\d+)d/', $str, $m)) $d += (int)$m * 86400;
    if (preg_match('/(\d+)h/', $str, $m)) $d += (int)$m * 3600;
    if (preg_match('/(\d+)m/', $str, $m)) $d += (int)$m * 60;
    if (preg_match('/(\d+)s/', $str, $m)) $d += (int)$m;
    return $d;
}
function detikKeTeks($s) {
    if ($s <= 0) return '0m';
    $d = floor($s / 86400); $s %= 86400;
    $h = floor($s / 3600);  $s %= 3600;
    $m = floor($s / 60);
    return ($d>0 ? $d.'h ' : '') . ($h>0 ? $h.'j ' : '') . $m.'m';
}

$map_pkt = [];
$qp_all = mysqli_query($conn, "SELECT * FROM paket");
while ($rp = mysqli_fetch_assoc($qp_all)) $map_pkt[$rp['nama_paket']] = $rp;

// Sinkronisasi real-time dengan MikroTik (/ip/hotspot/user & /ip/hotspot/active)
$sync_in = 0; $sync_exp = 0;
$info_mt = []; // Menyimpan status pemakaian & sisa waktu per kode voucher

$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        // 1. Ambil daftar user yang sedang ONLINE di /ip/hotspot/active
        $akt_hs = []; $cur = [];
        foreach ($api->comm('/ip/hotspot/active/print') as $l) {
            if ($l == '!re') { if (!empty($cur['user'])) $akt_hs[$cur['user']] = $cur; $cur = []; }
            elseif (strpos($l,'=.id=')===0) $cur['id'] = substr($l,5);
            elseif (strpos($l,'=user=')===0) $cur['user'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
            elseif (strpos($l,'=session-time-left=')===0) $cur['left'] = substr($l,19);
        }
        if (!empty($cur['user'])) $akt_hs[$cur['user']] = $cur;

        // 2. Ambil seluruh user dari /ip/hotspot/user/print
        $cur = []; $mt_users = [];
        foreach ($api->comm('/ip/hotspot/user/print') as $l) {
            if ($l == '!re') { if (!empty($cur['name'])) $mt_users[] = $cur; $cur = []; }
            elseif (strpos($l,'=.id=')===0) $cur['id'] = substr($l,5);
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
            elseif (strpos($l,'=password=')===0) $cur['pass'] = substr($l,10);
            elseif (strpos($l,'=profile=')===0) $cur['prof'] = substr($l,9);
            elseif (strpos($l,'=limit-uptime=')===0) $cur['lim'] = substr($l,14);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
        }
        if (!empty($cur['name'])) $mt_users[] = $cur;

        foreach ($mt_users as $u) {
            $nm = trim($u['name']);
            if ($nm == '' || $nm == 'default-trial') continue;
            $pr = trim($u['prof'] ?? 'default');
            $lim_str = $u['lim'] ?? ($map_pkt[$pr]['durasi_uptime'] ?? '');
            $up_str  = $u['up'] ?? '0s';
            $lim_s   = waktuKeDetik($lim_str);
            $up_s    = waktuKeDetik($up_str);

            $sk = mysqli_real_escape_string($conn, $nm);
            $sp = mysqli_real_escape_string($conn, $pr);

            // Jika durasi waktu sudah habis -> logout & hapus dari MikroTik + catat ke Log Pemberitahuan
            if ($lim_s > 0 && $up_s >= $lim_s) {
                if (!empty($u['id'])) $api->comm('/ip/hotspot/user/remove', ['=.id='.$u['id']]);
                if (isset($akt_hs[$nm]) && !empty($akt_hs[$nm]['id'])) $api->comm('/ip/hotspot/active/remove', ['=.id='.$akt_hs[$nm]['id']]);
                mysqli_query($conn, "DELETE FROM radcheck WHERE username='$sk'");
                mysqli_query($conn, "DELETE FROM radusergroup WHERE username='$sk'");
                mysqli_query($conn, "DELETE FROM voucher WHERE kode='$sk'");
                mysqli_query($conn, "INSERT INTO voucher_log (kode, profil, status_log, keterangan) VALUES ('$sk', '$sp', 'expired', 'Sudah logout otomatis karena durasi waktu habis ($up_str)')");
                $sync_exp++;
                continue;
            }

            // Masukkan ke database jika belum ada (sinkronisasi user bawaan MikroTik)
            $cek = mysqli_query($conn, "SELECT id, status FROM voucher WHERE kode='$sk'");
            if (mysqli_num_rows($cek) == 0) {
                $hrg  = (float)($map_pkt[$pr]['harga'] ?? 0);
                $masa = mysqli_real_escape_string($conn, $map_pkt[$pr]['masa_aktif'] ?? ($lim_str ?: '1 Hari'));
                $st_db = ($up_s > 0 || isset($akt_hs[$nm])) ? 'terjual' : 'stok';
                mysqli_query($conn, "INSERT INTO voucher (kode, profil, harga, status, masa_aktif, router_id, sudah_cetak) VALUES ('$sk', '$sp', $hrg, '$st_db', '$masa', ".(int)$rt['id'].", 1)");
                $pw = mysqli_real_escape_string($conn, $u['pass'] ?? $nm);
                mysqli_query($conn, "DELETE FROM radcheck WHERE username='$sk'");
                mysqli_query($conn, "INSERT INTO radcheck (username, attribute, op, value) VALUES ('$sk', 'Cleartext-Password', ':=', '$pw')");
                mysqli_query($conn, "DELETE FROM radusergroup WHERE username='$sk'");
                mysqli_query($conn, "INSERT INTO radusergroup (username, groupname, priority) VALUES ('$sk', '$sp', 1)");
                $sync_in++;
            } else {
                $rv = mysqli_fetch_assoc($cek);
                // Tandai sebagai terjual/terpakai jika sudah pernah digunakan (uptime > 0 atau sedang online)
                if (($up_s > 0 || isset($akt_hs[$nm])) && $rv['status'] == 'stok') {
                    mysqli_query($conn, "UPDATE voucher SET status='terjual' WHERE id=".(int)$rv['id']);
                    mysqli_query($conn, "INSERT INTO voucher_log (kode, profil, status_log, keterangan) VALUES ('$sk', '$sp', 'aktif', 'Voucher mulai dipakai pelanggan')");
                }
            }

            // Hitung keterangan status pemakaian & sisa waktu untuk ditampilkan di tabel
            if (isset($akt_hs[$nm])) {
                $sisa_txt = !empty($akt_hs[$nm]['left']) ? $akt_hs[$nm]['left'] : ($lim_s > 0 ? detikKeTeks(max(0, $lim_s - $up_s)) : 'Unlimited');
                $info_mt[$nm] = ['kondisi' => 'online', 'sisa' => "Sisa: $sisa_txt (IP: ".($akt_hs[$nm]['ip']??'-').")"];
            } elseif ($up_s > 0) {
                $sisa_txt = ($lim_s > 0) ? detikKeTeks(max(0, $lim_s - $up_s)) : 'Unlimited';
                $info_mt[$nm] = ['kondisi' => 'terpakai', 'sisa' => "Terpakai $up_str | Sisa: $sisa_txt"];
            } else {
                $info_mt[$nm] = ['kondisi' => 'baru', 'sisa' => "Utuh ($lim_str)"];
            }
        }
        $api->disconnect();
    }
}
if (isset($_POST['gen_vcr'])) {
    $pkt_id = (int)$_POST['paket_id'];
    $jml    = max(1, min(200, (int)$_POST['jumlah']));
    $panjang= max(4, min(12, (int)($_POST['panjang'] ?? 8)));
    $tipe   = $_POST['tipe_kode'] ?? 'angka';
    $rid    = (int)($_POST['router_id'] ?? 1);

    $pk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM paket WHERE id=$pkt_id"));
    $rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=$rid"));
    if ($pk) {
        $prof = $pk['nama_paket']; $hrg = (float)$pk['harga']; $masa = $pk['masa_aktif'] ?: '1 Hari';
        $uptime_lim = trim($pk['durasi_uptime'] ?? '');
        $api_ok = ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port']));
        $buat = 0;
        for ($i = 0; $i < $jml; $i++) {
            $kode = acakKodeVoucher($panjang, $tipe);
            if (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM voucher WHERE kode='$kode'")) > 0) { $i--; continue; }
            $sk = mysqli_real_escape_string($conn, $kode);
            $sp = mysqli_real_escape_string($conn, $prof);
            $sm = mysqli_real_escape_string($conn, $masa);
            mysqli_query($conn, "INSERT INTO voucher (kode, profil, harga, status, masa_aktif, router_id, sudah_cetak) VALUES ('$sk', '$sp', $hrg, 'stok', '$sm', $rid, 0)");
            mysqli_query($conn, "DELETE FROM radcheck WHERE username='$sk'");
            mysqli_query($conn, "INSERT INTO radcheck (username, attribute, op, value) VALUES ('$sk', 'Cleartext-Password', ':=', '$sk')");
            mysqli_query($conn, "DELETE FROM radusergroup WHERE username='$sk'");
            mysqli_query($conn, "INSERT INTO radusergroup (username, groupname, priority) VALUES ('$sk', '$sp', 1)");
            if ($api_ok) {
                $params = ['=name='.$kode, '=password='.$kode, '=profile='.$prof];
                if ($uptime_lim !== '') $params[] = '=limit-uptime='.$uptime_lim;
                $api->comm('/ip/hotspot/user/add', $params);
            }
            $buat++;
        }
        if ($api_ok) $api->disconnect();
        echo "<script>location.href='?page=voucher';</script>"; exit;
    }
}
if (isset($_GET['del_vcr'])) {
    $vid = (int)$_GET['del_vcr'];
    $vr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM voucher WHERE id=$vid"));
    if ($vr) {
        $sk = mysqli_real_escape_string($conn, $vr['kode']);
        mysqli_query($conn, "DELETE FROM radcheck WHERE username='$sk'");
        mysqli_query($conn, "DELETE FROM radusergroup WHERE username='$sk'");
        $rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=".(int)$vr['router_id']));
        if ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
            $cur = [];
            foreach ($api->comm('/ip/hotspot/user/print') as $l) {
                if ($l == '!re') { if (($cur['name']??'') == $vr['kode'] && !empty($cur['id'])) $api->comm('/ip/hotspot/user/remove', ['=.id='.$cur['id']]); $cur=[]; }
                elseif (strpos($l,'=.id=')===0) $cur['id']=substr($l,5);
                elseif (strpos($l,'=name=')===0) $cur['name']=substr($l,6);
            }
            if (($cur['name']??'') == $vr['kode'] && !empty($cur['id'])) $api->comm('/ip/hotspot/user/remove', ['=.id='.$cur['id']]);
            $api->disconnect();
        }
        mysqli_query($conn, "DELETE FROM voucher WHERE id=$vid");
    }
    echo "<script>location.href='?page=voucher';</script>"; exit;
}
if (isset($_GET['clear_vlog'])) { mysqli_query($conn, "TRUNCATE TABLE voucher_log"); echo "<script>location.href='?page=voucher';</script>"; exit; }
?>
<?php if($sync_in > 0 || $sync_exp > 0): ?>
<div class="card" style="background:#f0f9ff;border-left:5px solid #0284c7;font-size:12px">
  <b>Sinkronisasi MikroTik:</b> <b><?= $sync_in ?></b> voucher ditarik & <b><?= $sync_exp ?></b> voucher habis durasi telah logout & dihapus otomatis!
</div>
<?php endif; ?>

<div class="card" style="border-left:5px solid #0284c7">
  <h4>Generate Voucher Hotspot Acak (Langsung Masuk ke MikroTik)</h4>
  <form method="POST" style="margin-top:8px">
    <select name="paket_id" required>
      <option value="">-- Pilih Paket Profil Hotspot --</option>
      <?php $qp=mysqli_query($conn,"SELECT * FROM paket WHERE UPPER(tipe)='HOTSPOT' ORDER BY harga ASC"); while($pk=mysqli_fetch_assoc($qp)) echo "<option value='$pk[id]'>$pk[nama_paket] - Rp ".number_format($pk['harga'],0,',','.')." ($pk[masa_aktif])</option>"; ?>
    </select>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px">
      <select name="tipe_kode" style="flex:1;min-width:150px">
        <option value="angka">Kode Angka Saja (0-9)</option>
        <option value="huruf">Kode Huruf Saja (A-Z)</option>
        <option value="campur">Kombinasi Huruf & Angka</option>
      </select>
      <select name="panjang" style="width:110px">
        <option value="6">6 Digit</option>
        <option value="7">7 Digit</option>
        <option value="8" selected>8 Digit</option>
      </select>
      <input type="number" name="jumlah" value="28" min="1" max="200" placeholder="Jumlah" required style="width:100px">
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;align-items:center">
      <select name="router_id" style="flex:1;min-width:150px">
        <?php $qr=mysqli_query($conn,"SELECT * FROM routers ORDER BY id ASC"); while($rt=mysqli_fetch_assoc($qr)) echo "<option value='$rt[id]'>$rt[nama_nas]</option>"; ?>
      </select>
      <button name="gen_vcr" class="btn btn-g">+ Generate & Kirim ke MikroTik</button>
    </div>
  </form>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:8px">
    <h4>Daftar Kartu Voucher & Pemantauan Sisa Waktu (<?= mysqli_num_rows(mysqli_query($conn,"SELECT id FROM voucher")) ?> Total)</h4>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <a href="?page=voucher" class="btn btn-g" style="padding:4px 8px;font-size:11px">Sinkron MikroTik</a>
      <a href="cetak_voucher.php?mode=baru" target="_blank" class="btn" style="padding:4px 8px;font-size:11px">Cetak Voucher Baru</a>
      <a href="cetak_voucher.php?mode=semua" target="_blank" class="btn btn-w" style="padding:4px 8px;font-size:11px">Cetak Semua Stok</a>
    </div>
  </div>
  <div style="overflow-x:auto"><table>
    <tr><th>No</th><th>Kode Voucher</th><th>Profil</th><th>Status Pemakaian</th><th>Keterangan Sisa Waktu</th><th>Aksi</th></tr>
    <?php $no=1; $qv=mysqli_query($conn,"SELECT * FROM voucher ORDER BY (status='terjual') DESC, id DESC LIMIT 200");
    while($v=mysqli_fetch_assoc($qv)):
      $kd = $v['kode'];
      $inf = $info_mt[$kd] ?? ['kondisi' => ($v['status']=='terjual'?'terpakai':'baru'), 'sisa' => $v['masa_aktif']];
      if ($inf['kondisi'] == 'online') { $bg='#22c55e'; $lbl='SEDANG DIPAKAI (ONLINE)'; }
      elseif ($inf['kondisi'] == 'terpakai') { $bg='#f59e0b'; $lbl='SUDAH TERPAKAI'; }
      else { $bg='#0284c7'; $lbl='STOK BELUM TERPAKAI'; }
    ?>
    <tr>
      <td><?= $no++ ?></td>
      <td style="font-family:monospace;font-size:14px;font-weight:900;letter-spacing:1px"><?= htmlspecialchars($kd) ?></td>
      <td><b><?= htmlspecialchars($v['profil']) ?></b><br><small>Rp <?= number_format($v['harga'],0,',','.') ?></small></td>
      <td><span style="background:<?= $bg ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700;white-space:nowrap"><?= $lbl ?></span></td>
      <td><small style="font-weight:700;color:#334155"><?= htmlspecialchars($inf['sisa']) ?></small></td>
      <td><a href="?page=voucher&del_vcr=<?= $v['id'] ?>" onclick="return confirm('Hapus voucher ini dari database & MikroTik?')" class="btn btn-r" style="padding:3px 7px;font-size:10px">Hapus</a></td>
    </tr>
    <?php endwhile; ?>
  </table></div>
</div>

<div class="card" style="border-top:4px solid #ef4444">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
    <h4>Pemberitahuan Log Voucher (Terpakai & Logout Habis Durasi)</h4>
    <a href="?page=voucher&clear_vlog=1" onclick="return confirm('Bersihkan riwayat log?')" class="btn btn-r" style="padding:3px 7px;font-size:10px">Bersihkan Log</a>
  </div>
  <div style="overflow-x:auto"><table>
    <tr><th>Waktu</th><th>Kode Voucher</th><th>Profil</th><th>Pemberitahuan</th><th>Keterangan</th></tr>
    <?php $ql=mysqli_query($conn,"SELECT * FROM voucher_log ORDER BY id DESC LIMIT 50");
    while($lg=mysqli_fetch_assoc($ql)):
      $is_act = ($lg['status_log'] == 'aktif');
    ?>
    <tr>
      <td style="font-size:11px;white-space:nowrap"><?= date('d/m H:i', strtotime($lg['waktu'])) ?></td>
      <td style="font-family:monospace;font-weight:700"><?= htmlspecialchars($lg['kode']) ?></td>
      <td><small><?= htmlspecialchars($lg['profil']) ?></small></td>
      <td><span style="background:<?= $is_act?'#f59e0b':'#ef4444' ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= $is_act?'MULAI TERPAKAI':'LOGOUT HABIS DURASI' ?></span></td>
      <td><small><?= htmlspecialchars($lg['keterangan']) ?></small></td>
    </tr>
    <?php endwhile; ?>
    <?php if(mysqli_num_rows($ql)==0) echo "<tr><td colspan='5' style='text-align:center;color:#64748b'>Belum ada pemberitahuan voucher terpakai atau logout habis durasi</td></tr>"; ?>
  </table></div>
</div>
