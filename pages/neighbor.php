<?php cekAkses(['owner','teknisi']);
$neighbors = [];
$qr_nb = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr_nb)) {
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        $raw = $api->comm('/ip/neighbor/print');
        $cur = [];
        foreach ($raw as $l) {
            if ($l == '!re') { if(!empty($cur)) $neighbors[] = $cur; $cur = ['nas'=>$rt['nama_nas']]; }
            elseif (strpos($l,'=interface=')===0) $cur['if'] = substr($l,11);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=mac-address=')===0) $cur['mac'] = substr($l,13);
            elseif (strpos($l,'=identity=')===0) $cur['id'] = substr($l,10);
            elseif (strpos($l,'=board=')===0) $cur['brd'] = substr($l,7);
        }
        if (!empty($cur['mac'])) $neighbors[] = $cur;
        $api->disconnect();
    }
}
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
    <h4>List Neighbor Perangkat Jaringan (Scan 5 Router NAS)</h4>
    <div style="display:flex;gap:6px">
      
      <a href="?page=neighbor" class="btn">Scan Ulang</a>
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tblNb">
    <tr><th>Router NAS</th><th>Interface</th><th>IP Address</th><th>MAC Address</th><th>Identity Perangkat</th><th>Platform / Board</th><th>Aksi</th></tr>
    <?php foreach($neighbors as $n): $ip_val = trim($n['ip'] ?? ''); ?>
    <tr>
      <td><b><?= htmlspecialchars($n['nas']) ?></b></td>
      <td><span style="background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:700"><?= htmlspecialchars($n['if']??'-') ?></span></td>
      <td><b><?= htmlspecialchars($ip_val ?: '-') ?></b></td>
      <td style="font-family:monospace"><?= htmlspecialchars($n['mac']??'-') ?></td>
      <td><b><?= htmlspecialchars($n['id']??'-') ?></b></td>
      <td><?= htmlspecialchars($n['brd']??'-') ?></td>
      <td>
        <?php if(!empty($ip_val)): ?>
          <a href="http://<?= htmlspecialchars($ip_val) ?>" target="_blank" class="btn btn-g" style="padding:4px 8px;font-size:11px">Buka Web</a>
        <?php else: ?>
          <small style="color:#94a3b8">No IP</small>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; if(empty($neighbors)) echo "<tr><td colspan='7' style='text-align:center;color:#64748b'>Belum ada perangkat neighbor terdeteksi (Pastikan Router NAS terhubung).</td></tr>"; ?>
  </table>
  </div>
</div>
<script>
function filterNb(){
  let q=document.getElementById('cariNb').value.toLowerCase();
  document.querySelectorAll('#tblNb tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
</script>
