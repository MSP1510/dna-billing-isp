
<?php
$hs_logs = [];
$qr_l = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt_l = mysqli_fetch_assoc($qr_l)) {
    if ($api->connect($rt_l['ip_address'], $rt_l['username'], $rt_l['password'], $rt_l['port'])) {
        $cur = [];
        foreach ($api->comm('/log/print') as $l) {
            if ($l == '!re') {
                $tp = $cur['topics'] ?? '';
                $mg = $cur['message'] ?? '';
                if (strpos($tp, 'hotspot') !== false && strpos($mg, 'via api') === false) {
                    $hs_logs[] = ['time' => $cur['time'] ?? '', 'msg' => $mg];
                }
                $cur = [];
            } elseif (strpos($l, '=time=') === 0) $cur['time'] = substr($l, 6);
            elseif (strpos($l, '=topics=') === 0) $cur['topics'] = substr($l, 8);
            elseif (strpos($l, '=message=') === 0) $cur['message'] = substr($l, 9);
        }
        $api->disconnect();
    }
}
$hs_logs = array_slice(array_reverse($hs_logs), 0, 50);
?>
<div class="card" style="border-top:4px solid #0284c7">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
    <h4>Hotspot Log (Real-Time MikroTik)</h4>
    <a href="?page=logs" class="btn" style="padding:4px 8px;font-size:11px">Refresh Log</a>
  </div>
  <div style="overflow-x:auto"><table>
    <tr><th>Time</th><th>Users (IP)</th><th>Messages</th></tr>
    <?php foreach($hs_logs as $lg): $rm=$lg['msg']; $ui='-'; $pm=$rm;
      if(strpos($rm, ': ')!==false){
        $pos=strpos($rm, ': '); $head=substr($rm, 0, $pos); $pm=substr($rm, $pos+2);
        if(strpos($head, '(')!==false){ $u_nm=trim(strtok($head, '(')); $u_ip=trim(strtok(')')); $ui="<b>".htmlspecialchars($u_nm)."</b><br><small style='color:#64748b'>(".htmlspecialchars($u_ip).")</small>"; }
        else { $ui="<b>".htmlspecialchars(trim($head))."</b>"; }
      }
      $mc=(stripos($pm,'logged in')!==false||stripos($pm,'log in')!==false)?'#00a65a':((stripos($pm,'failed')!==false||stripos($pm,'timeout')!==false)?'#dd4b39':'#334155');
    ?>
    <tr><td style="font-size:11px;white-space:nowrap;color:#475569"><?= htmlspecialchars($lg['time']) ?></td><td style="font-size:12px"><?= $ui ?></td><td style="font-size:12px;color:<?= $mc ?>;font-weight:600"><?= htmlspecialchars($pm) ?></td></tr>
    <?php endforeach; ?>
    <?php if(empty($hs_logs)) echo "<tr><td colspan='3' style='text-align:center;color:#64748b'>Belum ada aktivitas log Hotspot</td></tr>"; ?>
  </table></div>
</div>

<?php if ($role !== 'owner') { echo "<div class='card'><h4>Akses Ditolak!</h4><p>Menu Log Aktivitas hanya dapat dibuka oleh Owner.</p></div>"; exit; }

if (isset($_GET['clear_logs'])) {
    mysqli_query($conn, "TRUNCATE TABLE login_logs");
    echo "<script>location.href='?page=logs';</script>"; exit;
}
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">
    <h4>Riwayat Log Login & Logout Staff (Khusus Owner)</h4>
    <div style="display:flex;gap:6px">
      <input type="text" id="cariLog" onkeyup="filterLog()" placeholder="Cari nama / role / aksi..." style="width:190px">
      <a href="?page=logs&clear_logs=1" onclick="return confirm('Hapus seluruh riwayat log aktivitas?')" class="btn btn-r">Bersihkan Log</a>
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tblLog">
    <tr><th>Tanggal & Jam</th><th>Nama Staff</th><th>Jabatan (Role)</th><th>Aktivitas</th><th>IP Perangkat</th></tr>
    <?php
    $q = mysqli_query($conn, "SELECT * FROM login_logs ORDER BY id DESC LIMIT 200");
    while ($d = mysqli_fetch_assoc($q)):
      $bg = ($d['aksi'] == 'LOGIN') ? '#00a65a' : '#dd4b39';
    ?>
    <tr>
      <td style="font-family:monospace;font-weight:700"><?= date('d/m/Y H:i:s', strtotime($d['waktu'])) ?></td>
      <td><b><?= htmlspecialchars($d['nama']) ?></b></td>
      <td><span style="background:#0f172a;color:#38bdf8;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= strtoupper($d['role']) ?></span></td>
      <td><span style="background:<?= $bg ?>;color:#fff;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700"><?= $d['aksi'] ?></span></td>
      <td style="font-family:monospace;font-size:12px;color:#475569"><?= htmlspecialchars($d['ip_addr']??'-') ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>
<script>
function filterLog(){
  let q=document.getElementById('cariLog').value.toLowerCase();
  document.querySelectorAll('#tblLog tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
</script>
