<?php cekAkses(['owner','admin','teknisi']);

$active_ips = [];
$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        $act = $api->comm('/ppp/active/print');
        $cur = [];
        foreach ($act as $l) {
            if ($l == '!re') { if(!empty($cur['name'])) $active_ips[$cur['name']] = $cur['addr'] ?? ''; $cur = []; }
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['addr'] = substr($l,9);
        }
        if (!empty($cur['name'])) $active_ips[$cur['name']] = $cur['addr'] ?? '';
        $api->disconnect();
    }
}
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">
    <div>
      <h4>Remote Modem / Router Pelanggan (Akses Luar Jaringan)</h4>
      <small style="color:#64748b">Klik tombol <b>Remote Web ONT</b> untuk membuka halaman login modem pelanggan dan mengganti SSID / Password Wi-Fi dari jarak jauh.</small>
    </div>
    <input type="text" id="cariRmt" onkeyup="filterRmt()" placeholder="Cari nama / user / IP..." style="width:200px">
  </div>

  <form method="GET" action="remote_proxy.php" target="_blank" style="background:#f8fafc;padding:10px;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;align-items:center">
    <b style="font-size:12px">Remote IP Manual:</b>
    <input name="set_target" placeholder="Contoh: 10.10.10.25 atau 10.10.10.25:8080" style="width:240px" required>
    <button class="btn btn-g">Buka Halaman Modem</button>
  </form>

  <div style="overflow-x:auto">
  <table id="tblRmt">
    <tr><th>Nama Pelanggan</th><th>User PPPoE / Layanan</th><th>IP Modem Aktif</th><th>Status Koneksi</th><th>Aksi Remote</th></tr>
    <?php
    $qp = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
    while ($p = mysqli_fetch_assoc($qp)):
      $u = $p['pppoe_user'] ?? '';
      $ip_now = ($p['tipe_layanan'] == 'static') ? ($p['ip_static'] ?? '') : ($active_ips[$u] ?? '');
      $online = !empty($ip_now);
    ?>
    <tr>
      <td><b><?= htmlspecialchars($p['nama']) ?></b><br><small style="color:#64748b"><?= htmlspecialchars($p['no_wa']) ?></small></td>
      <td><b><?= htmlspecialchars($u ?: $p['ip_static']) ?></b> <small>(<?= strtoupper($p['tipe_layanan']) ?>)</small></td>
      <td style="font-family:monospace;font-weight:700;color:#0284c7"><?= $online ? htmlspecialchars($ip_now) : '-' ?></td>
      <td><?= $online ? "<span style='background:#00a65a;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>ONLINE</span>" : "<span style='background:#94a3b8;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>OFFLINE</span>" ?></td>
      <td style="white-space:nowrap">
        <?php if($online): ?>
          <a href="remote_proxy.php?set_target=<?= urlencode($ip_now) ?>" target="_blank" class="btn" style="padding:5px 10px;font-size:11px">Remote Web (Port 80)</a>
          <a href="remote_proxy.php?set_target=<?= urlencode($ip_now.':8080') ?>" target="_blank" class="btn btn-w" style="padding:5px 10px;font-size:11px">Port 8080</a>
        <?php else: ?>
          <small style="color:#94a3b8">Modem Offline</small>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>
<script>
function filterRmt(){
  let q=document.getElementById('cariRmt').value.toLowerCase();
  document.querySelectorAll('#tblRmt tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
</script>
