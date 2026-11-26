<?php
function pushPktMt($api,$rt,$tp,$nm,$sh,$rl,$ip='',$pool='',$del=false){
  if(!$rt||!$api)return; $api->timeout=2;
  if($api->connect($rt['ip_address'],$rt['username'],$rt['password'],$rt['port'])){
    $b=($tp=='PPP')?'/ppp/profile':'/ip/hotspot/user/profile';
    $ex=$api->comm($b.'/print',['?name'=>$nm]); $id='';
    foreach($ex as $x){ if(strpos($x,'=.id=')===0)$id=substr($x,5); }
    if($del){ if($id!=='')$api->comm($b.'/remove',['.id'=>$id]); }
    else{ $p=($tp=='PPP')?['name'=>$nm,'local-address'=>$ip,'remote-address'=>$pool,'rate-limit'=>$rl]:['name'=>$nm,'shared-users'=>$sh,'rate-limit'=>$rl]; if($id!==''){$p['.id']=$id;$api->comm($b.'/set',$p);}else{$api->comm($b.'/add',$p);} }
    $api->disconnect();
  }
}
