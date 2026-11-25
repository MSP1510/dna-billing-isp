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
    // Hitung juga pelanggan Static IP aktif (ARP) & isolir dari database agar sinkron dengan Monitor Online
        $arp_on = []; $cur_a = [];
        foreach ($api->comm('/ip/arp/print') as $al) {
            if ($al === '!re') { if (!empty($cur_a['ip']) && ($cur_a['c'] ?? 'false') === 'true') $arp_on[trim($cur_a['ip'])] = true; $cur_a = []; }
            elseif (strpos($al, '=address=') === 0) $cur_a['ip'] = substr($al, 9);
            elseif (strpos($al, '=complete=') === 0) $cur_a['c'] = substr($al, 10);
        }
        $q_st = mysqli_query($conn, "SELECT p.ip_static, p.status, COALESCE(pk.nama_paket, 'STATIC IP') as pkt FROM pelanggan p LEFT JOIN paket pk ON p.paket_id=pk.id WHERE p.tipe_layanan='static' AND p.nama NOT LIKE '<hotspot-%'");
        while ($st = mysqli_fetch_assoc($q_st)) {
            $res['ppp_tot']++;
            $pnm = $st['pkt'];
            if (!isset($pkg[$pnm])) $pkg[$pnm] = ['name' => $pnm, 'total' => 0, 'online' => 0];
            $pkg[$pnm]['total']++;
            $ip_s = trim($st['ip_static'] ?? '');
            if ($st['status'] === 'isolir') $res['ppp_isolir']++;
            elseif ($ip_s !== '' && isset($arp_on[$ip_s])) { $res['ppp_on']++; $pkg[$pnm]['online']++; }
        }

        // Cocokkan status isolir database pelanggan PPPoE
        $q_iso = mysqli_query($conn, "SELECT COUNT(*) as jml FROM pelanggan WHERE status='isolir' AND tipe_layanan!='static' AND nama NOT LIKE '<hotspot-%'");
        $db_iso = (int)(mysqli_fetch_assoc($q_iso)['jml'] ?? 0);
        if ($db_iso > $res['ppp_isolir']) $res['ppp_isolir'] = $db_iso;

        $res['ppp_off'] = max(0, $res['ppp_tot'] - $res['ppp_on'] - $res['ppp_isolir']);
        $pkg = array_filter($pkg, function($v){ return stripos($v['name'], 'isolir') === false; });
        $res['packages'] = array_values($pkg);

        // Sinkronkan Hotspot Online & Total Voucher dengan menu Kartu Voucher (MikroTik + DB)
        $hs_act_users = [];
        foreach ($api->comm('/ip/hotspot/active/print') as $l) {
            if (strpos($l, '=user=') === 0) $hs_act_users[trim(substr($l, 6))] = true;
        }
        $res['hs_on'] = count($hs_act_users);

        $hs_all_users = $hs_act_users;
        foreach ($api->comm('/ip/hotspot/user/print') as $l) {
            if (strpos($l, '=name=') === 0) {
                $unm = trim(substr($l, 6));
                if ($unm !== '' && $unm !== 'default-trial') $hs_all_users[$unm] = true;
            }
        }
        $qv_db = mysqli_query($conn, "SELECT kode FROM voucher");
        while ($rv = mysqli_fetch_assoc($qv_db)) {
            $kd = trim($rv['kode'] ?? '');
            if ($kd !== '') $hs_all_users[$kd] = true;
        }
        $res['hs_tot'] = max($res['hs_on'], count($hs_all_users));
        $res['hs_off'] = max(0, $res['hs_tot'] - $res['hs_on']);
        $api->disconnect();
  }
}

$q_in1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) as s FROM keuangan WHERE tipe='masuk'"));
$q_in2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) as s FROM tagihan WHERE status='lunas'"));
$q_out = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) as s FROM keuangan WHERE tipe='keluar'"));
$res['kas_masuk']  = (float)$q_in1['s'] + (float)$q_in2['s'];
$res['kas_keluar'] = (float)$q_out['s'];
$res['kas_total']  = $res['kas_masuk'] - $res['kas_keluar'];
echo json_encode($res);

