<?php
if (isset($_POST["add_olt"])) {
    $rid = (int)($_POST["router_id"] ?? 0);
    $nm  = mysqli_real_escape_string($conn, trim($_POST["nama_olt"] ?? ""));
    $mr  = mysqli_real_escape_string($conn, trim($_POST["merek_olt"] ?? ""));
    $tp  = mysqli_real_escape_string($conn, trim($_POST["tipe_olt"] ?? ""));
    $ip  = mysqli_real_escape_string($conn, trim($_POST["ip_olt"] ?? ""));
    $pt  = (int)($_POST["port_olt"] ?: 80);
    $pr  = mysqli_real_escape_string($conn, trim($_POST["protokol_olt"] ?? "HTTP/Web"));
    $us  = mysqli_real_escape_string($conn, trim($_POST["user_olt"] ?? ""));
    $pw  = mysqli_real_escape_string($conn, trim($_POST["pass_olt"] ?? ""));
    $nport = rand(18100, 18999);
    if ($nm !== "" && $ip !== "") {
        if ($rid > 0) {
            $rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routers WHERE id=$rid"));
            if ($rt && $api->connect($rt["ip_address"], $rt["username"], $rt["password"], (int)($rt["port"] ?: 8728))) {
                $api->comm("/ip/firewall/nat/add", [
                    "chain" => "dstnat",
                    "protocol" => "tcp",
                    "dst-port" => (string)$nport,
                    "dst-address-type" => "local", "action" => "dst-nat",
                    "to-addresses" => $ip,
                    "to-ports" => (string)$pt,
                    "comment" => "DNA-OLT-$nm"
                ]);
                $api->comm("/ip/firewall/nat/add", ["chain"=>"srcnat","protocol"=>"tcp","dst-address"=>$ip,"dst-port"=>(string)$pt,"action"=>"masquerade","comment"=>"DNA-OLT-$nm"]);
                $api->disconnect();
            }
        }
        mysqli_query($conn, "INSERT INTO olt (router_id,nat_port,nama_olt,merek,tipe_olt,ip_address,port,protokol,username,password) VALUES ($rid,$nport,'$nm','$mr','$tp','$ip',$pt,'$pr','$us','$pw')");
    }
    echo "<script>location.href='?page=router';</script>"; exit;
}
if (isset($_GET["del_olt"])) {
    $oid = (int)$_GET["del_olt"];
    $od = mysqli_fetch_assoc(mysqli_query($conn, "SELECT o.*, r.ip_address as r_ip, r.username as r_u, r.password as r_p, r.port as r_pt FROM olt o LEFT JOIN routers r ON o.router_id=r.id WHERE o.id=$oid"));
    if ($od && !empty($od["r_ip"]) && $api->connect($od["r_ip"], $od["r_u"], $od["r_p"], (int)($od["r_pt"] ?: 8728))) {
        $nats = $api->comm("/ip/firewall/nat/print", ["?comment" => "DNA-OLT-" . $od["nama_olt"]]);
        foreach ($nats as $nl) { if (strpos($nl, "=.id=") === 0) $api->comm("/ip/firewall/nat/remove", [".id" => substr($nl, 5)]); }
                $api->disconnect();
    }
    mysqli_query($conn, "DELETE FROM olt WHERE id=$oid"); mysqli_query($conn, "ALTER TABLE olt AUTO_INCREMENT = 1");
    echo "<script>location.href='?page=router';</script>"; exit;
}
?>
<?php cekAkses(['owner']);
$jml_r = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM routers"));
if (isset($_POST['add_nas'])) {
    if ($jml_r >= 15) {
        echo "<div class='card' style='border-left:4px solid #dd4b39'>Maksimal 5 Router [NAS] telah tercapai!</div>";
    } else {
        mysqli_query($conn, "INSERT INTO routers (nama_nas,ip_address,username,password,port) VALUES ('$_POST[nas]','$_POST[ip]','$_POST[u]','$_POST[p]','$_POST[prt]')");
        echo "<div class='card' style='border-left:4px solid #00a65a'>Router NAS baru berhasil ditambahkan!</div>";
    }
}
if (isset($_GET['del_nas'])) {
    mysqli_query($conn, "DELETE FROM routers WHERE id='$_GET[del_nas]'"); mysqli_query($conn, "UPDATE olt SET router_id=0 WHERE router_id=" . (int)$_GET['del'] . "");
}
?>
<div class="card">
  <h4>Daftarkan Router [ NAS ] MikroTik API (Maksimal 15 NAS: <?= $jml_r ?>/15)</h4>
  <?php if($jml_r < 15): ?>
  <form method="POST" style="margin-top:10px">
    <input name="nas" placeholder="Nama NAS (mis: NAS-02 Cabang)" required>
    <input name="ip" placeholder="IP / Domain MikroTik" required>
    <input name="u" placeholder="User API" required>
    <input name="p" type="password" placeholder="Password API">
    <input name="prt" type="number" value="8728" style="width:80px">
    <button name="add_nas" class="btn btn-g">+ Tambah NAS</button>
  </form>
  <?php endif; ?>
  <div style="overflow-x:auto;width:100%"><table style="min-width:560px;font-size:12px">
    <tr><th>ID</th><th>Nama NAS</th><th>Merek / Board</th><th>IP Address : Port</th><th>CPU</th><th>RAM</th><th>Status</th><th>Aksi</th></tr>
    <?php
    $qr = mysqli_query($conn, "SELECT * FROM routers ORDER BY id ASC");
    while($r = mysqli_fetch_assoc($qr)) {
        $cek = new RouterosAPI();
        $st = $cek->connect($r['ip_address'], $r['username'], $r['password'], $r['port']);
                    $merek = "-"; $cpu = "-"; $ram = "-";
                    if($st) {
                        $res = $cek->comm('/system/resource/print');
                        $tot_m = 0; $free_m = 0; $ver = "";
                        foreach($res as $rl) {
                            if(strpos($rl, '=board-name=')===0) $merek = substr($rl, 12);
                            elseif(strpos($rl, '=version=')===0) $ver = "v" . strtok(substr($rl, 9), " ");
                            elseif(strpos($rl, '=cpu-load=')===0) $cpu = substr($rl, 10) . "%";
                            elseif(strpos($rl, '=total-memory=')===0) $tot_m = (float)substr($rl, 14);
                            elseif(strpos($rl, '=free-memory=')===0) $free_m = (float)substr($rl, 13);
                        }
                        if($merek !== "-" && $ver !== "") $merek .= " <small style='color:#64748b'>($ver)</small>";
                        if($tot_m > 0) {
                            $used_mb = round(($tot_m - $free_m) / 1048576);
                            $tot_mb = round($tot_m / 1048576);
                            $ram = "$used_mb/$tot_mb MB";
                        }
                        $cek->disconnect();
                    }
                    $badge = $st ? "<b style='color:#00a65a'>TERHUBUNG</b>" : "<b style='color:#dd4b39'>TERPUTUS</b>";
                    echo "<tr><td>NAS-$r[id]</td><td><b>$r[nama_nas]</b></td><td><b>$merek</b></td><td>$r[ip_address]:$r[port]</td><td><span style='font-weight:700;color:#0284c7'>$cpu</span></td><td><span style='font-weight:700;color:#8b5cf6'>$ram</span></td><td>$badge</td>
        <td><a href='?page=router&del_nas=$r[id]' onclick=\"return confirm('Hapus NAS ini?')\" class='btn btn-r' style='padding:4px 8px;font-size:11px'>Hapus</a></div></td></tr>";
    }
    ?>
  </table></div>
</div>

<div class="card" style="margin-top:18px;">
    <h3 style="color:#0275d8;margin-bottom:14px;">Daftarkan IP OLT (GPON / EPON / XGPON)</h3>
    <form method="POST" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:18px;">
        <select name="router_id" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;"><option value="0">-- Jaringan Lokal STB (Langsung) --</option><?php $qr_olt=mysqli_query($conn,"SELECT id,nama_nas,ip_address FROM routers ORDER BY id ASC"); while($ro=mysqli_fetch_assoc($qr_olt)){ echo "<option value='{$ro["id"]}'>Via Router: {$ro["nama_nas"]} ({$ro["ip_address"]})</option>"; } ?></select>
        <input type="text" name="nama_olt" placeholder="Nama OLT (mis: OLT-Pusat-01)" required class="form-control" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        <select name="merek_olt" required style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;">
            <option value="">-- Pilih Merek OLT --</option>
            <option value="ZTE">ZTE (GPON/XGPON)</option>
            <option value="Huawei">Huawei (GPON/XGPON)</option>
            <option value="FiberHome">FiberHome</option>
            <option value="HSGQ">HSGQ (EPON/GPON)</option>
            <option value="CDATA">C-DATA (EPON/GPON)</option>
            <option value="V-SOL">V-SOL (EPON/GPON)</option>
            <option value="Global / HiOSO">Global / HiOSO</option>
            <option value="BDCOM">BDCOM</option>
            <option value="Raisecom">Raisecom</option>
            <option value="Lainnya">Merek Lainnya</option>
        </select>
        <input type="text" name="tipe_olt" placeholder="Tipe OLT (mis: C320 / MA5608T / E04)" required style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        <input type="text" name="ip_olt" placeholder="IP Address / Domain OLT" required style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        <select name="protokol_olt" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;">
            <option value="HTTP/Web">Web GUI (HTTP/HTTPS)</option>
            <option value="Telnet">Telnet (CLI)</option>
            <option value="SSH">SSH (CLI)</option>
            <option value="SNMP">SNMP</option>
        </select>
        <input type="number" name="port_olt" value="80" placeholder="Port (80/443/23/22)" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        <input type="text" name="user_olt" placeholder="Username OLT" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        <input type="password" name="pass_olt" placeholder="Password OLT" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        <button type="submit" name="add_olt" class="btn btn-g" style="background:#00994d;color:#fff;border:none;border-radius:8px;padding:10px 16px;font-weight:700;cursor:pointer;">+ Tambah OLT</button>
    </form>
    <div style="overflow-x:auto;">
        <table class="table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#0275d8;color:#fff;text-align:left;">
                    <th style="padding:10px;">ID</th>
                    <th style="padding:10px;">Nama OLT</th>
                    <th style="padding:10px;">Merek & Tipe</th>
                    <th style="padding:10px;">IP Address : Port</th>
                    <th style="padding:10px;">Akses</th>
                    <th style="padding:10px;">Status</th>
                    <th style="padding:10px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
<?php
$qo = mysqli_query($conn, "SELECT o.*, r.nama_nas, r.ip_address as r_ip FROM olt o LEFT JOIN routers r ON o.router_id=r.id ORDER BY o.id ASC");
if ($qo && mysqli_num_rows($qo) > 0) {
    $no_olt = 1;
    while ($o = mysqli_fetch_assoc($qo)) {
        $t_ip = ($o["router_id"] > 0 && !empty($o["r_ip"])) ? $o["r_ip"] : $o["ip_address"];
        $t_pt = ($o["router_id"] > 0 && $o["nat_port"] > 0) ? $o["nat_port"] : $o["port"];
        $fp = @fsockopen($t_ip, (int)$t_pt, $errno, $errstr, 3);
        if ($fp) { fclose($fp); $st_olt = "<b style='color:#00a65a;'>ONLINE</b>"; }
        else { $st_olt = "<b style='color:#dd4b39;'>OFFLINE</b>"; }
        $via = ($o["router_id"] > 0) ? "<br><small style='color:#0284c7;'>Via: " . htmlspecialchars($o["nama_nas"]) . "</small>" : "";
        $url_open = ($o["protokol"] === "HTTP/Web") ? "<a href='remote_proxy.php?set_target={$t_ip}&port={$t_pt}&oid={$o["id"]}' target='_blank' style='background:#0284c7;color:#fff;padding:6px 10px;border-radius:5px;text-decoration:none;font-size:12px;font-weight:700;'>Buka</a>" : "";
        echo "<tr style='border-bottom:1px solid #e2e8f0;'>
            <td style='padding:10px;white-space:nowrap;font-weight:700;'>OLT-{$no_olt}</td>
            <td style='padding:10px;'><b>" . htmlspecialchars($o["nama_olt"]) . "</b></td>
            <td style='padding:10px;'><b>" . htmlspecialchars($o["merek"]) . "</b> <small style='color:#64748b;'>(" . htmlspecialchars($o["tipe_olt"]) . ")</small></td>
            <td style='padding:10px;'>" . htmlspecialchars($o["ip_address"]) . ":{$o["port"]}$via</td>
            <td style='padding:10px;'>" . htmlspecialchars($o["protokol"]) . "</td>
            <td style='padding:10px;white-space:nowrap;'>$st_olt</td>
            <td style='padding:10px;'><div style='display:flex;gap:8px;align-items:center;'>$url_open<a href='?page=router&del_olt={$o["id"]}' onclick=\"return confirm('Hapus OLT ini?')\" class='btn btn-r' style='padding:6px 10px;font-size:12px;margin:0;'>Hapus</a></div></td>
        </tr>";
        $no_olt++;
    }
} else {
    echo "<tr><td colspan='7' style='text-align:center;padding:14px;color:#64748b;'>Belum ada OLT terdaftar.</td></tr>";
}
?>
</tbody>
        </table>
    </div>
</div>