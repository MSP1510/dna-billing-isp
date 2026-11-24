<?php cekAkses(['owner','admin','teknisi']);
$neighbors = []; $arp_devices = []; $router_status = [];

$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    $ok = $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port']);
    $router_status[] = ['nama' => $rt['nama_nas'], 'ip' => $rt['ip_address'], 'ok' => $ok];
    if ($ok) {
        // 1. Ambil MNDP/CDP/LLDP Neighbor (/ip/neighbor/print)
        $cur = [];
        foreach ($api->comm('/ip/neighbor/print') as $l) {
            if ($l == '!re') {
                if (!empty($cur['mac']) || !empty($cur['ip'])) { $cur['nas'] = $rt['nama_nas']; $neighbors[] = $cur; }
                $cur = [];
            }
            elseif (strpos($l,'=interface=')===0) $cur['iface'] = substr($l,11);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=identity=')===0) $cur['id'] = substr($l,10);
            elseif (strpos($l,'=platform=')===0) $cur['plat'] = substr($l,10);
            elseif (strpos($l,'=version=')===0) $cur['ver'] = substr($l,9);
            elseif (strpos($l,'=board=')===0) $cur['board'] = substr($l,7);
        }
        if (!empty($cur['mac']) || !empty($cur['ip'])) { $cur['nas'] = $rt['nama_nas']; $neighbors[] = $cur; }

        // 2. Ambil nama host dari DHCP Lease untuk mencocokkan nama OLT / AP / Router
        $dhcp_names = []; $cur = [];
        foreach ($api->comm('/ip/dhcp-server/lease/print') as $l) {
            if ($l == '!re') {
                if (!empty($cur['mac']) && !empty($cur['host'])) $dhcp_names[strtoupper($cur['mac'])] = $cur['host'];
                $cur = [];
            }
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=host-name=')===0) $cur['host'] = substr($l,11);
            elseif (strpos($l,'=comment=')===0 && empty($cur['host'])) $cur['host'] = substr($l,9);
        }

        // 3. Ambil Perangkat Aktif di ARP (/ip/arp/print) agar OLT / AP non-MNDP tetap terdeteksi
        $cur = [];
        foreach ($api->comm('/ip/arp/print') as $l) {
            if ($l == '!re') {
                if (!empty($cur['ip']) && !empty($cur['mac']) && ($cur['comp'] ?? 'false') == 'true') {
                    $mc = strtoupper($cur['mac']);
                    $cur['nas'] = $rt['nama_nas'];
                    $cur['host'] = $dhcp_names[$mc] ?? ($cur['cmt'] ?? '-');
                    $arp_devices[] = $cur;
                }
                $cur = [];
            }
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=interface=')===0) $cur['iface'] = substr($l,11);
            elseif (strpos($l,'=comment=')===0) $cur['cmt'] = substr($l,9);
            elseif (strpos($l,'=complete=')===0) $cur['comp'] = substr($l,10);
        }
        $api->disconnect();
    }
}
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:8px">
    <div>
      <h4>List Neighbor Perangkat Jaringan (Scan <?= count($router_status) ?> Router NAS)</h4>
      <small style="color:#64748b">Mendeteksi perangkat AP, Switch, OLT, dan Router MikroTik (MNDP/CDP/LLDP & ARP Aktif).</small>
    </div>
    <a href="?page=neighbor" class="btn" style="padding:5px 10px;font-size:11px">Scan Ulang</a>
  </div>

  <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px">
    <?php foreach($router_status as $rs): ?>
      <span style="background:<?= $rs['ok']?'#00a65a':'#ef4444' ?>;color:#fff;padding:3px 8px;border-radius:12px;font-size:11px;font-weight:700">
        <?= $rs['ok']?'✓':'✗' ?> <?= htmlspecialchars($rs['nama']) ?> (<?= htmlspecialchars($rs['ip']) ?>)
      </span>
    <?php endforeach; ?>
  </div>

  <h5 style="margin:8px 0 4px;color:#0284c7">1. Perangkat Neighbor Discovery (MNDP / CDP / LLDP)</h5>
  <div style="overflow-x:auto;margin-bottom:14px"><table>
    <tr><th>Router NAS</th><th>Interface</th><th>IP Address</th><th>MAC Address</th><th>Identity Perangkat</th><th>Tipe / Versi OS</th><th>Aksi</th></tr>
    <?php foreach($neighbors as $n): $ip_n = $n['ip'] ?? ''; ?>
    <tr>
      <td><b><?= htmlspecialchars($n['nas']) ?></b></td>
      <td><span style="background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= htmlspecialchars($n['iface']??'-') ?></span></td>
      <td style="font-family:monospace;font-weight:700"><?= htmlspecialchars($ip_n ?: '-') ?></td>
      <td style="font-family:monospace;font-size:11px"><?= htmlspecialchars($n['mac']??'-') ?></td>
      <td><b><?= htmlspecialchars($n['id']??'-') ?></b></td>
      <td><small><?= htmlspecialchars(trim(($n['plat']??'').' '.($n['board']??'').' '.($n['ver']??''))) ?></small></td>
      <td><?php if($ip_n !== ''): ?><a href="remote_proxy.php?set_target=<?= urlencode($ip_n) ?>&port=80" target="_blank" class="btn btn-g" style="padding:3px 7px;font-size:10px">Buka Web</a><?php else: ?>-<?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($neighbors)) echo "<tr><td colspan='7' style='text-align:center;color:#64748b;font-size:11px'>Belum ada perangkat MNDP/LLDP. Jalankan <code>/ip neighbor discovery-settings set discover-interface-list=all</code> di MikroTik jika OLT/Switch mendukung LLDP.</td></tr>"; ?>
  </table></div>

  <h5 style="margin:8px 0 4px;color:#8b5cf6">2. Perangkat Jaringan Aktif Terdeteksi (OLT / AP / Switch via ARP & DHCP)</h5>
  <div style="overflow-x:auto"><table>
    <tr><th>Router NAS</th><th>Interface</th><th>IP Address</th><th>MAC Address</th><th>Nama Host / Keterangan</th><th>Aksi Remote</th></tr>
    <?php foreach($arp_devices as $a): ?>
    <tr>
      <td><b><?= htmlspecialchars($a['nas']) ?></b></td>
      <td><span style="background:#f3e8ff;color:#6b21a8;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= htmlspecialchars($a['iface']??'-') ?></span></td>
      <td style="font-family:monospace;font-weight:700;color:#0284c7"><?= htmlspecialchars($a['ip']) ?></td>
      <td style="font-family:monospace;font-size:11px"><?= htmlspecialchars($a['mac']) ?></td>
      <td><b><?= htmlspecialchars($a['host']) ?></b></td>
      <td><a href="remote_proxy.php?set_target=<?= urlencode($a['ip']) ?>&port=80" target="_blank" class="btn" style="padding:3px 7px;font-size:10px">Remote Web</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($arp_devices)) echo "<tr><td colspan='6' style='text-align:center;color:#64748b'>Belum ada perangkat aktif di ARP</td></tr>"; ?>
  </table></div>
</div>
