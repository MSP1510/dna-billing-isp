<?php cekAkses(['owner','admin']);
if (isset($_POST['gen_vc'])) {
    set_time_limit(120);
    $rid = (int)$_POST['nas'];
    $pid = (int)$_POST['pkt'];
    $qty = max(1, min(300, (int)$_POST['qty']));
    $pk  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM paket WHERE id=$pid"));
    $rt  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=$rid"));
    $prof = $pk['nama_paket'] ?? 'default';
    $hrg  = (float)($pk['harga'] ?? 0);
    $ma   = $pk['masa_aktif'] ?? '4 Jam';
    $du   = $pk['durasi_uptime'] ?? '4h';
    $ds   = (int)($pk['durasi_detik'] ?? 14400);
    $mt_on = ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port']));
    for ($i = 0; $i < $qty; $i++) {
        $kode = "DNA" . rand(100000, 999999);
        mysqli_query($conn, "INSERT INTO voucher (kode,profil,harga,masa_aktif,router_id) VALUES ('$kode','$prof',$hrg,'$ma',$rid)");
        if (function_exists('syncRadiusUser')) {
            syncRadiusUser($conn, $kode, $kode, $pk['profile_mikrotik'] ?? '');
            mysqli_query($conn, "INSERT INTO radreply (username, attribute, op, value) VALUES ('$kode', 'Session-Timeout', ':=', '$ds')");
        }
        if ($mt_on) $api->comm('/ip/hotspot/user/add', ['name'=>$kode, 'password'=>$kode, 'profile'=>$prof, 'limit-uptime'=>$du]);
    }
    if ($mt_on) $api->disconnect();
    echo "<script>location.href='?page=voucher';</script>"; exit;
}
if (isset($_GET['jual'])) {
    $vid = (int)$_GET['jual'];
    $v = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM voucher WHERE id=$vid"));
    if ($v && $v['status'] == 'stok') {
        mysqli_query($conn, "UPDATE voucher SET status='terjual' WHERE id=$vid");
        mysqli_query($conn, "INSERT INTO keuangan (tipe,kategori,keterangan,jumlah) VALUES ('masuk','Voucher Hotspot','Terjual Voucher $v[kode] ($v[profil])',$v[harga])");
    }
    echo "<script>location.href='?page=voucher';</script>"; exit;
}
if (isset($_GET['del_vc'])) {
    $vid = (int)$_GET['del_vc'];
    $v = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM voucher WHERE id=$vid"));
    if ($v) {
        mysqli_query($conn, "DELETE FROM radcheck WHERE username='$v[kode]'");
        mysqli_query($conn, "DELETE FROM radreply WHERE username='$v[kode]'");
        mysqli_query($conn, "DELETE FROM voucher WHERE id=$vid");
    }
    echo "<script>location.href='?page=voucher';</script>"; exit;
}
$stok_cnt = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM voucher WHERE status='stok'"));
$jual_sum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(harga) as t, COUNT(id) as c FROM voucher WHERE status='terjual'"));
?>
<div class="tiles">
  <div class="tile t-blue"><h2><?= $stok_cnt ?> Kartu</h2><p>Stok Voucher Tersedia</p></div>
  <div class="tile t-green"><h2>Rp <?= number_format($jual_sum['t']??0) ?></h2><p>Voucher Terjual (<?= $jual_sum['c']??0 ?> Kartu)</p></div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
    <h4>Generate Kartu Voucher Hotspot (1 - 300 Kartu / Klik)</h4>
    <button type="button" onclick="cetakSemuaVoucher()" class="btn btn-w">Cetak Semua Kartu Stok</button>
  </div>
  <form method="POST">
    <select name="nas"><?php foreach($all_routers as $r) echo "<option value='$r[id]'>$r[nama_nas]</option>"; ?></select>
    <select name="pkt" required><option value="">-- Pilih Profil Hotspot --</option>
      <?php $qp=mysqli_query($conn,"SELECT * FROM paket WHERE tipe='HOTSPOT'"); while($p=mysqli_fetch_assoc($qp)) echo "<option value='$p[id]'>$p[nama_paket] - Rp ".number_format($p['harga'])." ($p[masa_aktif])</option>"; ?>
    </select>
    <input name="qty" type="number" value="10" min="1" max="300" style="width:90px" placeholder="1-300" title="Jumlah 1 - 300 Kartu" required>
    <button name="gen_vc" class="btn btn-g">+ Generate Voucher</button>
  </form>

  <div style="overflow-x:auto">
  <table>
    <tr><th>Kode Voucher</th><th>Paket / Masa Aktif</th><th>Harga</th><th>Router NAS</th><th>Status</th><th>Aksi</th></tr>
    <?php
    $q = mysqli_query($conn, "SELECT v.*, r.nama_nas FROM voucher v LEFT JOIN routers r ON v.router_id=r.id ORDER BY v.id DESC LIMIT 300");
    $stok_list = [];
    while($d = mysqli_fetch_assoc($q)):
      if($d['status']=='stok') $stok_list[] = $d;
      $cetak_tag = !empty($d['sudah_cetak']) ? " <span style='background:#64748b;color:#fff;padding:2px 5px;border-radius:4px;font-size:9px'>SUDAH CETAK</span>" : " <span style='background:#f39c12;color:#fff;padding:2px 5px;border-radius:4px;font-size:9px'>BELUM CETAK</span>";
      $st = ($d['status']=='terjual' ? "<span style='background:#00a65a;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>TERJUAL</span>" : "<span style='background:#0284c7;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>STOK</span>") . $cetak_tag;
    ?>
    <tr>
      <td><b style="font-family:monospace;font-size:14px;color:#0f172a"><?= $d['kode'] ?></b></td>
      <td><b><?= $d['profil'] ?></b> <small>(<?= $d['masa_aktif'] ?>)</small></td>
      <td>Rp <?= number_format($d['harga']) ?></td>
      <td><?= $d['nama_nas']??'NAS-1' ?></td>
      <td><?= $st ?></td>
      <td style="white-space:nowrap">
        <?php if($d['status']=='stok'): ?>
          <a href="?page=voucher&jual=<?= $d['id'] ?>" class="btn btn-g" style="padding:3px 7px;font-size:11px">Terjual</a>
        <?php endif; ?>
        <button type="button" onclick='cetakSatu(<?= json_encode($d) ?>)' class="btn" style="padding:3px 7px;font-size:11px">Print</button>
        <a href="?page=voucher&del_vc=<?= $d['id'] ?>" class="btn btn-r" style="padding:3px 7px;font-size:11px">Hapus</a>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>

<script>
const stokVC = <?= json_encode($stok_list) ?>;
function cetakSatu(v){ cetakList([v]); }
function cetakSemuaVoucher(){ if(!stokVC.length) return alert('Tidak ada stok voucher.'); cetakList(stokVC); }
function cetakList(arr){
  let h = '<div style="display:flex;flex-wrap:wrap;gap:8px;font-family:sans-serif">';
  arr.forEach(v => {
    h += `<div style="border:1.5px dashed #334155;border-radius:6px;padding:8px 12px;width:165px;text-align:center">
      <div style="font-size:10px;font-weight:800;color:#0284c7">D.N.A_NETWORK</div>
      <div style="font-size:9px;color:#64748b;margin-bottom:4px">by MSP_NET</div>
      <div style="background:#f1f5f9;padding:4px;font-family:monospace;font-size:15px;font-weight:900;letter-spacing:1px;border-radius:4px">${v.kode}</div>
      <div style="font-size:10px;margin-top:4px"><b>${v.profil}</b> (${v.masa_aktif})</div>
      <div style="font-size:11px;font-weight:700;color:#00a65a">Rp ${Number(v.harga).toLocaleString('id-ID')}</div>
    </div>`;
  });
  h += '</div>';
  let w = window.open('','','width=650,height=600');
  w.document.write(h); w.print();
}
</script>
