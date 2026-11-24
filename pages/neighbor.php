<?php cekAkses(['owner','admin','teknisi']);
$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
$total_nas = mysqli_num_rows($qr);
$rows = [];
$status_nas = [];

while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        $status_nas[] = "<span style='background:#00a65a;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px'>&#10003; $rt[nama_nas] ($rt[ip_address]) Terhubung</span>";
        $res = $api->comm('/ip/neighbor/print');
        $cur = [];
        foreach ($res as $l) {
            if ($l == '!re') { if (!empty($cur)) $rows[] = $cur; $cur = ['nas' => $rt['nama_nas']]; }
            elseif (strpos($l, '=interface=') === 0) $cur['if'] = substr($l, 11);
            elseif (strpos($l, '=address=') === 0) $cur['ip'] = substr($l, 9);
            elseif (strpos($l, '=address4=') === 0 && empty($cur['ip'])) $cur['ip'] = substr($l, 10);
            elseif (strpos($l, '=mac-address=') === 0) $cur['mac'] = substr($l, 13);
            elseif (strpos($l, '=identity=') === 0) $cur['id'] = substr($l, 10);
            elseif (strpos($l, '=board=') === 0) $cur['brd'] = substr($l, 7);
            elseif (strpos($l, '=platform=') === 0 && empty($cur['brd'])) $cur['brd'] = substr($l, 10);
            elseif (strpos($l, '=version=') === 0) $cur['ver'] = substr($l, 9);
        }
        if (!empty($cur) && count($cur) > 1) $rows[] = $cur;
        $api->disconnect();
    } else {
        $status_nas[] = "<span style='background:#dd4b39;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px'>&#10007; $rt[nama_nas] ($rt[ip_address]) Gagal Konek API</span>";
    }
}
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
    <div>
      <h4>List Neighbor Perangkat Jaringan (Scan <?= $total_nas ?> Router NAS)</h4>
      <small style="color:#64748b">Mendeteksi perangkat AP, Switch, OLT, dan Router MikroTik yang terhubung ke jaringan Anda (MNDP/CDP/LLDP).</small>
    </div>
    <a href="?page=neighbor" class="btn">Scan Ulang</a>
  </div>

  <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
    <?= implode(' ', $status_nas) ?>
  </div>

  <div style="overflow-x:auto">
  <table>
    <tr><th>Router NAS</th><th>Interface</th><th>IP Address</th><th>MAC Address</th><th>Identity Perangkat</th><th>Tipe / Versi OS</th><th>Aksi</th></tr>
    <?php if (empty($rows)): ?>
      <tr><td colspan="7" style="text-align:center;color:#64748b;padding:16px">Belum ada perangkat neighbor terdeteksi. Pastikan Router NAS terhubung API & jalankan Script Integrasi MikroTik.</td></tr>
    <?php else: foreach ($rows as $n): $ip_n = $n['ip'] ?? ''; ?>
      <tr>
        <td><b><?= htmlspecialchars($n['nas'] ?? '-') ?></b></td>
        <td><span style="background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:700"><?= htmlspecialchars($n['if'] ?? '-') ?></span></td>
        <td style="font-family:monospace;font-weight:700;color:#0284c7"><?= htmlspecialchars($ip_n ?: '-') ?></td>
        <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($n['mac'] ?? '-') ?></td>
        <td><b><?= htmlspecialchars($n['id'] ?? '-') ?></b></td>
        <td style="font-size:11px"><?= htmlspecialchars(($n['brd'] ?? '-') . ' ' . ($n['ver'] ?? '')) ?></td>
        <td>
          <?php if (!empty($ip_n)): ?>
            <a href="remote_proxy.php?set_target=<?= urlencode($ip_n) ?>" target="_blank" class="btn btn-g" style="padding:4px 8px;font-size:11px">Remote Web</a>
          <?php else: ?> - <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </table>
  </div>
</div>
