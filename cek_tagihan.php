<?php
error_reporting(0);
require_once __DIR__ . "/core/config.php";
$cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1")) ?: [];
$nama_isp = $cfg['nama_isp'] ?? 'D.N.A_Billing';
$bank = $cfg['info_bank'] ?? '-';
$ewallet = $cfg['info_ewallet'] ?? '-';
$qris = $cfg['url_qris'] ?? '';
$no_cs = preg_replace('/\D/', '', $cfg['no_cs'] ?? '62895630720504');
if (strpos($no_cs, '0') === 0) $no_cs = '62' . substr($no_cs, 1);

$cols = [];
$qc = mysqli_query($conn, "SHOW COLUMNS FROM pelanggan");
while ($rc = mysqli_fetch_assoc($qc)) $cols[] = $rc['Field'];
$hp_col = in_array('no_wa', $cols) ? 'no_wa' : (in_array('no_hp', $cols) ? 'no_hp' : (in_array('telepon', $cols) ? 'telepon' : 'nama'));
$usr_col = in_array('pppoe_user', $cols) ? 'pppoe_user' : (in_array('username', $cols) ? 'username' : 'nama');

$q = trim($_GET['q'] ?? '');
$pel = null; $tagihan = []; $total_tunggakan = 0;
if ($q !== '') {
    $sq = mysqli_real_escape_string($conn, $q);
    $qp = mysqli_query($conn, "SELECT p.*, pk.nama_paket, pk.harga FROM pelanggan p LEFT JOIN paket pk ON p.paket_id=pk.id WHERE p.nama LIKE '%$sq%' OR p.`$hp_col` LIKE '%$sq%' OR p.`$usr_col`='$sq' LIMIT 1");
    if ($qp) $pel = mysqli_fetch_assoc($qp);
    if ($pel) {
        $qt = mysqli_query($conn, "SELECT * FROM tagihan WHERE pelanggan_id=".(int)$pel['id']." ORDER BY id DESC LIMIT 6");
        while ($qt && ($rt = mysqli_fetch_assoc($qt))) {
            $tagihan[] = $rt;
            if ($rt['status'] !== 'lunas') $total_tunggakan += (float)$rt['jumlah'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cek Tagihan - <?= htmlspecialchars($nama_isp) ?></title>
<style>
  *{box-sizing:border-box;font-family:system-ui,-apple-system,sans-serif;margin:0;padding:0}
  body{background:#0f172a;color:#f8fafc;padding:16px;min-height:100vh;display:flex;flex-direction:column;align-items:center}
  .card{width:100%;max-width:440px;background:#1e293b;border:1px solid #334155;border-radius:16px;padding:22px;margin-top:10px;box-shadow:0 12px 30px rgba(0,0,0,0.45)}
  h2{font-size:20px;color:#38bdf8;text-align:center;margin-bottom:4px}
  .sub{font-size:12px;color:#94a3b8;text-align:center;margin-bottom:18px}
  input{width:100%;padding:12px;background:#0f172a;border:1px solid #475569;border-radius:10px;color:#fff;font-size:14px;margin-bottom:10px}
  .btn{display:block;width:100%;padding:12px;background:#0284c7;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;text-align:center;text-decoration:none}
  .btn-wa{background:#16a34a;margin-top:14px}
  .info{background:#0f172a;border-radius:12px;padding:14px;margin-top:14px;font-size:13px;line-height:1.65;border:1px solid #334155}
  .badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700}
  .lunas{background:#064e3b;color:#6ee7b7} .belum{background:#7f1d1d;color:#fca5a5}
  table{width:100%;border-collapse:collapse;margin-top:12px;font-size:12px}
  th,td{padding:9px 6px;border-bottom:1px solid #334155;text-align:left}
  .qris{max-width:190px;border-radius:10px;margin:10px auto 0;display:block;background:#fff;padding:6px}
</style>
</head>
<body>
<div class="card">
  <h2><?= htmlspecialchars($nama_isp) ?></h2>
  <div class="sub">Portal Cek Tagihan & Pembayaran Mandiri</div>
  <form method="GET">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ketik Nama, No. WA, atau User PPPoE..." required>
    <button type="submit" class="btn">Cek Tagihan Sekarang</button>
  </form>
  <?php if ($q !== '' && !$pel): ?>
    <div class="info" style="color:#fca5a5;text-align:center">Data pelanggan tidak ditemukan. Periksa kembali ejaan nama atau nomor HP Anda.</div>
  <?php elseif ($pel): ?>
    <div class="info">
      <div><b>Pelanggan:</b> <?= htmlspecialchars($pel['nama']) ?></div>
      <div><b>Paket:</b> <?= htmlspecialchars($pel['nama_paket'] ?? '-') ?> (Rp <?= number_format($pel['harga'] ?? 0,0,',','.') ?>/bln)</div>
      <div><b>Status Layanan:</b> <span class="badge <?= ($pel['status']??'')=='isolir'?'belum':'lunas' ?>"><?= strtoupper($pel['status'] ?? 'AKTIF') ?></span></div>
      <div style="margin-top:12px;padding:14px;border-radius:10px;background:#1e293b;border:1px solid #38bdf8">
        <div style="font-size:14px;font-weight:800;color:#38bdf8;margin-bottom:6px">Informasi Pembayaran Tagihan</div>
        <p style="font-size:12.5px;color:#cbd5e1;margin-bottom:10px;line-height:1.5">Silakan lakukan pembayaran tagihan Wi-Fi Anda melalui transfer rekening, E-Wallet, atau scan kode QRIS di bawah ini agar layanan internet tetap aktif lancar:</p>
        <div style="background:#0f172a;padding:10px 12px;border-radius:8px;margin-bottom:8px;border-left:4px solid #38bdf8">
          <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:700">Transfer Bank</div>
          <div style="font-size:16px;font-weight:800;color:#ffffff;letter-spacing:0.3px;margin-top:2px"><?= htmlspecialchars($bank) ?></div>
        </div>
        <div style="background:#0f172a;padding:10px 12px;border-radius:8px;margin-bottom:10px;border-left:4px solid #22c55e">
          <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:700">Transfer E-Wallet</div>
          <div style="font-size:16px;font-weight:800;color:#ffffff;letter-spacing:0.3px;margin-top:2px"><?= htmlspecialchars($ewallet) ?></div>
        </div>
        <?php if (!empty($qris) && file_exists(__DIR__.'/'.$qris)): ?>
          <div style="text-align:center;margin-top:10px">
            <img src="<?= htmlspecialchars($qris) ?>" class="qris" alt="QRIS">
            <a href="<?= htmlspecialchars($qris) ?>" download="QRIS_Pembayaran_WiFi.png" style="display:inline-block;margin-top:10px;padding:9px 16px;background:#0284c7;color:#fff;font-size:12.5px;font-weight:700;border-radius:8px;text-decoration:none">&#11015; Unduh / Simpan Gambar QRIS</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <table>
      <tr><th>Periode</th><th>Nominal</th><th>Status</th></tr>
      <?php if (empty($tagihan)): ?><tr><td colspan="3" style="text-align:center;color:#94a3b8">Belum ada catatan tagihan tercetak.</td></tr><?php endif; ?><?php foreach ($tagihan as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['bulan_tahun'] ?? '-') ?></td>
        <td>Rp <?= number_format($t['jumlah'] ?? 0,0,',','.') ?></td>
        <td><span class="badge <?= $t['status']=='lunas'?'lunas':'belum' ?>"><?= $t['status']=='lunas'?'LUNAS':'BELUM BAYAR' ?></span><?php if($t['status']=='lunas'): ?> <a href="cetak_invoice_pdf.php?id=<?= (int)$t['id'] ?>" target="_blank" style="display:inline-block;margin-left:4px;padding:2px 7px;background:#0284c7;color:#fff;border-radius:5px;font-size:10px;text-decoration:none;font-weight:700">&#128196; Invoice</a><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php $wa_txt = urlencode("Halo Admin $nama_isp, saya *{$pel['nama']}* ingin konfirmasi pembayaran tagihan Wi-Fi sebesar Rp " . number_format($total_tunggakan ?: ($pel['harga']??0),0,',','.')); ?>
    <a href="https://wa.me/<?= $no_cs ?>?text=<?= $wa_txt ?>" target="_blank" class="btn btn-wa">Konfirmasi Pembayaran via WhatsApp</a>
  <?php endif; ?>
</div>
</body>
</html>
