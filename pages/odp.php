<?php cekAkses(['owner','teknisi']);
if(isset($_GET['del_odp'])){ mysqli_query($conn,"DELETE FROM odp WHERE id=".(int)$_GET['del_odp']); echo "<script>location.href='?page=odp';</script>"; exit; }
if(isset($_GET['del_pel'])){ mysqli_query($conn,"DELETE FROM pelanggan WHERE id=".(int)$_GET['del_pel']); echo "<script>location.href='?page=odp';</script>"; exit; }
if (isset($_POST['add_odp'])) {
    mysqli_query($conn,"INSERT INTO odp (nama_odp,kapasitas,alamat,lat,lng) VALUES ('$_POST[n]','$_POST[k]','$_POST[a]','$_POST[lat]','$_POST[lng]')"); echo "<script>location.href='?page=odp';</script>"; exit;
}
$online_users = [];
foreach($ppp_list as $item) { $online_users[] = trim(explode(' (', $item)); }
$odps = []; $qo = mysqli_query($conn,"SELECT * FROM odp"); while($o = mysqli_fetch_assoc($qo)) $odps[] = $o;
$custs = []; $on_cnt = 0; $off_cnt = 0;
$qp = mysqli_query($conn,"SELECT p.*, o.lat as olat, o.lng as olng, o.nama_odp FROM pelanggan p LEFT JOIN odp o ON p.odp_id=o.id WHERE p.lat IS NOT NULL AND p.lat!=''");
while($p = mysqli_fetch_assoc($qp)) {
    $is_on = in_array($p['pppoe_user'], $online_users);
    if($is_on) $on_cnt++; else $off_cnt++;
    $p['online'] = $is_on; $custs[] = $p;
}
?>
<link rel="stylesheet" href="assets/leaflet.css"/>
<script src="assets/leaflet.js"></script>
<style>
  @keyframes dashFlow { to { stroke-dashoffset: -24; } }
  .line-on { stroke-dasharray: 8, 8; animation: dashFlow 0.8s linear infinite; }
  .line-off { stroke-dasharray: 8, 8; animation: dashFlow 1.2s linear infinite; }
  .gis-wrap { position:relative; border-radius:10px; overflow:hidden; border:1px solid #1e293b; }
  #gisMap { height:450px; width:100%; background:#1e293b; z-index:1; }
  .hud-health { position:absolute; bottom:12px; right:12px; z-index:999; background:rgba(15,23,42,0.9); color:#fff; padding:10px; border-radius:8px; font-size:11px; width:170px; }
  .hud-grid { display:grid; grid-template-columns:1fr 1fr; gap:5px; margin-top:5px; }
  .hud-box { background:#1e293b; padding:5px; border-radius:5px; text-align:center; }
  .hud-legend { position:absolute; bottom:12px; left:12px; z-index:999; background:rgba(15,23,42,0.9); color:#cbd5e1; padding:8px; border-radius:8px; font-size:10px; }
</style>

<div class="card" style="padding:12px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
    <b>GIS Network Map Satelit (ODP & Pelanggan)</b>
    <div>
      <button type="button" onclick="filterMap('all')" class="btn">Semua (<?= count($custs) ?>)</button>
      <button type="button" onclick="filterMap('on')" class="btn btn-g">Online (<?= $on_cnt ?>)</button>
      <button type="button" onclick="filterMap('off')" class="btn btn-r">Offline (<?= $off_cnt ?>)</button>
    </div>
  </div>
  <div class="gis-wrap">
    <div id="gisMap"></div>
    <div class="hud-legend">
      <div><span style="color:#38bdf8">&#9632;</span> Titik ODP / POP</div>
      <div><span style="color:#22c55e">&#9473;</span> Online (Hijau Berjalan)</div>
      <div><span style="color:#ef4444">&#9473;</span> Offline (Merah Berjalan)</div>
    </div>
    <div class="hud-health">
      <b>NETWORK HEALTH</b>
      <div class="hud-grid">
        <div class="hud-box"><small>ODP</small><br><b style="color:#38bdf8"><?= count($odps) ?></b></div>
        <div class="hud-box"><small>NAS</small><br><b style="color:#22c55e"><?= $mt_ok?'LIVE':'OFF' ?></b></div>
        <div class="hud-box"><small>ONLINE</small><br><b style="color:#22c55e"><?= $on_cnt ?></b></div>
        <div class="hud-box"><small>OFFLINE</small><br><b style="color:#ef4444"><?= $off_cnt ?></b></div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <h4>Tambah Titik ODP (Klik Peta Satelit / Paste Google Maps)</h4>
  <form method="POST">
    <input name="n" placeholder="Nama ODP (ODP-01)" required>
    <input name="k" type="number" value="8" style="width:65px">
    <input name="a" placeholder="Alamat / Tiang">
    <input name="lat" id="olat" placeholder="Latitude (-6.0756838)" required>
    <input name="lng" id="olng" placeholder="Longitude (106.5464156)" required>
    <button type="button" onclick="gpsODP()" class="btn btn-w">GPS / Buka Maps</button>
    <button type="button" onclick="pasteKoord()" class="btn">Paste Maps</button>
    <button name="add_odp" class="btn btn-g">+ Simpan ODP</button>
  </form>
</div>

<script>
const odps = <?= json_encode($odps) ?>;
const custs = <?= json_encode($custs) ?>;
const cLat = (odps.length && parseFloat(odps[0].lat)) ? parseFloat(odps[0].lat) : -6.0756838;
const cLng = (odps.length && parseFloat(odps[0].lng)) ? parseFloat(odps[0].lng) : 106.5464156;

const map = L.map('gisMap').setView([-6.0756838, 106.5464156], 18);
L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { maxZoom: 20 }).addTo(map);
const layerGroup = L.layerGroup().addTo(map);
let pinTemp = null;

map.on('click', e => {
  document.getElementById('olat').value = e.latlng.lat.toFixed(6);
  document.getElementById('olng').value = e.latlng.lng.toFixed(6);
  if(pinTemp) map.removeLayer(pinTemp);
  pinTemp = L.marker(e.latlng).addTo(map).bindPopup('Titik ODP Dipilih').openPopup();
});

function gpsODP(){
  if(navigator.geolocation && location.protocol === 'https:'){
    navigator.geolocation.getCurrentPosition(p=>{
      setPin(p.coords.latitude.toFixed(6), p.coords.longitude.toFixed(6), 'GPS HP');
    });
  } else {
    window.open('https://google.com' + cLat + ',' + cLng, '_blank');
  }
}

function pasteKoord(){
  let txt = prompt('Tempel koordinat dari Google Maps (bisa pakai koma atau titik):');
  if(!txt) return;
  let clean = txt.replace(/(\d),(\d)/g, '$1.$2');
  let parts = clean.match(/-?\d+\.\d+/g);
  if(parts && parts.length >= 2){
    setPin(parts.at(0), parts.at(1), 'Koordinat Maps');
  } else {
    alert('Format tidak valid. Contoh: -6,0737954, 106,5468505');
  }
}

function setPin(la, lo, label){
  document.getElementById('olat').value = la;
  document.getElementById('olng').value = lo;
  map.setView([parseFloat(la), parseFloat(lo)], 18);
  if(pinTemp) map.removeLayer(pinTemp);
  pinTemp = L.marker([parseFloat(la), parseFloat(lo)]).addTo(map).bindPopup(label).openPopup();
}

function filterMap(mode){
  layerGroup.clearLayers();
  odps.forEach(o => {
    if(!o.lat || !o.lng) return;
    L.circleMarker([parseFloat(o.lat), parseFloat(o.lng)], {radius:9, color:'#fff', weight:2, fillColor:'#0284c7', fillOpacity:1})
      .bindPopup(`<b>ODP/ODC: ${o.nama_odp}</b><br><small>${o.alamat||''}</small><br><br><a href="?page=odp&del_odp=${o.id}" onclick="return confirm('Hapus titik ODP/ODC ini?')" style="background:#dd4b39;color:#fff;padding:4px 8px;border-radius:4px;text-decoration:none;font-size:11px;font-weight:bold">Hapus ODP/ODC</a>`).addTo(layerGroup);
  });
  custs.forEach(c => {
    if(mode=='on' && !c.online) return;
    if(mode=='off' && c.online) return;
    const cPos = [parseFloat(c.lat), parseFloat(c.lng)];
    const clr = c.online ? '#22c55e' : '#ef4444';
    L.circleMarker(cPos, {radius:7, color:'#fff', weight:2, fillColor:clr, fillOpacity:1})
      .bindPopup(`<b>${c.nama}</b><br>Status: <b style="color:${clr}">${c.online?'ONLINE':'OFFLINE'}</b><br><br><a href="?page=odp&del_pel=${c.id}" onclick="return confirm('Hapus pelanggan ini dari peta?')" style="background:#dd4b39;color:#fff;padding:4px 8px;border-radius:4px;text-decoration:none;font-size:11px;font-weight:bold">Hapus Pelanggan</a>`).addTo(layerGroup);
    if(c.olat && c.olng){
      L.polyline([[parseFloat(c.olat), parseFloat(c.olng)], cPos], {
        color: clr, weight: 4, opacity: 0.95, className: c.online ? 'line-on' : 'line-off'
      }).addTo(layerGroup);
    }
  });
}
filterMap('all');
</script>
