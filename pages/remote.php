<?php cekAkses(['owner','admin','teknisi']);
$online_list = [];

$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        // PPPoE Online
        $ppp = $api->comm('/ppp/active/print');
        $cur = [];
        foreach ($ppp as $l) {
            if ($l == '!re') { if (!empty($cur['ip'])) $online_list[] = $cur; $cur = ['tipe'=>'PPPoE']; }
            elseif (strpos($l,'=name=')===0) $cur['nama'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
        }
        if (!empty($cur['ip'])) $online_list[] = $cur;
        $api->disconnect();
    }
}

// Static IP dari Database Pelanggan
$qs = mysqli_query($conn, "SELECT nama, ip_static FROM pelanggan WHERE tipe_layanan='static' AND ip_static != '' ORDER BY nama ASC");
while ($s = mysqli_fetch_assoc($qs)) {
    $online_list[] = ['nama' => $s['nama'], 'ip' => trim($s['ip_static']), 'tipe' => 'Static IP'];
}
?>
<div class="card">
  <h4>Remote Halaman Login Modem / Router Pelanggan (ONT / AP)</h4>
  <p style="font-size:12px;color:#475569;margin:4px 0 12px">Buka halaman pengaturan modem pelanggan (ZTE, Huawei, FiberHome, TP-Link, Tenda) dari luar jaringan untuk mengganti nama Wi-Fi atau password pelanggan.</p>

  <form action="remote_proxy.php" method="GET" target="_blank" style="background:#f8fafc;padding:12px;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:14px;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
    <div>
      <small><b>Pilih Pelanggan / Ketik IP Modem:</b></small><br>
      <input name="set_target" id="ip_rem" placeholder="Contoh: 10.10.10.25" required style="width:180px">
    </div>
    <div>
      <small><b>Port Web Modem:</b></small><br>
      <select name="port" id="port_rem">
        <option value="80" selected>Port 80 (HTTP Standar)</option>
        <option value="8080">Port 8080 (ONT / TP-Link)</option>
        <option value="443">Port 443 (HTTPS ZTE/Huawei)</option>
      </select>
    </div>
    <div style="align-self:flex-end">
      <button class="btn btn-g">Buka Halaman Modem</button>
    </div>
  </form>

  <div style="overflow-x:auto">
  <table>
    <tr><th>Nama / User Pelanggan</th><th>Layanan</th><th>IP Modem Aktif</th><th>Aksi Remote Cepat</th></tr>
    <?php foreach ($online_list as $m): ?>
    <tr>
      <td><b><?= htmlspecialchars($m['nama']) ?></b></td>
      <td><span style="background:<?= $m['tipe']=='PPPoE'?'#0284c7':'#8b5cf6' ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= $m['tipe'] ?></span></td>
      <td style="font-family:monospace;font-weight:700;color:#0284c7"><?= htmlspecialchars($m['ip']) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=80" target="_blank" class="btn btn-g" style="padding:4px 8px;font-size:11px">Port 80</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=8080" target="_blank" class="btn" style="padding:4px 8px;font-size:11px">Port 8080</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=443" target="_blank" class="btn btn-w" style="padding:4px 8px;font-size:11px">Port 443</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>
