<?php cekAkses(['owner','admin','teknisi']);
if (isset($_GET['kick_u']) && isset($_GET['nas'])) {
    $nas_id = (int)$_GET['nas'];
    $ku = $_GET['kick_u'];
    $kt = $_GET['tipe'] ?? 'ppp';
    $rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=$nas_id"));
    if ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        if ($kt == 'static') {
            $api->comm('/ip/firewall/address-list/add', ['list' => 'ISOLIR', 'address' => $ku, 'comment' => 'Kick Static dari Billing']);
        } else {
            $path = ($kt == 'hotspot') ? '/ip/hotspot/active' : '/ppp/active';
            $res = $api->comm("$path/print");
            $id_kick = ''; $cur_id = ''; $cur_nm = '';
            foreach ($res as $l) {
                if ($l == '!re') { if ($cur_nm == $ku) $id_kick = $cur_id; $cur_id = ''; $cur_nm = ''; }
                elseif (strpos($l, '=.id=') === 0) $cur_id = substr($l, 5);
                elseif (strpos($l, '=name=') === 0) $cur_nm = substr($l, 6);
                elseif (strpos($l, '=user=') === 0) $cur_nm = substr($l, 6);
            }
            if ($cur_nm == $ku) $id_kick = $cur_id;
            if ($id_kick) $api->comm("$path/remove", ['.id' => $id_kick]);
        }
        $api->disconnect();
    }
    echo "<script>location.href='?page=session';</script>"; exit;
}

// Ambil daftar pelanggan Static IP dari database
$static_map = [];
$qs_db = mysqli_query($conn, "SELECT nama, ip_static, router_id FROM pelanggan WHERE tipe_layanan='static' AND ip_static != ''");
while ($sp = mysqli_fetch_assoc($qs_db)) {
    $static_map[trim($sp['ip_static'])] = $sp['nama'];
}

$sessions = [];
$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        // 1. PPPoE Active
        $ppp = $api->comm('/ppp/active/print');
        $cur = [];
        foreach ($ppp as $l) {
            if ($l == '!re') { if (!empty($cur['u'])) $sessions[] = $cur; $cur = ['nas'=>$rt['nama_nas'],'nas_id'=>$rt['id'],'tipe'=>'PPPoE','kick_id'=>'']; }
            elseif (strpos($l,'=name=')===0) { $cur['u'] = substr($l,6); $cur['kick_id'] = $cur['u']; }
            elseif (strpos($l,'=caller-id=')===0) $cur['mac'] = substr($l,11);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
        }
        if (!empty($cur['u'])) $sessions[] = $cur;

        // 2. Hotspot Active
        $hs = $api->comm('/ip/hotspot/active/print');
        $cur = [];
        foreach ($hs as $l) {
            if ($l == '!re') { if (!empty($cur['u'])) $sessions[] = $cur; $cur = ['nas'=>$rt['nama_nas'],'nas_id'=>$rt['id'],'tipe'=>'Hotspot','kick_id'=>'']; }
            elseif (strpos($l,'=user=')===0) { $cur['u'] = substr($l,6); $cur['kick_id'] = $cur['u']; }
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['up'] = substr($l,8);
        }
        if (!empty($cur['u'])) $sessions[] = $cur;

        // 3. Static IP Active (Cek tabel IP ARP & DHCP Leases MikroTik)
        $arp = $api->comm('/ip/arp/print');
        $cur = [];
        foreach ($arp as $l) {
            if ($l == '!re') {
                $ip_a = trim($cur['ip'] ?? '');
                if (!empty($ip_a) && isset($static_map[$ip_a]) && ($cur['comp'] ?? 'false') == 'true') {
                    $sessions[] = [
                        'u' => $static_map[$ip_a],
                        'kick_id' => $ip_a,
                        'tipe' => 'Static IP',
                        'nas' => $rt['nama_nas'],
                        'nas_id' => $rt['id'],
                        'mac' => $cur['mac'] ?? '-',
                        'ip' => $ip_a,
                        'up' => 'Terhubung (ARP)'
                    ];
                }
                $cur = [];
            }
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=complete=')===0) $cur['comp'] = substr($l,10);
        }
        $ip_a = trim($cur['ip'] ?? '');
        if (!empty($ip_a) && isset($static_map[$ip_a]) && ($cur['comp'] ?? 'false') == 'true') {
            $sessions[] = [
                'u' => $static_map[$ip_a],
                'kick_id' => $ip_a,
                'tipe' => 'Static IP',
                'nas' => $rt['nama_nas'],
                'nas_id' => $rt['id'],
                'mac' => $cur['mac'] ?? '-',
                'ip' => $ip_a,
                'up' => 'Terhubung (ARP)'
            ];
        }
        $api->disconnect();
    }
}
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
    <h4>Session User Aktif Real-Time (<?= count($sessions) ?> Online)</h4>
    <div style="display:flex;gap:6px">
      <input type="text" id="cariSes" onkeyup="filterSes()" placeholder="Cari nama / IP / MAC..." style="width:190px">
      <a href="?page=session" class="btn">Refresh</a>
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tblSes">
    <tr><th>Nama / Username</th><th>Tipe</th><th>Router [NAS]</th><th>MAC Address</th><th>IP Address</th><th>Status / Uptime</th><th>Aksi</th></tr>
    <?php if (empty($sessions)): ?>
      <tr><td colspan="7" style="text-align:center;padding:15px;color:#64748b">Belum ada user PPPoE, Hotspot, maupun Static IP yang aktif.</td></tr>
    <?php else: foreach ($sessions as $s):
      $bg = ($s['tipe']=='PPPoE') ? '#0284c7' : (($s['tipe']=='Static IP') ? '#8b5cf6' : '#00a65a');
      $t_param = ($s['tipe']=='Static IP') ? 'static' : strtolower($s['tipe']);
    ?>
      <tr>
        <td><b><?= htmlspecialchars($s['u']) ?></b></td>
        <td><span style="background:<?= $bg ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= $s['tipe'] ?></span></td>
        <td><?= htmlspecialchars($s['nas']) ?></td>
        <td style="font-family:monospace;font-size:11px"><?= htmlspecialchars($s['mac']??'-') ?></td>
        <td style="font-family:monospace;font-weight:700;color:#0284c7"><?= htmlspecialchars($s['ip']??'-') ?></td>
        <td><?= htmlspecialchars($s['up']??'-') ?></td>
        <td><a href="?page=session&kick_u=<?= urlencode($s['kick_id']) ?>&nas=<?= $s['nas_id'] ?>&tipe=<?= $t_param ?>" onclick="return confirm('Putus / Isolir sesi user ini sekarang?')" class="btn btn-r" style="padding:4px 8px;font-size:11px"><?= $s['tipe']=='Static IP'?'Isolir':'Kick' ?></a></td>
      </tr>
    <?php endforeach; endif; ?>
  </table>
  </div>
</div>
<script>
function filterSes(){
  let q=document.getElementById('cariSes').value.toLowerCase();
  document.querySelectorAll('#tblSes tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
</script>
