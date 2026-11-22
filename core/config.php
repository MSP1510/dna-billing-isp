<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost", "dna_admin", "DnaBilling2026", "dna_billing");
if (!$conn) die("Koneksi database gagal: " . mysqli_connect_error());

function punyaAkses($menu) {
    if (($_SESSION['role'] ?? '') === 'owner') return true;
    $m = explode(',', $_SESSION['akses_menu'] ?? 'dashboard');
    return in_array($menu, $m);
}

function cekAkses($roles = []) {
    global $page;
    if (($_SESSION['role'] ?? '') === 'owner') return true;
    if (!empty($_SESSION['akses_menu'])) {
        if (!punyaAkses($page)) {
            echo "<div class='card' style='border-left:4px solid #dd4b39'><h4>Akses Ditolak!</h4><p>Akun Anda tidak diizinkan membuka menu ini.</p></div>";
            exit;
        }
        return true;
    }
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        echo "<div class='card'><h4>Akses Ditolak!</h4></div>";
        exit;
    }
    return true;
}
?>
