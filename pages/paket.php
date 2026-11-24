<?php cekAkses(['owner','admin']);
$sub = $_GET['sub'] ?? 'ppp';

function formatRate($bw) {
    if (!$bw) return '';
    return "{$bw['up_max']}/{$bw['dl_max']} 0/0 0/0 0/0 8 {$bw['up_min']}/{$bw['dl_min']}";
}
?>
<div class="card" style="padding:12px;margin-bottom:14px">
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    <a href="?page=paket&sub=bw" class="btn <?= $sub=='bw'?'btn-g':'' ?>">1. Bandwidth (Up To)</a>
    <a href="?page=paket&sub=grup" class="btn <?= $sub=='grup'?'btn-g':'' ?>">2. Grup Profil / IP Pool</a>
    <a href="?page=paket&sub=hotspot" class="btn <?= $sub=='hotspot'?'btn-g':'' ?>">3. Profil Hotspot (Voucher)</a>
    <a href="?page=paket&sub=ppp" class="btn <?= $sub=='ppp'?'btn-g':'' ?>">4. Profil PPP (Bulanan)</a>
  </div>
</div>
<?php if ($sub == 'bw'):
if (isset($_POST['save_bw'])) {
    if (!empty($_POST['id'])) {
        mysqli_query($conn,"UPDATE bandwidth SET nama_bw='$_POST[nb]',up_min='$_POST[umin]',up_max='$_POST[umax]',dl_min='$_POST[dmin]',dl_max='$_POST[dmax]' WHERE id=".(int)$_POST['id']);
    } else {
        mysqli_query($conn,"INSERT INTO bandwidth (nama_bw,up_min,up_max,dl_min,dl_max) VALUES ('$_POST[nb]','$_POST[umin]','$_POST[umax]','$_POST[dmin]','$_POST[dmax]')");
    }
    echo "<script>location.href='?page=paket&sub=bw';</script>"; exit;
}
if (isset($_GET['del_bw'])) { mysqli_query($conn,"DELETE FROM bandwidth WHERE id=".(int)$_GET['del_bw']); echo "<script>location.href='?page=paket&sub=bw';</script>"; exit; }
$eb = isset($_GET['edit_bw']) ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM bandwidth WHERE id=".(int)$_GET['edit_bw'])) : null;
?>
<div class="card">
  <h4><?= $eb?'Edit':'Tambah' ?> Profil Bandwidth (Min / Max Up To)</h4>
  <form method="POST" style="margin-top:10px">
    <input type="hidden" name="id" value="<?= $eb['id']??'' ?>">
    <input name="nb" value="<?= $eb['nama_bw']??'' ?>" placeholder="Nama BW (mis: PROFBAND-20M)" required>
    <input name="umin" value="<?= $eb['up_min']??'5M' ?>" placeholder="Upload Min (5M)" style="width:110px" required>
    <input name="umax" value="<?= $eb['up_max']??'10M' ?>" placeholder="Upload Max/UpTo (10M)" style="width:120px" required>
    <input name="dmin" value="<?= $eb['dl_min']??'10M' ?>" placeholder="Download Min (10M)" style="width:120px" required>
    <input name="dmax" value="<?= $eb['dl_max']??'20M' ?>" placeholder="Download Max/UpTo (20M)" style="width:130px" required>
    <button name="save_bw" class="btn btn-g"><?= $eb?'Update':'+ Simpan' ?> Bandwidth</button>
    <?php if($eb) echo "<a href='?page=paket&sub=bw' class='btn btn-r'>Batal</a>"; ?>
  </form>
  <table><tr><th>Nama Bandwidth</th><th>Upload (Min | Max)</th><th>Download (Min | Max)</th><th>Aksi</th></tr>
  <?php $q=mysqli_query($conn,"SELECT * FROM bandwidth ORDER BY id DESC"); while($d=mysqli_fetch_assoc($q)): ?>
  <tr><td><b><?= $d['nama_bw'] ?></b></td><td><?= $d['up_min'] ?> | <b><?= $d['up_max'] ?></b></td><td><?= $d['dl_min'] ?> | <b><?= $d['dl_max'] ?></b></td>
  <td><a href="?page=paket&sub=bw&edit_bw=<?= $d['id'] ?>" class="btn" style="padding:4px 8px">Edit</a> <a href="?page=paket&sub=bw&del_bw=<?= $d['id'] ?>" onclick="return confirm('Hapus?')" class="btn btn-r" style="padding:4px 8px">Hapus</a></td></tr>
  <?php endwhile; ?></table>
</div>

<?php elseif ($sub == 'grup'):
if (isset($_POST['save_grp'])) {
    mysqli_query($conn,"INSERT INTO grup_profil (nama_grup,tipe,ip_lokal,ip_pertama,ip_terakhir,router_id) VALUES ('$_POST[ng]','$_POST[tp]','$_POST[ipl]','$_POST[ip1]','$_POST[ip2]','$_POST[nas]')");
    $rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=".(int)$_POST['nas']));
    if($rt && $_POST['tp']=='PPP' && $api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port'])){
        $api->comm('/ip/pool/add',['name'=>$_POST['ng'],'ranges'=>"$_POST[ip1]-$_POST[ip2]"]);
        $api->disconnect();
    }
    echo "<script>location.href='?page=paket&sub=grup';</script>"; exit;
}
if (isset($_GET['del_grp'])) { mysqli_query($conn,"DELETE FROM grup_profil WHERE id=".(int)$_GET['del_grp']); echo "<script>location.href='?page=paket&sub=grup';</script>"; exit; }
?>
<div class="card">
  <h4>Manajemen Grup Profil & IP Pool (Export ke Router NAS)</h4>
  <form method="POST" style="margin-top:10px">
    <input name="ng" placeholder="Nama Grup (mis: CBD-PRM)" required>
    <select name="tp"><option value="PPP">PPP</option><option value="HOTSPOT">HOTSPOT</option></select>
    <input name="ipl" placeholder="IP Lokal (10.249.252.1)">
    <input name="ip1" placeholder="IP Pertama (10.249.252.2)">
    <input name="ip2" placeholder="IP Terakhir (10.249.252.254)">
    <select name="nas"><?php foreach($all_routers as $r) echo "<option value='$r[id]'>$r[nama_nas]</option>"; ?></select>
    <button name="save_grp" class="btn btn-g">+ Simpan & Export Pool</button>
  </form>
  <table><tr><th>Nama Grup</th><th>Tipe</th><th>IP Lokal</th><th>Range Pool</th><th>Router NAS</th><th>Aksi</th></tr>
  <?php $q=mysqli_query($conn,"SELECT g.*,r.nama_nas FROM grup_profil g LEFT JOIN routers r ON g.router_id=r.id ORDER BY g.id DESC"); while($d=mysqli_fetch_assoc($q)): ?>
  <tr><td><b><?= $d['nama_grup'] ?></b></td><td><?= $d['tipe'] ?></td><td><?= $d['ip_lokal'] ?></td><td><?= $d['ip_pertama'] ?> - <?= $d['ip_terakhir'] ?></td><td><?= $d['nama_nas'] ?></td>
  <td><a href="?page=paket&sub=grup&del_grp=<?= $d['id'] ?>" class="btn btn-r" style="padding:4px 8px">Hapus</a></td></tr>
  <?php endwhile; ?></table>
</div>
<?php endif; ?>
<?php if ($sub == 'ppp' || $sub == 'hotspot'):
$tipe_pkt = ($sub == 'ppp') ? 'PPP' : 'HOTSPOT';
if (isset($_POST['save_pkt'])) {
    $bwid = (int)$_POST['bwid']; $grid = (int)$_POST['grid'];
    $bw = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM bandwidth WHERE id=$bwid"));
    $gr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM grup_profil WHERE id=$grid"));
    $rate = formatRate($bw);
    if (!empty($_POST['id'])) {
        mysqli_query($conn,"UPDATE paket SET nama_paket='$_POST[np]',bw_id=$bwid,grup_id=$grid,harga_modal='$_POST[hm]',harga='$_POST[hj]',masa_aktif='$_POST[ma]',kuota='$_POST[ku]',shared_users='$_POST[sh]',profile_mikrotik='$rate' WHERE id=".(int)$_POST['id']);
    } else {
        mysqli_query($conn,"INSERT INTO paket (nama_paket,tipe,bw_id,grup_id,harga_modal,harga,masa_aktif,kuota,shared_users,profile_mikrotik) VALUES ('$_POST[np]','$tipe_pkt',$bwid,$grid,'$_POST[hm]','$_POST[hj]','$_POST[ma]','$_POST[ku]','$_POST[sh]','$rate')");
    }
    if ($gr && $rt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=".(int)$gr['router_id']))) {
        if ($api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port'])) {
            if ($tipe_pkt=='PPP') $api->comm('/ppp/profile/add',['name'=>$_POST['np'],'local-address'=>$gr['ip_lokal'],'remote-address'=>$gr['nama_grup'],'rate-limit'=>$rate]);
            else $api->comm('/ip/hotspot/user/profile/add',['name'=>$_POST['np'],'shared-users'=>$_POST['sh'],'rate-limit'=>$rate]);
            $api->disconnect();
        }
    }
    echo "<script>location.href='?page=paket&sub=$sub';</script>"; exit;
}
if (isset($_GET['del_pkt'])) { mysqli_query($conn,"DELETE FROM paket WHERE id=".(int)$_GET['del_pkt']); echo "<script>location.href='?page=paket&sub=$sub';</script>"; exit; }
$ep = isset($_GET['edit_pkt']) ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM paket WHERE id=".(int)$_GET['edit_pkt'])) : null;
?>
<div class="card">
  <h4><?= $ep?'Edit':'Tambah' ?> Profil <?= $tipe_pkt ?> (<?= $tipe_pkt=='PPP'?'Langganan Bulanan':'Voucher Hotspot' ?>)</h4>
  <form method="POST" style="margin-top:10px">
    <input type="hidden" name="id" value="<?= $ep['id']??'' ?>">
    <input name="np" value="<?= $ep['nama_paket']??'' ?>" placeholder="<?= $tipe_pkt=='PPP'?'Nama Paket (INT-PRM-50Mbps)':'Nama Voucher (VC-4JAM)' ?>" required>
    <select name="bwid" required><option value="">-- Pilih Bandwidth --</option>
      <?php $qb=mysqli_query($conn,"SELECT * FROM bandwidth"); while($b=mysqli_fetch_assoc($qb)) echo "<option value='$b[id]' ".(($ep['bw_id']??0)==$b['id']?'selected':'').">$b[nama_bw] ($b[up_max]/$b[dl_max])</option>"; ?>
    </select>
    <select name="grid"><option value="0">-- Pilih Grup / Pool --</option>
      <?php $qg=mysqli_query($conn,"SELECT * FROM grup_profil"); while($g=mysqli_fetch_assoc($qg)) echo "<option value='$g[id]' ".(($ep['grup_id']??0)==$g['id']?'selected':'').">$g[nama_grup]</option>"; ?>
    </select><br>
    <input name="hm" type="number" value="<?= $ep['harga_modal']??'' ?>" placeholder="Harga Modal (Rp)" required>
    <input name="hj" type="number" value="<?= $ep['harga']??'' ?>" placeholder="Harga Jual (Rp)" required>
    <input name="ma" value="<?= $ep['masa_aktif']??($tipe_pkt=='PPP'?'1 Bulan':'4 Jam') ?>" placeholder="Masa Aktif (1 Bulan / 4 Jam)" style="width:140px">
    <input name="ku" value="<?= $ep['kuota']??'Unlimited' ?>" placeholder="Kuota (Unlimited / 5 GB)" style="width:130px">
    <input name="sh" type="number" value="<?= $ep['shared_users']??1 ?>" placeholder="Shared" style="width:70px">
    <button name="save_pkt" class="btn btn-g"><?= $ep?'Update Paket':'+ Simpan Paket' ?></button>
    <?php if($ep) echo "<a href='?page=paket&sub=$sub' class='btn btn-r'>Batal</a>"; ?>
  </form>
  <div style="overflow-x:auto">
  <table>
    <tr><th>Nama Profil</th><th>Grup</th><th>Bandwidth (Up/Down)</th><th>Harga Modal</th><th>Harga Jual</th><th>Masa Aktif | Kuota</th><th>Shared</th><th>Aksi</th></tr>
    <?php
    $q=mysqli_query($conn,"SELECT p.*, b.nama_bw, b.up_max, b.dl_max, g.nama_grup FROM paket p LEFT JOIN bandwidth b ON p.bw_id=b.id LEFT JOIN grup_profil g ON p.grup_id=g.id WHERE p.tipe='$tipe_pkt' ORDER BY p.id DESC");
    while($d=mysqli_fetch_assoc($q)): ?>
    <tr>
      <td><b><?= $d['nama_paket'] ?></b></td>
      <td><?= $d['nama_grup']??'-' ?></td>
      <td><b><?= $d['nama_bw']??'-' ?></b><br><small><?= $d['up_max']??'' ?> / <?= $d['dl_max']??'' ?></small></td>
      <td>Rp <?= number_format($d['harga_modal']) ?></td>
      <td><b style="color:#00a65a">Rp <?= number_format($d['harga']) ?></b></td>
      <td><?= $d['masa_aktif'] ?> | <?= $d['kuota'] ?></td>
      <td><?= $d['shared_users'] ?> Device</td>
      <td><a href="?page=paket&sub=<?= $sub ?>&edit_pkt=<?= $d['id'] ?>" class="btn" style="padding:4px 8px">Edit</a> <a href="?page=paket&sub=<?= $sub ?>&del_pkt=<?= $d['id'] ?>" onclick="return confirm('Hapus paket ini?')" class="btn btn-r" style="padding:4px 8px">Hapus</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>
<?php endif; ?>
