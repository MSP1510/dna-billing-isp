<?php cekAkses(['owner','admin']);

if (isset($_POST['save_aset'])) {
    $nm  = mysqli_real_escape_string($conn, $_POST['nama']);
    $kat = mysqli_real_escape_string($conn, $_POST['kat']);
    $jml = max(1, (int)$_POST['jml']);
    $hrg = (float)$_POST['hrg'];
    $knd = mysqli_real_escape_string($conn, $_POST['kondisi']);
    $lok = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $tgl = $_POST['tgl'] ?: date('Y-m-d');

    if (!empty($_POST['id_aset'])) {
        $aid = (int)$_POST['id_aset'];
        mysqli_query($conn, "UPDATE aset SET nama_aset='$nm', kategori='$kat', jumlah=$jml, harga_satuan=$hrg, kondisi='$knd', lokasi='$lok', tgl_beli='$tgl' WHERE id=$aid");
    } else {
        $kode = "AST-" . rand(1000, 9999);
        mysqli_query($conn, "INSERT INTO aset (kode_aset,nama_aset,kategori,jumlah,harga_satuan,kondisi,lokasi,tgl_beli) VALUES ('$kode','$nm','$kat',$jml,$hrg,'$knd','$lok','$tgl')");
        if (isset($_POST['catat_kas']) && ($jml * $hrg) > 0) {
            $tot_beli = $jml * $hrg;
            mysqli_query($conn, "INSERT INTO keuangan (tipe,kategori,keterangan,jumlah) VALUES ('keluar','Pembelian Aset','Beli Aset: $nm ($jml unit)',$tot_beli)");
        }
    }
    echo "<script>location.href='?page=aset';</script>"; exit;
}

if (isset($_GET['del_aset'])) {
    mysqli_query($conn, "DELETE FROM aset WHERE id=".(int)$_GET['del_aset']);
    echo "<script>location.href='?page=aset';</script>"; exit;
}

$ea = isset($_GET['edit']) ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM aset WHERE id=".(int)$_GET['edit'])) : null;
$sum_unit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as u, SUM(jumlah*harga_satuan) as val FROM aset"));
$cnt_baik = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as u FROM aset WHERE kondisi='Baik'"))['u'] ?? 0;
$cnt_rsk  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as u FROM aset WHERE kondisi!='Baik'"))['u'] ?? 0;
?>
<div class="tiles">
  <div class="tile t-blue"><h2><?= (int)($sum_unit['u']??0) ?> Unit</h2><p>Total Seluruh Aset</p></div>
  <div class="tile t-green"><h2>Rp <?= number_format($sum_unit['val']??0) ?></h2><p>Total Nilai Kekayaan Aset</p></div>
  <div class="tile t-yellow"><h2><?= (int)$cnt_baik ?> Unit</h2><p>Aset Kondisi Baik</p></div>
  <div class="tile t-red"><h2><?= (int)$cnt_rsk ?> Unit</h2><p>Perlu Servis / Rusak</p></div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">
    <h4><?= $ea ? 'Edit Data Aset Perusahaan' : 'Catat Aset Perusahaan Wi-Fi (D.N.A_NETWORK)' ?></h4>
    <input type="text" id="cariAset" onkeyup="filterAset()" placeholder="Cari nama aset / lokasi..." style="width:210px">
  </div>

  <form method="POST" style="background:#f8fafc;padding:12px;border-radius:6px;border:1px solid #e2e8f0;margin-bottom:14px">
    <input type="hidden" name="id_aset" value="<?= $ea['id']??'' ?>">
    <input name="nama" value="<?= htmlspecialchars($ea['nama_aset']??'') ?>" placeholder="Nama Aset (mis: OLT EPON 8 Port / Splicer / Tiang 7m)" style="width:240px" required>
    <select name="kat">
      <?php $kats = ['Perangkat Core (OLT/Router/Server)','Jaringan FO (ODP/ODC/Kabel/Tiang)','Alat Kerja Teknisi (Splicer/OTDR/Tangga)','Stok CPE (ONT/Modem/Router Pelanggan)','Aset Kantor & Kendaraan'];
      foreach($kats as $k) echo "<option value='$k' ".(($ea['kategori']??'')==$k?'selected':'').">$k</option>"; ?>
    </select>
    <input name="jml" type="number" value="<?= $ea['jumlah']??1 ?>" min="1" style="width:75px" title="Jumlah Unit" required>
    <input name="hrg" type="number" value="<?= $ea['harga_satuan']??'' ?>" placeholder="Harga Satuan (Rp)" required>
    <select name="kondisi">
      <option value="Baik" <?= ($ea['kondisi']??'')=='Baik'?'selected':'' ?>>Kondisi: Baik</option>
      <option value="Perlu Servis" <?= ($ea['kondisi']??'')=='Perlu Servis'?'selected':'' ?>>Kondisi: Perlu Servis</option>
      <option value="Rusak" <?= ($ea['kondisi']??'')=='Rusak'?'selected':'' ?>>Kondisi: Rusak</option>
    </select>
    <input name="lokasi" value="<?= htmlspecialchars($ea['lokasi']??'') ?>" placeholder="Lokasi / Dipegang Oleh (mis: Server Pusat / Teknisi A)">
    <input type="date" name="tgl" value="<?= $ea['tgl_beli']??date('Y-m-d') ?>">
    <?php if(!$ea): ?>
      <label style="font-size:12px;margin:0 6px"><input type="checkbox" name="catat_kas"> Potong dari Saldo Kas (Catat Pengeluaran)</label>
    <?php endif; ?>
    <button name="save_aset" class="btn btn-g"><?= $ea ? 'Update Aset' : '+ Simpan Aset' ?></button>
    <?php if($ea) echo "<a href='?page=aset' class='btn btn-r'>Batal</a>"; ?>
  </form>

  <div style="overflow-x:auto">
  <table id="tblAset">
    <tr><th>Kode</th><th>Nama Aset</th><th>Kategori</th><th>Qty</th><th>Harga Satuan</th><th>Total Nilai</th><th>Lokasi / PIC</th><th>Kondisi</th><th>Aksi</th></tr>
    <?php
    $q = mysqli_query($conn, "SELECT * FROM aset ORDER BY id DESC");
    while($d = mysqli_fetch_assoc($q)):
      $tot = $d['jumlah'] * $d['harga_satuan'];
      $bg = $d['kondisi']=='Baik' ? '#00a65a' : ($d['kondisi']=='Perlu Servis' ? '#f39c12' : '#dd4b39');
    ?>
    <tr>
      <td style="font-family:monospace;font-weight:700;color:#0284c7"><?= $d['kode_aset'] ?></td>
      <td><b><?= htmlspecialchars($d['nama_aset']) ?></b><br><small style="color:#64748b">Beli: <?= $d['tgl_beli'] ?></small></td>
      <td style="font-size:11px"><?= htmlspecialchars($d['kategori']) ?></td>
      <td><b><?= $d['jumlah'] ?></b></td>
      <td>Rp <?= number_format($d['harga_satuan']) ?></td>
      <td><b>Rp <?= number_format($tot) ?></b></td>
      <td><?= htmlspecialchars($d['lokasi']?:'-') ?></td>
      <td><span style="background:<?= $bg ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= strtoupper($d['kondisi']) ?></span></td>
      <td style="white-space:nowrap">
        <a href="?page=aset&edit=<?= $d['id'] ?>" class="btn" style="padding:3px 7px;font-size:11px">Edit</a>
        <a href="?page=aset&del_aset=<?= $d['id'] ?>" onclick="return confirm('Hapus aset ini?')" class="btn btn-r" style="padding:3px 7px;font-size:11px">Hapus</a>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>
<script>
function filterAset(){
  let q=document.getElementById('cariAset').value.toLowerCase();
  document.querySelectorAll('#tblAset tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
</script>
