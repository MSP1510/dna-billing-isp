<?php cekAkses(['owner','admin','teknisi']);
$online_users = [];
$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        $act = $api->comm('/ppp/active/print');
        $cur = [];
        foreach ($act as $l) {
            if ($l == '!re') { if(!empty($cur['name'])) $online_users[$cur['name']] = $cur; $cur = []; }
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
            elseif (strpos($l,'=caller-id=')===0) $cur['mac'] = substr($l,11);
        }
        if (!empty($cur['name'])) $online_users[$cur['name']] = $cur;
        $api->disconnect();
    }
}
$q_all = mysqli_query($conn, "SELECT p.*, o.nama_odp FROM pelanggan p LEFT JOIN odp o ON p.odp_id=o.id ORDER BY p.nama ASC");
$rows = []; $cnt_on = 0; $cnt_off = 0;
while ($p = mysqli_fetch_assoc($q_all)) {
    $u = $p['pppoe_user'] ?? '';
    $is_on = isset($online_users[$u]);
    if ($is_on) $cnt_on++; else $cnt_off++;
    $p['is_on'] = $is_on;
    $p['net'] = $online_users[$u] ?? [];
    $rows[] = $p;
}
?>
<div class="tiles">
  <div class="tile t-green"><h2><?= $cnt_on ?> Modem</h2><p>Pelanggan Online (Aktif)</p></div>
  <div class="tile t-red"><h2><?= $cnt_off ?> Modem</h2><p>Pelanggan Offline (Mati / LOS)</p></div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <h4>Monitor Status Modem Pelanggan Real-Time (Deteksi Jalur ODP Putus)</h4>
    <a href="?page=monitor" class="btn">Refresh Status</a>
  </div>
  <div style="overflow-x:auto">
  <table>
    <tr><th>Status</th><th>Nama Pelanggan</th><th>Jalur ODP</th><th>User PPPoE</th><th>IP & MAC Modem</th><th>Uptime Nyala</th></tr>
    <?php foreach($rows as $r): ?>
    <tr>
      <td><?= $r['is_on'] ? "<span style='background:#00a65a;color:#fff;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700'>ONLINE</span>" : "<span style='background:#dd4b39;color:#fff;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700'>OFFLINE</span>" ?></td>
      <td><b><?= htmlspecialchars($r['nama']) ?></b></td>
      <td><span style="background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-size:11px"><?= htmlspecialchars($r['nama_odp']??'-') ?></span></td>
      <td><?= htmlspecialchars($r['pppoe_user']??'-') ?></td>
      <td style="font-family:monospace;font-size:11px"><?= htmlspecialchars($r['net']['ip']??'-') ?><br><small><?= htmlspecialchars($r['net']['mac']??'') ?></small></td>
      <td><b><?= htmlspecialchars($r['net']['up']??'Terputus') ?></b></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>
