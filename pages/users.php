<?php if ($role !== 'owner') { echo "<div class='card'>Khusus Owner!</div>"; exit; }

$daftar_menu = [
    'dashboard' => 'Dashboard & Grafik',
    'session'   => 'Session User (MikroTik)',
    'router'    => 'Router [ NAS ]',
    'odp'       => 'Data ODP | POP (Peta GIS)',
    'paket'     => 'Profil Paket & Bandwidth',
    'pelanggan' => 'List Pelanggan (Isolir/Edit)',
    'tagihan'   => 'Invoice & Broadcast WA',
    'keuangan'  => 'Keuangan, Kas & Download PDF',
    'aset'      => 'Inventaris Aset Perusahaan',
    'voucher'   => 'Kartu Voucher Hotspot',
    'tiket'     => 'Tiket Laporan Teknisi',
    'neighbor'  => 'List Neighbor Perangkat'
];

if (isset($_POST['save_usr'])) {
    $nm  = mysqli_real_escape_string($conn, $_POST['nama']);
    $un  = mysqli_real_escape_string($conn, $_POST['user']);
    $pw  = mysqli_real_escape_string($conn, $_POST['pass']);
    $rl  = mysqli_real_escape_string($conn, $_POST['role_u']);
    $st  = mysqli_real_escape_string($conn, $_POST['status']);
    $aks = isset($_POST['menus']) ? implode(',', $_POST['menus']) : 'dashboard';
    if ($rl === 'owner') $aks = implode(',', array_keys($daftar_menu));

    if (!empty($_POST['uid'])) {
        $uid = (int)$_POST['uid'];
        mysqli_query($conn, "UPDATE users SET nama='$nm', username='$un', password='$pw', role='$rl', status='$st', akses_menu='$aks' WHERE id=$uid");
    } else {
        mysqli_query($conn, "INSERT INTO users (nama,username,password,role,status,akses_menu) VALUES ('$nm','$un','$pw','$rl','$st','$aks')");
    }
    echo "<script>location.href='?page=users';</script>"; exit;
}

if (isset($_GET['del_u'])) {
    $uid = (int)$_GET['del_u'];
    if ($uid > 1) mysqli_query($conn, "DELETE FROM users WHERE id=$uid");
    echo "<script>location.href='?page=users';</script>"; exit;
}

$eu = isset($_GET['edit']) ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=".(int)$_GET['edit'])) : null;
$eu_menus = $eu ? explode(',', $eu['akses_menu'] ?? '') : ['dashboard'];
?>
<div class="card">
  <h4><?= $eu ? 'Edit Akun & Hak Akses Staff' : 'Tambah User Login & Atur Hak Akses Menu' ?></h4>
  <form method="POST" style="margin-top:12px;background:#f8fafc;padding:14px;border-radius:8px;border:1px solid #e2e8f0">
    <input type="hidden" name="uid" value="<?= $eu['id']??'' ?>">
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
      <input name="nama" value="<?= htmlspecialchars($eu['nama']??'') ?>" placeholder="Nama Lengkap (mis: Teknisi Budi)" required>
      <input name="user" value="<?= htmlspecialchars($eu['username']??'') ?>" placeholder="Username Login" required>
      <input name="pass" value="<?= htmlspecialchars($eu['password']??'') ?>" placeholder="Password Login" required>
      <select name="role_u">
        <option value="admin" <?= ($eu['role']??'')=='admin'?'selected':'' ?>>Jabatan: ADMIN</option>
        <option value="teknisi" <?= ($eu['role']??'')=='teknisi'?'selected':'' ?>>Jabatan: TEKNISI</option>
        <option value="owner" <?= ($eu['role']??'')=='owner'?'selected':'' ?>>Jabatan: OWNER (Full)</option>
      </select>
      <select name="status">
        <option value="aktif" <?= ($eu['status']??'')=='aktif'?'selected':'' ?>>Status: AKTIF</option>
        <option value="nonaktif" <?= ($eu['status']??'')=='nonaktif'?'selected':'' ?>>Status: NONAKTIF</option>
      </select>
    </div>

    <div style="margin-bottom:12px">
      <div style="font-size:12px;font-weight:700;margin-bottom:6px;color:#0f172a">Centang Menu yang Boleh Diakses User Ini:</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:6px;background:#fff;padding:10px;border-radius:6px;border:1px solid #cbd5e1">
        <?php foreach($daftar_menu as $k => $lbl): ?>
          <label style="font-size:12px;display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="checkbox" name="menus[]" value="<?= $k ?>" <?= in_array($k, $eu_menus)?'checked':'' ?>> <?= $lbl ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <button name="save_usr" class="btn btn-g"><?= $eu ? 'Update User & Hak Akses' : '+ Simpan User Baru' ?></button>
    <?php if($eu) echo "<a href='?page=users' class='btn btn-r'>Batal</a>"; ?>
  </form>

  <div style="overflow-x:auto">
  <table>
    <tr><th>Nama Staff</th><th>Username</th><th>Password</th><th>Jabatan</th><th>Menu yang Diizinkan</th><th>Status</th><th>Aksi</th></tr>
    <?php
    $q = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
    while($u = mysqli_fetch_assoc($q)):
      $st = $u['status']=='aktif' ? "<span style='background:#00a65a;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>AKTIF</span>" : "<span style='background:#dd4b39;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px'>NONAKTIF</span>";
      $aks_txt = ($u['role']=='owner') ? '<b>SEMUA MENU (FULL AKSES)</b>' : str_replace(',', ', ', $u['akses_menu']??'-');
    ?>
    <tr>
      <td><b><?= htmlspecialchars($u['nama']) ?></b></td>
      <td><?= htmlspecialchars($u['username']) ?></td>
      <td><code><?= htmlspecialchars($u['password']) ?></code></td>
      <td><span style="background:#0f172a;color:#38bdf8;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700"><?= strtoupper($u['role']) ?></span></td>
      <td style="font-size:11px;max-width:260px"><?= $aks_txt ?></td>
      <td><?= $st ?></td>
      <td style="white-space:nowrap">
        <a href="?page=users&edit=<?= $u['id'] ?>" class="btn" style="padding:4px 8px;font-size:11px">Edit Akses</a>
        <?php if($u['id'] > 1): ?>
          <a href="?page=users&del_u=<?= $u['id'] ?>" onclick="return confirm('Hapus akun user ini?')" class="btn btn-r" style="padding:4px 8px;font-size:11px">Hapus</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
</div>
