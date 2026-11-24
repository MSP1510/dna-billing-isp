<?php cekAkses(['owner','admin','kasir']);
function acakKodeVoucher($panjang = 8, $tipe = 'angka') {
    if ($tipe == 'huruf') $kar = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    elseif ($tipe == 'campur') $kar = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    else $kar = '0123456789';
    $res = ''; $max = strlen($kar) - 1;
    for ($i = 0; $i < $panjang; $i++) $res .= $kar[random_int(0, $max)];
    return $res;
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
        $prof = $pk['nama_paket'];
        $hrg  = (float)$pk['harga'];
        $masa = $pk['masa_aktif'] ?: '1 Hari';
        $uptime_lim = trim($pk['durasi_uptime'] ?? '');

        $api_ok = ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port']));
        $buat = 0;
        for ($i = 0; $i < $jml; $i++) {
            $kode = acakKodeVoucher($panjang, $tipe);
            if (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM voucher WHERE kode='$kode'")) > 0) { $i--; continue; }

            $sk = mysqli_real_escape_string($conn, $kode);
            $sp = mysqli_real_escape_string($conn, $prof);
            $sm = mysqli_real_escape_string($conn, $masa);

            // 1. Simpan ke database voucher
            mysqli_query($conn, "INSERT INTO voucher (kode, profil, harga, status, masa_aktif, router_id, sudah_cetak) VALUES ('$sk', '$sp', $hrg, 'stok', '$sm', $rid, 0)");

            // 2. Sinkron ke FreeRADIUS (Username = Password)
            mysqli_query($conn, "DELETE FROM radcheck WHERE username='$sk'");
            mysqli_query($conn, "INSERT INTO radcheck (username, attribute, op, value) VALUES ('$sk', 'Cleartext-Password', ':=', '$sk')");
            mysqli_query($conn, "DELETE FROM radusergroup WHERE username='$sk'");
            mysqli_query($conn, "INSERT INTO radusergroup (username, groupname, priority) VALUES ('$sk', '$sp', 1)");

            // 3. Langsung buat user Hotspot di MikroTik (User = Password, tanpa awalan)
            if ($api_ok) {
                $params = ['=name='.$kode, '=password='.$kode, '=profile='.$prof];
                if ($uptime_lim !== '') $params[] = '=limit-uptime='.$uptime_lim;
                $api->comm('/ip/hotspot/user/add', $params);
            }
            $buat++;
        }
        if ($api_ok) $api->disconnect();
        echo "<div class='card' style='border-left:5px solid #00a65a;background:#f0fdf4'><b>Berhasil Generate $buat Voucher!</b> Kode acak murni (User = Password) telah masuk ke database & langsung terdaftar di MikroTik!</div>";
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
?>
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
    <h4>Stok Kartu Voucher Siap Cetak</h4>
    <div style="display:flex;gap:6px">
      <a href="cetak_voucher.php?mode=baru" target="_blank" class="btn">Cetak Voucher Baru</a>
      <a href="cetak_voucher.php?mode=semua" target="_blank" class="btn btn-w">Cetak Semua Stok</a>
    </div>
  </div>
  <div style="overflow-x:auto"><table>
    <tr><th>No</th><th>Kode (User=Pass)</th><th>Profil Paket</th><th>Harga</th><th>Masa Aktif</th><th>Status</th><th>Aksi</th></tr>
    <?php $no=1; $qv=mysqli_query($conn,"SELECT * FROM voucher WHERE status='stok' ORDER BY id DESC LIMIT 100");
    while($v=mysqli_fetch_assoc($qv)): ?>
    <tr>
      <td><?= $no++ ?></td>
      <td style="font-family:monospace;font-size:14px;font-weight:900;letter-spacing:1px"><?= htmlspecialchars($v['kode']) ?></td>
      <td><b><?= htmlspecialchars($v['profil']) ?></b></td>
      <td>Rp <?= number_format($v['harga'],0,',','.') ?></td>
      <td><?= htmlspecialchars($v['masa_aktif']) ?></td>
      <td><span style="background:#00a65a;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px">STOK</span></td>
      <td><a href="?page=voucher&del_vcr=<?= $v['id'] ?>" onclick="return confirm('Hapus voucher ini dari database & MikroTik?')" class="btn btn-r" style="padding:3px 7px;font-size:10px">Hapus</a></td>
    </tr>
    <?php endwhile; ?>
  </table></div>
</div>
