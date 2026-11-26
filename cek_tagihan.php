<?php
require_once __DIR__ . "/core/config.php";
$cfg = [];
$qc = mysqli_query($conn, "SELECT * FROM pengaturan");
while ($rc = mysqli_fetch_assoc($qc)) $cfg[$rc['kunci']] = $rc['nilai'];
$nama_usaha = $cfg['nama_usaha'] ?? 'D.N.A_NETWORK';
$no_cs = $cfg['no_cs'] ?? '0895 6307 20504';
$rekening = $cfg['rekening_bank'] ?? ($cfg['bank'] ?? 'Hubungi Admin untuk Nomor Rekening');

$q = trim($_GET['q'] ?? '');
$pel = null; $tagihan = [];
if ($q !== '') {
    $sq = mysqli_real_escape_string($conn, $q);
    $qp = mysqli_query($conn, "SELECT p.*, pk.nama_paket, pk.harga FROM pelanggan p LEFT JOIN paket pk ON p.paket_id=pk.id WHERE p.nama LIKE '%$sq%' OR p.no_hp LIKE '%$sq%' OR p.pppoe_user='$sq' LIMIT 1");
    $pel = mysqli_fetch_assoc($qp);
    if ($pel) {
        $qt = mysqli_query($conn, "SELECT * FROM tagihan WHERE pelanggan_id=".(int)$pel['id']." ORDER BY id DESC LIMIT 6");
        while ($rt = mysqli_fetch_assoc($qt)) $tagihan[] = $rt;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cek Tagihan Wi-Fi - <?= htmlspecialchars($nama_usaha) ?></title>
<style>
  *{box-sizing:border-box;font-family:system-ui,-apple-system,sans-serif;margin:0;padding:0}
  body{background:#0f172a;color:#f8fafc;padding:16px;min-height:100vh;display:flex;flex-direction:column;align-items:center}
  .card{width:100%;max-width:440px;background:#1e293b;border:1px solid #334155;border-radius:16px;padding:22px;margin-top:12px;box-shadow:0 12px 30px rgba(0,0,0,0.4)}
  h2{font-size:19px;color:#38bdf8;text-align:center;margin-bottom:4px}
  .sub{font-size:12px;color:#94a3b8;text-align:center;margin-bottom:18px}
  input{width:100%;padding:12px;background:#0f172a;border:1px solid #475569;border-radius:10px;color:#fff;font-size:14px;margin-bottom:10px}
  button{width:100%;padding:12px;background:#0284c7;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer}
  .info{background:#0f172a;border-radius:10px;padding:12px;margin-top:14px;font-size:13px;line-height:1.6}
  .badge{display:inline-block;padding:3px 9px;border-radius:6px;font-size:11px;font-weight:700}
  .lunas{background:#064e3b;color:#6ee7b7} .belum{background:#7f1d1d;color:#fca5a5}
  table{width:100%;border-collapse:collapse;margin-top:12px;font-size:12px}
  th,td{padding:8px 6px;border-bottom:1px solid #334155;text-align:left}
</style>
</head>
<body>
<div class="card">
  <h2><?= htmlspecialchars($nama_usaha) ?></h2>
  <div class="sub">Portal Cek Tagihan & Status Layanan Pelanggan</div>
  <form method="GET">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ketik Nama, No. HP, atau Username PPPoE..." required>
    <button type="submit">Cek Tagihan Sekarang</button>
  </form>
  <?php if ($q !== '' && !$pel): ?>
    <div class="info" style="color:#fca5a5;text-align:center">Data pelanggan tidak ditemukan. Pastikan ejaan nama atau nomor HP sesuai.</div>
  <?php elseif ($pel): ?>
    <div class="info">
      <div><b>Nama:</b> <?= htmlspecialchars($pel['nama']) ?></div>
      <div><b>Paket:</b> <?= htmlspecialchars($pel['nama_paket'] ?? '-') ?> (Rp <?= number_format($pel['harga'] ?? 0,0,',','.') ?>/bln)</div>
      <div><b>Status Koneksi:</b> <span class="badge <?= $pel['status']=='isolir'?'belum':'lunas' ?>"><?= strtoupper($pel['status'] ?? 'AKTIF') ?></span></div>
      <div style="margin-top:8px;padding-top:8px;border-top:1px dashed #334155;color:#cbd5e1"><b>Info Pembayaran:</b><br><?= nl2br(htmlspecialchars($rekening)) ?></div>
    </div>
    <table>
      <tr><th>Periode</th><th>Nominal</th><th>Status</th></tr>
      <?php foreach ($tagihan as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['bulan'] ?? $t['periode'] ?? '-') ?></td>
        <td>Rp <?= number_format($t['jumlah'] ?? 0,0,',','.') ?></td>
        <td><span class="badge <?= $t['status']=='lunas'?'lunas':'belum' ?>"><?= strtoupper($t['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  <div style="text-align:center;margin-top:16px;font-size:11px;color:#64748b">Bantuan CS: <?= htmlspecialchars($no_cs) ?></div>
</div>
</body>
</html>
