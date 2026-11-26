<?php
$cap_now = (string)rand(10000,99999);
$bg_pc = file_exists('/var/www/html/assets/bg_pc.jpg') ? 'assets/bg_pc.jpg?v='.filemtime('/var/www/html/assets/bg_pc.jpg') : '';
$bg_hp = file_exists('/var/www/html/assets/bg_hp.jpg') ? 'assets/bg_hp.jpg?v='.filemtime('/var/www/html/assets/bg_hp.jpg') : $bg_pc;
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login - D.N.A_Billing</title>
<link rel="manifest" href="manifest.json"><meta name="theme-color" content="#0f172a">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}
body{
  min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;
  background-color:#0f172a;background-size:cover;background-position:center;background-repeat:no-repeat;
  background-image:url('<?= $bg_pc ?>');
}
@media (max-width: 768px) {
  body{ background-image:url('<?= $bg_hp ?>'); }
}
.box{
  width:100%;max-width:380px;background:rgba(255,255,255,0.92);backdrop-filter:blur(6px);
  border-radius:14px;padding:28px 24px;box-shadow:0 20px 45px rgba(0,0,0,0.5);border:1px solid rgba(255,255,255,0.7)
}
.logo{text-align:center;margin-bottom:14px}.logo h2{color:#0284c7;font-size:26px;font-weight:900}
.logo small{display:block;color:#1e293b;font-size:11px;font-weight:800;letter-spacing:1px}
.sub{text-align:center;color:#dc2626;font-size:12px;margin-bottom:16px;font-weight:700}
label{display:block;font-size:13px;color:#334155;margin-bottom:4px;font-weight:700}
input[type=text],input[type=password]{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;margin-bottom:14px;background:#fff}
.cap-row{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.cap-img{background:#e2e8f0;color:#0f172a;font-family:monospace;font-size:20px;font-weight:900;letter-spacing:4px;padding:8px 14px;border-radius:8px;text-decoration:line-through;user-select:none}
.chk{font-size:13px;color:#334155;margin-bottom:18px;display:flex;align-items:center;gap:8px;font-weight:600}
.btn{width:100%;padding:12px;border:none;border-radius:8px;background:linear-gradient(90deg,#0284c7,#4f46e5);color:#fff;font-weight:800;font-size:14px;cursor:pointer}
.copy{margin-top:16px;color:#f8fafc;font-size:11px;text-align:center;text-shadow:0 1px 4px rgba(0,0,0,0.9);font-weight:600}
</style></head><body>
<div class="box">
  <div class="logo"><h2>D.N.A_Billing</h2><small>D.N.A_NETWORK BY MSP_NET</small></div>
  <div class="sub"><?= isset($err) ? $err : 'Silakan Login ke Sistem' ?></div>
  <form method="POST">
    <input type="hidden" name="cap_key" value="<?= $cap_now ?>">
    <label>Username</label><input type="text" name="username" placeholder="owner / admin / teknisi" required>
    <label>Password</label><input type="password" id="pw" name="password" placeholder="Type your password" required>
    <label>Captcha</label>
    <div class="cap-row"><div class="cap-img"><?= $cap_now ?></div><input type="text" name="captcha" placeholder="Ketik angka" style="margin:0" required></div>
    <div class="chk"><input type="checkbox" onclick="pw.type=this.checked?'text':'password'"> Show Password</div>
    <button class="btn" name="login">SIGN IN</button>
  </form><div style="margin-top:12px;text-align:center"><a href="cek_tagihan.php" style="color:#38bdf8;font-size:12px;text-decoration:none;font-weight:600">Portal Cek Tagihan Pelanggan &rarr;</a></div>
</div>
<div class="copy">Copyright &copy; <?= date('Y') ?> D.N.A_NETWORK BY MSP_NET</div>
</body></html>
