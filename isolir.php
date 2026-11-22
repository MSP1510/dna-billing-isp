<?php
require 'core/config.php';
$cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan WHERE id=1"));
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Layanan Terisolir - D.N.A_NET</title><style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}
body{min-height:100vh;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;padding:20px}
.box{max-width:420px;width:100%;background:#1e293b;border:2px solid #ef4444;border-radius:14px;padding:26px;text-align:center;box-shadow:0 15px 35px rgba(0,0,0,0.5)}
.badge{background:#ef4444;color:#fff;font-size:11px;font-weight:800;padding:4px 12px;border-radius:20px;display:inline-block;margin-bottom:12px;letter-spacing:0.5px}
h2{font-size:20px;margin-bottom:8px;color:#f8fafc}
p{font-size:13px;color:#cbd5e1;line-height:1.5;margin-bottom:16px}
.pay{background:#0f172a;border-radius:8px;padding:12px;text-align:left;font-size:12px;margin-bottom:16px;border:1px solid #334155}
.pay div{margin-bottom:6px}
.btn{display:block;width:100%;padding:12px;background:#16a34a;color:#fff;text-decoration:none;border-radius:8px;font-weight:700;font-size:13px}
</style></head><body>
<div class="box">
  <div class="badge">LAYANAN INTERNET TERISOLIR</div>
  <h2>Tagihan Wi-Fi Belum Dibayarkan</h2>
  <p>Mohon maaf, koneksi internet <b>D.N.A_NET</b> Anda sedang dinonaktifkan sementara karena melewati batas jatuh tempo pembayaran.</p>
  <div class="pay">
    <div><b>Transfer Bank:</b><br><span style="color:#38bdf8"><?= htmlspecialchars($cfg['info_bank']??'-') ?></span></div>
    <div><b>E-Wallet:</b><br><span style="color:#38bdf8"><?= htmlspecialchars($cfg['info_ewallet']??'-') ?></span></div>
    <?php if(!empty($cfg['url_qris'])): ?>
      <div style="text-align:center;margin-top:8px"><img src="<?= htmlspecialchars($cfg['url_qris']) ?>" style="max-width:160px;border-radius:6px"><br><small>Scan QRIS di atas untuk bayar instan</small></div>
    <?php endif; ?>
  </div>
  <a href="https://wa.me" class="btn">Konfirmasi Pembayaran via WhatsApp</a>
</div>
</body></html>
