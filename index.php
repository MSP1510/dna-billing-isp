<?php
require 'core/config.php';
require 'core/wa_gateway.php';
require 'core/mikrotik.php';
if (file_exists('core/radius_sync.php')) require 'core/radius_sync.php';

if (isset($_POST['login'])) {
    if (trim($_POST['captcha'] ?? '') !== trim($_POST['cap_key'] ?? '')) {
        $err = "Kode Captcha Salah!";
    } else {
        $u = mysqli_real_escape_string($conn, $_POST['username']);
        $p = mysqli_real_escape_string($conn, $_POST['password']);
        $q = mysqli_query($conn, "SELECT * FROM users WHERE username='$u' AND password='$p' AND status='aktif'");
        if ($r = mysqli_fetch_assoc($q)) {
            $_SESSION['user_id'] = $r['id'];
            $_SESSION['nama'] = $r['nama'];
            $_SESSION['role'] = $r['role'];
            $_SESSION['akses_menu'] = $r['akses_menu'] ?? '';
            $ip_u = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '-';
            mysqli_query($conn, "INSERT INTO login_logs (user_id,nama,username,role,aksi,ip_addr) VALUES ($r[id],'$r[nama]','$r[username]','$r[role]','LOGIN','$ip_u')");
            header("Location: index.php?page=dashboard"); exit;
        }
        $err = "Username atau Password salah!";
    }
}
if (isset($_GET['logout'])) {
    if (!empty($_SESSION['user_id'])) {
        $ip_u = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '-';
        $nm_u = mysqli_real_escape_string($conn, $_SESSION['nama'] ?? '-');
        $rl_u = mysqli_real_escape_string($conn, $_SESSION['role'] ?? '-');
        $uid_u = (int)($_SESSION['user_id'] ?? 0);
        mysqli_query($conn, "INSERT INTO login_logs (user_id,nama,username,role,aksi,ip_addr) VALUES ($uid_u,'$nm_u','-','$rl_u','LOGOUT','$ip_u')");
    }
    session_destroy(); header("Location: index.php"); exit;
}
if (!isset($_SESSION['user_id'])) { require 'login_ui.php'; exit; }
$role = $_SESSION['role']; $page = $_GET['page'] ?? 'dashboard';
require 'dashboard_ui.php';
require 'dashboard_content.php';
?>
