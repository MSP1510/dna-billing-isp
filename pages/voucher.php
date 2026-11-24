<?php cekAkses(['owner','admin','kasir']);
function acakKodeVoucher($p=8,$t='angka'){
  $k=($t=='huruf')?'ABCDEFGHJKLMNPQRSTUVWXYZ':(($t=='campur')?'23456789ABCDEFGHJKLMNPQRSTUVWXYZ':'0123456789');
  $r=''; $m=strlen($k)-1; for($i=0;$i<$p;$i++)$r.=$k[random_int(0,$m)]; return $r;
}
function waktuKeDetik($s){
  $d=0; $num='';
  for($i=0;$i<strlen($s);$i++){
    $ch=$s[$i];
    if(ctype_digit($ch)){$num.=$ch;}
    elseif($num!==''){
      $v=(int)$num; $num='';
      if($ch=='w')$d+=$v*604800; elseif($ch=='d')$d+=$v*86400; elseif($ch=='h')$d+=$v*3600; elseif($ch=='m')$d+=$v*60; elseif($ch=='s')$d+=$v;
    }
  }
  return $d;
}
function detikKeTeks($s){
  if($s<=0)return 'Habis'; $d=floor($s/86400);$s%=86400; $h=floor($s/3600);$s%=3600; $m=floor($s/60);
  return ($d>0?$d.'d ':'').($h>0?$h.'h ':'').$m.'m';
}
$map_pkt=[]; $qp_all=mysqli_query($conn,"SELECT * FROM paket"); while($rp=mysqli_fetch_assoc($qp_all))$map_pkt[$rp['nama_paket']]=$rp;
if(isset($_POST['gen_vcr'])){
  $pk=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM paket WHERE id=".(int)$_POST['paket_id']));
  $rid=(int)($_POST['router_id']??1); $rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=$rid"));
  if($pk){
    $prof=$pk['nama_paket']; $hrg=(float)$pk['harga']; $masa=$pk['masa_aktif']?:'1 Hari'; $ul=trim($pk['durasi_uptime']??''); $km=trim($_POST['komentar']??'');
    $ok=($rt && $api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port']));
    for($i=0;$i<max(1,min(200,(int)$_POST['jumlah']));$i++){
      $kd=acakKodeVoucher(max(4,min(12,(int)$_POST['panjang'])),$_POST['tipe_kode']);
      if(mysqli_num_rows(mysqli_query($conn,"SELECT id FROM voucher WHERE kode='$kd'"))>0){$i--;continue;}
      $sk=mysqli_real_escape_string($conn,$kd); $sp=mysqli_real_escape_string($conn,$prof); $sm=mysqli_real_escape_string($conn,$masa);
      mysqli_query($conn,"INSERT INTO voucher (kode,profil,harga,status,masa_aktif,router_id,sudah_cetak) VALUES ('$sk','$sp',$hrg,'stok','$sm',$rid,0)");
      mysqli_query($conn,"INSERT IGNORE INTO radcheck (username,attribute,op,value) VALUES ('$sk','Cleartext-Password',':=','$sk')");
      mysqli_query($conn,"INSERT IGNORE INTO radusergroup (username,groupname,priority) VALUES ('$sk','$sp',1)");
      if($ok){ $par=['=name='.$kd,'=password='.$kd,'=profile='.$prof]; if($ul!=='')$par[]='=limit-uptime='.$ul; if($km!=='')$par[]='=comment='.$km; $api->comm('/ip/hotspot/user/add',$par); }
    }
    if($ok)$api->disconnect(); echo "<script>location.href='?page=voucher';</script>"; exit;
  }
}
if(isset($_GET['del_mt_user'])&&isset($_GET['rid'])){
  $rid=(int)$_GET['rid']; $usr=$_GET['del_mt_user']; $mtid=$_GET['mtid']??'';
  $rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=$rid"));
  if($rt && $api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port'])){
    if($mtid!=='')$api->comm('/ip/hotspot/user/remove',['=.id='.$mtid]);
    $cur=[]; foreach($api->comm('/ip/hotspot/active/print') as $l){ if($l=='!re'){if(($cur['user']??'')==$usr&&!empty($cur['id']))$api->comm('/ip/hotspot/active/remove',['=.id='.$cur['id']]);$cur=[];} elseif(strpos($l,'=.id=')===0)$cur['id']=substr($l,5); elseif(strpos($l,'=user=')===0)$cur['user']=substr($l,6); }
    if(($cur['user']??'')==$usr&&!empty($cur['id']))$api->comm('/ip/hotspot/active/remove',['=.id='.$cur['id']]); $api->disconnect();
  }
  $sk=mysqli_real_escape_string($conn,$usr); mysqli_query($conn,"DELETE FROM radcheck WHERE username='$sk'"); mysqli_query($conn,"DELETE FROM radusergroup WHERE username='$sk'"); mysqli_query($conn,"DELETE FROM voucher WHERE kode='$sk'");
  echo "<script>location.href='?page=voucher';</script>"; exit;
}
$mt_list=[]; $hs_logs=[]; $profiles_opt=[]; $comments_opt=[]; $del_exp=0;
$qr=mysqli_query($conn,"SELECT * FROM routers ORDER BY id ASC");
while($rt=mysqli_fetch_assoc($qr)){
  if($api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port'])){
    $akt=[]; $cur=[]; foreach($api->comm('/ip/hotspot/active/print') as $l){ if($l=='!re'){if(!empty($cur['user']))$akt[$cur['user']]=$cur;$cur=[];} elseif(strpos($l,'=.id=')===0)$cur['id']=substr($l,5); elseif(strpos($l,'=user=')===0)$cur['user']=substr($l,6); elseif(strpos($l,'=address=')===0)$cur['ip']=substr($l,9); elseif(strpos($l,'=session-time-left=')===0)$cur['left']=substr($l,19); }
    if(!empty($cur['user']))$akt[$cur['user']]=$cur;
    $raw=[]; $cur=[]; foreach($api->comm('/ip/hotspot/user/print') as $l){ if($l=='!re'){if(!empty($cur['name']))$raw[]=$cur;$cur=[];} elseif(strpos($l,'=.id=')===0)$cur['id']=substr($l,5); elseif(strpos($l,'=name=')===0)$cur['name']=substr($l,6); elseif(strpos($l,'=password=')===0)$cur['pass']=substr($l,10); elseif(strpos($l,'=profile=')===0)$cur['prof']=substr($l,9); elseif(strpos($l,'=limit-uptime=')===0)$cur['lim']=substr($l,14); elseif(strpos($l,'=uptime=')===0)$cur['up']=substr($l,8); elseif(strpos($l,'=comment=')===0)$cur['cmt']=substr($l,9); }
    if(!empty($cur['name']))$raw[]=$cur;
    foreach($raw as $u){
      $nm=trim($u['name']); if($nm==''||$nm=='default-trial')continue; $pr=trim($u['prof']??'default'); $cm=trim($u['cmt']??'');
      $ls=$u['lim']??($map_pkt[$pr]['durasi_uptime']??''); $us=$u['up']??'0s'; $lsec=waktuKeDetik($ls); $usec=waktuKeDetik($us);
      $sk=mysqli_real_escape_string($conn,$nm); $sp=mysqli_real_escape_string($conn,$pr);
      if($lsec>0 && $usec>=$lsec){
        if(!empty($u['id']))$api->comm('/ip/hotspot/user/remove',['=.id='.$u['id']]);
        if(isset($akt[$nm])&&!empty($akt[$nm]['id']))$api->comm('/ip/hotspot/active/remove',['=.id='.$akt[$nm]['id']]);
        mysqli_query($conn,"DELETE FROM radcheck WHERE username='$sk'"); mysqli_query($conn,"DELETE FROM radusergroup WHERE username='$sk'"); mysqli_query($conn,"DELETE FROM voucher WHERE kode='$sk'"); $del_exp++; continue;
      }
      if(mysqli_num_rows(mysqli_query($conn,"SELECT id FROM voucher WHERE kode='$sk'"))==0){
        $hrg=(float)($map_pkt[$pr]['harga']??0); $ms=mysqli_real_escape_string($conn,$map_pkt[$pr]['masa_aktif']??($ls?:'1 Hari'));
        mysqli_query($conn,"INSERT INTO voucher (kode,profil,harga,status,masa_aktif,router_id,sudah_cetak) VALUES ('$sk','$sp',$hrg,'stok','$ms',".(int)$rt['id'].",1)");
      }
      if($pr!=='')$profiles_opt[$pr]=true; if($cm!=='')$comments_opt[$cm]=true; $on=isset($akt[$nm]);
      $sisa=$on?(!empty($akt[$nm]['left'])?$akt[$nm]['left']:($lsec>0?detikKeTeks($lsec-$usec):'Unlimited')):($lsec>0?detikKeTeks($lsec-$usec):($ls?:'Unlimited'));
      $mt_list[]=['rid'=>$rt['id'],'mtid'=>$u['id']??'','name'=>$nm,'prof'=>$pr,'up'=>$us,'sisa'=>$sisa,'cmt'=>$cm,'online'=>$on,'used'=>($usec>0||$on)];
    }
    $cur=[]; foreach($api->comm('/log/print') as $l){ if($l=='!re'){$tp=$cur['topics']??'';$mg=$cur['message']??''; if(strpos($tp,'hotspot')!==false||strpos($mg,'logged in')!==false||strpos($mg,'logged out')!==false||strpos($mg,'login failed')!==false)$hs_logs[]=['time'=>$cur['time']??'','msg'=>$mg];$cur=[];} elseif(strpos($l,'=time=')===0)$cur['time']=substr($l,6); elseif(strpos($l,'=topics=')===0)$cur['topics']=substr($l,8); elseif(strpos($l,'=message=')===0)$cur['message']=substr($l,9); }
    $api->disconnect();
  }
}
$hs_logs=array_slice(array_reverse($hs_logs),0,50);
$fs=trim($_GET['q']??''); $fp=trim($_GET['prof']??''); $fc=trim($_GET['cmt']??'');
$filtered=array_filter($mt_list,function($u)use($fs,$fp,$fc){ if($fs!==''&&stripos($u['name'],$fs)===false)return false; if($fp!==''&&$u['prof']!==$fp)return false; if($fc!==''&&$u['cmt']!==$fc)return false; return true; });
?>
<?php if($del_exp>0): ?><div class="card" style="background:#fef2f2;border-left:5px solid #ef4444;color:#991b1b;font-size:12px"><b>Auto-Clean:</b> <?= $del_exp ?> voucher habis durasi dihapus otomatis!</div><?php endif; ?>
<div class="card" style="border-left:5px solid #0284c7">
  <h4>Generate Voucher Hotspot Acak (Langsung Masuk ke MikroTik)</h4>
  <form method="POST" style="margin-top:8px">
    <select name="paket_id" required><option value="">-- Pilih Paket Profil Hotspot --</option><?php $qp=mysqli_query($conn,"SELECT * FROM paket WHERE UPPER(tipe)='HOTSPOT' ORDER BY harga ASC"); while($pk=mysqli_fetch_assoc($qp))echo "<option value='$pk[id]'>$pk[nama_paket] - Rp ".number_format($pk['harga'],0,',','.')." ($pk[masa_aktif])</option>"; ?></select>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px">
      <select name="tipe_kode" style="flex:1;min-width:130px"><option value="angka">Angka Saja (0-9)</option><option value="huruf">Huruf Saja (A-Z)</option><option value="campur">Huruf & Angka</option></select>
      <select name="panjang" style="width:90px"><option value="6">6 Digit</option><option value="7">7 Digit</option><option value="8" selected>8 Digit</option></select>
      <input type="number" name="jumlah" value="28" min="1" max="200" required style="width:80px">
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;align-items:center">
      <input name="komentar" placeholder="Comment (opsional)" style="flex:1;min-width:120px">
      <select name="router_id" style="width:130px"><?php $qr=mysqli_query($conn,"SELECT * FROM routers ORDER BY id ASC"); while($rt=mysqli_fetch_assoc($qr))echo "<option value='$rt[id]'>$rt[nama_nas]</option>"; ?></select>
      <button name="gen_vcr" class="btn btn-g">+ Generate</button>
    </div>
  </form>
</div>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:8px">
    <h4>Users Hotspot (<?= count($filtered) ?> / <?= count($mt_list) ?>)</h4>
    <div style="display:flex;gap:5px"><a href="cetak_voucher.php?mode=baru" target="_blank" class="btn" style="padding:5px 9px;font-size:11px">Default</a><a href="cetak_voucher.php?mode=semua&prof=<?= urlencode($fp) ?>" target="_blank" class="btn btn-g" style="padding:5px 9px;font-size:11px">QR Semua</a></div>
  </div>
  <form method="GET" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px">
    <input type="hidden" name="page" value="voucher">
    <input name="q" value="<?= htmlspecialchars($fs) ?>" placeholder="Search..." style="flex:1;min-width:100px">
    <select name="prof" onchange="this.form.submit()" style="flex:1;min-width:95px"><option value="">Profile</option><?php foreach(array_keys($profiles_opt) as $o)echo "<option value='".htmlspecialchars($o)."' ".($fp===$o?'selected':'').">".htmlspecialchars($o)."</option>"; ?></select>
    <select name="cmt" onchange="this.form.submit()" style="flex:1;min-width:95px"><option value="">Comment</option><?php foreach(array_keys($comments_opt) as $o)echo "<option value='".htmlspecialchars($o)."' ".($fc===$o?'selected':'').">".htmlspecialchars($o)."</option>"; ?></select>
    <button class="btn" style="padding:5px 10px">Cari</button>
  </form>
  <div style="overflow-x:auto"><table>
    <tr><th>Aksi</th><th>Name</th><th>QR</th><th>Profile</th><th>Uptime</th><th>Sisa Waktu</th><th>Comment</th></tr>
    <?php foreach($filtered as $u): $sc=$u['online']?'#22c55e':($u['used']?'#f59e0b':'#0284c7'); $st=$u['online']?'ON':($u['used']?'PAKAI':'BARU'); ?>
    <tr>
      <td style="white-space:nowrap"><a href="?page=voucher&del_mt_user=<?= urlencode($u['name']) ?>&mtid=<?= urlencode($u['mtid']) ?>&rid=<?= $u['rid'] ?>" onclick="return confirm('Hapus user <?= htmlspecialchars($u['name']) ?>?')" class="btn btn-r" style="padding:2px 7px;font-size:12px;font-weight:900">-</a> <span style="background:<?= $sc ?>;color:#fff;padding:2px 5px;border-radius:4px;font-size:9px;font-weight:700"><?= $st ?></span></td>
      <td style="font-family:monospace;font-size:13px;font-weight:800"><?= htmlspecialchars($u['name']) ?></td>
      <td><a href="cetak_voucher.php?kode=<?= urlencode($u['name']) ?>" target="_blank" style="text-decoration:none;font-size:12px;font-weight:700;color:#0284c7">QR</a></td>
      <td><b><?= htmlspecialchars($u['prof']) ?></b></td>
      <td><small><?= htmlspecialchars($u['up']) ?></small></td>
      <td><span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700;white-space:nowrap"><?= htmlspecialchars($u['sisa']) ?></span></td>
      <td><small style="color:#64748b"><?= htmlspecialchars($u['cmt']?:'-') ?></small></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
