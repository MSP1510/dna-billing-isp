<?php
error_reporting(0);
require_once __DIR__ . "/core/config.php";
$cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1")) ?: [];
$nama_isp = $cfg['nama_isp'] ?? 'D.N.A_Billing';
$logo_src = "";
foreach (["uploads/logo.png","uploads/logo.jpg","uploads/logo.webp","assets/logo.png","assets/img/logo.png"] as $lp) {
    if (file_exists(__DIR__."/".$lp)) { $logo_src = $lp . "?v=" . filemtime(__DIR__."/".$lp); break; }
}
if (!$logo_src && !empty($cfg["logo"]) && file_exists(__DIR__."/".$cfg["logo"])) { $logo_src = $cfg["logo"]; }
$bank = $cfg['info_bank'] ?? '-';
$ewallet = $cfg['info_ewallet'] ?? '-';
$qris = $cfg['url_qris'] ?? '';
$no_cs_raw = $cfg['no_cs'] ?? '0895 6307 20504';
$no_cs = preg_replace('/\D/', '', $no_cs_raw);
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
<title>Portal Pelanggan - <?= htmlspecialchars($nama_isp) ?></title>
<style>
  *{box-sizing:border-box;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;margin:0;padding:0}
  body{background:radial-gradient(circle at top,#1e293b,#090d16);color:#f8fafc;padding:18px 16px 40px;overflow-x:hidden;min-height:100vh;display:flex;flex-direction:column;align-items:center}
  .wrap{width:100%;max-width:450px}
  .hero{text-align:center;margin-bottom:18px}
  .logo-badge{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#0284c7,#38bdf8);display:inline-flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 8px 20px rgba(56,189,248,0.3);margin-bottom:10px}
  h1{font-size:22px;font-weight:800;color:#fff;letter-spacing:0.3px}
  .sub{font-size:13px;color:#94a3b8;margin-top:3px}
  .card{background:rgba(30,41,59,0.9);backdrop-filter:blur(10px);border:1px solid rgba(148,163,184,0.2);border-radius:18px;padding:20px;margin-bottom:14px;box-shadow:0 12px 30px rgba(0,0,0,0.35)}
  label{display:block;font-size:11.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px}
  input{width:100%;padding:13px 14px;background:#0f172a;border:1.5px solid #334155;border-radius:12px;color:#fff;font-size:14.5px;margin-bottom:12px;outline:none}
  input:focus{border-color:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,0.2)}
  .btn{display:block;width:100%;padding:13px;background:linear-gradient(135deg,#0284c7,#2563eb);color:#fff;border:none;border-radius:12px;font-weight:700;font-size:14.5px;cursor:pointer;text-align:center;text-decoration:none;box-shadow:0 6px 16px rgba(37,99,235,0.35)}
  .btn-wa{background:linear-gradient(135deg,#16a34a,#15803d);margin-top:14px;box-shadow:0 6px 16px rgba(22,163,74,0.35)}
  .step{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;font-size:13px;color:#cbd5e1;line-height:1.45}
  .step:last-child{margin-bottom:0}
  .num{min-width:26px;height:26px;border-radius:8px;background:rgba(56,189,248,0.18);color:#38bdf8;font-weight:800;display:flex;align-items:center;justify-content:center;font-size:12px}
  .pel-head{display:flex;justify-content:space-between;align-items:center;padding-bottom:12px;border-bottom:1px solid #334155;margin-bottom:12px}
  .badge{padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800}
  .lunas{background:rgba(16,185,129,0.2);color:#34d399;border:1px solid rgba(16,185,129,0.4)}
  .belum{background:rgba(239,68,68,0.2);color:#f87171;border:1px solid rgba(239,68,68,0.4)}
  .pay-box{background:#0f172a;border:1px solid #38bdf8;border-radius:14px;padding:14px;margin-top:12px}
  .rek-item{background:#1e293b;padding:10px 12px;border-radius:10px;margin-top:8px;border-left:4px solid #38bdf8}
  .qris{max-width:200px;border-radius:12px;margin:12px auto 0;display:block;background:#fff;padding:8px}
  table{width:100%;border-collapse:collapse;margin-top:8px;font-size:12.5px}
  th{color:#94a3b8;font-weight:700;padding:8px 4px;border-bottom:1px solid #334155;text-align:left}
  td{padding:10px 4px;border-bottom:1px solid rgba(51,65,85,0.6)}
</style>
</head>
<body>
<div class="wrap">
  <div class="hero">
    <?php if ($logo_src): ?><img src="<?= htmlspecialchars($logo_src) ?>" style="width:74px;height:74px;object-fit:contain;border-radius:18px;background:rgba(255,255,255,0.08);padding:6px;margin-bottom:10px;box-shadow:0 8px 20px rgba(0,0,0,0.35)" alt="Logo"><?php else: ?><div class="logo-badge"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg></div><?php endif; ?>
    <h1><?= htmlspecialchars($nama_isp) ?></h1>
    <div class="sub">Layanan Cek Tagihan & Pembayaran Mandiri</div>
  </div>

  <div class="card">
    <form method="GET">
      <label>Cari Data Pelanggan</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ketik Nama, No. WhatsApp, atau ID PPPoE..." required>
      <button type="submit" class="btn">&#128269; Cek Tagihan Sekarang</button>
    </form>
  </div>

  <?php if ($q === ''): ?>
    <div class="card">
      <div style="font-size:14px;font-weight:700;color:#38bdf8;margin-bottom:12px">Cara Cek & Pembayaran Tagihan:</div>
      <div class="step"><div class="num">1</div><div>Masukkan <b>Nama Lengkap</b>, <b>Nomor WhatsApp</b>, atau <b>Username PPPoE</b> Anda pada kolom di atas lalu tekan tombol cek.</div></div>
      <div class="step"><div class="num">2</div><div>Periksa rincian tagihan, salin <b>Nomor Rekening / E-Wallet</b>, atau tekan tombol <b>Unduh QRIS</b> untuk membayar lewat aplikasi M-Banking / DANA / GoPay.</div></div>
      <div class="step"><div class="num">3</div><div>Tekan tombol <b>Konfirmasi via WhatsApp</b> agar admin segera memverifikasi pembayaran dan mengunduh bukti Invoice lunas Anda.</div></div>
    </div>
  <?php elseif (!$pel): ?>
    <div class="card" style="text-align:center;border-color:#ef4444">
      <div style="font-size:32px;margin-bottom:6px">&#128533;</div>
      <div style="font-weight:700;color:#f87171;margin-bottom:4px">Data Pelanggan Tidak Ditemukan</div>
      <div style="font-size:12.5px;color:#94a3b8">Pastikan ejaan nama sesuai pendaftaran atau gunakan Nomor WhatsApp yang terdaftar.</div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="pel-head">
        <div>
          <div style="font-size:17px;font-weight:800;color:#fff"><?= htmlspecialchars($pel['nama']) ?></div>
          <div style="font-size:12.5px;color:#38bdf8;margin-top:2px"><?= htmlspecialchars($pel['nama_paket'] ?? '-') ?> &bull; Rp <?= number_format($pel['harga'] ?? 0,0,',','.') ?>/bln</div>
        </div>
        <span class="badge <?= ($pel['status']??'')=='isolir'?'belum':'lunas' ?>"><?= strtoupper($pel['status'] ?? 'AKTIF') ?></span>
      </div>

      <div style="font-size:13px;font-weight:700;color:#cbd5e1;margin-bottom:4px">Riwayat Tagihan Terakhir</div>
      <table>
        <tr><th>Periode</th><th>Nominal</th><th>Status</th></tr>
        <?php if (empty($tagihan)): ?>
          <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:14px">Belum ada tagihan tercetak untuk akun ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($tagihan as $t): ?>
        <tr>
          <td><b><?= htmlspecialchars($t['bulan_tahun'] ?? '-') ?></b></td>
          <td>Rp <?= number_format($t['jumlah'] ?? 0,0,',','.') ?></td>
          <td>
            <span class="badge <?= $t['status']=='lunas'?'lunas':'belum' ?>"><?= $t['status']=='lunas'?'LUNAS':'BELUM' ?></span>
            <?php if($t['status']=='lunas'): ?><a href="cetak_invoice_pdf.php?id=<?= (int)$t['id'] ?>" target="_blank" style="display:inline-block;margin-left:4px;padding:3px 7px;background:#0284c7;color:#fff;border-radius:6px;font-size:10.5px;text-decoration:none;font-weight:700">&#128196; Invoice</a><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>

      <div class="pay-box">
        <div style="font-size:14px;font-weight:800;color:#38bdf8">Metode Pembayaran Tagihan</div>
        <p style="font-size:12px;color:#94a3b8;margin-top:4px;line-height:1.45">Silakan transfer sesuai nominal tagihan ke rekening di bawah ini atau unduh kode QRIS untuk pembayaran instan:</p>
        <div class="rek-item">
          <div style="font-size:10.5px;color:#94a3b8;font-weight:700">TRANSFER BANK</div>
          <div style="font-size:15.5px;font-weight:800;color:#fff;margin-top:2px"><?= htmlspecialchars($bank) ?></div>
        </div>
        <div class="rek-item" style="border-left-color:#22c55e">
          <div style="font-size:10.5px;color:#94a3b8;font-weight:700">E-WALLET (DANA / OVO / GOPAY)</div>
          <div style="font-size:15.5px;font-weight:800;color:#fff;margin-top:2px"><?= htmlspecialchars($ewallet) ?></div>
        </div>
        <?php if (!empty($qris) && file_exists(__DIR__.'/'.$qris)): ?>
          <div style="text-align:center">
            <img src="<?= htmlspecialchars($qris) ?>" class="qris" alt="QRIS">
            <a href="<?= htmlspecialchars($qris) ?>" download="QRIS_<?= preg_replace('/\W+/','_',$nama_isp) ?>.png" style="display:inline-block;margin-top:10px;padding:9px 16px;background:#0284c7;color:#fff;font-size:12.5px;font-weight:700;border-radius:9px;text-decoration:none">&#11015; Unduh / Simpan Gambar QRIS</a>
          </div>
        <?php endif; ?>
      </div>

      <?php $wa_txt = urlencode("Halo Admin $nama_isp, saya *{$pel['nama']}* ingin konfirmasi pembayaran tagihan Wi-Fi sebesar Rp " . number_format($total_tunggakan ?: ($pel['harga']??0),0,',','.')); ?>
      <a href="https://wa.me/<?= $no_cs ?>?text=<?= $wa_txt ?>" target="_blank" class="btn btn-wa">&#128172; Konfirmasi Pembayaran via WhatsApp</a>
    </div>
  <?php endif; ?>

  <div style="text-align:center;font-size:12px;color:#64748b;margin-top:8px">Layanan Bantuan CS: <b style="color:#94a3b8"><?= htmlspecialchars($no_cs_raw) ?></b></div>
</div>
</body>
</html>
