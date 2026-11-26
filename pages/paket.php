<?php
$f_rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0; include_once __DIR__.'/paket_sync.php'; include_once __DIR__.'/paket_push.php';
if(isset($_GET["sync_mt"]) && $f_rid>0){ $rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=$f_rid")); if($rt && class_exists("RouterosAPI")){ $api=new RouterosAPI(); $api->timeout=2; if($api->connect($rt["ip_address"],$rt["username"],$rt["password"],$rt["port"])){ $qg=mysqli_query($conn,"SELECT id FROM grup_profil WHERE router_id=$f_rid LIMIT 1"); $gid=(mysqli_num_rows($qg)>0)?(int)mysqli_fetch_assoc($qg)["id"]:0; if($gid==0){ mysqli_query($conn,"INSERT INTO grup_profil (nama_grup,router_id) VALUES (\"".mysqli_real_escape_string($conn,$rt["nama_nas"])."\",$f_rid)"); $gid=(int)mysqli_insert_id($conn); } foreach(["PPP"=>"/ppp/profile/print","HOTSPOT"=>"/ip/hotspot/user/profile/print"] as $tp=>$cmd){ $cur=[]; foreach($api->comm($cmd) as $l){ if($l!=="!re"){ $nm=$cur["name"]??""; if($nm!=="" && !in_array($nm,["default","default-encryption"])){ $rl=mysqli_real_escape_string($conn,$cur["rate-limit"]??""); $sh=(int)($cur["shared-users"]??1); $sn=mysqli_real_escape_string($conn,$nm); $chk=mysqli_query($conn,"SELECT p.id FROM paket p JOIN grup_profil g ON p.grup_id=g.id WHERE p.nama_paket=\"$sn\" AND p.tipe=\"$tp\" AND (g.router_id=$f_rid OR g.router_id IS NULL)"); if(mysqli_num_rows($chk)==0) mysqli_query($conn,"INSERT INTO paket (nama_paket,tipe,bw_id,grup_id,harga_modal,harga,masa_aktif,kuota,shared_users,profile_mikrotik) VALUES (\"$sn\",\"$tp\",0,$gid,0,0,\"30d\",\"Unlimited\",$sh,\"$rl\")"); } $cur=[]; } elseif(strpos($l,"=")===0){ $p=explode("=",substr($l,1),2); if(count($p)==2)$cur[$p]=$p; } } } $api->disconnect(); } } $sb=$_GET["sub"]??"bw"; echo "<script>location.href=\"?page=paket&sub=$sb&rid=$f_rid&synced=1\";</script>"; exit; }


$f_rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0; include_once __DIR__.'/paket_sync.php'; include_once __DIR__.'/paket_push.php';
if(isset($_GET["sync_mt"]) && $f_rid>0){ $rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=$f_rid")); if($rt && class_exists("RouterosAPI")){ $api=new RouterosAPI(); $api->timeout=2; if($api->connect($rt["ip_address"],$rt["username"],$rt["password"],$rt["port"])){ $qg=mysqli_query($conn,"SELECT id FROM grup_profil WHERE router_id=$f_rid LIMIT 1"); $gid=(mysqli_num_rows($qg)>0)?(int)mysqli_fetch_assoc($qg)["id"]:0; if($gid==0){ mysqli_query($conn,"INSERT INTO grup_profil (nama_grup,router_id) VALUES (\"".mysqli_real_escape_string($conn,$rt["nama_nas"])."\",$f_rid)"); $gid=(int)mysqli_insert_id($conn); } foreach(["PPP"=>"/ppp/profile/print","HOTSPOT"=>"/ip/hotspot/user/profile/print"] as $tp=>$cmd){ $cur=[]; foreach($api->comm($cmd) as $l){ if($l!=="!re"){ $nm=$cur["name"]??""; if($nm!=="" && !in_array($nm,["default","default-encryption"])){ $rl=mysqli_real_escape_string($conn,$cur["rate-limit"]??""); $sh=(int)($cur["shared-users"]??1); $sn=mysqli_real_escape_string($conn,$nm); $chk=mysqli_query($conn,"SELECT p.id FROM paket p JOIN grup_profil g ON p.grup_id=g.id WHERE p.nama_paket=\"$sn\" AND p.tipe=\"$tp\" AND (g.router_id=$f_rid OR g.router_id IS NULL)"); if(mysqli_num_rows($chk)==0) mysqli_query($conn,"INSERT INTO paket (nama_paket,tipe,bw_id,grup_id,harga_modal,harga,masa_aktif,kuota,shared_users,profile_mikrotik) VALUES (\"$sn\",\"$tp\",0,$gid,0,0,\"30d\",\"Unlimited\",$sh,\"$rl\")"); } $cur=[]; } elseif(strpos($l,"=")===0){ $p=explode("=",substr($l,1),2); if(count($p)==2)$cur[$p]=$p; } } } $api->disconnect(); } } $sb=$_GET["sub"]??"bw"; echo "<script>location.href=\"?page=paket&sub=$sb&rid=$f_rid&synced=1\";</script>"; exit; }

$sub = $_GET['sub'] ?? 'bw';
if(isset($_GET['sync_mt']) && $f_rid>0){
  $rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=$f_rid"));
  if($rt && class_exists('RouterosAPI')){
    $api=new RouterosAPI(); $api->timeout=2;
    if($api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port'])){
      $qg=mysqli_query($conn,"SELECT id FROM grup_profil WHERE router_id=$f_rid LIMIT 1");
      if(mysqli_num_rows($qg)>0){ $gid=(int)mysqli_fetch_assoc($qg)['id']; } else { mysqli_query($conn,"INSERT INTO grup_profil (nama_grup,router_id) VALUES ('".mysqli_real_escape_string($conn,$rt['nama_nas'])."',$f_rid)"); $gid=(int)mysqli_insert_id($conn); }
      foreach(['PPP'=>'/ppp/profile/print','HOTSPOT'=>'/ip/hotspot/user/profile/print'] as $tp=>$cmd){
        $cur=[]; foreach($api->comm($cmd) as $l){
          if($l!=='!re'){ $nm=$cur['name']??''; if($nm!=='' && !in_array($nm,['default','default-encryption'])){
            $rl=mysqli_real_escape_string($conn,$cur['rate-limit']??''); $sh=(int)($cur['shared-users']??1); $sn=mysqli_real_escape_string($conn,$nm);
            $chk=mysqli_query($conn,"SELECT p.id FROM paket p JOIN grup_profil g ON p.grup_id=g.id WHERE p.nama_paket='$sn' AND p.tipe='$tp' AND (g.router_id=$f_rid OR g.router_id IS NULL)");
            if(mysqli_num_rows($chk)==0){ mysqli_query($conn,"INSERT INTO paket (nama_paket,tipe,bw_id,grup_id,harga_modal,harga,masa_aktif,kuota,shared_users,profile_mikrotik) VALUES ('$sn','$tp',0,$gid,0,0,'30d','Unlimited',$sh,'$rl')"); }
          } $cur=[]; } elseif(strpos($l,'=')===0){ $p=explode('=',substr($l,1),2); if(count($p)==2)$cur[$p[0]]=$p[1]; }
        }
      } $api->disconnect();
    }
  } echo "<script>location.href='?page=paket&sub=$sub&rid=$f_rid&synced=1';</script>"; exit;
}
 cekAkses(['owner','admin']);
$sub = $_GET['sub'] ?? 'ppp';

function formatRate($bw) {
    if (!$bw) return '';
    return "{$bw['up_max']}/{$bw['dl_max']} 0/0 0/0 0/0 8 {$bw['up_min']}/{$bw['dl_min']}";
}
?>
<div class="card" style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;"><div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"><b style="font-size:13px;color:#0f172a;">Filter Router MikroTik</b><select onchange="location.href='?page=paket&rid=$f_rid&sub=<?= $sub ?>&rid='+this.value" style="padding:8px 12px;border-radius:8px;border:1px solid #cbd5e1;font-weight:700;font-size:13px;background:#fff;color:#0f172a;"><option value="0">Semua Router (Total Gabungan)</option><?php $qr_p=mysqli_query($conn,"SELECT id,nama_nas FROM routers ORDER BY id ASC");while($rp=mysqli_fetch_assoc($qr_p)){$sl=($f_rid==(int)$rp["id"])?"selected":"";echo "<option value='{$rp['id']}' $sl>MikroTik: ".htmlspecialchars($rp["nama_nas"])."</option>";}?></select></div><?php if($f_rid>0): ?><a href="?page=paket&rid=<?= $f_rid ?>&sub=<?= $sub ?>&rid=<?= $f_rid ?>&sync_mt=1" class="btn btn-g" style="padding:8px 12px;font-size:12px;">Sync Profil dari MikroTik</a><?php endif; ?></div><?php if(isset($_GET["synced"])): ?><div class="card" style="background:#ecfdf5;border-left:5px solid #10b981;color:#065f46;font-size:12px;margin-bottom:12px;"><b>Sinkronisasi Berhasil:</b> Daftar paket PPPoE & Hotspot berhasil ditarik dari MikroTik!</div><?php endif; ?>
<div class="card" style="padding:12px;margin-bottom:14px">
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    <a href="?page=paket&rid=<?= $f_rid ?>&sub=bw" class="btn <?= $sub=='bw'?'btn-g':'' ?>">1. Bandwidth (Up To)</a>
    <a href="?page=paket&rid=<?= $f_rid ?>&sub=grup" class="btn <?= $sub=='grup'?'btn-g':'' ?>">2. Grup Profil / IP Pool</a>
    <a href="?page=paket&rid=<?= $f_rid ?>&sub=hotspot" class="btn <?= $sub=='hotspot'?'btn-g':'' ?>">3. Profil Hotspot (Voucher)</a>
    <a href="?page=paket&rid=<?= $f_rid ?>&sub=ppp" class="btn <?= $sub=='ppp'?'btn-g':'' ?>">4. Profil PPP (Bulanan)</a>
  </div>
</div>
<?php if ($sub == 'bw'):
if (isset($_POST['save_bw'])) {
    if (!empty($_POST['id'])) {
        mysqli_query($conn,"UPDATE bandwidth SET nama_bw='$_POST[nb]',up_min='$_POST[umin]',up_max='$_POST[umax]',dl_min='$_POST[dmin]',dl_max='$_POST[dmax]' WHERE id=".(int)$_POST['id']);
    } else {
        mysqli_query($conn,"INSERT INTO bandwidth (nama_bw,up_min,up_max,dl_min,dl_max) VALUES ('$_POST[nb]','$_POST[umin]','$_POST[umax]','$_POST[dmin]','$_POST[dmax]')");
    }
    echo "<script>location.href='?page=paket&rid=$f_rid&sub=bw';</script>"; exit;
}
if (isset($_GET['del_bw'])) { mysqli_query($conn,"DELETE FROM bandwidth WHERE id=".(int)$_GET['del_bw']); echo "<script>location.href='?page=paket&rid=$f_rid&sub=bw';</script>"; exit; }
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
    <?php if($eb) echo "<a href='?page=paket&rid=$f_rid&sub=bw' class='btn btn-r'>Batal</a>"; ?>
  </form>
  <table><tr><th>Nama Bandwidth</th><th>Upload (Min | Max)</th><th>Download (Min | Max)</th><th>Aksi</th></tr>
  <?php $q=mysqli_query($conn,"SELECT * FROM bandwidth ORDER BY id DESC"); while($d=mysqli_fetch_assoc($q)): ?>
  <tr><td><b><?= $d['nama_bw'] ?></b></td><td><?= $d['up_min'] ?> | <b><?= $d['up_max'] ?></b></td><td><?= $d['dl_min'] ?> | <b><?= $d['dl_max'] ?></b></td>
  <td><a href="?page=paket&rid=<?= $f_rid ?>&sub=bw&edit_bw=<?= $d['id'] ?>" class="btn" style="padding:4px 8px">Edit</a> <a href="?page=paket&rid=<?= $f_rid ?>&sub=bw&del_bw=<?= $d['id'] ?>" onclick="return confirm('Hapus?')" class="btn btn-r" style="padding:4px 8px">Hapus</a></td></tr>
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
    echo "<script>location.href='?page=paket&rid=$f_rid&sub=grup';</script>"; exit;
}
if (isset($_GET['del_grp'])) { mysqli_query($conn,"DELETE FROM grup_profil WHERE id=".(int)$_GET['del_grp']); echo "<script>location.href='?page=paket&rid=$f_rid&sub=grup';</script>"; exit; }
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
  <?php $q=mysqli_query($conn,"SELECT g.*,r.nama_nas FROM grup_profil g LEFT JOIN routers r ON g.router_id=r.id ".($f_rid>0?"WHERE g.router_id=$f_rid":"")." ORDER BY g.id DESC"); while($d=mysqli_fetch_assoc($q)): ?>
  <tr><td><b><?= $d['nama_grup'] ?></b></td><td><?= $d['tipe'] ?></td><td><?= $d['ip_lokal'] ?></td><td><?= $d['ip_pertama'] ?> - <?= $d['ip_terakhir'] ?></td><td><?= $d['nama_nas'] ?></td>
  <td><a href="?page=paket&rid=<?= $f_rid ?>&sub=grup&del_grp=<?= $d['id'] ?>" class="btn btn-r" style="padding:4px 8px">Hapus</a></td></tr>
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
            pushPktMt($api,$rt,$tipe_pkt,$_POST['np'],$_POST['sh'],$rate,$gr['ip_lokal'],$gr['nama_grup'],false);
            $api->disconnect();
        }
    }
    echo "<script>location.href='?page=paket&rid=$f_rid&sub=$sub';</script>"; exit;
}
if (isset($_GET['del_pkt'])) { $dp=mysqli_fetch_assoc(mysqli_query($conn,"SELECT p.nama_paket,p.tipe,g.router_id FROM paket p LEFT JOIN grup_profil g ON p.grup_id=g.id WHERE p.id=".(int)$_GET['del_pkt'])); if($dp&&$dp['router_id']>0&&$rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=".(int)$dp['router_id'])))pushPktMt($api,$rt,strtoupper($dp['tipe']),$dp['nama_paket'],1,'','','',true); $dp=mysqli_fetch_assoc(mysqli_query($conn,"SELECT p.nama_paket,p.tipe,g.router_id FROM paket p LEFT JOIN grup_profil g ON p.grup_id=g.id WHERE p.id=".(int)$_GET['del_pkt'])); if($dp && $dp['router_id']>0 && $rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=".(int)$dp['router_id']))){ $api->timeout=2; if($api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port'])){ $bs=(strtoupper($dp['tipe'])=='PPP')?'/ppp/profile':'/ip/hotspot/user/profile'; $ex=$api->comm($bs.'/print',['?name'=>$dp['nama_paket']]); foreach($ex as $xl){ if(strpos($xl,'=.id=')===0) $api->comm($bs.'/remove',['.id'=>substr($xl,5)]); } $api->disconnect(); } } mysqli_query($conn,"DELETE FROM paket WHERE id=".(int)$_GET['del_pkt']); echo "<script>location.href='?page=paket&rid=$f_rid&sub=$sub';</script>"; exit; }
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
      <?php $qg=mysqli_query($conn,"SELECT * FROM grup_profil ".($f_rid>0?"WHERE router_id=$f_rid":"").""); while($g=mysqli_fetch_assoc($qg)) echo "<option value='$g[id]' ".(($ep['grup_id']??0)==$g['id']?'selected':'').">$g[nama_grup]</option>"; ?>
    </select><br>
    <input name="hm" type="number" value="<?= $ep['harga_modal']??'' ?>" placeholder="Harga Modal (Rp)" required>
    <input name="hj" type="number" value="<?= $ep['harga']??'' ?>" placeholder="Harga Jual (Rp)" required>
    <input name="ma" value="<?= $ep['masa_aktif']??($tipe_pkt=='PPP'?'1 Bulan':'4 Jam') ?>" placeholder="Masa Aktif (1 Bulan / 4 Jam)" style="width:140px">
    <input name="ku" value="<?= $ep['kuota']??'Unlimited' ?>" placeholder="Kuota (Unlimited / 5 GB)" style="width:130px">
    <input name="sh" type="number" value="<?= $ep['shared_users']??1 ?>" placeholder="Shared" style="width:70px">
    <button name="save_pkt" class="btn btn-g"><?= $ep?'Update Paket':'+ Simpan Paket' ?></button>
    <?php if($ep) echo "<a href='?page=paket&rid=$f_rid&sub=$sub' class='btn btn-r'>Batal</a>"; ?>
  </form>
  <div style="overflow-x:auto">
  <table>
    <tr><th>Nama Profil</th><th>Grup</th><th>Bandwidth (Up/Down)</th><th>Harga Modal</th><th>Harga Jual</th><th>Masa Aktif | Kuota</th><?php if($tipe_pkt=='HOTSPOT'): ?><th>Shared</th><?php endif; ?><th>Aksi</th></tr>
    <?php
    $q=mysqli_query($conn,"SELECT p.*, b.nama_bw, b.up_max, b.dl_max, g.nama_grup FROM paket p LEFT JOIN bandwidth b ON p.bw_id=b.id LEFT JOIN grup_profil g ON p.grup_id=g.id WHERE p.tipe='$tipe_pkt' ".($f_rid>0?"AND (g.router_id=$f_rid OR g.router_id IS NULL)":"")." ORDER BY p.id DESC");
    while($d=mysqli_fetch_assoc($q)): ?>
    <tr>
      <td><b><?= $d['nama_paket'] ?></b></td>
      <td><?= $d['nama_grup']??'-' ?></td>
      <td><b><?= $d['nama_bw']??'-' ?></b><br><small><?= $d['up_max']??'' ?> / <?= $d['dl_max']??'' ?></small></td>
      <td>Rp <?= number_format($d['harga_modal']) ?></td>
      <td><b style="color:#00a65a">Rp <?= number_format($d['harga']) ?></b></td>
      <td><?= $d['masa_aktif'] ?> | <?= $d['kuota'] ?></td>
      <?php if($tipe_pkt=='HOTSPOT'): ?><td><?= $d['shared_users'] ?> Device</td><?php endif; ?>
      <td style="white-space:nowrap"><a href="?page=paket&rid=<?= $f_rid ?>&sub=<?= $sub ?>&edit_pkt=<?= $d['id'] ?>" class="btn" style="padding:4px 8px">Edit</a> <a href="?page=paket&rid=<?= $f_rid ?>&sub=<?= $sub ?>&del_pkt=<?= $d['id'] ?>" onclick="return confirm('Hapus paket ini?')" class="btn btn-r" style="padding:4px 8px">Hapus</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>
<?php endif; ?>
