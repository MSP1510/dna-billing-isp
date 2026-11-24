<?php cekAkses(['owner','admin','teknisi']);
$online_ppp = [];
$online_arp = [];

$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        // 1. Cek PPPoE Active
        $act = $api->comm('/ppp/active/print');
        $cur = [];
        foreach ($act as $l) {
            if ($l == '!re') { if(!empty($cur['name'])) $online_ppp[$cur['name']] = $cur; $cur = []; }
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
            elseif (strpos($l,'=caller-id=')===0) $cur['mac'] = substr($l,11);
        }
        if (!empty($cur['name'])) $online_ppp[$cur['name']] = $cur;

        // 2. Cek Static IP via ARP MikroTik
        $arp = $api->comm('/ip/arp/print');
        $cur = [];
        foreach ($arp as $l) {
            if ($l == '!re') {
                if (!empty($cur['ip']) && ($cur['comp'] ?? 'false') == 'true') {
                    $online_arp[trim($cur['ip'])] = ['ip' => trim($cur['ip']), 'mac' => $cur['mac'] ?? '-', 'up' => 'Aktif (ARP)'];
                }
                $cur = [];
            }
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=complete=')===0) $cur['comp'] = substr($l,10);
        }
        if (!empty($cur['ip']) && ($cur['comp'] ?? 'false') == 'true') {
            $online_arp[trim($cur['ip'])] = ['ip' => trim($cur['ip']), 'mac' => $cur['mac'] ?? '-', 'up' => 'Aktif (ARP)'];
        }
        $api->disconnect();
    }
}

$q_all = mysqli_query($conn, "SELECT p.*, o.nama_odp FROM pelanggan p LEFT JOIN odp o ON p.odp_id=o.id ORDER BY p.nama ASC");
$rows = []; $cnt_on = 0; $cnt_off = 0;
while ($p = mysqli_fetch_assoc($q_all)) {
    $is_static = (($p['tipe_layanan'] ?? 'pppoe') == 'static');
    if ($is_static) {
        $ip_s = trim($p['ip_static'] ?? '');
        $is_on = !empty($ip_s) && isset($online_arp[$ip_s]);
        $net = $online_arp[$ip_s] ?? ['ip' => $ip_s ?: '-', 'mac' => '', 'up' => 'Terputus'];
        $akun = $ip_s ?: '-';
    } else {
        $u = trim($p['pppoe_user'] ?? '');
        $is_on = !empty($u) && isset($online_ppp[$u]);
        $net = $online_ppp[$u] ?? ['ip' => '-', 'mac' => '', 'up' => 'Terputus'];
        $akun = $u ?: '-';
    }
    if ($is_on) $cnt_on++; else $cnt_off++;
    $p['is_on'] = $is_on;
    $p['net'] = $net;
    $p['akun'] = $akun;
    $rows[] = $p;
}
?>
<div class="tiles">
  <div class="tile t-green"><h2><?= $cnt_on ?> Modem</h2><p>Pelanggan Online (PPPoE & Static)</p></div>
  <div class="tile t-red"><h2><?= $cnt_off ?> Modem</h2><p>Pelanggan Offline (Mati / LOS)</p></div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
    <h4>Monitor Status Modem Pelanggan Real-Time (PPPoE & Static IP)</h4>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <input type="text" id="cariMon" onkeyup="filterMon()" placeholder="Cari nama / IP / ODP..." style="width:180px">
      <a href="?page=monitor" class="btn">Refresh</a>
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tblMon">
    <tr><th>Status</th><th>Nama Pelanggan</th><th>Layanan</th><th>Jalur ODP</th><th>User / IP Static</th><th>IP & MAC Aktif</th><th>Uptime</th></tr>
    <?php foreach($rows as $r):
      $is_st = (($r['tipe_layanan'] ?? 'pppoe') == 'static');
    ?>
    <tr>
      <td><?= $r['is_on'] ? "<span style='background:#00a65a;color:#fff;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700'>ONLINE</span>" : "<span style='background:#dd4b39;color:#fff;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700'>OFFLINE</span>" ?></td>
      <td><b><?= htmlspecialchars($r['nama']) ?></b></td>
      <td><span style="background:<?= $is_st?'#8b5cf6':'#0284c7' ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= $is_st?'STATIC':'PPPoE' ?></span></td>
      <td><span style="background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-size:11px"><?= htmlspecialchars($r['nama_odp']??'-') ?></span></td>
      <td><b><?= htmlspecialchars($r['akun']) ?></b></td>
      <td style="font-family:monospace;font-size:11px"><?= htmlspecialchars($r['net']['ip']??'-') ?><br><small><?= htmlspecialchars($r['net']['mac']??'') ?></small></td>
      <td><b><?= htmlspecialchars($r['net']['up']??'Terputus') ?></b></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>
<script>
function filterMon(){
  let q=document.getElementById('cariMon').value.toLowerCase();
  document.querySelectorAll('#tblMon tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
</script>
