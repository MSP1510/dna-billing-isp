<?php

if (isset($_GET['del_mitra'])) {
    $mid = (int)$_GET['del_mitra'];
    mysqli_query($conn, "DELETE FROM mitra_setoran WHERE mitra_id=$mid");
    mysqli_query($conn, "DELETE FROM mitra WHERE id=$mid");
    echo "<script>location.href='?page=mitra';</script>"; exit;
}
if (isset($_GET['del_setor'])) {
    $sid = (int)$_GET['del_setor'];
    mysqli_query($conn, "DELETE FROM mitra_setoran WHERE id=$sid");
    echo "<script>location.href='?page=mitra';</script>"; exit;
}
 cekAkses(['owner','admin']);
if (isset($_POST['add_mitra'])) {
    mysqli_query($conn, "INSERT INTO mitra (nama_mitra,no_wa,alamat,komisi_persen) VALUES ('$_POST[nm]','$_POST[wa]','$_POST[al]',".(int)$_POST['km'].")");
    echo "<script>location.href='?page=mitra';</script>"; exit;
}
if (isset($_POST['titip_vc'])) {
    $mid = (int)$_POST['mid']; $pid = (int)$_POST['pid']; $qty = (int)$_POST['qty'];
    $pk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM paket WHERE id=$pid"));
    $mt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mitra WHERE id=$mid"));
    $hrg_jual = ($pk['harga'] ?? 0) * $qty;
    $pot_komisi = $hrg_jual * (($mt['komisi_persen'] ?? 10) / 100);
    $setor_bersih = $hrg_jual - $pot_komisi;
    mysqli_query($conn, "INSERT INTO mitra_setoran (mitra_id,paket,qty_ambil,qty_laku,total_setor,status) VALUES ($mid,'$pk[nama_paket]',$qty,$qty,$setor_bersih,'titip')");
    echo "<script>location.href='?page=mitra';</script>"; exit;
}
if (isset($_GET['setor'])) {
    $sid = (int)$_GET['setor'];
    $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, m.nama_mitra FROM mitra_setoran s JOIN mitra m ON s.mitra_id=m.id WHERE s.id=$sid"));
    if ($s && $s['status'] == 'titip') {
        mysqli_query($conn, "UPDATE mitra_setoran SET status='lunas' WHERE id=$sid");
        mysqli_query($conn, "INSERT INTO keuangan (tipe,kategori,keterangan,jumlah) VALUES ('masuk','Setoran Mitra Voucher','Setoran $s[nama_mitra] ($s[qty_laku]x $s[paket])',$s[total_setor])");
    }
    echo "<script>location.href='?page=mitra';</script>"; exit;
}
?>
<div class="card">
  <h4>1. Daftarkan Agen / Warung Mitra Voucher</h4>
  <form method="POST" style="margin-top:8px">
    <input name="nm" placeholder="Nama Warung / Agen (mis: Warung Bu Siti)" required>
    <input name="wa" placeholder="No WA Agen">
    <input name="al" placeholder="Lokasi / RT">
    <input name="km" type="number" value="10" style="width:85px" title="Komisi Agen (%)"> % Komisi
    <button name="add_mitra" class="btn btn-g">+ Tambah Mitra</button>
  </form>

  <div style="overflow-x:auto;margin-top:12px">
    <table>
      <tr><th>No</th><th>Nama Mitra / Warung</th><th>No. WA / Info</th><th>Komisi</th><th>Delete</th></tr>
      <?php $no_m=1; $qm_list=mysqli_query($conn,"SELECT * FROM mitra ORDER BY id DESC");
      while($ml=mysqli_fetch_assoc($qm_list)): ?>
      <tr>
        <td><?= $no_m++ ?></td>
        <td><b><?= htmlspecialchars($ml['nama_mitra']) ?></b></td>
        <td><small><?= htmlspecialchars($ml['no_wa'] ?? ($ml['alamat'] ?? '-')) ?></small></td>
        <td><span style="background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:700"><?= $ml['komisi_persen'] ?>%</span></td>
        <td><a href="?page=mitra&del_mitra=<?= $ml['id'] ?>" onclick="return confirm('Hapus permanen Mitra ini beserta seluruh riwayat setorannya?')" class="btn btn-r" style="padding:4px 8px;font-size:11px">Delete</a></td>
      </tr>
      <?php endwhile; ?>
      <?php if(mysqli_num_rows($qm_list)==0) echo "<tr><td colspan='5' style='text-align:center;color:#64748b'>Belum ada mitra terdaftar</td></tr>"; ?>
    </table>
  </div>
</div>

<div class="card">
  <h4>2. Catat Titip Stok & Terima Setoran Mitra</h4>
  <form method="POST" style="margin-top:8px;margin-bottom:12px">
    <select name="mid" required><option value="">-- Pilih Mitra / Agen --</option>
      <?php $qm=mysqli_query($conn,"SELECT * FROM mitra"); while($m=mysqli_fetch_assoc($qm)) echo "<option value='$m[id]'>$m[nama_mitra] (Komisi $m[komisi_persen]%)</option>"; ?>
    </select>
    <select name="pid" required><option value="">-- Pilih Paket Voucher --</option>
      <?php $qp=mysqli_query($conn,"SELECT * FROM paket WHERE tipe='HOTSPOT'"); while($p=mysqli_fetch_assoc($qp)) echo "<option value='$p[id]'>$p[nama_paket] (Rp ".number_format($p['harga']).")</option>"; ?>
    </select>
    <input name="qty" type="number" value="20" min="1" style="width:80px" placeholder="Qty">
    <button name="titip_vc" class="btn">+ Titip Voucher ke Mitra</button>
  </form>

  <div style="overflow-x:auto">
  <table>
    <tr><th>Tanggal</th><th>Nama Mitra / Warung</th><th>Paket & Jumlah</th><th>Wajib Setor (Bersih)</th><th>Status</th><th>Aksi</th></tr>
    <?php
    $qs = mysqli_query($conn, "SELECT s.*, m.nama_mitra FROM mitra_setoran s JOIN mitra m ON s.mitra_id=m.id ORDER BY s.id DESC");
    while($d = mysqli_fetch_assoc($qs)):
      $st = $d['status']=='lunas' ? "<span style='background:#00a65a;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>SUDAH SETOR</span>" : "<span style='background:#f39c12;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>BELUM SETOR</span>";
    ?>
    <tr>
      <td style="font-size:11px"><?= date('d/m/Y', strtotime($d['tgl'])) ?></td>
      <td><b><?= htmlspecialchars($d['nama_mitra']) ?></b></td>
      <td><b><?= $d['qty_ambil'] ?> Kartu</b> (<?= $d['paket'] ?>)</td>
      <td style="color:#0284c7;font-weight:700">Rp <?= number_format($d['total_setor']) ?></td>
      <td><?= $st ?></td>
      <td><?= $d['status']=='titip' ? "<a href='?page=mitra&setor=$d[id]' onclick=\"return confirm('Terima uang setoran & masukkan ke Kas?')\" class='btn btn-g' style='padding:4px 8px;font-size:11px'>Terima Setoran</a>" : "<small>Masuk Kas</small>" ?> <a href='?page=mitra&del_setor=$d[id]' onclick=\"return confirm('Hapus baris catatan setoran ini?')\" class='btn btn-r' style='padding:2px 6px;font-size:10px;margin-left:4px'>Delete</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>
