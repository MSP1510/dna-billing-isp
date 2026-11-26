<?php
if(isset($_GET['sync_mt']) && $f_rid>0){
  $rt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM routers WHERE id=$f_rid"));
  if($rt && class_exists('RouterosAPI')){
    $api=new RouterosAPI(); $api->timeout=2;
    if($api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port'])){
      $qg=mysqli_query($conn,"SELECT id FROM grup_profil WHERE router_id=$f_rid LIMIT 1");
      $gid=(mysqli_num_rows($qg)>0)?(int)mysqli_fetch_assoc($qg)['id']:0;
      if($gid==0){ mysqli_query($conn,"INSERT INTO grup_profil (nama_grup,tipe,ip_lokal,ip_awal,ip_akhir,router_id) VALUES ('".mysqli_real_escape_string($conn,$rt['nama_nas'])."','PPP','10.10.10.1','10.10.10.2','10.10.10.254',$f_rid)"); $gid=(int)mysqli_insert_id($conn); }
      foreach(['PPP'=>'/ppp/profile/print','HOTSPOT'=>'/ip/hotspot/user/profile/print'] as $tp=>$cmd){
        $c=[]; $mt_names=[]; foreach($api->comm($cmd) as $l){
          if($l!=='!re'){ $nm=$c['name']??''; if($nm!=='' && !in_array($nm,['default','default-encryption'])){
            $mt_names[]="'".mysqli_real_escape_string($conn,$nm)."'";
            $rl=mysqli_real_escape_string($conn,$c['rate-limit']??''); $sh=(int)($c['shared-users']??1); $sn=mysqli_real_escape_string($conn,$nm);
            $hrg=0; if(preg_match('/,(\d{3,7}),/',($c['on-up']??'').' '.($c['comment']??''),$m)) $hrg=(int)$m;
            $chk=mysqli_query($conn,"SELECT p.id,p.harga FROM paket p JOIN grup_profil g ON p.grup_id=g.id WHERE p.nama_paket='$sn' AND p.tipe='$tp' AND g.router_id=$f_rid");
            if(mysqli_num_rows($chk)==0){ mysqli_query($conn,"INSERT INTO paket (nama_paket,tipe,bw_id,grup_id,harga_modal,harga,masa_aktif,kuota,shared_users,profile_mikrotik) VALUES ('$sn','$tp',0,$gid,$hrg,$hrg,'30d','Unlimited',$sh,'$rl')"); }
            else { $old=mysqli_fetch_assoc($chk); $nh=($hrg>0)?$hrg:(int)$old['harga']; mysqli_query($conn,"UPDATE paket SET profile_mikrotik='$rl',shared_users=$sh,harga=$nh WHERE id=".(int)$old['id']); }
          } $c=[]; } elseif(strpos($l,'=')===0){ $p=explode('=',substr($l,1),2); if(count($p)==2)$c[$p]=$p; }
        }
      } $api->disconnect();
    }
  } $sb=$_GET['sub']??'bw'; echo "<script>location.href='?page=paket&sub=$sb&rid=$f_rid&synced=1';</script>"; exit;
}
