<?php
$cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan WHERE id=1"));
$api = new RouterosAPI();
$mt_ok = false; $ppp_on = 0; $hs_on = 0; $ppp_list = []; $nb_list = [];
$mt_info = ['cpu'=>0,'ram_pct'=>0,'uptime'=>'-','board'=>'Multi-NAS','ver'=>'-','rx'=>0,'tx'=>0,'iface'=>'ether1'];
$ifaces = []; $all_routers = [];

$qr_all = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while($rt = mysqli_fetch_assoc($qr_all)) {
    $all_routers[] = $rt;
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        $mt_ok = true;
        $r1 = $api->comm('/ppp/active/print');
        foreach($r1 as $l) { if(strpos($l,'=name=')===0) { $ppp_on++; $ppp_list[] = substr($l,6)." (".$rt['nama_nas'].")"; } }
        $r2 = $api->comm('/ip/hotspot/active/print');
        foreach($r2 as $l) { if(strpos($l,'=user=')===0) $hs_on++; }
        if ($rt['id'] == 1 || $mt_info['board'] == 'Multi-NAS') {
            $nb_list = $api->comm('/ip/neighbor/print');
            $res = $api->comm('/system/resource/print');
            $tm=1; $fm=1;
            foreach($res as $l) {
                if(strpos($l,'=cpu-load=')===0) $mt_info['cpu'] = (int)substr($l,10);
                if(strpos($l,'=total-memory=')===0) $tm = max(1,(int)substr($l,14));
                if(strpos($l,'=free-memory=')===0) $fm = (int)substr($l,13);
                if(strpos($l,'=uptime=')===0) $mt_info['uptime'] = substr($l,8);
                if(strpos($l,'=board-name=')===0) $mt_info['board'] = $rt['nama_nas']." - ".substr($l,12);
                if(strpos($l,'=version=')===0) $mt_info['ver'] = substr($l,9);
            }
            $mt_info['ram_pct'] = round((($tm - $fm) / $tm) * 100);
            $ifl = $api->comm('/interface/print');
            foreach($ifl as $l) { if(strpos($l,'=name=')===0) $ifaces[] = substr($l,6); }
            $sel_if = $_GET['iface'] ?? ($ifaces ?? 'ether1');
            $mt_info['iface'] = $sel_if;
            $tr = $api->comm('/interface/monitor-traffic', ['interface'=>$sel_if, 'once'=>'']);
            foreach($tr as $l) {
                if(strpos($l,'=rx-bits-per-second=')===0) $mt_info['rx'] = round((int)substr($l,20)/1000000, 2);
                if(strpos($l,'=tx-bits-per-second=')===0) $mt_info['tx'] = round((int)substr($l,20)/1000000, 2);
            }
        }
        $api->disconnect();
    }
}
$file_page = "/var/www/html/pages/" . preg_replace('/[^a-z0-9_]/', '', $page) . ".php";
if (file_exists($file_page)) { require $file_page; } else { require "/var/www/html/pages/dashboard.php"; }
?>
</div></div></body></html>
