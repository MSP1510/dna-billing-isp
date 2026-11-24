<?php
require 'core/config.php';
require 'core/wa_gateway.php';
if (isset($_POST['login'])) {
    $u = mysqli_real_escape_string($conn, $_POST['username']);
    $p = mysqli_real_escape_string($conn, $_POST['password']);
    $q = mysqli_query($conn, "SELECT * FROM users WHERE username='$u' AND password='$p' AND status='aktif'");
    if ($r = mysqli_fetch_assoc($q)) {
        $_SESSION['user_id'] = $r['id']; $_SESSION['nama'] = $r['nama']; $_SESSION['role'] = $r['role'];
        header("Location: index.php?page=dashboard"); exit;
    }
    $err = "Login Gagal!";
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }
if (!isset($_SESSION['user_id'])):
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login D.N.A_Billing</title><link href="https://jsdelivr.net" rel="stylesheet"></head>
<body class="bg-dark d-flex align-items-center vh-100"><div class="container"><div class="row justify-content-center"><div class="col-md-4"><div class="card p-4">
<h3 class="text-center text-primary fw-bold">D.N.A_Billing</h3><p class="text-center text-muted small">ISP & FTTH Billing</p>
<?php if(isset($err)) echo "<div class='alert alert-danger'>$err</div>"; ?>
<form method="POST"><input type="text" name="username" class="form-control mb-2" placeholder="owner / admin / teknisi" required>
<input type="password" name="password" class="form-control mb-3" placeholder="123456" required>
<button name="login" class="btn btn-primary w-100">Masuk</button></form></div></div></div></div></body></html>
<?php exit; endif;
$role = $_SESSION['role']; $page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>D.N.A_Billing</title><link href="https://jsdelivr.net" rel="stylesheet"></head>
<body class="bg-light"><nav class="navbar navbar-expand navbar-dark bg-primary px-3">
<a class="navbar-brand fw-bold" href="?page=dashboard">D.N.A_Billing</a><div class="navbar-nav me-auto">
<a class="nav-link" href="?page=dashboard">Home</a>
<?php if(in_array($role,['owner','admin'])): ?><a class="nav-link" href="?page=paket">Paket</a><a class="nav-link" href="?page=pelanggan">Pelanggan</a><a class="nav-link" href="?page=tagihan">Tagihan WA</a><?php endif; ?>
<?php if(in_array($role,['owner','teknisi'])): ?><a class="nav-link" href="?page=tiket">Tiket</a><?php endif; ?>
<?php if($role=='owner'): ?><a class="nav-link" href="?page=staff">Config</a><?php endif; ?>
</div><span class="text-white me-2 small"><?= strtoupper($role) ?></span><a href="?logout=1" class="btn btn-sm btn-danger">Out</a></nav>
<div class="container mt-4"><div class="card p-3 shadow-sm">
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>D.N.A_Billing</title><link href="https://jsdelivr.net" rel="stylesheet"></head>
<body class="bg-light"><nav class="navbar navbar-expand navbar-dark bg-primary px-3">
<a class="navbar-brand fw-bold" href="?page=dashboard">D.N.A_Billing</a><div class="navbar-nav me-auto">
<a class="nav-link" href="?page=dashboard">Home</a>
<?php if(in_array($role,['owner','admin'])): ?><a class="nav-link" href="?page=paket">Paket</a><a class="nav-link" href="?page=pelanggan">Pelanggan</a><a class="nav-link" href="?page=tagihan">Tagihan WA</a><?php endif; ?>
<?php if(in_array($role,['owner','teknisi'])): ?><a class="nav-link" href="?page=tiket">Tiket</a><?php endif; ?>
<?php if($role=='owner'): ?><a class="nav-link" href="?page=staff">Config</a><?php endif; ?>
</div><span class="text-white me-2 small"><?= strtoupper($role) ?></span><a href="?logout=1" class="btn btn-sm btn-danger">Out</a></nav>
<div class="container mt-4"><div class="card p-3 shadow-sm">
<?php
if ($page == 'dashboard') {
    echo "<h4>Dashboard ".strtoupper($role)."</h4><p>Selamat datang di <b>D.N.A_Billing</b>.</p>";
} elseif ($page == 'paket') {
    cekAkses(['owner','admin']);
    if (isset($_POST['add'])) mysqli_query($conn, "INSERT INTO paket (nama_paket,harga,profile_mikrotik) VALUES ('$_POST[n]','$_POST[h]','$_POST[p]')");
    echo "<h5>Paket Internet</h5><form method='POST' class='d-flex gap-2 mb-3'><input name='n' class='form-control' placeholder='Nama'><input name='h' class='form-control' placeholder='Harga'><input name='p' class='form-control' placeholder='Profile'><button name='add' class='btn btn-primary'>+</button></form>";
    $q=mysqli_query($conn,"SELECT * FROM paket"); while($d=mysqli_fetch_assoc($q)) echo "<div>- $d[nama_paket] (Rp ".number_format($d['harga']).")</div>";
} elseif ($page == 'pelanggan') {
    cekAkses(['owner','admin']);
    if (isset($_POST['add'])) {
        mysqli_query($conn, "INSERT INTO pelanggan (nama,no_wa,paket_id) VALUES ('$_POST[n]','$_POST[w]','$_POST[p]')");
        $pid=mysqli_insert_id($conn); $hrg=mysqli_fetch_assoc(mysqli_query($conn,"SELECT harga FROM paket WHERE id='$_POST[p]'"))['harga']??0;
        mysqli_query($conn, "INSERT INTO tagihan (no_invoice,pelanggan_id,bulan_tahun,jumlah) VALUES ('INV-$pid','$pid','".date('m-Y')."','$hrg')");
    }
    echo "<h5>Pelanggan</h5><form method='POST' class='d-flex gap-2 mb-3'><input name='n' class='form-control' placeholder='Nama'><input name='w' class='form-control' placeholder='No WA'><select name='p' class='form-select'>";
    $qp=mysqli_query($conn,"SELECT * FROM paket"); while($p=mysqli_fetch_assoc($qp)) echo "<option value='$p[id]'>$p[nama_paket]</option>";
    echo "</select><button name='add' class='btn btn-success'>+</button></form>";
    $q=mysqli_query($conn,"SELECT * FROM pelanggan"); while($d=mysqli_fetch_assoc($q)) echo "<div>- $d[nama] ($d[no_wa])</div>";
} elseif ($page == 'tagihan') {
    cekAkses(['owner','admin']);
    if (isset($_GET['wa'])) {
        $t=mysqli_fetch_assoc(mysqli_query($conn,"SELECT t.*,p.nama,p.no_wa FROM tagihan t JOIN pelanggan p ON t.pelanggan_id=p.id WHERE t.id='$_GET[wa]'"));
        $res=kirimWA($conn,$t['no_wa'],"Tagihan D.N.A_Billing $t[nama]: Rp ".number_format($t['jumlah']));
        echo "<div class='alert alert-info'>WA: $res</div>";
    }
    echo "<h5>Tagihan & WA</h5>";
    $q=mysqli_query($conn,"SELECT t.*,p.nama,p.no_wa FROM tagihan t JOIN pelanggan p ON t.pelanggan_id=p.id");
    while($d=mysqli_fetch_assoc($q)) echo "<div class='mb-2'>$d[no_invoice] - $d[nama] - Rp ".number_format($d['jumlah'])." <a href='?page=tagihan&wa=$d[id]' class='btn btn-sm btn-success'>Kirim WA</a></div>";
} elseif ($page == 'tiket') {
    cekAkses(['owner','teknisi']);
    echo "<h5>Modul Tiket & Lapangan Teknisi</h5><p>Siap digunakan untuk penugasan teknisi.</p>";
} elseif ($page == 'staff') {
    cekAkses(['owner']);
    echo "<h5>Pengaturan Owner & WA Gateway</h5><p>Endpoint: http://127.0.0</p>";
}
?>
</div></div></body></html>
