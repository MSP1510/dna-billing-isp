<?php
if (isset($_GET['sync_mt'])) { echo "<div class='card' style='border-left:5px solid #00a65a'>Sinkronisasi MikroTik selesai!</div>"; }

echo "<div style=margin-bottom:10px><a href=?page=pelanggan&sync_mt=1 class=btn>Tarik Semua Pelanggan dari MikroTik</a></div>";
 cekAkses(['owner','admin','teknisi']);
$sub_p = $_GET['sub'] ?? 'all';

function sinkronMikrotik($conn, $api, $p, $pk, $mode = 'aktif') {
    $rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=".(int)$p['router_id']));
    $prof = ($mode == 'isolir') ? 'ISOLIR' : ($pk['nama_paket'] ?? 'default');
    $rate = ($mode == 'isolir') ? '1k/1k' : ($pk['profile_mikrotik'] ?? '');
    if ($p['tipe_layanan']=='pppoe' && function_exists('syncRadiusUser')) {
        syncRadiusUser($conn, $p['pppoe_user'], '123456', $rate);
    }
    if ($rt && $api->connect($rt['ip_address'], $rt['username'], $rt['password'], $rt['port'])) {
        if ($p['tipe_layanan'] == 'pppoe' && !empty($p['pppoe_user'])) {
            $sec = $api->comm('/ppp/secret/print', ['?name' => $p['pppoe_user']]);
            $sid = ''; foreach($sec as $l) { if(strpos($l,'=.id=')===0) $sid = substr($l,5); }
            if ($sid) $api->comm('/ppp/secret/set', ['.id'=>$sid, 'profile'=>$prof]);
            else $api->comm('/ppp/secret/add', ['name'=>$p['pppoe_user'], 'password'=>'123456', 'service'=>'pppoe', 'profile'=>$prof]);
            $act = $api->comm('/ppp/active/print', ['?name' => $p['pppoe_user']]);
            foreach($act as $l) { if(strpos($l,'=.id=')===0) $api->comm('/ppp/active/remove', ['.id'=>substr($l,5)]); }
        } elseif ($p['tipe_layanan'] == 'static' && !empty($p['ip_static'])) {
            if ($mode == 'isolir') {
                $api->comm('/ip/firewall/address-list/add', ['list'=>'ISOLIR', 'address'=>$p['ip_static'], 'comment'=>$p['nama']]);
            } else {
                $al = $api->comm('/ip/firewall/address-list/print', ['?address'=>$p['ip_static'], '?list'=>'ISOLIR']);
                foreach($al as $l) { if(strpos($l,'=.id=')===0) $api->comm('/ip/firewall/address-list/remove', ['.id'=>substr($l,5)]); }
            }
        }
        $api->disconnect();
    }
}

if (isset($_POST['add_pel'])) {
    $rid = (int)$_POST['nas']; $oid = (int)$_POST['odp']; $pid_pkt = (int)$_POST['p'];
    $kode = "6075" . rand(100000, 999999);
    $jt = $_POST['jt'] ?: date('Y-m-d', strtotime('+30 days'));
    $own = $_SESSION['nama'];
    mysqli_query($conn,"INSERT INTO pelanggan (kode_pel,nama,no_wa,paket_id,tipe_layanan,pppoe_user,ip_static,router_id,odp_id,lat,lng,jatuh_tempo,owner_data) VALUES ('$kode','$_POST[n]','$_POST[w]',$pid_pkt,'$_POST[t]','$_POST[u]','$_POST[ip]',$rid,$oid,'$_POST[lat]','$_POST[lng]','$jt','$own')");
    $pid = mysqli_insert_id($conn);
    $p = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM pelanggan WHERE id=$pid"));
    $pk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM paket WHERE id=$pid_pkt"));
    mysqli_query($conn,"INSERT INTO tagihan (no_invoice,pelanggan_id,bulan_tahun,jumlah) VALUES ('INV-$kode',$pid,'".date('m-Y')."','".($pk['harga']??0)."')");
    sinkronMikrotik($conn, $api, $p, $pk, 'aktif');
    echo "<script>location.href='?page=pelanggan';</script>"; exit;
}

if (isset($_POST['edit_pel'])) {
    $id = (int)$_POST['id_pel']; $pid_pkt = (int)$_POST['p']; $rid = (int)$_POST['nas'];
    mysqli_query($conn,"UPDATE pelanggan SET nama='$_POST[n]', no_wa='$_POST[w]', paket_id=$pid_pkt, router_id=$rid, tipe_layanan='$_POST[t]', pppoe_user='$_POST[u]', ip_static='$_POST[ip]', jatuh_tempo='$_POST[jt]' WHERE id=$id");
    $p = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM pelanggan WHERE id=$id"));
    $pk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM paket WHERE id=$pid_pkt"));
    sinkronMikrotik($conn, $api, $p, $pk, $p['status']);
    echo "<script>location.href='?page=pelanggan';</script>"; exit;
}

if (isset($_GET['act']) && isset($_GET['id'])) {
    $id = (int)$_GET['id']; $act = $_GET['act'];
    $p = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM pelanggan WHERE id=$id"));
    $pk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM paket WHERE id=".(int)($p['paket_id']??0)));
    if ($act == 'isolir') {
        mysqli_query($conn,"UPDATE pelanggan SET status='isolir' WHERE id=$id");
        sinkronMikrotik($conn, $api, $p, $pk, 'isolir');
    } elseif ($act == 'buka_isolir') {
            mysqli_query($conn,"UPDATE pelanggan SET status='aktif' WHERE id=$id");
            mysqli_query($conn,"UPDATE tagihan SET status='lunas', tgl_bayar=NOW() WHERE pelanggan_id=$id AND status='belum_bayar'");
            sinkronMikrotik($conn, $api, $p, $pk, 'aktif');
            require_once __DIR__ . '/../cetak_invoice_pdf.php';
            $pdf_inf = buatPdfInvoiceLunas($conn, $id);
            if ($pdf_inf && !empty($p['no_wa'])) {
                $psn_lns = "*PEMBAYARAN LUNAS & BUKA ISOLIR*\nYth. Bpk/Ibu *{$pdf_inf['nama']}*,\nTerima kasih telah melakukan pembayaran tagihan internet sebesar *Rp {$pdf_inf['nominal']}* (No. Invoice: *{$pdf_inf['no_inv']}*).\nStatus layanan Wi-Fi Anda telah *AKTIF KEMBALI*. Terlampir bukti *PDF Invoice Lunas* Anda.";
                kirimWA($conn, $p['no_wa'], $psn_lns, $pdf_inf['url']);
            }
        } elseif ($act == 'renew') {
        mysqli_query($conn,"UPDATE pelanggan SET status='aktif', tgl_perpanjang=NOW(), jatuh_tempo=DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id=$id");
        sinkronMikrotik($conn, $api, $p, $pk, 'aktif');
    } elseif ($act == 'wa_tagih') {
        $t = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM tagihan WHERE pelanggan_id=$id ORDER BY id DESC LIMIT 1"));
        $nom = number_format($t['jumlah'] ?? ($pk['harga'] ?? 0));
        $inv = $t['no_invoice'] ?? ('INV-'.$p['kode_pel']);
        $per = $t['bulan_tahun'] ?? date('m-Y');
        $rep = ['{nama}'=>$p['nama'],'{id_pel}'=>$p['kode_pel'],'{invoice}'=>$inv,'{periode}'=>$per,'{nominal}'=>$nom,'{jatuh_tempo}'=>$p['jatuh_tempo'],'{bank}'=>$cfg['info_bank']??'-','{ewallet}'=>$cfg['info_ewallet']??'-'];
        $msg = str_replace(array_keys($rep), array_values($rep), $cfg['tpl_tagihan'] ?? "Tagihan $p[nama]: Rp $nom");
        $res = kirimWA($conn, $p['no_wa'], $msg, $cfg['url_qris'] ?? '');
        echo "<div class='card' style='border-left:4px solid #00a65a'>Tagihan WA dikirim ke <b>$p[nama] ($p[no_wa])</b>. Respon: <code>$res</code></div>";
    } elseif ($act == 'del') {
        mysqli_query($conn,"DELETE FROM pelanggan WHERE id=$id");
    }
    if ($act != 'wa_tagih') { echo "<script>location.href='?page=pelanggan';</script>"; exit; }
}
?>
<?php
$reg_bln = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM pelanggan WHERE MONTH(tgl_daftar)=MONTH(CURDATE())"));
$ren_bln = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM pelanggan WHERE MONTH(tgl_perpanjang)=MONTH(CURDATE()) AND status='aktif'"));
$cnt_iso = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM pelanggan WHERE status='isolir'"));
$cnt_dis = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM pelanggan WHERE status='disable'"));
?>
<div class="tiles">
  <div class="tile t-blue"><h2><?= $reg_bln ?></h2><p>Registrasi Bulan Ini</p></div>
  <div class="tile t-green"><h2><?= $ren_bln ?></h2><p>Renewal Bulan Ini</p></div>
  <div class="tile t-yellow"><h2><?= $cnt_iso ?></h2><p>Pelanggan Isolir</p></div>
  <div class="tile t-red"><h2><?= $cnt_dis ?></h2><p>Akun Disable</p></div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">
    <div>
      <a href="?page=pelanggan&sub=all" class="btn <?= $sub_p=='all'?'btn-g':'' ?>">Semua</a>
      <a href="?page=pelanggan&sub=pppoe" class="btn <?= $sub_p=='pppoe'?'btn-g':'' ?>">User PPP</a>
      <a href="?page=pelanggan&sub=static" class="btn <?= $sub_p=='static'?'btn-g':'' ?>">Static IP</a>
      <a href="?page=odp" class="btn btn-w">Peta Pelanggan</a>
    </div>
    <input type="text" id="cariPel" onkeyup="filterPel()" placeholder="Search ID / Nama / Paket..." style="width:200px">
  </div>

  <details style="margin-bottom:14px;background:#f8fafc;padding:10px;border-radius:6px;border:1px solid #e2e8f0">
    <summary style="cursor:pointer;font-weight:700;color:#00a65a">+ Tambah Pelanggan Baru</summary>
    <form method="POST" style="margin-top:10px">
      <select name="nas"><?php $qr=mysqli_query($conn,"SELECT * FROM routers"); while($r=mysqli_fetch_assoc($qr)) echo "<option value='$r[id]'>$r[nama_nas]</option>"; ?></select>
      <select name="odp"><option value="0">-- Pilih ODP --</option><?php $qo=mysqli_query($conn,"SELECT * FROM odp"); while($o=mysqli_fetch_assoc($qo)) echo "<option value='$o[id]'>$o[nama_odp]</option>"; ?></select>
      <input name="n" placeholder="Nama Pelanggan" required>
      <input name="w" placeholder="No WA (08xx)" required>
      <select name="t"><option value="pppoe">PPPOE</option><option value="static">STATIC IP</option></select>
      <input name="u" placeholder="User PPPoE">
      <input name="ip" placeholder="IP Static (opsional)">
      <select name="p"><?php $qp=mysqli_query($conn,"SELECT * FROM paket"); while($p=mysqli_fetch_assoc($qp)) echo "<option value='$p[id]'>$p[nama_paket]</option>"; ?></select>
      <input type="date" name="jt" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
      <input name="lat" id="plat" placeholder="Latitude">
      <input name="lng" id="plng" placeholder="Longitude">
      <button type="button" onclick="pasteGPSPel()" class="btn">Paste Maps</button>
      <button name="add_pel" class="btn btn-g">Simpan Pelanggan</button>
    </form>
  </details>

  <div style="overflow-x:auto">
  <table id="tblPel">
    <tr><th>ID Pelanggan</th><th>Nama & Status</th><th>Service</th><th>Paket Langganan</th><th>IP / User</th><th>Jatuh Tempo</th><th>Aksi Manual</th></tr>
    <?php
    $where = ($sub_p != 'all') ? "WHERE p.tipe_layanan='$sub_p'" : "";
    $q = mysqli_query($conn,"SELECT p.*, pk.nama_paket, pk.harga, r.nama_nas FROM pelanggan p LEFT JOIN paket pk ON p.paket_id=pk.id LEFT JOIN routers r ON p.router_id=r.id $where ORDER BY p.id DESC");
    while($d = mysqli_fetch_assoc($q)):
      $is_iso = ($d['status'] == 'isolir');
      $dot = $is_iso ? '#f39c12' : '#00a65a';
      $st_lbl = $is_iso ? "<span style='background:#f39c12;color:#fff;padding:1px 5px;border-radius:3px;font-size:10px'>ISOLIR</span>" : "<span style='background:#00a65a;color:#fff;padding:1px 5px;border-radius:3px;font-size:10px'>AKTIF</span>";
    ?>
    <tr>
      <td style="color:#0284c7;font-weight:700"><?= $d['kode_pel'] ?></td>
      <td><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $dot ?>;margin-right:4px"></span><b><?= $d['nama'] ?></b> <?= $st_lbl ?><br><small style="color:#64748b"><?= $d['no_wa'] ?> &bull; <?= $d['nama_nas']??'NAS-1' ?></small></td>
      <td><b><?= strtoupper($d['tipe_layanan']) ?></b></td>
      <td><b><?= $d['nama_paket']??'-' ?></b><br><small>Rp <?= number_format($d['harga']??0) ?></small></td>
      <td><?= $d['pppoe_user'] ?: ($d['ip_static'] ?: 'Automatic') ?></td>
      <td style="color:#0284c7;font-size:11px;font-weight:700"><?= $d['jatuh_tempo'] ?></td>
      <td style="white-space:nowrap">
        <?php if(!$is_iso): ?>
          <a href="?page=pelanggan&act=isolir&id=<?= $d['id'] ?>" onclick="return confirm('Isolir manual pelanggan ini?')" class="btn btn-w" style="padding:4px 7px;font-size:11px">Isolir</a>
        <?php else: ?>
          <a href="?page=pelanggan&act=buka_isolir&id=<?= $d['id'] ?>" onclick="return confirm('Buka isolir pelanggan ini?')" class="btn btn-g" style="padding:4px 7px;font-size:11px">Buka Isolir</a>
        <?php endif; ?>
        <button type="button" onclick='bukaEditPel(<?= json_encode($d) ?>)' class="btn" style="padding:4px 7px;font-size:11px">Edit Paket</button>
        <a href="?page=pelanggan&act=wa_tagih&id=<?= $d['id'] ?>" class="btn" style="background:#16a34a;padding:4px 7px;font-size:11px">Tagih WA</a>
        <a href="?page=pelanggan&act=del&id=<?= $d['id'] ?>" onclick="return confirm('Hapus pelanggan ini?')" class="btn btn-r" style="padding:4px 7px;font-size:11px">Hapus</a>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>

<!-- MODAL EDIT PAKET & DATA PELANGGAN -->
<div id="mdlEditPel" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center;padding:16px">
  <div class="card" style="width:100%;max-width:400px">
    <h4>Edit Paket & Data Pelanggan</h4>
    <form method="POST" style="margin-top:10px">
      <input type="hidden" name="id_pel" id="ed_id">
      <label style="font-size:11px;font-weight:700">Pilih Paket Langganan Baru:</label>
      <select name="p" id="ed_pkt" style="width:100%;margin-bottom:8px">
        <?php $qp=mysqli_query($conn,"SELECT * FROM paket"); while($p=mysqli_fetch_assoc($qp)) echo "<option value='$p[id]'>$p[nama_paket] (Rp ".number_format($p['harga']).")</option>"; ?>
      </select>
      <label style="font-size:11px;font-weight:700">Router NAS:</label>
      <select name="nas" id="ed_nas" style="width:100%;margin-bottom:8px">
        <?php $qr=mysqli_query($conn,"SELECT * FROM routers"); while($r=mysqli_fetch_assoc($qr)) echo "<option value='$r[id]'>$r[nama_nas]</option>"; ?>
      </select>
      <label style="font-size:11px;font-weight:700">Nama & No WhatsApp:</label>
      <input name="n" id="ed_nama" style="width:100%;margin-bottom:6px" required>
      <input name="w" id="ed_wa" style="width:100%;margin-bottom:8px" required>
      <label style="font-size:11px;font-weight:700">Tipe & User PPPoE / IP Static:</label>
      <select name="t" id="ed_tipe" style="width:35%"><option value="pppoe">PPPOE</option><option value="static">STATIC</option></select>
      <input name="u" id="ed_user" placeholder="User PPPoE" style="width:60%">
      <input name="ip" id="ed_ip" placeholder="IP Static" style="width:100%;margin-bottom:8px">
      <label style="font-size:11px;font-weight:700">Tanggal Jatuh Tempo:</label>
      <input type="date" name="jt" id="ed_jt" style="width:100%;margin-bottom:12px">
      <button name="edit_pel" class="btn btn-g">Simpan & Sinkron MikroTik</button>
      <button type="button" onclick="document.getElementById('mdlEditPel').style.display='none'" class="btn btn-r">Batal</button>
    </form>
  </div>
</div>

<script>
function bukaEditPel(d){
  document.getElementById('ed_id').value = d.id;
  document.getElementById('ed_pkt').value = d.paket_id;
  document.getElementById('ed_nas').value = d.router_id;
  document.getElementById('ed_nama').value = d.nama;
  document.getElementById('ed_wa').value = d.no_wa;
  document.getElementById('ed_tipe').value = d.tipe_layanan;
  document.getElementById('ed_user').value = d.pppoe_user || '';
  document.getElementById('ed_ip').value = d.ip_static || '';
  document.getElementById('ed_jt').value = d.jatuh_tempo || '';
  document.getElementById('mdlEditPel').style.display = 'flex';
}
function filterPel(){
  let q=document.getElementById('cariPel').value.toLowerCase();
  document.querySelectorAll('#tblPel tr').forEach((r,i)=>{if(i>0)r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
}
function pasteGPSPel(){
  let t=prompt('Tempel koordinat dari Google Maps:'); if(!t) return;
  let c=t.replace(/(\d),(\d)/g,'$1.$2').match(/-?\d+\.\d+/g);
  if(c && c.length>=2){ document.getElementById('plat').value=c.at(0); document.getElementById('plng').value=c.at(1); }
}
</script>
