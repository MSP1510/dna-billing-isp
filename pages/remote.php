<?php
$f_rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
$w_rt = $f_rid > 0 ? "WHERE id=$f_rid" : "ORDER BY id ASC";
$w_pl = $f_rid > 0 ? "AND router_id=$f_rid" : "";
 cekAkses(['owner','admin','teknisi']);
$online_ppp = []; $online_arp = [];
$qr = mysqli_query($conn, "SELECT * FROM routers ".$w_rt."");
while ($rt = mysqli_fetch_assoc($qr)) {
    $api->timeout = 1; if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        // Ambil khusus PPPoE Active (abaikan akun hotspot dinamis)
        $cur = [];
        foreach ($api->comm('/ppp/active/print') as $l) {
            if ($l == '!re') {
                $nm = trim($cur['name'] ?? '');
                if ($nm !== '' && strpos($nm, '<hotspot-') === false && !empty($cur['ip'])) {
                    $online_ppp[$nm] = trim($cur['ip']);
                }
                $cur = [];
            }
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
        }
        $nm = trim($cur['name'] ?? '');
        if ($nm !== '' && strpos($nm, '<hotspot-') === false && !empty($cur['ip'])) $online_ppp[$nm] = trim($cur['ip']);

        // Ambil ARP aktif untuk dicocokkan HANYA dengan pelanggan Static IP terdaftar
        $cur = [];
        foreach ($api->comm('/ip/arp/print') as $l) {
            if ($l == '!re') {
                if (!empty($cur['ip']) && ($cur['comp'] ?? 'false') == 'true') $online_arp[trim($cur['ip'])] = true;
                $cur = [];
            }
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=complete=')===0) $cur['comp'] = substr($l,10);
        }
        if (!empty($cur['ip']) && ($cur['comp'] ?? 'false') == 'true') $online_arp[trim($cur['ip'])] = true;
        $api->disconnect();
    }
}

$list_pppoe = []; $list_static = [];
$qp = mysqli_query($conn, "SELECT nama, pppoe_user, ip_static, tipe_layanan FROM pelanggan WHERE nama NOT LIKE '<hotspot-%' ".$w_pl." ORDER BY nama ASC");
while ($p = mysqli_fetch_assoc($qp)) {
    $tipe = $p['tipe_layanan'] ?? 'pppoe';
    if ($tipe == 'static') {
        $ip = trim($p['ip_static'] ?? '');
        if ($ip !== '' && isset($online_arp[$ip])) {
            $list_static[] = ['nama' => $p['nama'], 'akun' => $ip, 'ip' => $ip];
        }
    } else {
        $usr = trim($p['pppoe_user'] ?? '');
        if ($usr !== '' && isset($online_ppp[$usr])) {
            $list_pppoe[] = ['nama' => $p['nama'], 'akun' => $usr, 'ip' => $online_ppp[$usr]];
        }
    }
}
?>
<div class="card" style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;"><b style="font-size:13px;color:#0f172a;">Filter Router MikroTik</b><select onchange="location.href='?page=remote&rid='+this.value" style="padding:8px 12px;border-radius:8px;border:1px solid #cbd5e1;font-weight:700;font-size:13px;background:#fff;color:#0f172a;"><option value="0">Semua Router (Total Gabungan)</option><?php $qr_r=mysqli_query($conn,"SELECT id,nama_nas FROM routers ORDER BY id ASC");while($rr=mysqli_fetch_assoc($qr_r)){$sl=($f_rid==(int)$rr["id"])?"selected":"";echo "<option value='{$rr['id']}' $sl>MikroTik: ".htmlspecialchars($rr["nama_nas"])."</option>";}?></select></div>
<div class="card" style="border-left:5px solid #0284c7;position:relative;">
<h4>Remote Cepat (Cari Nama Pelanggan / IP)</h4>
<form action="remote_proxy.php" method="GET" target="_blank" onsubmit="if(!document.getElementById('rt-ip').value)document.getElementById('rt-ip').value=document.getElementById('rt-q').value.trim();" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;position:relative;">
<input type="hidden" name="set_target" id="rt-ip" required>
<div style="flex:1;min-width:170px;position:relative;">
<input type="text" id="rt-q" placeholder="Ketik Nama Pelanggan / IP..." required autocomplete="off" oninput="filterModem(this.value)" onfocus="filterModem(this.value)" style="width:100%;box-sizing:border-box;">
<div id="rt-drop" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:999;background:#fff;border:1px solid #cbd5e1;border-radius:8px;max-height:210px;overflow-y:auto;box-shadow:0 6px 16px rgba(0,0,0,0.15);margin-top:4px;"></div>
</div>
<select name="port" style="width:110px">
<option value="80">Port 80</option>
<option value="8080">Port 8080</option>
<option value="443">Port 443</option>
</select>
<button class="btn">Buka Remote</button>
</form>
<script>
const MODEM_LIST = <?= json_encode(array_values(array_merge($list_pppoe, $list_static))) ?>;
function filterModem(q){
    const d = document.getElementById("rt-drop");
    document.getElementById("rt-ip").value = "";
    q = q.trim().toLowerCase();
    if(!q){ d.style.display="none"; return; }
    const m = MODEM_LIST.filter(x => (x.nama+" "+x.akun+" "+x.ip).toLowerCase().includes(q)).slice(0, 15);
    if(!m.length){ d.style.display="none"; return; }
    d.innerHTML = m.map(x => `<div onclick="pickModem('${x.ip}','${x.nama.replace(/'/g,"")}')" style="padding:8px 10px;border-bottom:1px solid #f1f5f9;cursor:pointer;font-size:12px;"><b>${x.nama}</b> <span style="color:#64748b;">(${x.akun})</span><br><span style="color:#0284c7;font-family:monospace;font-weight:700;">${x.ip}</span></div>`).join("");
    d.style.display = "block";
}
function pickModem(ip, nama){
    document.getElementById("rt-q").value = nama + " (" + ip + ")";
    document.getElementById("rt-ip").value = ip;
    document.getElementById("rt-drop").style.display = "none";
}
document.addEventListener("click", e => { if(!e.target.closest("#rt-q") && !e.target.closest("#rt-drop")) document.getElementById("rt-drop").style.display="none"; });
</script>
</div>

<div class="card" style="border-top:4px solid #0284c7">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
    <h4>1. Remote Modem ONT — Pelanggan PPPoE Online (<?= count($list_pppoe) ?>)</h4>
    <a href="?page=remote&rid=<?= $f_rid ?>" class="btn" style="padding:4px 8px;font-size:11px">Refresh</a>
  </div>
  <div style="overflow-x:auto"><table>
    <tr><th>Nama Pelanggan</th><th>User PPPoE</th><th>IP Modem Aktif</th><th>Aksi Remote Cepat</th></tr>
    <?php foreach($list_pppoe as $m): ?>
    <tr>
      <td><b><?= htmlspecialchars($m['nama']) ?></b></td>
      <td><small><?= htmlspecialchars($m['akun']) ?></small></td>
      <td style="font-family:monospace;font-weight:700;color:#0284c7"><?= htmlspecialchars($m['ip']) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=80" target="_blank" class="btn btn-g" style="padding:4px 8px;font-size:11px">Port 80</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=8080" target="_blank" class="btn" style="padding:4px 8px;font-size:11px">Port 8080</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=443" target="_blank" class="btn btn-w" style="padding:4px 8px;font-size:11px">Port 443</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($list_pppoe)) echo "<tr><td colspan='4' style='text-align:center;color:#64748b'>Tidak ada modem PPPoE online</td></tr>"; ?>
  </table></div>
</div>

<div class="card" style="border-top:4px solid #8b5cf6">
  <h4>2. Remote Modem ONT — Pelanggan Static IP Online (<?= count($list_static) ?>)</h4>
  <div style="overflow-x:auto;margin-top:8px"><table>
    <tr><th>Nama Pelanggan</th><th>IP Static Modem</th><th>Aksi Remote Cepat</th></tr>
    <?php foreach($list_static as $m): ?>
    <tr>
      <td><b><?= htmlspecialchars($m['nama']) ?></b></td>
      <td style="font-family:monospace;font-weight:700;color:#8b5cf6"><?= htmlspecialchars($m['ip']) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=80" target="_blank" class="btn btn-g" style="padding:4px 8px;font-size:11px">Port 80</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=8080" target="_blank" class="btn" style="padding:4px 8px;font-size:11px">Port 8080</a>
        <a href="remote_proxy.php?set_target=<?= urlencode($m['ip']) ?>&port=443" target="_blank" class="btn btn-w" style="padding:4px 8px;font-size:11px">Port 443</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($list_static)) echo "<tr><td colspan='3' style='text-align:center;color:#64748b'>Tidak ada modem Static IP online</td></tr>"; ?>
  </table></div>
</div>
<style>.table td,.table th,table td,table th{padding:7px 8px!important;font-size:12px!important;line-height:1.25!important;vertical-align:middle!important;height:auto!important;}table .btn,table a{padding:5px 8px!important;font-size:11px!important;white-space:nowrap!important;}</style>
