<?php
require 'core/config.php';
cekAkses(['owner','admin']);

$per = $_GET['per'] ?? 'bulanan';
$sel_tgl = $_GET['tgl'] ?? date('Y-m-d');
$sel_bln = (int)($_GET['bln'] ?? date('m'));
$sel_thn = (int)($_GET['thn'] ?? date('Y'));
$nama_bln = [1=>'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
$w_kas = "1=1"; $w_tgh = "t.status='lunas'";
$judul_per = "SEMUA PERIODE";
if ($per == 'harian')   { $w_kas = "DATE(tanggal)='$sel_tgl'"; $w_tgh .= " AND DATE(t.tgl_bayar)='$sel_tgl'"; $judul_per = "HARIAN (" . date('d/m/Y', strtotime($sel_tgl)) . ")"; }
if ($per == 'mingguan') { $w_kas = "YEARWEEK(tanggal,1)=YEARWEEK('$sel_tgl',1)"; $w_tgh .= " AND YEARWEEK(t.tgl_bayar,1)=YEARWEEK('$sel_tgl',1)"; $judul_per = "MINGGUAN (TGL " . date('d/m/Y', strtotime($sel_tgl)) . ")"; }
if ($per == 'bulanan')  { $w_kas = "MONTH(tanggal)=$sel_bln AND YEAR(tanggal)=$sel_thn"; $w_tgh .= " AND MONTH(t.tgl_bayar)=$sel_bln AND YEAR(t.tgl_bayar)=$sel_thn"; $judul_per = "BULAN " . ($nama_bln[$sel_bln]??'') . " $sel_thn"; }
if ($per == 'tahunan')  { $w_kas = "YEAR(tanggal)=$sel_thn"; $w_tgh .= " AND YEAR(t.tgl_bayar)=$sel_thn"; $judul_per = "TAHUN $sel_thn"; }

$sql = "(SELECT tanggal as tgl, tipe, kategori, keterangan, jumlah FROM keuangan WHERE $w_kas)
        UNION ALL
        (SELECT t.tgl_bayar as tgl, 'masuk' as tipe, 'Invoice' as kategori, CONCAT('PEMBAYARAN ', t.no_invoice, ' - ', p.nama) as keterangan, t.jumlah FROM tagihan t JOIN pelanggan p ON t.pelanggan_id=p.id WHERE $w_tgh)
        ORDER BY tgl ASC";
$q = mysqli_query($conn, $sql);
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan_Keuangan_DNA_Billing_<?= strtoupper($per) ?></title>
<style>
  @page { size: A4 portrait; margin: 14mm; }
  * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
  body { background: #e2e8f0; margin: 0; padding: 16px; color: #0f172a; }
  .toolbar { max-width: 794px; margin: 0 auto 14px; background: #0f172a; color: #fff; padding: 12px 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
  .btn-pdf { background: #dc2626; color: #fff; border: none; padding: 9px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13px; }
  .sheet { max-width: 794px; min-height: 1050px; margin: 0 auto; background: #fff; padding: 28px 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
  .header { text-align: center; border-bottom: 3px double #1e293b; padding-bottom: 12px; margin-bottom: 18px; }
  .header h1 { margin: 0; font-size: 19px; letter-spacing: 0.8px; color: #0f172a; }
  .header .brand { font-size: 12px; font-weight: 700; color: #0284c7; margin-top: 3px; }
  .header .sub { font-size: 11px; color: #475569; margin-top: 4px; }
  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  th, td { border: 1.5px solid #334155; padding: 6px 8px; }
  th { background: #f1f5f9; font-weight: 800; text-align: center; color: #0f172a; font-size: 11px; letter-spacing: 0.5px; }
  .rp { display: flex; justify-content: space-between; font-variant-numeric: tabular-nums; }
  .tot-row td { background: #f8fafc; font-weight: 800; font-size: 12px; border-top: 2px solid #0f172a; }
  .saldo-row td { background: #e0f2fe; font-weight: 800; font-size: 13px; border-bottom: 2px solid #0f172a; }
  .ttd { display: flex; justify-content: space-between; margin-top: 32px; font-size: 12px; text-align: center; }
  .ttd div { width: 200px; }
  @media print {
    body { background: #fff; padding: 0; }
    .toolbar { display: none !important; }
    .sheet { box-shadow: none; padding: 0; max-width: 100%; }
  }
</style></head><body>
<div class="toolbar">
  <div><b>Pratinjau Laporan PDF</b> <small>(Pilih "Simpan sebagai PDF" saat dialog cetak muncul)</small></div>
  <button onclick="window.print()" class="btn-pdf">Download / Cetak PDF</button>
</div>

<div class="sheet">
  <div class="header">
    <h1>LAPORAN KEUANGAN WIFI & ARUS KAS</h1>
    <div class="brand">D.N.A_Billing &bull; D.N.A_NETWORK by MSP_NET</div>
    <div class="sub">Periode Laporan: <b><?= $judul_per ?></b> &nbsp;|&nbsp; Dicetak pada: <?= date('d/m/Y H:i') ?> WIB</div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:38px">NO</th>
        <th style="width:92px">TANGGAL</th>
        <th>KETERANGAN</th>
        <th style="width:135px">PEMASUKAN</th>
        <th style="width:135px">PENGELUARAN</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1; $tot_in = 0; $tot_out = 0;
      while ($d = mysqli_fetch_assoc($q)):
        $is_in = ($d['tipe'] == 'masuk');
        if ($is_in) $tot_in += (float)$d['jumlah']; else $tot_out += (float)$d['jumlah'];
        $ket_full = strtoupper($d['kategori'] . ($d['keterangan'] ? ' - ' . $d['keterangan'] : ''));
      ?>
      <tr>
        <td align="center"><?= $no++ ?></td>
        <td align="center"><?= date('n/j/Y', strtotime($d['tgl'])) ?></td>
        <td><?= htmlspecialchars($ket_full) ?></td>
        <td><?php if($is_in): ?><div class="rp"><span>Rp</span><b><?= number_format($d['jumlah']) ?></b></div><?php endif; ?></td>
        <td><?php if(!$is_in): ?><div class="rp"><span>Rp</span><b><?= number_format($d['jumlah']) ?></b></div><?php endif; ?></td>
      </tr>
      <?php endwhile;
      for ($pad = $no; $pad <= 22; $pad++): ?>
      <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
      <?php endfor;
      $saldo = $tot_in - $tot_out;
      $saldo_txt = ($saldo < 0) ? '(' . number_format(abs($saldo)) . ')' : number_format($saldo);
      ?>
      <tr class="tot-row">
        <td colspan="3" align="center">TOTAL</td>
        <td><div class="rp"><span>Rp</span><span><?= number_format($tot_in) ?></span></div></td>
        <td><div class="rp"><span>Rp</span><span><?= number_format($tot_out) ?></span></div></td>
      </tr>
      <tr class="saldo-row">
        <td colspan="4" align="center" style="letter-spacing:1px">SALDO</td>
        <td><div class="rp"><span>Rp</span><span style="color:<?= $saldo<0?'#dc2626':'#0f172a' ?>"><?= $saldo_txt ?></span></div></td>
      </tr>
    </tbody>
  </table>

  <div class="ttd">
    <div>Mengetahui,<br><b>Owner D.N.A_NETWORK</b><br><br><br><br>( ______________________ )</div>
    <div>Sukadiri, <?= date('d F Y') ?><br><b>Admin Keuangan</b><br><br><br><br>( <?= htmlspecialchars($_SESSION['nama']) ?> )</div>
  </div>
</div>
<script>window.onload = function(){ setTimeout(function(){ window.print(); }, 500); };</script>
</body></html>
