<?php
require_once __DIR__ . "/core/config.php";
$cfg = [];
$qc = mysqli_query($conn, "SELECT * FROM pengaturan");
while ($rc = mysqli_fetch_assoc($qc)) $cfg[$rc['kunci']] = $rc['nilai'];

$nama_wifi = $cfg['nama_usaha'] ?? 'D.N.A_NETWORK';
$dns_login = !empty($cfg['dns_hotspot']) ? $cfg['dns_hotspot'] : 'http://hotspot.login';
if (strpos($dns_login, 'http') !== 0) $dns_login = 'http://' . $dns_login;

$mode = $_GET['mode'] ?? 'semua';
$kode = trim($_GET['kode'] ?? '');
$prof = trim($_GET['prof'] ?? '');

$list = [];
if ($kode !== '') {
    $sk = mysqli_real_escape_string($conn, $kode);
    $qv = mysqli_query($conn, "SELECT * FROM voucher WHERE kode='$sk' LIMIT 1");
    while ($r = mysqli_fetch_assoc($qv)) $list[] = $r;
} elseif ($prof !== '') {
    $sp = mysqli_real_escape_string($conn, $prof);
    $qv = mysqli_query($conn, "SELECT * FROM voucher WHERE profil='$sp' ORDER BY id DESC LIMIT 200");
    while ($r = mysqli_fetch_assoc($qv)) $list[] = $r;
} elseif ($mode == 'baru') {
    $qv = mysqli_query($conn, "SELECT * FROM voucher WHERE sudah_cetak=0 ORDER BY id ASC LIMIT 200");
    while ($r = mysqli_fetch_assoc($qv)) $list[] = $r;
    if (empty($list)) {
        $qv = mysqli_query($conn, "SELECT * FROM voucher ORDER BY id DESC LIMIT 40");
        while ($r = mysqli_fetch_assoc($qv)) $list[] = $r;
    }
    mysqli_query($conn, "UPDATE voucher SET sudah_cetak=1 WHERE sudah_cetak=0");
} else {
    $qv = mysqli_query($conn, "SELECT * FROM voucher ORDER BY id DESC LIMIT 200");
    while ($r = mysqli_fetch_assoc($qv)) $list[] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Cetak Kartu Voucher - <?= htmlspecialchars($nama_wifi) ?></title>
<script src="https://cloudflare.com"></script>
<style>
  @page { size: A4 portrait; margin: 6mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; }
  body { background: #fff; color: #000; }
  .no-print { padding: 10px; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; text-align: center; margin-bottom: 8px; }
  .btn-p { background: #0284c7; color: #fff; border: none; padding: 8px 16px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 13px; }
  .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; width: 100%; }
  .v-card { border: 1.5px solid #222; display: flex; align-items: stretch; height: 76px; page-break-inside: avoid; background: #fff; }
  .v-price { writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; font-weight: 800; font-size: 11px; padding: 3px 2px; border-left: 1px solid #444; background: #f8fafc; white-space: nowrap; }
  .v-body { flex: 1; padding: 3px 5px; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; }
  .v-title { font-size: 10px; font-weight: 700; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .v-code { font-size: 15px; font-weight: 900; text-align: center; letter-spacing: 0.5px; color: #000; margin: 1px 0; }
  .v-foot { font-size: 8px; color: #222; line-height: 1.2; }
  .v-qr { width: 58px; padding: 3px 3px 1px 0; display: flex; flex-direction: column; align-items: center; justify-content: space-between; }
  .qr-box { width: 50px; height: 50px; }
  .qr-box img, .qr-box canvas { width: 50px !important; height: 50px !important; }
  .v-num { font-size: 7.5px; font-weight: 700; align-self: flex-end; color: #333; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="no-print">
  <button class="btn-p" onclick="window.print()">Cetak / Simpan PDF (A4 - 4 Kolom)</button>
  <span style="font-size:12px;margin-left:10px">Total Siap Cetak: <b><?= count($list) ?> Voucher</b></span>
</div>
<div class="grid">
<?php $no = 1; foreach ($list as $v):
  $login_url = $dns_login . "/login?username=" . urlencode($v['kode']) . "&password=" . urlencode($v['kode']);
  $qid = "qr_" . $no;
?>
  <div class="v-card">
    <div class="v-price">Rp <?= number_format($v['harga'], 0, ',', '.') ?></div>
    <div class="v-body">
      <div class="v-title"><?= htmlspecialchars($nama_wifi) ?></div>
      <div class="v-code"><?= htmlspecialchars($v['kode']) ?></div>
      <div class="v-foot">
        Aktif <?= htmlspecialchars($v['masa_aktif']) ?><br>
        Login: <?= htmlspecialchars($dns_login) ?>
      </div>
    </div>
    <div class="v-qr">
      <div id="<?= $qid ?>" class="qr-box" data-url="<?= htmlspecialchars($login_url) ?>"></div>
      <div class="v-num">[<?= $no++ ?>]</div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<script>
document.querySelectorAll('.qr-box').forEach(function(el){
  if (typeof QRCode !== 'undefined') {
    new QRCode(el, { text: el.getAttribute('data-url'), width: 50, height: 50, correctLevel: QRCode.CorrectLevel.L });
  }
});
</script>
</body>
</html>
