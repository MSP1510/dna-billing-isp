<?php cekAkses(['owner','admin','teknisi']);
if (isset($_POST['add_tkt'])) {
    $pid = (int)$_POST['p']; $jns = $_POST['jns'];
    $kel = mysqli_real_escape_string($conn, $_POST['k']);
    $tek = mysqli_real_escape_string($conn, $_POST['tek']);
    mysqli_query($conn, "INSERT INTO tiket (pelanggan_id,jenis,keluhan,teknisi_nama) VALUES ($pid,'$jns','$kel','$tek')");
    $pl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pelanggan WHERE id=$pid"));
    if ($pl && isset($_POST['notif_wa'])) {
        kirimWA($conn, $pl['no_wa'], "*TIKET GANGGUAN - D.N.A_Billing*\nHalo Kak *$pl[nama]*,\nLaporan Anda (*$kel*) telah kami terima dan ditugaskan ke teknisi *$tek*. Mohon ditunggu.");
    }
    echo "<script>location.href='?page=tiket';</script>"; exit;
}
if (isset($_POST['selesai_tkt'])) {
    $tid = (int)$_POST['tid'];
    $cat = mysqli_real_escape_string($conn, $_POST['catatan']);
    mysqli_query($conn, "UPDATE tiket SET status='selesai', catatan_selesai='$cat' WHERE id=$tid");
    $tk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT t.*, p.nama, p.no_wa FROM tiket t JOIN pelanggan p ON t.pelanggan_id=p.id WHERE t.id=$tid"));
    if ($tk && isset($_POST['notif_selesai'])) {
        kirimWA($conn, $tk['no_wa'], "*PERBAIKAN SELESAI - D.N.A_Billing*\nHalo Kak *$tk[nama]*,\nTiket laporan Anda telah *SELESAI* ditangani ($cat). Layanan internet sudah kembali normal. Terima kasih.");
    }
    echo "<script>location.href='?page=tiket';</script>"; exit;
}
if (isset($_GET['del_tkt']) && $role == 'owner') {
    mysqli_query($conn, "DELETE FROM tiket WHERE id=".(int)$_GET['del_tkt']);
    echo "<script>location.href='?page=tiket';</script>"; exit;
}
?>
<div class="card">
  <h4>Tiket Laporan Gangguan & Instalasi Teknisi</h4>
  <form method="POST" style="margin-top:10px">
    <select name="p" required><option value="">-- Pilih Pelanggan --</option>
      <?php $qp=mysqli_query($conn,"SELECT * FROM pelanggan ORDER BY nama ASC"); while($p=mysqli_fetch_assoc($qp)) echo "<option value='$p[id]'>$p[nama] ($p[no_wa])</option>"; ?>
    </select>
    <select name="jns"><option value="gangguan">Gangguan / LOS</option><option value="pasang_baru">Pasang Baru</option></select>
    <select name="tek">
      <?php $qt=mysqli_query($conn,"SELECT nama FROM users WHERE role IN ('teknisi','owner')"); while($u=mysqli_fetch_assoc($qt)) echo "<option value='$u[nama]'>Teknisi: $u[nama]</option>"; ?>
    </select>
    <input name="k" placeholder="Keterangan keluhan / alamat ODP" style="width:240px" required>
    <label style="font-size:12px"><input type="checkbox" name="notif_wa" checked> Info WA Pelanggan</label>
    <button name="add_tkt" class="btn btn-w">+ Buat Tiket</button>
  </form>

  <div style="overflow-x:auto">
  <table>
    <tr><th>Waktu</th><th>Pelanggan</th><th>Jenis</th><th>Keluhan / Tugas</th><th>Teknisi</th><th>Status</th><th>Aksi</th></tr>
    <?php
    $q = mysqli_query($conn, "SELECT t.*, p.nama, p.no_wa, p.lat, p.lng FROM tiket t JOIN pelanggan p ON t.pelanggan_id=p.id ORDER BY t.id DESC");
    while($d = mysqli_fetch_assoc($q)):
      $st = $d['status']=='selesai' ? "<span style='background:#00a65a;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>SELESAI</span>" : "<span style='background:#f39c12;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>PENDING</span>";
    ?>
    <tr>
      <td style="font-size:11px"><?= date('d/m H:i', strtotime($d['tgl_buat'])) ?></td>
      <td><b><?= $d['nama'] ?></b><br><small><?= $d['no_wa'] ?></small></td>
      <td><?= strtoupper($d['jenis']) ?></td>
      <td><?= htmlspecialchars($d['keluhan']) ?><?php if($d['catatan_selesai']) echo "<br><small style='color:#00a65a'>Catatan: ".htmlspecialchars($d['catatan_selesai'])."</small>"; ?></td>
      <td><b><?= $d['teknisi_nama']??'-' ?></b></td>
      <td><?= $st ?></td>
      <td style="white-space:nowrap">
        <?php if(!empty($d['lat']) && !empty($d['lng'])): ?>
          <a href="https://google.com<?= $d['lat'] ?>,<?= $d['lng'] ?>" target="_blank" class="btn" style="padding:3px 7px;font-size:11px">Rute GPS</a>
        <?php endif; ?>
        <?php if($d['status']=='pending'): ?>
          <button type="button" onclick="bukaSelesai(<?= $d['id'] ?>,'<?= addslashes($d['nama']) ?>')" class="btn btn-g" style="padding:3px 7px;font-size:11px">Selesai</button>
        <?php endif; ?>
        <?php if($role=='owner'): ?>
          <a href="?page=tiket&del_tkt=<?= $d['id'] ?>" class="btn btn-r" style="padding:3px 7px;font-size:11px">Hapus</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>

<div id="mdlTkt" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center;padding:16px">
  <div class="card" style="width:100%;max-width:360px">
    <h4>Selesaikan Tiket Teknisi</h4>
    <p id="lblTkt" style="margin:8px 0;font-size:13px"></p>
    <form method="POST">
      <input type="hidden" name="tid" id="inpTid">
      <input name="catatan" placeholder="Tindakan perbaikan (mis: Sambung ulang FO / Ganti ONT)" style="width:100%;margin-bottom:10px" required>
      <label style="font-size:12px;display:block;margin-bottom:12px"><input type="checkbox" name="notif_selesai" checked> Kirim WA pemberitahuan selesai ke pelanggan</label>
      <button name="selesai_tkt" class="btn btn-g">Tandai Selesai</button>
      <button type="button" onclick="document.getElementById('mdlTkt').style.display='none'" class="btn btn-r">Batal</button>
    </form>
  </div>
</div>
<script>
function bukaSelesai(id, nm){ document.getElementById('inpTid').value=id; document.getElementById('lblTkt').innerHTML='Pelanggan: <b>'+nm+'</b>'; document.getElementById('mdlTkt').style.display='flex'; }
</script>
