<?php cekAkses(['owner','admin']);
if (isset($_POST['add_kas'])) {
    $tp = $_POST['tipe']; $kt = mysqli_real_escape_string($conn, $_POST['kat']);
    $ket = mysqli_real_escape_string($conn, $_POST['ket']); $jml = (float)$_POST['jml'];
    mysqli_query($conn, "INSERT INTO keuangan (tipe,kategori,keterangan,jumlah) VALUES ('$tp','$kt','$ket','$jml')");
    echo "<script>location.href='?page=keuangan';</script>"; exit;
}

function hitungMasuk($conn, $cond_tagihan, $cond_kas) {
    $t1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as t FROM tagihan WHERE status='lunas' AND $cond_tagihan"))['t'] ?? 0;
    $t2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as t FROM keuangan WHERE tipe='masuk' AND $cond_kas"))['t'] ?? 0;
    return $t1 + $t2;
}

$in_hari  = hitungMasuk($conn, "DATE(tgl_bayar)=CURDATE()", "DATE(tanggal)=CURDATE()");
$in_minggu= hitungMasuk($conn, "YEARWEEK(tgl_bayar,1)=YEARWEEK(CURDATE(),1)", "YEARWEEK(tanggal,1)=YEARWEEK(CURDATE(),1)");
$in_bulan = hitungMasuk($conn, "MONTH(tgl_bayar)=MONTH(CURDATE()) AND YEAR(tgl_bayar)=YEAR(CURDATE())", "MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())");
$in_tahun = hitungMasuk($conn, "YEAR(tgl_bayar)=YEAR(CURDATE())", "YEAR(tanggal)=YEAR(CURDATE())");

$per = $_GET['per'] ?? 'bulanan';
$sel_tgl = $_GET['tgl'] ?? date('Y-m-d');
$sel_bln = (int)($_GET['bln'] ?? date('m'));
$sel_thn = (int)($_GET['thn'] ?? date('Y'));

$w_kas = "1=1"; $w_tgh = "status='lunas'";
if ($per == 'harian')   { $w_kas = "DATE(tanggal)='$sel_tgl'"; $w_tgh .= " AND DATE(tgl_bayar)='$sel_tgl'"; }
if ($per == 'mingguan') { $w_kas = "YEARWEEK(tanggal,1)=YEARWEEK('$sel_tgl',1)"; $w_tgh .= " AND YEARWEEK(tgl_bayar,1)=YEARWEEK('$sel_tgl',1)"; }
if ($per == 'bulanan')  { $w_kas = "MONTH(tanggal)=$sel_bln AND YEAR(tanggal)=$sel_thn"; $w_tgh .= " AND MONTH(tgl_bayar)=$sel_bln AND YEAR(tgl_bayar)=$sel_thn"; }
if ($per == 'tahunan')  { $w_kas = "YEAR(tanggal)=$sel_thn"; $w_tgh .= " AND YEAR(tgl_bayar)=$sel_thn"; }

$sum_in_per  = (mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(jumlah) as t FROM tagihan WHERE $w_tgh"))['t'] ?? 0) + (mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(jumlah) as t FROM keuangan WHERE tipe='masuk' AND $w_kas"))['t'] ?? 0);
$sum_out_per = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(jumlah) as t FROM keuangan WHERE tipe='keluar' AND $w_kas"))['t'] ?? 0;
$nama_bln = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>
<div class="tiles">
  <div class="tile t-blue"><h2>Rp <?= number_format($in_hari) ?></h2><p>Pemasukan Hari Ini</p></div>
  <div class="tile t-green"><h2>Rp <?= number_format($in_minggu) ?></h2><p>Pemasukan Minggu Ini</p></div>
  <div class="tile t-yellow"><h2>Rp <?= number_format($in_bulan) ?></h2><p>Pemasukan Bulan Ini</p></div>
  <div class="tile t-red"><h2>Rp <?= number_format($in_tahun) ?></h2><p>Pemasukan Tahun Ini</p></div>
</div>

<div class="card">
  <h4>Filter & Download Laporan Keuangan PDF</h4>
  <form method="GET" style="margin:10px 0;display:flex;flex-wrap:wrap;gap:6px;align-items:center">
    <input type="hidden" name="page" value="keuangan">
    <select name="per" onchange="this.form.submit()">
      <option value="harian" <?= $per=='harian'?'selected':'' ?>>Laporan Harian</option>
      <option value="mingguan" <?= $per=='mingguan'?'selected':'' ?>>Laporan Mingguan</option>
      <option value="bulanan" <?= $per=='bulanan'?'selected':'' ?>>Laporan Bulanan</option>
      <option value="tahunan" <?= $per=='tahunan'?'selected':'' ?>>Laporan Tahunan</option>
      <option value="all" <?= $per=='all'?'selected':'' ?>>Semua Transaksi</option>
    </select>
    <?php if($per=='harian' || $per=='mingguan'): ?>
      <input type="date" name="tgl" value="<?= htmlspecialchars($sel_tgl) ?>" onchange="this.form.submit()">
    <?php endif; ?>
    <?php if($per=='bulanan'): ?>
      <select name="bln" onchange="this.form.submit()">
        <?php foreach($nama_bln as $k=>$v) echo "<option value='$k' ".($sel_bln==$k?'selected':'').">$v</option>"; ?>
      </select>
    <?php endif; ?>
    <?php if($per=='bulanan' || $per=='tahunan'): ?>
      <select name="thn" onchange="this.form.submit()">
        <?php for($y=2024; $y<=date('Y')+1; $y++) echo "<option value='$y' ".($sel_thn==$y?'selected':'').">$y</option>"; ?>
      </select>
    <?php endif; ?>
    <a href="cetak_keuangan_pdf.php?per=<?= $per ?>&tgl=<?= $sel_tgl ?>&bln=<?= $sel_bln ?>&thn=<?= $sel_thn ?>" target="_blank" class="btn btn-r">Download PDF (<?= strtoupper($per) ?>)</a>
  </form>

  <div style="background:#f8fafc;padding:10px;border-radius:6px;margin-bottom:12px;font-size:13px">
    Rekap Terpilih &rarr; Saldo Masuk: <b style="color:#00a65a">Rp <?= number_format($sum_in_per) ?></b> | Saldo Keluar: <b style="color:#dd4b39">Rp <?= number_format($sum_out_per) ?></b> | Sisa Saldo: <b style="color:#0284c7">Rp <?= number_format($sum_in_per - $sum_out_per) ?></b>
  </div>

  <form method="POST" style="margin-bottom:12px">
    <select name="tipe"><option value="keluar">Saldo Keluar (Pengeluaran)</option><option value="masuk">Saldo Masuk (Pemasukan Lain)</option></select>
    <input name="kat" placeholder="Kategori (Bandwidth/Alat/Gaji)" required>
    <input name="ket" placeholder="Keterangan detail" required>
    <input name="jml" type="number" placeholder="Nominal (Rp)" required>
    <button name="add_kas" class="btn btn-g">+ Simpan Transaksi</button>
  </form>

  <div style="overflow-x:auto">
  <table><tr><th>Tanggal</th><th>Tipe</th><th>Kategori</th><th>Keterangan</th><th>Nominal</th></tr>
  <?php
  $q = mysqli_query($conn, "(SELECT tanggal as tgl, tipe, kategori, keterangan, jumlah FROM keuangan WHERE $w_kas) UNION ALL (SELECT t.tgl_bayar as tgl, 'masuk' as tipe, 'Invoice Bulanan' as kategori, CONCAT(t.no_invoice, ' - ', p.nama) as keterangan, t.jumlah FROM tagihan t JOIN pelanggan p ON t.pelanggan_id=p.id WHERE $w_tgh) ORDER BY tgl DESC LIMIT 100");
  while($d = mysqli_fetch_assoc($q)) {
      $clr = $d['tipe']=='masuk' ? '#00a65a' : '#dd4b39';
      $sign = $d['tipe']=='masuk' ? '+' : '-';
      echo "<tr><td>".date('d/m/Y H:i',strtotime($d['tgl']))."</td><td><b style='color:$clr'>".strtoupper($d['tipe'])."</b></td><td>$d[kategori]</td><td>$d[keterangan]</td><td style='color:$clr'><b>$sign Rp ".number_format($d['jumlah'])."</b></td></tr>";
  }
  ?>
  </table>
  </div>
</div>
