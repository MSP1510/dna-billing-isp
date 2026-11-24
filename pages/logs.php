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
