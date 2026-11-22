<?php cekAkses(['owner','admin']);
$bln_ini = date('m-Y');

function formatPesanWA($tpl, $d) {
    $rep = [
        '{nama}' => $d['nama'] ?? '',
        '{id_pel}' => $d['kode_pel'] ?? '-',
        '{invoice}' => $d['no_invoice'] ?? '',
        '{periode}' => $d['bulan_tahun'] ?? '',
        '{nominal}' => number_format($d['jumlah'] ?? 0),
        '{jatuh_tempo}' => $d['jatuh_tempo'] ?? '-',
        '{metode}' => $d['metode_bayar'] ?? 'Tunai'
    ];
    return str_replace(array_keys($rep), array_values($rep), $tpl);
}

if (isset($_POST['gen_massal'])) {
    $qp = mysqli_query($conn, "SELECT p.*, pk.harga FROM pelanggan p LEFT JOIN paket pk ON p.paket_id=pk.id WHERE p.status!='disable'");
    $cnt = 0;
    while($p = mysqli_fetch_assoc($qp)) {
        $cek = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tagihan WHERE pelanggan_id=$p[id] AND bulan_tahun='$bln_ini'"));
        if ($cek == 0) {
            $inv = "INV-" . date('Ym') . "-" . ($p['kode_pel'] ?: $p['id']);
            $hrg = (float)($p['harga'] ?? 0);
            mysqli_query($conn, "INSERT INTO tagihan (no_invoice,pelanggan_id,bulan_tahun,jumlah) VALUES ('$inv',$p[id],'$bln_ini',$hrg)");
            $cnt++;
        }
    }
    echo "<div class='card' style='border-left:4px solid #00a65a'>Berhasil menerbitkan <b>$cnt invoice baru</b> untuk periode $bln_ini!</div>";
}

if (isset($_GET['wa'])) {
    $id_t = (int)$_GET['wa'];
    $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT t.*, p.nama, p.no_wa, p.kode_pel, p.jatuh_tempo FROM tagihan t JOIN pelanggan p ON t.pelanggan_id=p.id WHERE t.id=$id_t"));
    if ($t) {
        $tpl = ($t['status']=='lunas') ? $cfg['tpl_lunas'] : $cfg['tpl_tagihan'];
        $res = kirimWA($conn, $t['no_wa'], formatPesanWA($tpl, $t));
        echo "<div class='card' style='border-left:4px solid #0284c7'>Notifikasi WA ke <b>$t[nama] ($t[no_wa])</b> diproses. Respon: <code>$res</code></div>";
    }
}

if (isset($_GET['wa_blast'])) {
    $q = mysqli_query($conn, "SELECT t.*, p.nama, p.no_wa, p.kode_pel, p.jatuh_tempo FROM tagihan t JOIN pelanggan p ON t.pelanggan_id=p.id WHERE t.status='belum_bayar'");
    $sent = 0;
    while($t = mysqli_fetch_assoc($q)) {
        kirimWA($conn, $t['no_wa'], formatPesanWA($cfg['tpl_tagihan'], $t));
        $sent++;
    }
    echo "<div class='card' style='border-left:4px solid #00a65a'>Broadcast penagihan WA dikirim ke <b>$sent pelanggan</b> belum bayar!</div>";
}

if (isset($_POST['bayar_lunas'])) {
    $id_t = (int)$_POST['id_t'];
    $mtd  = mysqli_real_escape_string($conn, $_POST['metode']);
    $adm  = mysqli_real_escape_string($conn, $_SESSION['nama']);
    mysqli_query($conn, "UPDATE tagihan SET status='lunas', tgl_bayar=NOW(), metode_bayar='$mtd', admin_nama='$adm' WHERE id=$id_t");
    $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT t.*, p.id as pid, p.nama, p.no_wa, p.kode_pel FROM tagihan t JOIN pelanggan p ON t.pelanggan_id=p.id WHERE t.id=$id_t"));
    if ($t) {
        mysqli_query($conn, "UPDATE pelanggan SET status='aktif', tgl_perpanjang=NOW(), jatuh_tempo=DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id=$t[pid]");
        $t['jatuh_tempo'] = date('Y-m-d', strtotime('+30 days'));
        if (isset($_POST['kirim_struk'])) kirimWA($conn, $t['no_wa'], formatPesanWA($cfg['tpl_lunas'], $t));
    }
    echo "<script>location.href='?page=tagihan';</script>"; exit;
}

if (isset($_POST['kirim_info_jaringan'])) {
    $jenis = $_POST['jenis_info'];
    $ket   = $_POST['ket_g'] ?: 'Gangguan Jaringan / FO Cut';
    $target = $_POST['target_pel'];
    $tpl_use = ($jenis == 'gangguan') ? $cfg['tpl_gangguan'] : $cfg['tpl_normal'];
    $w_pel = ($target == 'all') ? "WHERE status!='disable'" : "WHERE id=" . (int)$target;
    $qp = mysqli_query($conn, "SELECT * FROM pelanggan $w_pel");
    $cnt = 0;
    while($p = mysqli_fetch_assoc($qp)) {
        $msg = formatPesanWA($tpl_use, $p, $cfg);
        $msg = str_replace('{ket_gangguan}', $ket, $msg);
        kirimWA($conn, $p['no_wa'], $msg);
        $cnt++;
    }
    echo "<div class='card' style='border-left:4px solid #0284c7'>Pemberitahuan <b>" . strtoupper($jenis) . "</b> berhasil dikirim ke <b>$cnt pelanggan</b>!</div>";
}
if (isset($_POST['save_tpl']) && $role == 'owner') {
    $t1 = mysqli_real_escape_string($conn, $_POST['t1']);
    $t2 = mysqli_real_escape_string($conn, $_POST['t2']);
    $t3 = mysqli_real_escape_string($conn, $_POST['t3']);
    $t4 = mysqli_real_escape_string($conn, $_POST['t4']);
    mysqli_query($conn, "UPDATE pengaturan SET tpl_tagihan='$t1', tpl_lunas='$t2', tpl_gangguan='$t3', tpl_normal='$t4' WHERE id=1");
    echo "<script>location.href='?page=tagihan';</script>"; exit;
}
if (isset($_GET['del_inv']) && $role == 'owner') {
    mysqli_query($conn, "DELETE FROM tagihan WHERE id=".(int)$_GET['del_inv']);
    echo "<script>location.href='?page=tagihan';</script>"; exit;
}
?>
<?php
$flt = $_GET['flt'] ?? 'all';
$tot_blm = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(jumlah) as t, COUNT(id) as c FROM tagihan WHERE status='belum_bayar'"));
$tot_lns = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(jumlah) as t, COUNT(id) as c FROM tagihan WHERE status='lunas'"));
?>
<div class="tiles">
  <div class="tile t-yellow"><h2>Rp <?= number_format($tot_blm['t']??0) ?></h2><p>Belum Bayar (<?= $tot_blm['c']??0 ?> Invoice)</p></div>
  <div class="tile t-green"><h2>Rp <?= number_format($tot_lns['t']??0) ?></h2><p>Sudah Lunas (<?= $tot_lns['c']??0 ?> Invoice)</p></div>
</div>

<div class="card" style="border-left:4px solid #f39c12">
  <h4>Broadcast Pemberitahuan Gangguan & Perbaikan Selesai</h4>
  <form method="POST" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;align-items:center">
    <select name="jenis_info">
      <option value="gangguan">Info Gangguan (Sedang Dikerjakan Teknisi)</option>
      <option value="normal">Info Normal (Perbaikan Selesai)</option>
    </select>
    <select name="target_pel">
      <option value="all">Kirim ke SEMUA Pelanggan Aktif</option>
      <?php $qpl=mysqli_query($conn,"SELECT id,nama,no_wa FROM pelanggan ORDER BY nama ASC"); while($pl=mysqli_fetch_assoc($qpl)) echo "<option value='$pl[id]'>$pl[nama] ($pl[no_wa])</option>"; ?>
    </select>
    <input name="ket_g" placeholder="Keterangan gangguan (mis: Kabel FO putus / Maintenance OLT)" style="flex:1;min-width:220px">
    <button name="kirim_info_jaringan" onclick="return confirm('Kirim pemberitahuan WhatsApp sekarang?')" class="btn btn-w">Kirim Info WA</button>
  </form>
</div>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">
    <form method="POST" style="margin:0;display:flex;gap:6px;flex-wrap:wrap">
      <button name="gen_massal" class="btn">+ Generate Tagihan (<?= $bln_ini ?>)</button>
      <a href="?page=tagihan&wa_blast=1" onclick="return confirm('Kirim tagihan WA ke semua pelanggan yang belum bayar?')" class="btn btn-g">Broadcast WA Belum Bayar</a>
      <a href="?page=tagihan&flt=all" class="btn" style="background:#475569">Semua</a>
      <a href="?page=tagihan&flt=belum_bayar" class="btn btn-w">Belum Bayar</a>
      <a href="?page=tagihan&flt=lunas" class="btn btn-g">Lunas</a>
    </form>
    <input type="text" id="cariInv" onkeyup="filterInv()" placeholder="Search invoice / nama..." style="width:200px">
  </div>

  <?php if($role == 'owner'): ?>
  <details style="margin-bottom:14px;background:#f8fafc;padding:10px;border-radius:6px;border:1px solid #e2e8f0">
    <summary style="cursor:pointer;font-weight:700;color:#0284c7">Pengaturan Template Pesan WhatsApp (Khusus Owner)</summary>
    <form method="POST" style="margin-top:10px">
      <small>Variabel tersedia: <code>{nama} {id_pel} {invoice} {periode} {nominal} {jatuh_tempo} {metode}</code></small><br><br>
      <label style="font-size:12px;font-weight:700">Template Tagihan Terbit:</label>
      <textarea name="t1" rows="4" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:4px"><?= htmlspecialchars($cfg['tpl_tagihan']??'') ?></textarea>
      <label style="font-size:12px;font-weight:700;margin-top:8px;display:block">Template Bukti Lunas:</label>
      <textarea name="t2" rows="4" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:4px"><?= htmlspecialchars($cfg['tpl_lunas']??'') ?></textarea>
      <label style="font-size:12px;font-weight:700;margin-top:8px;display:block;color:#d97706">Template Info Gangguan (Dikerjakan Teknisi):</label>
      <textarea name="t3" rows="4" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:4px"><?= htmlspecialchars($cfg['tpl_gangguan']??'') ?></textarea>
      <label style="font-size:12px;font-weight:700;margin-top:8px;display:block;color:#00a65a">Template Info Internet Kembali Normal:</label>
      <textarea name="t4" rows="4" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:4px"><?= htmlspecialchars($cfg['tpl_normal']??'') ?></textarea>
      <button name="save_tpl" class="btn btn-g" style="margin-top:8px">Simpan Semua Template WA</button>
    </form>
  </details>
  <?php endif; ?>

  <div style="overflow-x:auto">
  <table id="tblInv">
    <tr><th>No Invoice</th><th>Pelanggan</th><th>Periode</th><th>Nominal</th><th>Status</th><th>Pembayaran</th><th>Aksi</th></tr>
    <?php
    $w = ($flt != 'all') ? "WHERE t.status='$flt'" : "";
    $q = mysqli_query($conn,"SELECT t.*, p.nama, p.no_wa, p.kode_pel FROM tagihan t JOIN pelanggan p ON t.pelanggan_id=p.id $w ORDER BY t.id DESC");
    while($d = mysqli_fetch_assoc($q)):
      $st = $d['status']=='lunas' ? "<span style='background:#00a65a;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700'>LUNAS</span>" : "<span style='background:#dd4b39;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700'>BELUM BAYAR</span>";
    ?>
    <tr>
      <td><b><?= $d['no_invoice'] ?></b></td>
      <td><b><?= $d['nama'] ?></b><br><small style="color:#64748b"><?= $d['no_wa'] ?></small></td>
      <td><?= $d['bulan_tahun'] ?></td>
      <td><b>Rp <?= number_format($d['jumlah']) ?></b></td>
      <td><?= $st ?></td>
      <td style="font-size:11px"><?= $d['status']=='lunas' ? "$d[metode_bayar]<br><small>$d[tgl_bayar] ($d[admin_nama])</small>" : '-' ?></td>
      <td style="white-space:nowrap">
        <a href="?page=tagihan&wa=<?= $d['id'] ?>" class="btn btn-g" style="padding:4px 8px;font-size:11px">Kirim WA</a>
        <?php if($d['status']=='belum_bayar'): ?>
          <button type="button" onclick="bukaBayar(<?= $d['id'] ?>,'<?= addslashes($d['nama']) ?>','<?= number_format($d['jumlah']) ?>')" class="btn" style="padding:4px 8px;font-size:11px">Bayar</button>
        <?php else: ?>
          <button type="button" onclick="cetakStruk('<?= $d['no_invoice'] ?>','<?= addslashes($d['nama']) ?>','<?= $d['bulan_tahun'] ?>','<?= number_format($d['jumlah']) ?>','<?= $d['metode_bayar'] ?>','<?= $d['tgl_bayar'] ?>')" class="btn btn-w" style="padding:4px 8px;font-size:11px">Struk</button>
        <?php endif; ?>
        <?php if($role=='owner'): ?>
          <a href="?page=tagihan&del_inv=<?= $d['id'] ?>" onclick="return confirm('Hapus invoice ini?')" class="btn btn-r" style="padding:4px 8px;font-size:11px">Hapus</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>

<!-- MODAL KONFIRMASI PEMBAYARAN -->
<div id="mdlBayar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center;padding:16px">
  <div class="card" style="width:100%;max-width:360px">
    <h4>Konfirmasi Pelunasan Tagihan</h4>
    <p id="lblPel" style="margin:8px 0;font-size:13px;color:#475569"></p>
    <form method="POST">
      <input type="hidden" name="id_t" id="inpIdT">
      <label style="font-size:12px;font-weight:700">Metode Pembayaran:</label>
      <select name="metode" style="width:100%;margin-bottom:10px">
        <option value="Tunai / Cash">Tunai / Cash</option>
        <option value="Transfer Bank">Transfer Bank</option>
        <option value="QRIS / E-Wallet">QRIS / E-Wallet</option>
      </select>
      <label style="font-size:12px;display:flex;align-items:center;gap:6px;margin-bottom:14px">
        <input type="checkbox" name="kirim_struk" checked> Otomatis kirim struk lunas ke WhatsApp
      </label>
      <button name="bayar_lunas" class="btn btn-g">Konfirmasi Lunas</button>
      <button type="button" onclick="document.getElementById('mdlBayar').style.display='none'" class="btn btn-r">Batal</button>
    </form>
  </div>
</div>

<script>
function bukaBayar(id, nama, nom){
  document.getElementById('inpIdT').value = id;
  document.getElementById('lblPel').innerHTML = 'Pelanggan: <b>'+nama+'</b><br>Total Tagihan: <b>Rp '+nom+'</b>';
  document.getElementById('mdlBayar').style.display = 'flex';
}
function filterInv(){
  let q=document.getElementById('cariInv').value.toLowerCase();
  document.querySelectorAll('#tblInv tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
function cetakStruk(inv, nama, per, nom, mtd, tgl){
  let w = window.open('','','width=320,height=480');
  w.document.write(`<pre style="font-family:monospace;font-size:12px;padding:10px">
==============================
        D.N.A_Billing
   D.N.A_NETWORK by MSP_NET
==============================
No Inv  : ${inv}
Tanggal : ${tgl}
Pelanggan: ${nama}
Periode : ${per}
Metode  : ${mtd}
------------------------------
TOTAL   : Rp ${nom}
STATUS  : LUNAS
==============================
 Terima kasih atas pembayaran
   Anda. Layanan tetap aktif.
==============================</pre>`);
  w.print();
}
</script>
