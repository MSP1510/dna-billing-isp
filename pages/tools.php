<?php cekAkses(['owner']);
if (isset($_POST['save_pay'])) {
    $qris_url = mysqli_real_escape_string($conn, $_POST['qris']);
    if (!empty($_FILES['foto_qris']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto_qris']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $fname = "qris_dna_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['foto_qris']['tmp_name'], "/var/www/html/uploads/" . $fname)) {
                $qris_url = "/uploads/" . $fname;
            }
        }
    }
    mysqli_query($conn,"UPDATE pengaturan SET info_bank='$_POST[bank]', info_ewallet='$_POST[ewal]', url_qris='$qris_url', wa_endpoint='$_POST[ep]', wa_api_key='$_POST[ky]' WHERE id=1");
    echo "<div class='card' style='border-left:4px solid #00a65a'>Pengaturan Bank, E-Wallet & QRIS tersimpan!</div>";
    $cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan WHERE id=1"));
}
$wa_raw = @file_get_contents('/tmp/dna_wa.json');
$wa_st = $wa_raw ? json_decode($wa_raw, true) : ['status'=>'Menunggu service WA...','qr'=>''];
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:16px">
  <div class="card">
    <h4>1. Tautkan Nomor WhatsApp Penagihan</h4>
    <p style="margin:8px 0;font-size:13px">Status Gateway: <b style="color:<?= ($wa_st['status']=='TERHUBUNG')?'#00a65a':'#f39c12' ?>"><?= $wa_st['status'] ?></b></p>
    <?php if(!empty($wa_st['qr'])): ?>
      <div style="text-align:center;background:#f8fafc;padding:12px;border-radius:8px;margin:10px 0">
        <img src="<?= $wa_st['qr'] ?>" style="width:220px;height:220px"><br>
        <small>WhatsApp HP &rarr; <b>Perangkat Tertaut</b> &rarr; Scan QR di atas</small>
      </div>
    <?php elseif($wa_st['status']=='TERHUBUNG'): ?>
      <div style="padding:12px;background:#dcfce7;color:#166534;border-radius:8px;font-size:13px">Nomor WhatsApp Anda sudah terhubung!</div>
    <?php endif; ?>
    <a href="?page=tools" class="btn" style="margin-top:8px">Refresh QR Code</a>
  </div>
  <div class="card">
    <h4>2. Template Bank, E-Wallet & Foto QRIS</h4>
    <form method="POST" enctype="multipart/form-data" style="margin-top:10px">
      <label style="font-size:12px;font-weight:700">Rekening Bank ({bank}):</label>
      <input name="bank" value="<?= htmlspecialchars($cfg['info_bank']??'') ?>" style="width:100%;margin-bottom:8px">
      <label style="font-size:12px;font-weight:700">Akun E-Wallet ({ewallet}):</label>
      <input name="ewal" value="<?= htmlspecialchars($cfg['info_ewallet']??'') ?>" style="width:100%;margin-bottom:8px">
      <label style="font-size:12px;font-weight:700;color:#0284c7">Upload Foto QRIS:</label>
      <input type="file" name="foto_qris" accept="image/*" style="width:100%;margin-bottom:6px">
      <input name="qris" value="<?= htmlspecialchars($cfg['url_qris']??'') ?>" style="width:100%;margin-bottom:8px">
      <?php if(!empty($cfg['url_qris'])): ?>
        <div style="text-align:center;margin-bottom:8px"><img src="<?= htmlspecialchars($cfg['url_qris']) ?>" style="max-height:110px;border-radius:4px"></div>
      <?php endif; ?>
      <label style="font-size:12px;font-weight:700">Endpoint WA Lokal & API Key:</label>
      <input name="ep" value="<?= htmlspecialchars($cfg['wa_endpoint']) ?>" style="width:65%">
      <input name="ky" value="<?= htmlspecialchars($cfg['wa_api_key']) ?>" style="width:30%">
      <br><br><button name="save_pay" class="btn btn-g">Simpan Pengaturan</button>
    </form>
  </div>
</div>
<?php
if (isset($_POST['save_bg'])) {
    if (!empty($_POST['b64_pc'])) {
        $d1 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['b64_pc']));
        if ($d1) file_put_contents('/var/www/html/assets/bg_pc.jpg', $d1);
    }
    if (!empty($_POST['b64_hp'])) {
        $d2 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['b64_hp']));
        if ($d2) file_put_contents('/var/www/html/assets/bg_hp.jpg', $d2);
    }
    echo "<div class='card' style='border-left:4px solid #00a65a'>Background Login diperbarui!</div>";
}
if (isset($_POST['save_ico']) && !empty($_POST['b64_ico'])) {
    $raw = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['b64_ico']));
    $im = @imagecreatefromstring($raw);
    if ($im) {
        $sizes = array(192, 512);
        foreach ($sizes as $sz) {
            $dst = imagecreatetruecolor($sz, $sz);
            imagecopyresampled($dst, $im, 0, 0, 0, 0, $sz, $sz, imagesx($im), imagesy($im));
            imagepng($dst, "/var/www/html/assets/icon-$sz.png");
            imagedestroy($dst);
        }
        imagedestroy($im);
        echo "<div class='card' style='border-left:4px solid #00a65a'>Logo Ikon Aplikasi Android diperbarui!</div>";
    }
}
?>
<div class="card" style="margin-top:16px">
  <h4>3. Ganti Background Halaman Login (PC & HP)</h4>
  <form method="POST" style="margin-top:10px">
    <input type="hidden" name="b64_pc" id="b64_pc">
    <input type="hidden" name="b64_hp" id="b64_hp">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-bottom:10px">
      <div><small><b>1. PC Landscape:</b></small><br><input type="file" accept="image/*" onchange="prosesImg(this,'b64_pc','st_pc',1440,0.85)"><div id="st_pc" style="font-size:11px;color:#00a65a"></div></div>
      <div><small><b>2. HP Portrait:</b></small><br><input type="file" accept="image/*" onchange="prosesImg(this,'b64_hp','st_hp',1080,0.85)"><div id="st_hp" style="font-size:11px;color:#00a65a"></div></div>
    </div>
    <button name="save_bg" class="btn btn-g">Simpan Background Login</button>
  </form>
</div>

<div class="card" style="margin-top:16px">
  <h4>4. Ganti Logo Ikon Aplikasi Android (Home Screen)</h4>
  <form method="POST" style="margin-top:10px">
    <input type="hidden" name="b64_ico" id="b64_ico">
    <input type="file" accept="image/*" onchange="prosesIco(this)" style="margin-bottom:8px">
    <div id="st_ico" style="font-size:11px;color:#00a65a;margin-bottom:8px"></div>
    <img src="assets/icon-192.png?v=<?= time() ?>" style="width:72px;height:72px;border-radius:16px;border:1px solid #cbd5e1;display:block;margin-bottom:10px">
    <button name="save_ico" class="btn btn-g">Simpan Logo Ikon Aplikasi</button>
  </form>
</div>

<script>
function prosesImg(inp, tid, lid, maxW, q){
  const f = inp.files.item(0); if(!f) return;
  const r = new FileReader();
  r.onload = function(e){
    const img = new Image();
    img.onload = function(){
      const c = document.createElement("canvas");
      const sc = img.width > maxW ? maxW / img.width : 1;
      c.width = img.width * sc; c.height = img.height * sc;
      c.getContext("2d").drawImage(img, 0, 0, c.width, c.height);
      document.getElementById(tid).value = c.toDataURL("image/jpeg", q);
      document.getElementById(lid).innerText = "Gambar siap disimpan!";
    };
    img.src = e.target.result;
  };
  r.readAsDataURL(f);
}
function prosesIco(inp){
  const f = inp.files.item(0); if(!f) return;
  const r = new FileReader();
  r.onload = function(e){
    const img = new Image();
    img.onload = function(){
      const c = document.createElement("canvas"); c.width = 512; c.height = 512;
      const minS = Math.min(img.width, img.height);
      c.getContext("2d").drawImage(img, (img.width-minS)/2, (img.height-minS)/2, minS, minS, 0, 0, 512, 512);
      document.getElementById("b64_ico").value = c.toDataURL("image/png");
      document.getElementById("st_ico").innerText = "Logo 512x512 siap disimpan!";
    };
    img.src = e.target.result;
  };
  r.readAsDataURL(f);
}
</script>
