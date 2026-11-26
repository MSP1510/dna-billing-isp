<?php
header('Content-Type: application/json');
error_reporting(0);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/mikrotik.php';

$res = [
    'status'      => 'Offline',
    'rx'          => 0,
    'tx'          => 0,
    'ppp_on'      => 0,
    'ppp_off'     => 0,
    'ppp_isolir'  => 0,
    'ppp_tot'     => 0,
    'hs_on'       => 0,
    'hs_off'      => 0,
    'hs_tot'      => 0,
    'interfaces'  => [],
    'selected_if' => '',
    'packages'    => [],
    'kas_masuk'   => 0,
    'kas_keluar'  => 0,
    'kas_total'   => 0,
    'pemasangan'  => 0
];

$rid    = isset($_GET['router_id']) ? (int)$_GET['router_id'] : 0;
$scope  = $_GET['scope'] ?? 'all';
$sel_if = trim($_GET['iface'] ?? '');

// 1. Ambil Traffic Interface untuk Router yang dipilih di bagian Speedometer
$w_tf = $rid > 0 ? "WHERE id = $rid" : "ORDER BY id ASC LIMIT 1";
$qr_tf = mysqli_query($conn, "SELECT * FROM routers $w_tf");
if ($qr_tf && $rt = mysqli_fetch_assoc($qr_tf)) {
    $api = new RouterosAPI();
    $api->timeout = 2;
    if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], (int)($rt['port'] ?: 8728))) {
        $res['status'] = 'Online';
        foreach ($api->comm('/interface/print') as $l) {
            if (strpos($l, '=name=') === 0) $res['interfaces'][] = substr($l, 6);
        }
        if (!$sel_if || !in_array($sel_if, $res['interfaces'])) {
            $sel_if = !empty($res['interfaces']) ? $res['interfaces'][0] : 'ether1';
        }
        $res['selected_if'] = $sel_if;
        foreach ($api->comm('/interface/monitor-traffic', ['interface' => $sel_if, 'once' => '']) as $l) {
            if (strpos($l, '=rx-bits-per-second=') === 0) $res['rx'] = round((int)substr($l, 20) / 1000000, 2);
            if (strpos($l, '=tx-bits-per-second=') === 0) $res['tx'] = round((int)substr($l, 20) / 1000000, 2);
        }
        $api->disconnect();
    }
}

// 2. Hitung PPPoE, Hotspot & Paket (Gabungan Semua Router atau Router Terpilih)
$w_sc = ($scope !== 'all' && (int)$scope > 0) ? "WHERE id = " . (int)$scope : "ORDER BY id ASC";
$qr_all = mysqli_query($conn, "SELECT * FROM routers $w_sc");
$pkg = [];

if ($qr_all) {
    while ($r = mysqli_fetch_assoc($qr_all)) {
        $api2 = new RouterosAPI();
        $api2->timeout = 1;
        if ($api2->connect($r['ip_address'], $r['username'], $r['password'], (int)($r['port'] ?: 8728))) {
            // PPP Active
            $act = [];
            foreach ($api2->comm('/ppp/active/print') as $l) {
                if (strpos($l, '=name=') === 0) $act[] = substr($l, 6);
            }
            $res['ppp_on'] += count($act);

            // PPP Secret & Profile
            $cn = ''; $cp = 'Default'; $dis = 'false';
            foreach ($api2->comm('/ppp/secret/print') as $l) {
                if ($l === '!re') {
                    if ($cn !== '') {
                        $res['ppp_tot']++;
                        if ($dis === 'true' || stripos($cp, 'isolir') !== false) $res['ppp_isolir']++;
                        if (!isset($pkg[$cp])) $pkg[$cp] = ['name' => $cp, 'total' => 0, 'online' => 0];
                        $pkg[$cp]['total']++;
                        if (in_array($cn, $act)) $pkg[$cp]['online']++;
                    }
                    $cn = ''; $cp = 'Default'; $dis = 'false';
                } elseif (strpos($l, '=name=') === 0) $cn = substr($l, 6);
                elseif (strpos($l, '=profile=') === 0) $cp = substr($l, 9);
                elseif (strpos($l, '=disabled=') === 0) $dis = substr($l, 10);
            }
            if ($cn !== '') {
                $res['ppp_tot']++;
                if ($dis === 'true' || stripos($cp, 'isolir') !== false) $res['ppp_isolir']++;
                if (!isset($pkg[$cp])) $pkg[$cp] = ['name' => $cp, 'total' => 0, 'online' => 0];
                $pkg[$cp]['total']++;
                if (in_array($cn, $act)) $pkg[$cp]['online']++;
            }

            // Hotspot Active & Users
            $hs_act = [];
            foreach ($api2->comm('/ip/hotspot/active/print') as $l) {
                if (strpos($l, '=user=') === 0) $hs_act[trim(substr($l, 6))] = true;
            }
            $res['hs_on'] += count($hs_act);

            $hs_all = $hs_act;
            foreach ($api2->comm('/ip/hotspot/user/print') as $l) {
                if (strpos($l, '=name=') === 0) {
                    $unm = trim(substr($l, 6));
                    if ($unm !== '' && $unm !== 'default-trial') $hs_all[$unm] = true;
                }
            }
            $tot_r_hs = max(count($hs_act), count($hs_all));
            $res['hs_tot'] += $tot_r_hs;
            $api2->disconnect();
        }
    }
}

$res['ppp_off'] = max(0, $res['ppp_tot'] - $res['ppp_on'] - $res['ppp_isolir']);
$res['hs_off']  = max(0, $res['hs_tot'] - $res['hs_on']);
$pkg = array_filter($pkg, function ($v) { return stripos($v['name'], 'isolir') === false; });
$res['packages'] = array_values($pkg);

// 3. Global Arus Kas & Pemasangan dari Seluruh Pelanggan & Router Terdaftar
$q_in1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) as s FROM keuangan WHERE tipe='masuk'"));
$q_in2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) as s FROM tagihan WHERE status='lunas'"));
$q_out = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) as s FROM keuangan WHERE tipe='keluar'"));
$q_psg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as j FROM pelanggan WHERE MONTH(tgl_daftar)=MONTH(CURDATE()) AND YEAR(tgl_daftar)=YEAR(CURDATE())"));

$res['kas_masuk']  = (float)($q_in1['s'] ?? 0) + (float)($q_in2['s'] ?? 0);
$res['kas_keluar'] = (float)($q_out['s'] ?? 0);
$res['kas_total']  = $res['kas_masuk'] - $res['kas_keluar'];
$res['pemasangan'] = (int)($q_psg['j'] ?? 0);

echo json_encode($res);
