<?php if ($role !== 'owner') { echo "<div class='card'><h4>Akses Ditolak!</h4><p>Menu Script MikroTik hanya dapat diakses oleh Owner.</p></div>"; exit; }

$ip_stb_def = $_SERVER['SERVER_ADDR'] ?? '10.20.30.50';
if ($ip_stb_def == '127.0.0.1' || strpos($ip_stb_def, ':') !== false) {
    $ip_stb_def = trim(shell_exec("hostname -I | awk '{print $1}'")) ?: '10.20.30.50';
}
$ip_stb = htmlspecialchars($_POST['ip_stb'] ?? $ip_stb_def);
$ros_v  = $_POST['ros_v'] ?? 'v6';
$rid    = (int)($_POST['nas_id'] ?? 0);
$rt     = $rid ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=$rid")) : mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC LIMIT 1"));

$api_u  = htmlspecialchars($rt['username'] ?? 'dna_api');
$api_p  = htmlspecialchars($rt['password'] ?? 'DnaApi2026');
$secret = htmlspecialchars($rt['rad_secret'] ?? 'dna12345');

$scr_api = "/ip service enable api\n"
         . "/ip service set api port=8728 address=0.0.0.0/0\n"
         . "/user group add name=dna_billing_grp policy=api,read,write,policy,test,sensitive\n"
         . "/user add name=\"$api_u\" password=\"$api_p\" group=dna_billing_grp comment=\"API D.N.A_Billing\"";

$scr_rad = "/radius add service=ppp,hotspot address=$ip_stb secret=\"$secret\" authentication-port=1812 accounting-port=1813 timeout=2s comment=\"RADIUS D.N.A_Billing\"\n"
         . "/radius incoming set accept=yes port=3799\n"
         . "/ppp aaa set use-radius=yes accounting=yes interim-update=5m\n"
         . "/ip hotspot profile set [find default=no] use-radius=yes radius-accounting=yes radius-interim-update=5m";

$scr_iso = "/ip pool add name=POOL-ISOLIR ranges=10.99.99.2-10.99.99.254\n"
         . "/ppp profile add name=ISOLIR local-address=10.99.99.1 remote-address=POOL-ISOLIR address-list=ISOLIR rate-limit=64k/64k comment=\"Profil Isolir D.N.A_Billing\"\n"
         . "/ip firewall nat add chain=dstnat protocol=tcp dst-port=80 src-address-list=ISOLIR action=dst-nat to-addresses=$ip_stb to-ports=8088 comment=\"Redirect Isolir ke STB Armbian\"\n"
         . "/ip firewall filter add chain=forward src-address-list=ISOLIR dst-address=!$ip_stb protocol=udp dst-port=!53 action=drop comment=\"Blokir Internet Pelanggan Isolir\"";

$scr_wg  = "/ip hotspot walled-garden ip add dst-address=$ip_stb action=accept comment=\"Allow Billing STB\"\n"
         . "/ip neighbor discovery-settings set discover-interface-list=all";
?>
<div class="card">
  <h4>Generator Script Integrasi MikroTik (RouterOS v6 & v7)</h4>
  <p style="font-size:12px;color:#475569;margin:4px 0 12px">Pilih Router NAS dan masukkan IP STB Armbian yang terbaca oleh MikroTik (IP Lokal satu jaringan atau IP VPN), lalu salin dan tempel ke <b>New Terminal</b> Winbox.</p>

  <form method="POST" style="background:#f8fafc;padding:12px;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:14px;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
    <div>
      <small><b>Pilih Router NAS:</b></small><br>
      <select name="nas_id" onchange="this.form.submit()">
        <?php $qr=mysqli_query($conn,"SELECT * FROM routers ORDER BY id ASC"); while($r=mysqli_fetch_assoc($qr)) echo "<option value='$r[id]' ".(($rt['id']??0)==$r['id']?'selected':'').">$r[nama_nas] ($r[ip_address])</option>"; ?>
      </select>
    </div>
    <div>
      <small><b>Versi RouterOS:</b></small><br>
      <select name="ros_v" onchange="this.form.submit()">
        <option value="v6" <?= $ros_v=='v6'?'selected':'' ?>>RouterOS v6 (6.43+)</option>
        <option value="v7" <?= $ros_v=='v7'?'selected':'' ?>>RouterOS v7 (7.1+)</option>
      </select>
    </div>
    <div>
      <small><b>IP STB Armbian (Tujuan dari MikroTik):</b></small><br>
      <input name="ip_stb" value="<?= $ip_stb ?>" placeholder="Contoh: 10.20.30.50" required>
    </div>
    <div style="align-self:flex-end">
      <button class="btn">Generate Script</button>
    </div>
  </form>

  <?php
  $scr_rmt = "/ip firewall filter add chain=forward src-address=$ip_stb protocol=tcp dst-port=80,8080,443 action=accept place-before=0 comment=\"Allow Remote Modem ONT dari STB\"";
$scr_ft  = "/ip firewall filter set [find action=fasttrack-connection] disabled=yes comment=\"Disable FastTrack agar Limit Bandwidth & Isolir Jalan\"";
$scr_ntp = ($ros_v == 'v7')
    ? "/system ntp client set enabled=yes servers=id.pool.ntp.org,asia.pool.ntp.org\n/system clock set time-zone-name=Asia/Jakarta"
    : "/system ntp client set enabled=yes primary-ntp=162.159.200.1 secondary-ntp=162.159.200.123\n/system clock set time-zone-name=Asia/Jakarta";
$scr_sec = "/ip dhcp-server alert add interface=all valid-server=00:00:00:00:00:00 on-alert=\":log error \\\"ROGUE DHCP TERDETEKSI!\\\"\" disabled=no\n/ip neighbor discovery-settings set discover-interface-list=all";
$scr_pkt = "";
$qp_mt = mysqli_query($conn, "SELECT * FROM paket ORDER BY id ASC");
while ($pk = mysqli_fetch_assoc($qp_mt)) {
    $nm = $pk['nama_paket'];
    $rl = $pk['profile_mikrotik'] ?: '2M/5M';
    $sh = (int)($pk['shared_users'] ?? 1);
    if ($pk['tipe'] == 'HOTSPOT') {
        $scr_pkt .= "/ip hotspot user profile add name=\"$nm\" rate-limit=\"$rl\" shared-users=$sh keepalive-timeout=2m status-autorefresh=1m\n";
    } else {
        $scr_pkt .= "/ppp profile add name=\"$nm\" rate-limit=\"$rl\" only-one=yes\n";
    }
}
if (empty($scr_pkt)) $scr_pkt = "# Belum ada paket di menu Profil Paket";
$blocks = [
    ['id'=>'s1', 'judul'=>'1. Script Aktifkan API & User Billing (Wajib Pertama)', 'isi'=>$scr_api],
    ['id'=>'s2', 'judul'=>'2. Script Koneksi FreeRADIUS (PPPoE, Hotspot & Kick Port 3799)', 'isi'=>$scr_rad],
    ['id'=>'s3', 'judul'=>'3. Script Auto-Isolir & Halaman Peringatan Tagihan (Port 8088)', 'isi'=>$scr_iso],
    ['id'=>'s4', 'judul'=>'4. Script Walled Garden Hotspot & Discovery Neighbor', 'isi'=>$scr_wg],
    ['id'=>'s5', 'judul'=>'5. Script Izin Remote Modem Pelanggan (ONT Port 80 & 8080)', 'isi'=>$scr_rmt],
    ['id'=>'s6', 'judul'=>'6. Script Optimasi Limit Bandwidth (Nonaktifkan FastTrack)', 'isi'=>$scr_ft],
    ['id'=>'s7', 'judul'=>'7. Script Sinkronisasi Jam NTP WIB (Akurasi Durasi Voucher)', 'isi'=>$scr_ntp],
    ['id'=>'s8', 'judul'=>'8. Script Keamanan Jaringan & Deteksi Rogue DHCP', 'isi'=>$scr_sec],
    ['id'=>'s9', 'judul'=>'9. Script Auto-Buat Semua Profil Paket (PPPoE & Voucher Hotspot) di MikroTik', 'isi'=>trim($scr_pkt)],
  ];
  foreach ($blocks as $b): ?>
  <div style="margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
      <b style="font-size:13px;color:#0f172a"><?= $b['judul'] ?></b>
      <button type="button" onclick="copyScr('<?= $b['id'] ?>', this)" class="btn btn-g" style="padding:5px 12px;font-size:11px">Copy Script</button>
    </div>
    <textarea id="<?= $b['id'] ?>" readonly rows="4" style="width:100%;background:#0f172a;color:#38bdf8;font-family:monospace;font-size:12px;padding:10px;border-radius:6px;border:none;line-height:1.45"><?= htmlspecialchars($b['isi']) ?></textarea>
  </div>
  <?php endforeach; ?>
</div>

<script>
function copyScr(id, btn){
  const el = document.getElementById(id);
  el.select(); el.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(el.value);
  const old = btn.innerText;
  btn.innerText = "Tersalin!";
  setTimeout(() => btn.innerText = old, 1800);
}
</script>
