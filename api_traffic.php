<?php
header('Content-Type: application/json');
error_reporting(0);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/mikrotik.php';

$res = ['status'=>'Offline','rx'=>0,'tx'=>0,'ppp_on'=>0,'ppp_off'=>0,'ppp_isolir'=>0,'ppp_tot'=>0,'hs_on'=>0,'hs_off'=>0,'hs_tot'=>0,'interfaces'=>[],'selected_if'=>'','packages'=>[]];
$rid = isset($_GET['router_id']) ? (int)$_GET['router_id'] : 0;
$sel_if = $_GET['iface'] ?? '';
$where = $rid > 0 ? "WHERE id = $rid" : "ORDER BY id ASC LIMIT 1";
$qr = mysqli_query($conn, "SELECT * FROM routers $where");

if ($qr && $rt = mysqli_fetch_assoc($qr)) {
  $api = new RouterosAPI();
  $api->timeout = 2;
  if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], (int)($rt['port'] ?: 8728))) {
    $res['status'] = 'Online';
    foreach ($api->comm('/interface/print') as $l) if (strpos($l, '=name=') === 0) $res['interfaces'][] = substr($l, 6);
    if (!$sel_if || !in_array($sel_if, $res['interfaces'])) $sel_if = $res['interfaces'] ?? 'ether1';
    $res['selected_if'] = $sel_if;
    foreach ($api->comm('/interface/monitor-traffic', ['interface'=>$sel_if, 'once'=>'']) as $l) {
      if (strpos($l, '=rx-bits-per-second=') === 0) $res['rx'] = round((int)substr($l, 20)/1000000, 2);
      if (strpos($l, '=tx-bits-per-second=') === 0) $res['tx'] = round((int)substr($l, 20)/1000000, 2);
    }
    $act = []; foreach ($api->comm('/ppp/active/print') as $l) if (strpos($l, '=name=') === 0) $act[] = substr($l, 6);
    $res['ppp_on'] = count($act);
    $pkg = []; $cn = ''; $cp = 'Default'; $dis = 'false';
    foreach ($api->comm('/ppp/secret/print') as $l) {
      if ($l === '!re') {
        if ($cn !== '') {
          $res['ppp_tot']++; if ($dis === 'true' || stripos($cp, 'isolir') !== false) $res['ppp_isolir']++;
          if (!isset($pkg[$cp])) $pkg[$cp] = ['name'=>$cp,'total'=>0,'online'=>0];
          $pkg[$cp]['total']++; if (in_array($cn, $act)) $pkg[$cp]['online']++;
        }
        $cn = ''; $cp = 'Default'; $dis = 'false';
      } elseif (strpos($l, '=name=') === 0) $cn = substr($l, 6);
      elseif (strpos($l, '=profile=') === 0) $cp = substr($l, 9);
      elseif (strpos($l, '=disabled=') === 0) $dis = substr($l, 10);
    }
    if ($cn !== '') {
      $res['ppp_tot']++; if ($dis === 'true' || stripos($cp, 'isolir') !== false) $res['ppp_isolir']++;
      if (!isset($pkg[$cp])) $pkg[$cp] = ['name'=>$cp,'total'=>0,'online'=>0];
      $pkg[$cp]['total']++; if (in_array($cn, $act)) $pkg[$cp]['online']++;
    }
    $res['ppp_off'] = max(0, $res['ppp_tot'] - $res['ppp_on']);
    $res['packages'] = array_values($pkg);
    foreach ($api->comm('/ip/hotspot/active/print') as $l) if (strpos($l, '=user=') === 0) $res['hs_on']++;
    foreach ($api->comm('/ip/hotspot/user/print') as $l) if (strpos($l, '=name=') === 0 && substr($l, 6) !== 'default-trial') $res['hs_tot']++;
    $res['hs_off'] = max(0, $res['hs_tot'] - $res['hs_on']);
    $api->disconnect();
  }
}
echo json_encode($res);
