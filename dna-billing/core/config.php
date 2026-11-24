<?php
session_start();
$conn = mysqli_connect("localhost", "dna_admin", "DnaBilling2026", "dna_billing");
if (!$conn) die("Koneksi DB Gagal");
function cekAkses($r = []) {
    if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
    if (!empty($r) && !in_array($_SESSION['role'], $r)) { die("<div class='alert alert-danger m-4'>Akses Ditolak untuk Role: {$_SESSION['role']}</div>"); }
}
?>
