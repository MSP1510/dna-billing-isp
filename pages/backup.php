<?php if ($role !== 'owner') { echo "<div class='card'>Khusus Owner!</div>"; exit; }
if (isset($_POST['restore_db']) && !empty($_FILES['sql_file']['tmp_name'])) {
    $tmp = $_FILES['sql_file']['tmp_name'];
    shell_exec("mysql -u dna_admin -pDnaBilling2026 dna_billing < " . escapeshellarg($tmp));
    echo "<div class='card' style='border-left:4px solid #00a65a'>Database berhasil di-restore!</div>";
}
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
  <div class="card" style="border-left:5px solid #0284c7">
    <h4>1. Backup Full Aplikasi + Database (Penyelamat STB Rusak)</h4>
    <p style="font-size:13px;color:#475569;margin:10px 0 14px">Unduh <b>seluruh kode aplikasi web, logo, background, mesin WA Gateway, dan seluruh database</b> menjadi 1 file <code>.tar.gz</code>. Simpan file ini di Google Drive / Laptop Anda!</p>
    <a href="download_full_app.php" class="btn" style="background:#0284c7">Download Full Paket Aplikasi (.tar.gz)</a>
  </div>
  <div class="card" style="border-left:5px solid #00a65a">
    <h4>2. Backup Database Harian (.SQL)</h4>
    <p style="font-size:13px;color:#475569;margin:10px 0 14px">Cadangkan data pelanggan, tagihan, keuangan, voucher, dan aset secara rutin ke HP Anda.</p>
    <a href="download_backup.php" class="btn btn-g">Download Database (.SQL)</a>
  </div>
  <div class="card" style="border-left:5px solid #f39c12">
    <h4>3. Restore Database (.SQL)</h4>
    <p style="font-size:13px;color:#475569;margin:10px 0">Pulihkan database dari file <code>.sql</code>:</p>
    <form method="POST" enctype="multipart/form-data">
      <input type="file" name="sql_file" accept=".sql" required style="margin-bottom:10px;width:100%"><br>
      <button name="restore_db" onclick="return confirm('Timpa database saat ini dengan file backup?')" class="btn btn-w">Mulai Restore Database</button>
    </form>
  </div>
</div>
