<?php
require 'core/config.php';
cekAkses(['owner','admin']);

$ids = $_GET['ids'] ?? 'all';
$batch = $_GET['batch'] ?? '';
$cs_wa = $_GET['cs'] ?? '0895 6307 20504';
$dns_hs = 'http://dna.net';
$promo = 'Terima Pemasangan Wifi Rumahan, Wifi Voucheran, CCTV DLL. HUBUNGI KONTAK ADMIN D.N.A_NET';

$where = "WHERE status='stok' AND sudah_cetak=0";
if (!empty($batch)) {
    $b_clean = mysqli_real_escape_string($conn, $batch);
    $where = "WHERE batch_kode='$b_clean'";
} elseif ($ids !== 'all') {
    $id_clean = implode(',', array_map('intval', explode(',', $ids)));
    $where = "WHERE id IN ($id_clean)";
}

$q = mysqli_query($conn, "SELECT * FROM voucher $where ORDER BY id ASC");
$vouchers = []; $mark_ids = [];
while ($r = mysqli_fetch_assoc($q)) {
    $vouchers[] = $r;
    $mark_ids[] = (int)$r['id'];
}
if (!empty($mark_ids)) {
    mysqli_query($conn, "UPDATE voucher SET sudah_cetak=1 WHERE id IN (" . implode(',', $mark_ids) . ")");
}

function getTemaWarna($harga, $durasi) {
    $h = (int)$harga;
    if ($h == 2000 || stripos($durasi, '5 jam') !== false) return ['main' => '#86198f']; // Ungu Magenta (2.000 - 5 Jam)
    if ($h == 5000 || stripos($durasi, '24 jam') !== false) return ['main' => '#0284c7']; // Biru Cerah (5.000 - 24 Jam)
    if ($h == 15000 || stripos($durasi, '7 hari') !== false || stripos($durasi, 'minggu') !== false) return ['main' => '#059669']; // Hijau Zamrud (15.000 - 7 Hari)
    if ($h >= 50000 || stripos($durasi, '30 hari') !== false || stripos($durasi, 'bulan') !== false) return ['main' => '#d97706']; // Oranye Emas (50.000 - 30 Hari)
    return ['main' => '#dc2626']; // Merah Ruby (Nominal Lainnya)
}
$chunks = array_chunk($vouchers, 42);
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Voucher_DNA_NET_A4_<?= count($vouchers) ?>_Kartu</title>
<style>
  @page { size: A4 portrait; margin: 4mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Arial Narrow', Arial, sans-serif; }
  body { background: #cbd5e1; padding: 10px; }
  .bar { max-width: 202mm; margin: 0 auto 10px; background: #0f172a; color: #fff; padding: 10px 14px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; }
  .btn-p { background: #16a34a; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; }
  .page-a4 {
    width: 202mm; height: 288mm; margin: 0 auto 12px; background: #fff; padding: 2mm;
    display: grid; grid-template-columns: repeat(6, 1fr); grid-template-rows: repeat(7, 39.5mm); gap: 1.5mm;
    page-break-after: always; break-after: page; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  .page-a4:last-child { page-break-after: auto; break-after: auto; }
  .vc {
    height: 39.5mm; border: 1.6px solid var(--c-main); border-radius: 2px; display: flex; flex-direction: column;
    justify-content: space-between; overflow: hidden; background: #fff;
  }
  .vc-top { display: flex; justify-content: space-between; align-items: baseline; padding: 2px 3px 0; }
  .vc-logo { font-weight: 900; font-size: 9.5px; color: var(--c-main); font-style: italic; letter-spacing: -0.4px; }
  .vc-price { font-weight: 900; font-size: 10.5px; color: #1e293b; }
  .vc-price small { font-size: 6px; vertical-align: super; margin-right: 1px; font-weight: 800; }
  .vc-mid { display: flex; padding: 1px 3px; gap: 2px; align-items: center; flex: 1; }
  .vc-left { flex: 1; text-align: center; }
  .vc-lbl { font-size: 5.5px; font-weight: 800; color: #64748b; letter-spacing: 0.4px; }
  .vc-code {
    background: #e2e8f0; border-radius: 2px; font-family: 'Arial Black', Arial, sans-serif;
    font-weight: 900; font-size: 9px; color: #0f172a; padding: 2px 1px; margin: 1px 0;
  }
  .vc-info { font-size: 4.3px; line-height: 1.12; color: #0f172a; font-weight: 800; }
  .vc-right { width: 31px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
  .vc-dur { font-size: 4.3px; font-weight: 900; color: #1e293b; margin-bottom: 2px; line-height: 1.1; }
  .vc-qr { width: 25px; height: 25px; border: 0.5px solid #94a3b8; }
  .vc-bot {
    background: var(--c-main); color: #fff; font-size: 5.5px; font-weight: 800;
    text-align: center; padding: 2px 1px; letter-spacing: 0.1px;
  }
  @media print {
    body { background: #fff; padding: 0; }
    .bar { display: none !important; }
    .page-a4 { box-shadow: none; margin: 0; }
  }
</style></head><body>

<div class="bar">
  <div><b>Cetak Voucher A4 Portrait (6x7 = 42 Kartu)</b> &bull; Total: <b><?= count($vouchers) ?> Kartu</b> (<b><?= count($chunks) ?> Lembar</b>)</div>
  <button onclick="window.print()" class="btn-p">Simpan PDF / Print Sekarang</button>
</div>

<?php
if (empty($chunks)) echo "<div style='background:#fff;padding:20px;text-align:center'>Semua voucher sudah dicetak. Silakan generate batch baru atau pilih riwayat batch di dropdown.</div>";
foreach ($chunks as $page_cards):
?>
<div class="page-a4">
  <?php foreach ($page_cards as $v):
    $tm = getTemaWarna((float)$v['harga'], $v['masa_aktif']);
    $qr_data = urlencode($dns_hs . "?username=" . $v['kode'] . "&password=" . $v['kode']);
  ?>
  <div class="vc" style="--c-main:<?= $tm['main'] ?>">
    <div class="vc-top">
      <span class="vc-logo">D.N.A_NET</span>
      <span class="vc-price"><small>Rp</small><?= number_format($v['harga'], 0, ',', '.') ?></span>
    </div>
    <div class="vc-mid">
      <div class="vc-left">
        <div class="vc-lbl">VOUCHER</div>
        <div class="vc-code"><?= htmlspecialchars($v['kode']) ?></div>
        <div class="vc-info"><?= htmlspecialchars($promo) ?></div>
      </div>
      <div class="vc-right">
        <div class="vc-dur">MASA AKTIF : <?= strtoupper(htmlspecialchars($v['masa_aktif'])) ?></div>
        <img class="vc-qr" src="https://qrserver.com<?= $qr_data ?>" alt="QR">
      </div>
    </div>
    <div class="vc-bot">CS: <?= htmlspecialchars($cs_wa) ?></div>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
<script>window.onload = function(){ setTimeout(function(){ window.print(); }, 800); };</script>
</body></html>
