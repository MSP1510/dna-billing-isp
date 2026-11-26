<?php cekAkses(['owner','admin','teknisi']);
if (isset($_POST['add_odp'])) {
    $jns = mysqli_real_escape_string($conn, $_POST['jenis'] ?? 'ODP');
    $pid = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : "NULL";
    $nm  = mysqli_real_escape_string($conn, $_POST['nama_odp']);
    $lok = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $kap = (int)$_POST['kapasitas'];
    $lat = mysqli_real_escape_string($conn, str_replace(',', '.', trim($_POST['lat'])));
    $lng = mysqli_real_escape_string($conn, str_replace(',', '.', trim($_POST['lng'])));
    mysqli_query($conn, "INSERT INTO odp (router_id,jenis,parent_id,nama_odp,lokasi,kapasitas,lat,lng) VALUES (".(int)($_POST['router_id']??0).",'$jns',$pid,'$nm','$lok',$kap,'$lat','$lng')");
    echo "<script>location.href='?page=odp';</script>"; exit;
}
if (isset($_POST['save_pel_gis'])) {
    $id_p  = (int)$_POST['pel_id'];
    $odp_p = !empty($_POST['odp_id']) ? (int)$_POST['odp_id'] : "NULL";
    $lat_p = mysqli_real_escape_string($conn, str_replace(',', '.', trim($_POST['lat_p'])));
    $lng_p = mysqli_real_escape_string($conn, str_replace(',', '.', trim($_POST['lng_p'])));
    mysqli_query($conn, "UPDATE pelanggan SET odp_id=$odp_p, lat='$lat_p', lng='$lng_p' WHERE id=$id_p");
    echo "<script>location.href='?page=odp';</script>"; exit;
}
if (isset($_GET['reset_pel'])) {
    mysqli_query($conn, "UPDATE pelanggan SET odp_id=NULL, lat=NULL, lng=NULL WHERE id=".(int)$_GET['reset_pel']);
    echo "<script>location.href='?page=odp';</script>"; exit;
}
if (isset($_GET['del_odp'])) {
    mysqli_query($conn, "DELETE FROM odp WHERE id=".(int)$_GET['del_odp']);
    echo "<script>location.href='?page=odp';</script>"; exit;
}
$online_ppp = []; $online_arp = [];
$qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
while ($rt = mysqli_fetch_assoc($qr)) {
    $api->timeout = 1; if ($api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        $act = $api->comm('/ppp/active/print'); $cur = [];
        foreach ($act as $l) {
            if ($l == '!re') { if(!empty($cur['name'])) $online_ppp[$cur['name']] = true; $cur = []; }
            elseif (strpos($l,'=name=')===0) $cur['name'] = substr($l,6);
        }
        if (!empty($cur['name'])) $online_ppp[$cur['name']] = true;
        $arp = $api->comm('/ip/arp/print'); $cur = [];
        foreach ($arp as $l) {
            if ($l == '!re') { if(!empty($cur['ip']) && ($cur['comp']??'false')=='true') $online_arp[trim($cur['ip'])] = true; $cur = []; }
            elseif (strpos($l,'=address=')===0) $cur['ip'] = substr($l,9);
            elseif (strpos($l,'=complete=')===0) $cur['comp'] = substr($l,10);
        }
        if (!empty($cur['ip']) && ($cur['comp']??'false')=='true') $online_arp[trim($cur['ip'])] = true;
        $api->disconnect();
    }
}
$f_rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
$w_odp = $f_rid > 0 ? "WHERE o.router_id=$f_rid" : "";
$w_pel = $f_rid > 0 ? "AND p.router_id=$f_rid" : "";
$odp_list = [];
$qo = mysqli_query($conn, "SELECT o.*, (SELECT COUNT(id) FROM pelanggan p WHERE p.odp_id=o.id) as terpakai FROM odp o $w_odp ORDER BY o.id ASC");
while ($r = mysqli_fetch_assoc($qo)) $odp_list[] = $r;
$pel_list = []; $cnt_on = 0; $cnt_off = 0; $cnt_iso = 0;
$qp = mysqli_query($conn, "SELECT p.id, p.nama, p.pppoe_user, p.ip_static, p.tipe_layanan, p.status, p.odp_id, p.lat, p.lng, o.nama_odp FROM pelanggan p LEFT JOIN odp o ON p.odp_id=o.id WHERE p.nama NOT LIKE '<hotspot-%' $w_pel ORDER BY p.nama ASC");
while ($p = mysqli_fetch_assoc($qp)) {
    $is_st = (($p['tipe_layanan'] ?? 'pppoe') == 'static');
    $is_on = $is_st ? (!empty($p['ip_static']) && isset($online_arp[trim($p['ip_static'])])) : (!empty($p['pppoe_user']) && isset($online_ppp[trim($p['pppoe_user'])]));
    if (($p['status'] ?? '') == 'isolir') { $st_net = 'isolir'; $cnt_iso++; }
    elseif ($is_on) { $st_net = 'online'; $cnt_on++; }
    else { $st_net = 'offline'; $cnt_off++; }
    $p['st_net'] = $st_net; $p['akun'] = $is_st ? ($p['ip_static'] ?? '-') : ($p['pppoe_user'] ?? '-');
    $pel_list[] = $p;
}
?>
<link rel="stylesheet" href="assets/leaflet.css"/><script src="assets/leaflet.js"></script>
<style>@keyframes kabelJalan{to{stroke-dashoffset:-24}}.kabel-online{stroke-dasharray:8,6;animation:kabelJalan .8s linear infinite}.pin-node{display:flex;align-items:center;justify-content:center;border-radius:50%;color:#fff;font-weight:900;font-size:9px;box-shadow:0 0 6px #000;border:2px solid #fff}</style>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
    <h4>1. Peta Satelit POP, ODC, ODP & Pelanggan</h4>
<div style="margin-bottom:10px;"><select onchange="location.href='?page=odp&rid='+this.value" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid #cbd5e1;font-weight:700;font-size:13px;background:#f8fafc;color:#0f172a;"><option value="0">Peta: Semua MikroTik (Total Gabungan)</option><?php $qr_m=mysqli_query($conn,"SELECT id,nama_nas FROM routers ORDER BY id ASC");while($rm=mysqli_fetch_assoc($qr_m)){$sl=($f_rid==(int)$rm["id"])?"selected":"";echo "<option value='{$rm['id']}' $sl>Peta MikroTik: ".htmlspecialchars($rm["nama_nas"])."</option>";}?></select></div>
    <div><button type="button" onclick="setMode('sat')" class="btn" style="padding:4px 8px;font-size:11px">Satelit</button> <button type="button" onclick="setMode('osm')" class="btn btn-g" style="padding:4px 8px;font-size:11px">Jalan Raya</button></div>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px">
    <button type="button" onclick="setF('all')" class="btn" style="padding:3px 8px;font-size:10px">Semua (<?= count($pel_list) ?>)</button>
    <button type="button" onclick="setF('online')" class="btn btn-g" style="padding:3px 8px;font-size:10px">Online (<?= $cnt_on ?>)</button>
    <button type="button" onclick="setF('offline')" class="btn btn-w" style="padding:3px 8px;font-size:10px">Offline (<?= $cnt_off ?>)</button>
    <button type="button" onclick="setF('isolir')" class="btn btn-r" style="padding:3px 8px;font-size:10px">Isolir (<?= $cnt_iso ?>)</button>
  </div>
  <div id="mapGis" style="width:100%;height:340px;border-radius:8px;border:1px solid #cbd5e1;z-index:1"></div>
</div>
<div class="card" style="border-left:5px solid #0284c7;background:#f0f9ff">
  <h4>2. Daftarkan Server Utama (POP), Box ODC & Box ODP</h4>
  <form method="POST" style="margin-top:8px">
    <select name="router_id" style="width:100%;margin-bottom:8px;"><?php $qr_f=mysqli_query($conn,"SELECT id,nama_nas FROM routers ORDER BY id ASC");while($rf=mysqli_fetch_assoc($qr_f)){$sl_f=($f_rid==(int)$rf['id'])?"selected":""; echo "<option value='{$rf['id']}' $sl_f>Router: ".htmlspecialchars($rf["nama_nas"])."</option>";}?></select>
<select name="jenis"><option value="POP">Server Utama (POP)</option><option value="ODC">Box ODC (Distribusi)</option><option value="ODP" selected>Box ODP (Tiang Pelanggan)</option></select>
    <select name="parent_id"><option value="">-- Induk Jalur (POP / ODC) --</option><?php foreach($odp_list as $o) if(($o['jenis']??'ODP')!='ODP') echo "<option value='$o[id]'>[$o[jenis]] $o[nama_odp]</option>"; ?></select>
    <input name="nama_odp" placeholder="Nama (mis: POP-PUSAT / ODC-01 / ODP-01)" required>
    <input name="lokasi" placeholder="Alamat / Patokan Lokasi" required>
    <input name="kapasitas" type="number" value="16" placeholder="Jumlah Port">
    <input type="hidden" name="lat" id="lat_odp" required><input type="hidden" name="lng" id="lng_odp" required>
    <div style="display:flex;gap:6px;align-items:center;margin-top:6px"><span id="st_gps_odp" style="font-size:11px;color:#0284c7;font-weight:700">Ketuk peta / GPS HP dulu</span><button type="button" onclick="ambilGps('odp')" class="btn btn-w">GPS HP</button><button name="add_odp" class="btn">+ Simpan Perangkat</button></div>
  </form>
</div>
<div class="card" style="border-left:5px solid #22c55e;background:#f0fdf4">
  <h4>3. Akuratkan Titik Rumah & Jalur ODP Pelanggan</h4>
  <form method="POST" style="margin-top:8px">
    <input type="hidden" name="pel_id" id="hid_pel_id" required>
    <input type="text" id="cariDropPel" oninput="cariPelVis()" placeholder="Ketik nama / IP pelanggan (mis: Faisal)..." autocomplete="off" style="border:2px solid #22c55e;background:#fff;font-weight:700;margin-bottom:2px">
    <div id="hasilCariPel" style="display:none;max-height:170px;overflow-y:auto;background:#fff;border:1px solid #cbd5e1;border-radius:6px;margin-bottom:6px"></div>
    <div id="badgePelOk" style="font-size:11px;color:#00a65a;font-weight:700;margin-bottom:6px"></div>
    <select name="odp_id" id="sel_pel_odp"><option value="">-- Pilih Box ODP --</option><?php foreach($odp_list as $o) echo "<option value='$o[id]'>[$o[jenis]] $o[nama_odp] ($o[lokasi])</option>"; ?></select>
    <input type="hidden" name="lat_p" id="lat_pel" required><input type="hidden" name="lng_p" id="lng_pel" required>
    <div style="display:flex;gap:6px;align-items:center;margin-top:6px"><span id="st_gps_pel" style="font-size:11px;color:#00a65a;font-weight:700">Ketuk rumah di peta / GPS HP</span><button type="button" onclick="ambilGps('pel')" class="btn btn-w">GPS HP</button><button name="save_pel_gis" onclick="if(!document.getElementById('hid_pel_id').value){alert('Ketik & pilih nama pelanggan dulu!');return false;}if(!document.getElementById('lat_pel').value){alert('Ketuk atap rumah di peta satelit dulu!');return false;}" class="btn btn-g">Simpan Titik Rumah</button></div>
  </form>
</div>
<div class="card">
  <h4>4. Daftar Perangkat POP / ODC / ODP (<?= count($odp_list) ?>)</h4>
  <div style="overflow-x:auto"><table><tr><th>Tipe</th><th>Nama</th><th>Lokasi</th><th>Port</th><th>Aksi</th></tr>
  <?php foreach($odp_list as $d): ?><tr><td><b><?= $d['jenis']??'ODP' ?></b></td><td><?= htmlspecialchars($d['nama_odp']) ?></td><td><?= htmlspecialchars($d['lokasi']) ?></td><td><?= $d['terpakai'] ?>/<?= $d['kapasitas'] ?></td><td><a href="?page=odp&rid=<?= $f_rid ?>&del_odp=<?= $d['id'] ?>" class="btn btn-r" style="padding:3px 7px;font-size:10px">Hapus</a></td></tr><?php endforeach; ?>
  </table></div>
</div>
<div class="card">
  <h4>5. Daftar Pelanggan & Koordinat (<?= count($pel_list) ?>)</h4>
  <div style="overflow-x:auto"><table><tr><th>Status</th><th>Nama Pelanggan</th><th>Jalur ODP</th><th>Koordinat</th><th>Aksi</th></tr>
  <?php foreach($pel_list as $p): $c=$p['st_net']=='online'?'#22c55e':($p['st_net']=='isolir'?'#ef4444':'#f59e0b'); ?>
  <tr><td><span style="background:<?= $c ?>;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px"><?= strtoupper($p['st_net']) ?></span></td><td><b><?= htmlspecialchars($p['nama']) ?></b><br><small><?= htmlspecialchars($p['akun']) ?></small></td><td><?= htmlspecialchars($p['nama_odp']??'-') ?></td><td style="font-size:11px"><?= !empty($p['lat'])?"$p[lat], $p[lng]":"Belum" ?></td><td><button type="button" onclick="pilihPelItem(<?= $p['id'] ?>);window.scrollTo({top:0,behavior:'smooth'})" class="btn btn-g" style="padding:4px 8px;font-size:11px">Set</button> <?php if(!empty($p['lat'])): ?><a href="?page=odp&rid=<?= $f_rid ?>&reset_pel=<?= $p['id'] ?>" class="btn btn-r" style="padding:4px 8px;font-size:11px">Hapus</a><?php endif; ?></td></tr>
  <?php endforeach; ?></table></div>
</div>
<script>
const nodes=<?= json_encode($odp_list) ?>,custs=<?= json_encode($pel_list) ?>;let flt='all';window.lastLat='';window.lastLng='';
const map=L.map('mapGis').setView([-6.0758,106.5464],16);
const uSat=['https:','',['mt1','google','com'].join('.'),'vt','lyrs=y'+String.fromCharCode(38)+'x={x}'+String.fromCharCode(38)+'y={y}'+String.fromCharCode(38)+'z={z}'].join('/');
const uOsm=['https:','',['{s}','tile','openstreetmap','org'].join('.'),'{z}','{x}','{y}.png'].join('/');
const lSat=L.tileLayer(uSat,{maxZoom:20}),lOsm=L.tileLayer(uOsm,{maxZoom:19});lSat.addTo(map);
function setMode(m){if(m=='sat'){map.removeLayer(lOsm);lSat.addTo(map);}else{map.removeLayer(lSat);lOsm.addTo(map);}}
function setF(f){flt=f;renderGis();if(window.lg&&window.lg.getLayers().length>0){map.fitBounds(L.featureGroup(window.lg.getLayers()).getBounds(),{padding:[35,35],maxZoom:18});}}
function ikon(bg,t,s){return L.divIcon({className:'',html:`<div class="pin-node" style="background:${bg};width:${s}px;height:${s}px">${t}</div>`,iconSize:[s,s],iconAnchor:[s/2,s/2]});}
function renderGis(){
  if(window.lg)map.removeLayer(window.lg);window.lg=L.layerGroup().addTo(map);const nm={};
  nodes.forEach(n=>{const la=parseFloat(n.lat),ln=parseFloat(n.lng);if(isNaN(la)||isNaN(ln))return;nm[n.id]=[la,ln];const bg=n.jenis=='POP'?'#8b5cf6':(n.jenis=='ODC'?'#3b82f6':'#06b6d4');L.marker([la,ln],{icon:ikon(bg,n.jenis||'ODP',28)}).bindPopup(`<b>[${n.jenis||'ODP'}] ${n.nama_odp}</b>`).addTo(window.lg);});
  nodes.forEach(n=>{if(n.parent_id&&nm[n.id]&&nm[n.parent_id])L.polyline([nm[n.parent_id],nm[n.id]],{color:'#38bdf8',weight:4}).addTo(window.lg);});
  custs.forEach(c=>{if(flt!=='all'&&c.st_net!==flt)return;const la=parseFloat(c.lat),ln=parseFloat(c.lng);if(isNaN(la)||isNaN(ln))return;const cl=c.st_net=='online'?'#22c55e':(c.st_net=='isolir'?'#ef4444':'#f59e0b');L.marker([la,ln],{icon:ikon(cl,'&#8962;',22)}).bindPopup(`<b>${c.nama}</b>`).addTo(window.lg);if(c.odp_id&&nm[c.odp_id])L.polyline([nm[c.odp_id],[la,ln]],c.st_net=='online'?{color:'#22c55e',weight:3,className:'kabel-online'}:{color:cl,weight:2,dashArray:'5,5'}).addTo(window.lg);});
}
renderGis();if(window.lg&&window.lg.getLayers().length>0){map.fitBounds(L.featureGroup(window.lg.getLayers()).getBounds(),{padding:[35,35],maxZoom:18});}let pin=null;
map.on('click',e=>{const la=e.latlng.lat.toFixed(6),ln=e.latlng.lng.toFixed(6);window.lastLat=la;window.lastLng=ln;document.getElementById('lat_pel').value=la;document.getElementById('lng_pel').value=ln;document.getElementById('lat_odp').value=la;document.getElementById('lng_odp').value=ln;document.getElementById('st_gps_odp').innerText='✓ Titik Peta Siap';document.getElementById('st_gps_pel').innerText='✓ Titik Rumah Siap';if(pin)map.removeLayer(pin);pin=L.marker([la,ln],{icon:ikon('#ef4444','PIN',26)}).addTo(map);});
function cariPelVis(){const q=document.getElementById('cariDropPel').value.toLowerCase().trim(),b=document.getElementById('hasilCariPel');if(!q){b.style.display='none';return;}const r=custs.filter(c=>c.nama.toLowerCase().includes(q)||(c.akun||'').toLowerCase().includes(q));b.innerHTML=r.length?r.map(c=>`<div onclick="pilihPelItem(${c.id})" style="padding:9px;border-bottom:1px solid #f1f5f9;font-size:12px"><b>${c.nama}</b> (${c.akun})</div>`).join(''):'<div style="padding:8px;font-size:12px">Tidak ditemukan</div>';b.style.display='block';}
function pilihPelItem(id){const c=custs.find(x=>x.id==id);if(!c)return;document.getElementById('hid_pel_id').value=c.id;document.getElementById('cariDropPel').value=c.nama+' ('+c.akun+')';document.getElementById('hasilCariPel').style.display='none';document.getElementById('badgePelOk').innerText='✓ Terpilih: '+c.nama;document.getElementById('sel_pel_odp').value=c.odp_id||'';if(c.lat&&c.lng){document.getElementById('lat_pel').value=c.lat;document.getElementById('lng_pel').value=c.lng;map.setView([parseFloat(c.lat),parseFloat(c.lng)],18);}else if(window.lastLat){document.getElementById('lat_pel').value=window.lastLat;document.getElementById('lng_pel').value=window.lastLng;}}
function ambilGps(m){navigator.geolocation.getCurrentPosition(p=>{const la=p.coords.latitude.toFixed(6),ln=p.coords.longitude.toFixed(6);window.lastLat=la;window.lastLng=ln;document.getElementById(m=='pel'?'lat_pel':'lat_odp').value=la;document.getElementById(m=='pel'?'lng_pel':'lng_odp').value=ln;document.getElementById(m=='pel'?'st_gps_pel':'st_gps_odp').innerText='✓ Titik GPS HP Siap';map.setView([la,ln],18);});}
</script>
