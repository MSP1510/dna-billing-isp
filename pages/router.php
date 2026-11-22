<?php cekAkses(['owner']);
$jml_r = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM routers"));
if (isset($_POST['add_nas'])) {
    if ($jml_r >= 5) {
        echo "<div class='card' style='border-left:4px solid #dd4b39'>Maksimal 5 Router [NAS] telah tercapai!</div>";
    } else {
        mysqli_query($conn, "INSERT INTO routers (nama_nas,ip_address,username,password,port) VALUES ('$_POST[nas]','$_POST[ip]','$_POST[u]','$_POST[p]','$_POST[prt]')");
        echo "<div class='card' style='border-left:4px solid #00a65a'>Router NAS baru berhasil ditambahkan!</div>";
    }
}
if (isset($_GET['del_nas'])) {
    mysqli_query($conn, "DELETE FROM routers WHERE id='$_GET[del_nas]'");
}
?>
<div class="card">
  <h4>Daftarkan Router [ NAS ] MikroTik API (Maksimal 5 NAS: <?= $jml_r ?>/5)</h4>
  <?php if($jml_r < 5): ?>
  <form method="POST" style="margin-top:10px">
    <input name="nas" placeholder="Nama NAS (mis: NAS-02 Cabang)" required>
    <input name="ip" placeholder="IP / Domain MikroTik" required>
    <input name="u" placeholder="User API" required>
    <input name="p" type="password" placeholder="Password API">
    <input name="prt" type="number" value="8728" style="width:80px">
    <button name="add_nas" class="btn btn-g">+ Tambah NAS</button>
  </form>
  <?php endif; ?>
  <table>
    <tr><th>ID</th><th>Nama NAS</th><th>IP Address : Port</th><th>User API</th><th>Status Koneksi</th><th>Aksi</th></tr>
    <?php
    $qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
    while($r = mysqli_fetch_assoc($qr)) {
        $cek = new RouterosAPI();
        $st = $cek->connect($r['ip_address'], $r['username'], $r['password'], $r['port']);
        if($st) $cek->disconnect();
        $badge = $st ? "<b style='color:#00a65a'>TERHUBUNG</b>" : "<b style='color:#dd4b39'>TERPUTUS</b>";
        echo "<tr><td>NAS-$r[id]</td><td><b>$r[nama_nas]</b></td><td>$r[ip_address]:$r[port]</td><td>$r[username]</td><td>$badge</td>
        <td><a href='?page=router&del_nas=$r[id]' onclick=\"return confirm('Hapus NAS ini?')\" class='btn btn-r' style='padding:4px 8px;font-size:11px'>Hapus</a></td></tr>";
    }
    ?>
  </table>
</div>
