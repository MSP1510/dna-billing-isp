<?php cekAkses(['owner','admin','teknisi']);
mysqli_query($conn, "ALTER TABLE odp ADD COLUMN IF NOT EXISTS foto_odp VARCHAR(255) NULL");

if (isset($_POST['add_odp'])) {
    $nm  = mysqli_real_escape_string($conn, $_POST['nama_odp']);
    $lok = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $kap = (int)$_POST['kapasitas'];
    $lat = mysqli_real_escape_string($conn, trim($_POST['lat']));
    $lng = mysqli_real_escape_string($conn, trim($_POST['lng']));
    $foto_path = '';
    if (!empty($_POST['b64_odp'])) {
        $raw = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['b64_odp']));
        if ($raw) {
            $fn = "odp_" . time() . ".jpg";
            file_put_contents("/var/www/html/uploads/" . $fn, $raw);
            $foto_path = "uploads/" . $fn;
        }
    }
    mysqli_query($conn, "INSERT INTO odp (nama_odp,lokasi,kapasitas,lat,lng,foto_odp) VALUES ('$nm','$lok',$kap,'$lat','$lng','$foto_path')");
    echo "<script>location.href='?page=odp';</script>"; exit;
}
if (isset($_GET['del_odp'])) {
    $id = (int)$_GET['del_odp'];
    mysqli_query($conn, "DELETE FROM odp WHERE id=$id");
    echo "<script>location.href='?page=odp';</script>"; exit;
}

$odp_list = [];
$qo = mysqli_query($conn, "SELECT o.*, (SELECT COUNT(id) FROM pelanggan p WHERE p.odp_id=o.id) as terpakai FROM odp o ORDER BY o.id DESC");
while ($r = mysqli_fetch_assoc($qo)) $odp_list[] = $r;
$sl = chr(47) . chr(47);
?>
<link rel="stylesheet" href="assets/leaflet.css"/>
<script src="assets/leaflet.js"></script>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:8px">
    <h4>1. Peta Satelit Lokasi ODP / POP</h4>
    <div style="display:flex;gap:4px">
      <button type="button" onclick="setLayer('sat')" class="btn" style="padding:4px 8px;font-size:11px">Satelit</button>
      <button type="button" onclick="setLayer('osm')" class="btn btn-g" style="padding:4px 8px;font-size:11px">Jalan Raya</button>
    </div>
  </div>
  <div id="mapOdp" style="width:100%;height:290px;border-radius:8px;border:1px solid #cbd5e1;z-index:1"></div>
  <small style="color:#64748b;display:block;margin-top:5px">Ketuk titik mana saja di peta untuk mengisi koordinat tiang ODP secara otomatis.</small>
</div>

<div class="card">
  <h4>2. Tambah Titik ODP / POP Baru</h4>
  <form method="POST" style="margin-top:10px">
    <input type="hidden" name="b64_odp" id="b64_odp">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;margin-bottom:8px">
      <input name="nama_odp" placeholder="Nama ODP (mis: ODP-01-BUARAN)" required>
      <input name="lokasi" placeholder="Alamat / Patokan Tiang" required>
      <select name="kapasitas">
        <option value="8">Kapasitas: 8 Port (1:8)</option>
        <option value="16" selected>Kapasitas: 16 Port (1:16)</option>
        <option value="24">Kapasitas: 24 Port (1:24)</option>
        <option value="4">Kapasitas: 4 Port (1:4)</option>
      </select>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-bottom:8px">
      <input name="lat" id="lat_odp" value="-6.068500" placeholder="Latitude" style="width:140px">
      <input name="lng" id="lng_odp" value="106.541200" placeholder="Longitude" style="width:140px">
      <button type="button" onclick="ambilGpsOdp()" class="btn btn-w">Ambil GPS HP Sekarang</button>
    </div>
    <div style="margin-bottom:10px">
      <small><b>Foto Box ODP di Tiang (Opsional):</b></small><br>
      <input type="file" accept="image/*" onchange="kompresOdp(this)">
      <span id="st_odp" style="font-size:11px;color:#00a65a"></span>
    </div>
    <button name="add_odp" class="btn btn-g">+ Simpan Titik ODP</button>
  </form>
</div>

<div class="card">
  <h4>3. Daftar Kapasitas Port & Lokasi ODP / POP</h4>
  <div style="overflow-x:auto">
  <table>
    <tr><th>Nama ODP</th><th>Alamat / Patokan</th><th>Status Port</th><th>Peta & Foto</th><th>Aksi</th></tr>
    <?php foreach ($odp_list as $d):
      $sisa = max(0, (int)$d['kapasitas'] - (int)$d['terpakai']);
      $clr  = ($sisa == 0) ? '#dd4b39' : (($sisa <= 2) ? '#f39c12' : '#00a65a');
    ?>
    <tr>
      <td><b><?= htmlspecialchars($d['nama_odp']) ?></b></td>
      <td><?= htmlspecialchars($d['lokasi']) ?></td>
      <td><span style="background:<?= $clr ?>;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700">Terpakai <?= $d['terpakai'] ?> / <?= $d['kapasitas'] ?> (Sisa <?= $sisa ?> Port)</span></td>
      <td style="font-size:11px">
        <?php if(!empty($d['lat']) && !empty($d['lng'])): ?>
          <button type="button" onclick="fokusPeta(<?= $d['lat'] ?>,<?= $d['lng'] ?>)" class="btn" style="padding:3px 7px;font-size:10px">Fokus Peta</button>
          <a href="https:<?= $sl ?>://google.com<?= $d['lat'] ?>,<?= $d['lng'] ?>" target="_blank" style="color:#0284c7;font-weight:700;margin-left:4px">Rute GPS</a>
        <?php else: ?> - <?php endif; ?>
        <?php if(!empty($d['foto_odp'])): ?><br><a href="<?= htmlspecialchars($d['foto_odp']) ?>" target="_blank" style="color:#00a65a">Lihat Foto ODP</a><?php endif; ?>
      </td>
      <td><a href="?page=odp&del_odp=<?= $d['id'] ?>" onclick="return confirm('Hapus ODP ini?')" class="btn btn-r" style="padding:4px 8px;font-size:11px">Hapus</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>

<script>
const sl = String.fromCharCode(47, 47);
const map = L.map('mapOdp').setView([-6.0685, 106.5412], 15);
const layerSat = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {maxZoom: 20});
const layerOsm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19});
layerSat.addTo(map);

function setLayer(t) {
  if (t === 'sat') { map.removeLayer(layerOsm); layerSat.addTo(map); }
  else { map.removeLayer(layerSat); layerOsm.addTo(map); }
}

const dataOdp = <?= json_encode($odp_list) ?>;
let pinPilih = null;
dataOdp.forEach(o => {
  const la = parseFloat(o.lat), ln = parseFloat(o.lng);
  if (!isNaN(la) && !isNaN(ln)) L.marker([la, ln]).addTo(map).bindPopup(`<b>${o.nama_odp}</b><br>${o.lokasi}<br>Port: ${o.terpakai}/${o.kapasitas}`);
});

map.on('click', function(e) {
  const la = e.latlng.lat.toFixed(6), ln = e.latlng.lng.toFixed(6);
  document.getElementById('lat_odp').value = la;
  document.getElementById('lng_odp').value = ln;
  if (pinPilih) map.removeLayer(pinPilih);
  pinPilih = L.marker([la, ln]).addTo(map).bindPopup('Titik ODP Baru').openPopup();
});

function fokusPeta(la, ln) { map.setView([la, ln], 18); window.scrollTo({top: 0, behavior: 'smooth'}); }
function ambilGpsOdp() {
  if (!navigator.geolocation) return alert('Browser tidak mendukung GPS.');
  navigator.geolocation.getCurrentPosition(pos => {
    const la = pos.coords.latitude.toFixed(6), ln = pos.coords.longitude.toFixed(6);
    document.getElementById('lat_odp').value = la;
    document.getElementById('lng_odp').value = ln;
    if (pinPilih) map.removeLayer(pinPilih);
    pinPilih = L.marker([la, ln]).addTo(map).bindPopup('Posisi HP Anda').openPopup();
    map.setView([la, ln], 18);
  }, err => alert('Pastikan GPS HP aktif: ' + err.message));
}
function kompresOdp(inp) {
  const f = inp.files.item(0); if (!f) return;
  const r = new FileReader();
  r.onload = e => {
    const img = new Image();
    img.onload = () => {
      const c = document.createElement('canvas');
      const sc = img.width > 1000 ? 1000 / img.width : 1;
      c.width = img.width * sc; c.height = img.height * sc;
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      document.getElementById('b64_odp').value = c.toDataURL('image/jpeg', 0.8);
      document.getElementById('st_odp').innerText = 'Foto siap disimpan!';
    };
    img.src = e.target.result;
  };
  r.readAsDataURL(f);
}
</script>
