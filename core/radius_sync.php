<?php
function syncRadiusUser($conn, $user, $pass, $rate_limit = '') {
    $u = mysqli_real_escape_string($conn, $user);
    $p = mysqli_real_escape_string($conn, $pass);
    mysqli_query($conn, "DELETE FROM radcheck WHERE username='$u'");
    mysqli_query($conn, "DELETE FROM radreply WHERE username='$u'");
    mysqli_query($conn, "INSERT INTO radcheck (username, attribute, op, value) VALUES ('$u', 'Cleartext-Password', ':=', '$p')");
    if (!empty($rate_limit)) {
        mysqli_query($conn, "INSERT INTO radreply (username, attribute, op, value) VALUES ('$u', 'Mikrotik-Rate-Limit', ':=', '$rate_limit')");
    }
}
function syncRadiusNAS($conn) {
    mysqli_query($conn, "TRUNCATE TABLE nas");
    $q = mysqli_query($conn, "SELECT * FROM routers");
    while($r = mysqli_fetch_assoc($q)) {
        mysqli_query($conn, "INSERT INTO nas (nasname, shortname, type, secret, description) VALUES ('$r[ip_address]', 'NAS-$r[id]', 'other', '$r[rad_secret]', '$r[nama_nas]')");
    }
    @shell_exec("sudo service freeradius reload");
}
?>
