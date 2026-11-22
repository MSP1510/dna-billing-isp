<?php cekAkses(['owner','teknisi']);
$tab = $_GET['tab'] ?? 'aktif';
$srv = $_GET['srv'] ?? 'all';
$sessions = []; $active_names = [];

if ($mt_ok && $api->connect($cfg['mt_ip'], $cfg['mt_user'], $cfg['mt_pass'])) {
    if (isset($_GET['kick_id']) && isset($_GET['kick_type'])) {
        $cmd = $_GET['kick_type']=='PPP' ? '/ppp/active/remove' : '/ip/hotspot/active/remove';
        $api->comm($cmd, ['.id' => $_GET['kick_id']]);
        echo "<div class='card' style='border-left:4px solid #00a65a'>Sesi user berhasil diputus (Kick) dari MikroTik!</div>";
    }
    if ($srv == 'all' || $srv == 'ppp') {
        $raw = $api->comm('/ppp/active/print'); $cur = [];
        foreach($raw as $l) {
            if ($l == '!re') { if(!empty($cur)) $sessions[] = $cur; $cur = ['type'=>'PPP','up'=>'-','dl'=>'-']; }
            elseif (strpos($l,'=.id=')===0) $cur['id'] = substr($l,5);
            elseif (strpos($l,'=name=')===0) { $cur['name'] = substr($l,6); $active_names[] = $cur['name']; }
            elseif (strpos($l,'=caller-id=')===0) $cur['mac'] = substr($l,11);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['uptime'] = substr($l,8);
        }
        if(!empty($cur['name'])) $sessions[] = $cur;
    }
    if ($srv == 'all' || $srv == 'hotspot') {
        $raw = $api->comm('/ip/hotspot/active/print'); $cur = [];
        foreach($raw as $l) {
            if ($l == '!re') { if(!empty($cur)) $sessions[] = $cur; $cur = ['type'=>'Hotspot']; }
            elseif (strpos($l,'=.id=')===0) $cur['id'] = substr($l,5);
            elseif (strpos($l,'=user=')===0) { $cur['name'] = substr($l,6); $active_names[] = $cur['name']; }
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=uptime=')===0) $cur['uptime'] = substr($l,8);
            elseif (strpos($l,'=bytes-in=')===0) $cur['up'] = round((int)substr($l,10)/1048576,1).' MB';
            elseif (strpos($l,'=bytes-out=')===0) $cur['dl'] = round((int)substr($l,11)/1048576,1).' MB';
        }
        if(!empty($cur['name'])) $sessions[] = $cur;
    }
    $api->disconnect();
}
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px">
    <div>
      <a href="?page=session&tab=aktif&srv=all" class="btn <?= ($tab=='aktif'&&$srv=='all')?'btn-g':'' ?>">Semua Aktif (<?= count($sessions) ?>)</a>
      <a href="?page=session&tab=aktif&srv=ppp" class="btn <?= $srv=='ppp'?'btn-g':'' ?>">PPP Online</a>
      <a href="?page=session&tab=aktif&srv=hotspot" class="btn <?= $srv=='hotspot'?'btn-g':'' ?>">Hotspot Online</a>
      <a href="?page=session&tab=offline" class="btn <?= $tab=='offline'?'btn-r':'btn-w' ?>">Pelanggan Offline</a>
    </div>
    <input type="text" id="cariSess" onkeyup="filterSess()" placeholder="Search username / IP / MAC..." style="width:220px">
  </div>

  <?php if ($tab == 'aktif'): ?>
  <div style="overflow-x:auto">
  <table id="tblSess">
    <tr><th>Username</th><th>Tipe</th><th>Router [NAS]</th><th>Calling Station (MAC)</th><th>IP Address</th><th>Uptime</th><th>Up / Down</th><th>Aksi</th></tr>
    <?php foreach($sessions as $s): ?>
    <tr>
      <td><span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:#00a65a;margin-right:6px"></span><b><?= htmlspecialchars($s['name']??'-') ?></b></td>
      <td><span style="background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:700"><?= $s['type'] ?></span></td>
      <td><?= $mt_info['board'] ?></td>
      <td style="font-family:monospace"><?= htmlspecialchars($s['mac']??'-') ?></td>
      <td><b><?= htmlspecialchars($s['ip']??'-') ?></b></td>
      <td><?= htmlspecialchars($s['uptime']??'-') ?></td>
      <td style="font-size:11px">&uarr; <?= $s['up']??'-' ?> &bull; &darr; <?= $s['dl']??'-' ?></td>
      <td><a href="?page=session&kick_id=<?= urlencode($s['id']??'') ?>&kick_type=<?= $s['type'] ?>" onclick="return confirm('Putuskan koneksi user ini?')" class="btn btn-r" style="padding:4px 8px;font-size:11px">Kick</a></td>
    </tr>
    <?php endforeach; if(empty($sessions)) echo "<tr><td colspan='8' style='text-align:center;color:#64748b'>Tidak ada session aktif atau Router NAS belum terhubung.</td></tr>"; ?>
  </table>
  </div>
  <?php else: ?>
  <div style="overflow-x:auto">
  <table id="tblSess">
    <tr><th>Status</th><th>Nama Pelanggan</th><th>User PPPoE</th><th>No WhatsApp</th><th>Status Akun</th></tr>
    <?php
    $qp = mysqli_query($conn, "SELECT * FROM pelanggan WHERE tipe_layanan='pppoe'");
    $off_cnt = 0;
    while($p = mysqli_fetch_assoc($qp)) {
        if (!in_array($p['pppoe_user'], $active_names)) {
            $off_cnt++;
            echo "<tr><td><span style='display:inline-block;width:9px;height:9px;border-radius:50%;background:#dd4b39;margin-right:6px'></span><b style='color:#dd4b39'>Offline</b></td><td>$p[nama]</td><td><b>$p[pppoe_user]</b></td><td>$p[no_wa]</td><td>$p[status]</td></tr>";
        }
    }
    if($off_cnt==0) echo "<tr><td colspan='5' style='text-align:center;color:#64748b'>Semua pelanggan PPPoE terdaftar sedang Online.</td></tr>";
    ?>
  </table>
  </div>
  <?php endif; ?>
</div>
<script>
function filterSess(){
  let q=document.getElementById('cariSess').value.toLowerCase();
  document.querySelectorAll('#tblSess tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
</script>
