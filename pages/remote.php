<?php cekAkses(['owner','admin','teknisi']);
$online_ppp = []; $online_arp = [];
$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        // Ambil khusus PPPoE Active (abaikan akun hotspot dinamis)
        $cur = [];
        foreach ($api->comm('/ppp/active/print') as $l) {
            if ($l == '!re') {
                $nm = trim($cur['name'] ?? '');
                if ($nm !== '' && strpos($nm, '<hotspot-') === false && !empty($cur['ip'])) {
                    $online_ppp[$nm] = trim($cur['ip']);
                }
                $cur = [];
            }
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
        }
        $nm = trim($cur['name'] ?? '');
        if ($nm !== '' && strpos($nm, '<hotspot-') === false && !empty($cur['ip'])) $online_ppp[$nm] = trim($cur['ip']);

        // Ambil ARP aktif untuk dicocokkan HANYA dengan pelanggan Static IP terdaftar
        $cur = [];
        foreach ($api->comm('/ip/arp/print') as $l) {
            if ($l == '!re') {
                if (!empty($cur['ip']) && ($cur['comp'] ?? 'false') == 'true') $online_arp[trim($cur['ip'])] = true;
                $cur = [];
            }
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=complete=')===0) $cur['comp'] = substr($l,10);
        }
        if (!empty($cur['ip']) && ($cur['comp'] ?? 'false') == 'true') $online_arp[trim($cur['ip'])] = true;
        $api->disconnect();
    }
}

$list_pppoe = []; $list_static = [];
$qp = mysqli_query($conn, "SELECT nama, pppoe_user, ip_static, tipe_layanan FROM pelanggan WHERE nama NOT LIKE '<hotspot-%' ORDER BY nama ASC");
while ($p = mysqli_fetch_assoc($qp)) {
    $tipe = $p['tipe_layanan'] ?? 'pppoe';
    if ($tipe == 'static') {
        $ip = trim($p['ip_static'] ?? '');
        if ($ip !== '' && isset($online_arp[$ip])) {
            $list_static[] = ['nama' => $p['nama'], 'akun' => $ip, 'ip' => $ip];
        }
    } else {
        $usr = trim($p['pppoe_user'] ?? '');
        if ($usr !== '' && isset($online_ppp[$usr])) {
            $list_pppoe[] = ['nama' => $p['nama'], 'akun' => $usr, 'ip' => $online_ppp[$usr]];
        }
    }
}
?>
<div class="card" style="border-left:5px solid #0284c7">
  <h4>Remote Cepat IP Manual (ONT / Router)</h4>
  <form action="remote_proxy.php" method="GET" target="_blank" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">
    <input name="set_target" placeholder="IP Modem ONT (mis: 192.168.100.1)" required style="flex:1;min-width:170px">
    <select name="port" style="width:110px">
      <option value="80">Port 80</option>
      <option value="8080">Port 8080</option>
      <option value="443">Port 443</option>
    </select>
    <button class="btn">Buka Remote</button>
  </form>
</div>

<div class="card" style="border-top:4px solid #0284c7">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
    <h4>1. Remote Modem ONT — Pelanggan PPPoE Online (<?= count($list_pppoe) ?>)</h4>
    <a href="?page=remote" class="btn" style="padding:4px 8px;font-size:11px">Refresh</a>
  </div>
  <div style="overflow-x:auto"><table>
    <tr><th>Nama Pelanggan</th><th>User PPPoE</th><th>IP Modem Aktif</th><th>Aksi Remote Cepat</th></tr>
    <?php foreach($list_pppoe as $m): ?>
    <tr>
      <td><b><?= htmlspecialchars($m['nama']) ?></b></td>
      <td><small><?= htmlspecialchars($m['akun']) ?></small></td>
      <td style="font-family:monospace;font-weight:700;color:#0284c7"><?= htmlspecialchars($m['ip']) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=80" target="_blank" class="btn btn-g" style="padding:4px 8px;font-size:11px">Port 80</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=8080" target="_blank" class="btn" style="padding:4px 8px;font-size:11px">Port 8080</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=443" target="_blank" class="btn btn-w" style="padding:4px 8px;font-size:11px">Port 443</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($list_pppoe)) echo "<tr><td colspan='4' style='text-align:center;color:#64748b'>Tidak ada modem PPPoE online</td></tr>"; ?>
  </table></div>
</div>

<div class="card" style="border-top:4px solid #8b5cf6">
  <h4>2. Remote Modem ONT — Pelanggan Static IP Online (<?= count($list_static) ?>)</h4>
  <div style="overflow-x:auto;margin-top:8px"><table>
    <tr><th>Nama Pelanggan</th><th>IP Static Modem</th><th>Aksi Remote Cepat</th></tr>
    <?php foreach($list_static as $m): ?>
    <tr>
      <td><b><?= htmlspecialchars($m['nama']) ?></b></td>
      <td style="font-family:monospace;font-weight:700;color:#8b5cf6"><?= htmlspecialchars($m['ip']) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=80" target="_blank" class="btn btn-g" style="padding:4px 8px;font-size:11px">Port 80</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=8080" target="_blank" class="btn" style="padding:4px 8px;font-size:11px">Port 8080</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=443" target="_blank" class="btn btn-w" style="padding:4px 8px;font-size:11px">Port 443</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($list_static)) echo "<tr><td colspan='3' style='text-align:center;color:#64748b'>Tidak ada modem Static IP online</td></tr>"; ?>
  </table></div>
</div>
