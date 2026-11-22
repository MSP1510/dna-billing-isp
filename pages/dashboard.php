<?php
$inv_in = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as t FROM tagihan WHERE status='lunas'"))['t'] ?? 0;
$kas_in = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as t FROM keuangan WHERE tipe='masuk'"))['t'] ?? 0;
$tot_masuk = $inv_in + $kas_in;
$tot_keluar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as t FROM keuangan WHERE tipe='keluar'"))['t'] ?? 0;
$sisa_saldo = $tot_masuk - $tot_keluar;

$inv_blm = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tagihan WHERE status='belum_bayar'"));
$inv_lns = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tagihan WHERE status='lunas'"));
$tot_inv = max(1, $inv_blm + $inv_lns);
$pct_lns = round(($inv_lns / $tot_inv) * 100);

$pel_akt = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM pelanggan WHERE status='aktif'"));
$pel_iso = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM pelanggan WHERE status='isolir'"));
$tot_pel = max(1, $pel_akt + $pel_iso);
$pct_akt = round(($pel_akt / $tot_pel) * 100);

$tot_arus = max(1, $tot_masuk + $tot_keluar);
$pct_msk = round(($tot_masuk / $tot_arus) * 100);
$pct_klr = round(($tot_keluar / $tot_arus) * 100);
$rx_bar = min(100, max(5, $mt_info['rx'] * 2));
$tx_bar = min(100, max(5, $mt_info['tx'] * 2));
?>
<div class="tiles">
  <div class="tile t-green"><h2>Rp <?= number_format($tot_masuk) ?></h2><p>Saldo Masuk (Invoice & Kas)</p></div>
  <div class="tile t-red"><h2>Rp <?= number_format($tot_keluar) ?></h2><p>Saldo Keluar (Operasional)</p></div>
  <div class="tile t-blue"><h2>Rp <?= number_format($sisa_saldo) ?></h2><p>Sisa Saldo / Kas Bersih</p></div>
  <div class="tile t-yellow"><h2><?= $inv_blm ?> Invoice</h2><p>Tagihan Belum Bayar (<?= $ppp_on ?> PPP On)</p></div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:16px">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <h4>Grafik Traffic MikroTik</h4>
      <form method="GET" style="margin:0"><input type="hidden" name="page" value="dashboard">
        <select name="iface" onchange="this.form.submit()" style="padding:4px 8px;font-size:12px">
          <?php if(empty($ifaces)) echo "<option>ether1</option>"; foreach($ifaces as $if) echo "<option value='$if' ".($if==$mt_info['iface']?'selected':'').">$if</option>"; ?>
        </select>
      </form>
    </div>
    <small style="color:#64748b">Router: <b><?= $mt_info['board'] ?></b> (<?= $mt_ok?'Online':'Offline' ?>)</small>
    <div style="margin:12px 0">
      <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px"><span>Download (RX)</span><b style="color:#00a65a"><?= $mt_info['rx'] ?> Mbps</b></div>
      <div style="height:10px;background:#e2e8f0;border-radius:6px;overflow:hidden"><div style="width:<?= $rx_bar ?>%;height:100%;background:#10b981"></div></div>
    </div>
    <div style="margin:12px 0">
      <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px"><span>Upload (TX)</span><b style="color:#0284c7"><?= $mt_info['tx'] ?> Mbps</b></div>
      <div style="height:10px;background:#e2e8f0;border-radius:6px;overflow:hidden"><div style="width:<?= $tx_bar ?>%;height:100%;background:#0284c7"></div></div>
    </div>
    <svg viewBox="0 0 300 65" style="width:100%;height:65px;background:#f8fafc;border-radius:6px">
      <polyline fill="none" stroke="#10b981" stroke-width="2.5" points="0,55 50,42 100,48 150,25 200,35 250,18 300,<?= max(10,60-$rx_bar/2) ?>"/>
      <polyline fill="none" stroke="#0284c7" stroke-width="2.5" points="0,62 50,55 100,58 150,45 200,50 250,38 300,<?= max(15,65-$tx_bar/2) ?>"/>
    </svg>
    <div style="text-align:right;margin-top:6px"><a href="?page=dashboard&iface=<?= $mt_info['iface'] ?>" class="btn" style="padding:4px 10px;font-size:11px">Refresh Traffic</a></div>
  </div>

  <div class="card">
    <h4>Arus Kas (Saldo Masuk vs Keluar)</h4>
    <small style="color:#64748b">Ringkasan keuangan D.N.A_Billing</small>
    <div style="margin:14px 0;font-size:12px">
      <div style="display:flex;justify-content:space-between"><span>Saldo Masuk (<?= $pct_msk ?>%)</span><b style="color:#00a65a">Rp <?= number_format($tot_masuk) ?></b></div>
      <div style="height:10px;background:#e2e8f0;border-radius:5px;margin:4px 0 12px;overflow:hidden"><div style="width:<?= $pct_msk ?>%;height:100%;background:#00a65a"></div></div>
      <div style="display:flex;justify-content:space-between"><span>Saldo Keluar (<?= $pct_klr ?>%)</span><b style="color:#dd4b39">Rp <?= number_format($tot_keluar) ?></b></div>
      <div style="height:10px;background:#e2e8f0;border-radius:5px;margin:4px 0 12px;overflow:hidden"><div style="width:<?= $pct_klr ?>%;height:100%;background:#dd4b39"></div></div>
      <div style="padding:10px;background:#f8fafc;border-radius:6px;display:flex;justify-content:space-between"><span><b>Sisa Saldo Bersih:</b></span><b style="color:#0284c7">Rp <?= number_format($sisa_saldo) ?></b></div>
    </div>
  </div>

  <div class="card">
    <h4>Kesehatan Router NAS (CPU & RAM)</h4>
    <small style="color:#64748b">RouterOS v<?= $mt_info['ver'] ?> &bull; Uptime: <?= $mt_info['uptime'] ?></small>
    <div style="display:flex;justify-content:space-around;align-items:center;margin-top:16px;text-align:center">
      <div><div style="width:90px;height:90px;border-radius:50%;background:conic-gradient(#6366f1 0% <?= $mt_info['cpu'] ?>%,#e2e8f0 <?= $mt_info['cpu'] ?>% 100%);display:flex;align-items:center;justify-content:center;margin:auto"><div style="width:64px;height:64px;background:#fff;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center"><b><?= $mt_info['cpu'] ?>%</b><small style="font-size:10px">CPU</small></div></div></div>
      <div><div style="width:90px;height:90px;border-radius:50%;background:conic-gradient(#f59e0b 0% <?= $mt_info['ram_pct'] ?>%,#e2e8f0 <?= $mt_info['ram_pct'] ?>% 100%);display:flex;align-items:center;justify-content:center;margin:auto"><div style="width:64px;height:64px;background:#fff;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center"><b><?= $mt_info['ram_pct'] ?>%</b><small style="font-size:10px">RAM</small></div></div></div>
    </div>
    <p style="font-size:12px;color:#475569;margin-top:14px;text-align:center">IP: <b><?= $cfg['mt_ip'] ?></b> (<?= $mt_ok?'<span style="color:#00a65a">Terhubung</span>':'<span style="color:#dd4b39">Terputus</span>' ?>) &bull; PPP: <b><?= $ppp_on ?></b> | HS: <b><?= $hs_on ?></b></p>
  </div>
</div>
