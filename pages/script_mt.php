<?php if ($role !== 'owner') { echo "<div class='card'><h4>Akses Ditolak!</h4><p>Menu Script MikroTik hanya dapat diakses oleh Owner.</p></div>"; exit; }

$ip_lokal = trim(shell_exec("hostname -I | awk '{print $1}'"));
if (empty($ip_lokal)) $ip_lokal = '172.18.22.2';

$ip_stb = htmlspecialchars($_POST['ip_stb'] ?? $ip_lokal);
$ros_v  = $_POST['ros_v'] ?? 'v6';
$rid    = (int)($_POST['nas_id'] ?? 0);
$rt     = $rid ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=$rid")) : mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC LIMIT 1"));

$api_u  = htmlspecialchars($rt['username'] ?? 'dna_api');
$api_p  = htmlspecialchars($rt['password'] ?? 'DnaApi2026');
$secret = htmlspecialchars($rt['rad_secret'] ?? 'dna12345');

// 1. API & User (Aman jika dijalankan berulang)
$scr_api = "/ip service enable api\n"
         . "/ip service set api port=8728 address=0.0.0.0/0\n"
         . ":if ([:len [/user group find name=\"dna_billing_grp\"]] = 0) do={ /user group add name=\"dna_billing_grp\" policy=api,read,write,policy,test,sensitive }\n"
         . ":if ([:len [/user find name=\"$api_u\"]] = 0) do={ /user add name=\"$api_u\" password=\"$api_p\" group=\"dna_billing_grp\" comment=\"API D.N.A_Billing\" } else={ /user set [find name=\"$api_u\"] password=\"$api_p\" }";

// 2. RADIUS & Accounting
$scr_rad = ":if ([:len [/radius find comment=\"RADIUS D.N.A_Billing\"]] = 0) do={ /radius add service=ppp,hotspot address=$ip_stb secret=\"$secret\" authentication-port=1812 accounting-port=1813 timeout=2s comment=\"RADIUS D.N.A_Billing\" } else={ /radius set [find comment=\"RADIUS D.N.A_Billing\"] address=$ip_stb secret=\"$secret\" }\n"
         . "/radius incoming set accept=yes port=3799\n"
         . "/ppp aaa set use-radius=yes accounting=yes interim-update=5m\n"
         . ":do { /ip hotspot profile set [find default=no] use-radius=yes radius-accounting=yes radius-interim-update=5m } on-error={}";

// 3. Auto-Isolir & Redirect Port 8088
$scr_iso = ":if ([:len [/ip pool find name=\"POOL-ISOLIR\"]] = 0) do={ /ip pool add name=\"POOL-ISOLIR\" ranges=10.99.99.2-10.99.99.254 }\n"
         . ":if ([:len [/ppp profile find name=\"ISOLIR\"]] = 0) do={ /ppp profile add name=\"ISOLIR\" local-address=10.99.99.1 remote-address=\"POOL-ISOLIR\" address-list=\"ISOLIR\" rate-limit=64k/64k comment=\"Profil Isolir D.N.A_Billing\" }\n"
         . ":if ([:len [/ip firewall nat find comment=\"Redirect Isolir ke STB Armbian\"]] = 0) do={ /ip firewall nat add chain=dstnat protocol=tcp dst-port=80 src-address-list=\"ISOLIR\" action=dst-nat to-addresses=$ip_stb to-ports=8088 comment=\"Redirect Isolir ke STB Armbian\" }\n"
         . ":if ([:len [/ip firewall filter find comment=\"Blokir Internet Pelanggan Isolir\"]] = 0) do={ /ip firewall filter add chain=forward src-address-list=\"ISOLIR\" dst-address=!$ip_stb protocol=udp dst-port=!53 action=drop comment=\"Blokir Internet Pelanggan Isolir\" }";

// 4. Walled Garden, Remote ONT, FastTrack & NTP WIB
$ntp_cmd = ($ros_v == 'v7')
    ? "/system ntp client set enabled=yes servers=id.pool.ntp.org,asia.pool.ntp.org\n/system clock set time-zone-name=Asia/Jakarta"
    : "/system ntp client set enabled=yes primary-ntp=162.159.200.1 secondary-ntp=162.159.200.123\n/system clock set time-zone-name=Asia/Jakarta";

$scr_opt = ":do { /ip hotspot walled-garden ip add dst-address=$ip_stb action=accept comment=\"Allow Billing STB\" } on-error={}\n"
         . ":if ([:len [/ip firewall filter find comment=\"Allow Remote Modem ONT dari STB\"]] = 0) do={ /ip firewall filter add chain=forward src-address=$ip_stb protocol=tcp dst-port=80,8080,443 action=accept place-before=0 comment=\"Allow Remote Modem ONT dari STB\" }\n"
         . ":do { /ip firewall filter set [find action=fasttrack-connection] disabled=yes } on-error={}\n"
         . "/ip neighbor discovery-settings set discover-interface-list=all\n"
         . $ntp_cmd;

// 5. Auto-Generate Semua Profil Paket PPPoE & Hotspot
$scr_pkt = "";
$qp_mt = mysqli_query($conn, "SELECT * FROM paket ORDER BY id ASC");
while ($pk = mysqli_fetch_assoc($qp_mt)) {
    $nm = $pk['nama_paket'];
    $rl = $pk['profile_mikrotik'] ?: '2M/5M';
    $sh = (int)($pk['shared_users'] ?? 1);
    if ($pk['tipe'] == 'HOTSPOT') {
        $scr_pkt .= ":if ([:len [/ip hotspot user profile find name=\"$nm\"]] = 0) do={ /ip hotspot user profile add name=\"$nm\" rate-limit=\"$rl\" shared-users=$sh keepalive-timeout=2m status-autorefresh=1m }\n";
    } else {
        $scr_pkt .= ":if ([:len [/ppp profile find name=\"$nm\"]] = 0) do={ /ppp profile add name=\"$nm\" rate-limit=\"$rl\" only-one=yes }\n";
    }
}

$scr_all = "# === SCRIPT INTEGRASI LENGKAP D.N.A_BILLING ($ros_v) ===\n"
         . $scr_api . "\n\n"
         . $scr_rad . "\n\n"
         . $scr_iso . "\n\n"
         . $scr_opt . "\n\n"
         . trim($scr_pkt) . "\n"
         . ":put \"=== INTEGRASI D.N.A_BILLING KE MIKROTIK BERHASIL 100%! ===\"";
?>
<div class="card">
  <h4>Generator Script Integrasi MikroTik (1-Klik Langsung Terhubung)</h4>
  <p style="font-size:12px;color:#475569;margin:4px 0 12px">Pilih Router NAS dan pastikan <b>IP STB Armbian</b> satu segmen/terhubung dengan MikroTik, lalu salin <b>Script Gabungan All-in-One</b> di bawah dan tempel ke <b>New Terminal</b> Winbox.</p>

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
      <small><b>IP STB Armbian (Terbaca MikroTik):</b></small><br>
      <input name="ip_stb" value="<?= $ip_stb ?>" required>
    </div>
    <div style="align-self:flex-end">
      <button class="btn">Perbarui Script</button>
    </div>
  </form>

  <div style="background:#ecfdf5;border:2px solid #00a65a;border-radius:8px;padding:12px;margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;gap:8px">
      <div>
        <b style="font-size:14px;color:#065f46">SCRIPT GABUNGAN ALL-IN-ONE (Cukup Copy Ini Saja!)</b><br>
        <small style="color:#047857">Otomatis mengatur API, RADIUS, Auto-Isolir, Remote Modem, Jam NTP, dan Semua Profil Paket sekaligus tanpa error duplikat.</small>
      </div>
      <button type="button" onclick="copyScr('s_all', this)" class="btn btn-g" style="padding:8px 14px;font-size:12px;white-space:nowrap">Copy Semua Script</button>
    </div>
    <textarea id="s_all" readonly rows="10" style="width:100%;background:#0f172a;color:#4ade80;font-family:monospace;font-size:11px;padding:10px;border-radius:6px;border:none;line-height:1.45"><?= htmlspecialchars($scr_all) ?></textarea>
  </div>

  <details style="background:#f8fafc;padding:10px;border-radius:6px;border:1px solid #cbd5e1">
    <summary style="font-weight:700;font-size:13px;cursor:pointer;color:#334155">Lihat Script Terpisah Per Bagian (Opsional)</summary>
    <div style="margin-top:12px">
      <?php
      $parts = [
        ['id'=>'s1','t'=>'1. API & User Billing','c'=>$scr_api],
        ['id'=>'s2','t'=>'2. FreeRADIUS & Accounting','c'=>$scr_rad],
        ['id'=>'s3','t'=>'3. Auto-Isolir Port 8088','c'=>$scr_iso],
        ['id'=>'s4','t'=>'4. Remote ONT, FastTrack & Jam NTP','c'=>$scr_opt],
        ['id'=>'s5','t'=>'5. Profil Paket PPPoE & Hotspot','c'=>trim($scr_pkt)]
      ];
      foreach ($parts as $p): ?>
        <div style="margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
            <small><b><?= $p['t'] ?></b></small>
            <button type="button" onclick="copyScr('<?= $p['id'] ?>', this)" class="btn" style="padding:3px 8px;font-size:10px">Copy</button>
          </div>
          <textarea id="<?= $p['id'] ?>" readonly rows="3" style="width:100%;background:#1e293b;color:#38bdf8;font-family:monospace;font-size:11px;padding:8px;border-radius:5px;border:none"><?= htmlspecialchars($p['c']) ?></textarea>
        </div>
      <?php endforeach; ?>
    </div>
  </details>
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
