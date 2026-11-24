<div class="card" style="border-left:5px solid #0284c7;background:linear-gradient(135deg,#f8fafc 0%,#e0f2fe 100%)">
  <h3 style="color:#0f172a;margin-bottom:6px">Tentang Aplikasi & Pengembang (Developer)</h3>
  <p style="font-size:13px;color:#334155;line-height:1.6;margin-bottom:12px">
    <b>D.N.A_Billing</b> adalah sistem manajemen operasional ISP, RT/RW Net, Billing Otomatis, dan Integrasi MikroTik terpadu yang dirancang khusus untuk berjalan ringan, cepat, dan hemat daya di atas perangkat server <b>STB Armbian (ARM64)</b>.
  </p>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;background:#fff;padding:12px;border-radius:8px;border:1px solid #cbd5e1">
    <div>
      <small style="color:#64748b">Nama Sistem & Lisensi</small><br>
      <b style="color:#0284c7;font-size:14px">D.N.A_Billing v2.6 Enterprise</b><br>
      <span style="font-size:12px;color:#334155">Pemilik Resmi: <b>D.N.A_NETWORK by MSP_NET</b></span>
    </div>
    <div>
      <small style="color:#64748b">Arsitek & Pengembang Sistem</small><br>
      <b style="color:#0f172a;font-size:14px">MSP_NET Tech & AI Engineering</b><br>
      <span style="font-size:12px;color:#475569">Integrasi RouterOS API, FreeRADIUS, GIS & WA Gateway</span>
    </div>
    <div>
      <small style="color:#64748b">Mesin & Teknologi Inti</small><br>
      <span style="font-size:12px;color:#334155">Nginx + PHP 8.3 FPM, MariaDB, FreeRADIUS, Node.js Baileys WA Engine, Leaflet GIS, & PWA Mobile</span>
    </div>
  </div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
    <div>
      <h4>Buku Panduan: Fungsi & Tujuan Setiap Menu Aplikasi</h4>
      <small style="color:#64748b">Gunakan kolom pencarian di samping untuk mencari fungsi menu tertentu dengan cepat.</small>
    </div>
    <input type="text" id="cariPnd" onkeyup="filterPnd()" placeholder="Cari nama menu atau fungsi..." style="width:210px">
  </div>

  <div style="overflow-x:auto">
  <table id="tblPnd">
    <tr><th style="width:180px">Nama Menu</th><th style="width:130px">Hak Akses</th><th>Fungsi Utama & Tujuan Penggunaan</th></tr>
    <tr>
      <td><b>1. Dashboard</b></td>
      <td>Semua Staf</td>
      <td>Menampilkan ringkasan kondisi usaha secara real-time: jumlah pelanggan aktif/isolir, pemasukan hari ini & bulan ini, grafik pendapatan, serta pintasan cepat.</td>
    </tr>
    <tr>
      <td><b>2. Session User</b></td>
      <td>Owner, Admin, Teknisi</td>
      <td>Memantau daftar pelanggan <b>PPPoE, Hotspot, dan Static IP</b> yang sedang aktif di MikroTik secara langsung beserta IP, MAC Address, Uptime, dan tombol <b>Kick / Isolir</b>.</td>
    </tr>
    <tr>
      <td><b>3. Monitor Online</b></td>
      <td>Owner, Admin, Teknisi</td>
      <td>Mendeteksi status perangkat modem pelanggan (<b>ONLINE</b> vs <b>OFFLINE</b>) beserta jalur ODP-nya untuk mengetahui gangguan kabel putus atau modem mati sebelum pelanggan melapor.</td>
    </tr>
    <tr>
      <td><b>4. Remote Modem</b></td>
      <td>Owner, Admin, Teknisi</td>
      <td>Membuka halaman pengaturan modem pelanggan (ZTE, Huawei, FiberHome, TP-Link) dari jarak jauh melalui proxy aman STB Armbian untuk membantu pelanggan mengganti nama/password Wi-Fi.</td>
    </tr>
    <tr>
      <td><b>5. Router [ NAS ]</b></td>
      <td>Khusus Owner</td>
      <td>Menghubungkan satu atau beberapa Router MikroTik ke aplikasi melalui jalur <b>API (Port 8728)</b> dan <b>FreeRADIUS</b> agar seluruh perintah sistem langsung berjalan di MikroTik.</td>
    </tr>
    <tr>
      <td><b>6. Data ODP | POP</b></td>
      <td>Owner, Admin, Teknisi</td>
      <td><b>GIS Network Map</b> interaktif untuk memetakan titik koordinat Server/POP, ODC, Box ODP, sisa kapasitas port, serta visualisasi garis kabel fiber optik berjalan untuk pelanggan yang sedang online.</td>
    </tr>
    <tr>
      <td><b>7. Profil Paket</b></td>
      <td>Owner, Admin</td>
      <td>Mengatur daftar paket langganan bulanan (PPPoE / Static IP) dan paket <b>Voucher Hotspot</b> lengkap dengan batas kecepatan (rate-limit), masa aktif, harga modal, dan harga jual.</td>
    </tr>
    <tr>
      <td><b>8. List Pelanggan</b></td>
      <td>Owner, Admin</td>
      <td>Database utama seluruh pelanggan beserta paket, tanggal jatuh tempo, koordinat rumah, tombol <b>Isolir / Buka Isolir 1-Klik</b>, serta sinkronisasi impor pelanggan dari MikroTik.</td>
    </tr>
    <tr>
      <td><b>9. Invoice & WA</b></td>
      <td>Owner, Admin</td>
      <td>Membuat tagihan bulanan massal, menerima pembayaran 1-klik (otomatis masuk kas & membuka isolir), serta mengirim <b>Broadcast WhatsApp</b> pengumuman gangguan atau promo ke seluruh pelanggan.</td>
    </tr>
    <tr>
      <td><b>10. Keuangan & Kas</b></td>
      <td>Owner, Admin</td>
      <td>Pembukuan arus kas masuk (pembayaran tagihan & penjualan voucher) dan pengeluaran operasional, dilengkapi filter <b>Harian, Mingguan, Bulanan, Tahunan</b> serta ekspor <b>Laporan PDF</b>.</td>
    </tr>
    <tr>
      <td><b>11. Inventaris Aset</b></td>
      <td>Owner, Admin</td>
      <td>Mencatat seluruh aset fisik perusahaan (OLT, Router, Splicer, Tiang, Stok ONT) beserta kondisi barang, penanggung jawab (PIC), dan opsi pemotongan saldo kas otomatis saat pembelian aset.</td>
    </tr>
    <tr>
      <td><b>12. Kartu Voucher</b></td>
      <td>Owner, Admin</td>
      <td>Membuat ratusan kode voucher hotspot dalam sekali klik dengan sistem <b>Batch Anti-Cetak Ganda</b>, template siap cetak <b>Kertas A4 (42 kartu/lembar)</b>, kode QR login otomatis, dan pembeda warna tiap nominal.</td>
    </tr>
    <tr>
      <td><b>13. Mitra / Reseller</b></td>
      <td>Owner, Admin</td>
      <td>Mengelola stok voucher yang dititipkan ke warung atau agen penjualan, menghitung komisi mitra secara otomatis, dan mencatat setoran bersih ke buku kas.</td>
    </tr>
    <tr>
      <td><b>14. Tiket Laporan</b></td>
      <td>Semua Staf</td>
      <td>Mencatat laporan gangguan atau permintaan pasang baru dari pelanggan agar teknisi lapangan dapat memantau antrean pekerjaan dan memperbarui status penyelesaiannya.</td>
    </tr>
    <tr>
      <td><b>15. List Neighbor</b></td>
      <td>Owner, Admin, Teknisi</td>
      <td>Mendeteksi perangkat jaringan tetangga (AP pemancar, Switch, MikroTik) yang terhubung satu jalur Layer-2 dengan router utama beserta tombol <b>Remote Web</b> cepat.</td>
    </tr>
    <tr>
      <td><b>16. Manajemen User</b></td>
      <td>Khusus Owner</td>
      <td>Menambah akun login untuk staf Admin, Kasir, atau Teknisi lapangan serta mengatur <b>hak akses centang per menu</b> sesuai batas kewenangan masing-masing.</td>
    </tr>
    <tr>
      <td><b>17. Log Aktivitas</b></td>
      <td>Khusus Owner</td>
      <td>Catatan keamanan (*audit trail*) yang merekam waktu, tanggal, dan alamat IP setiap kali staf melakukan <b>LOGIN</b> maupun <b>LOGOUT</b> dari aplikasi.</td>
    </tr>
    <tr>
      <td><b>18. Script MikroTik</b></td>
      <td>Khusus Owner</td>
      <td>Menyediakan <b>Script Gabungan All-in-One</b> siap copy-paste ke Winbox untuk mengatur API, FreeRADIUS, Auto-Isolir Port 8088, Remote ONT, NTP, dan seluruh Profil Paket otomatis.</td>
    </tr>
    <tr>
      <td><b>19. Backup & Restore</b></td>
      <td>Khusus Owner</td>
      <td>Mengunduh salinan cadangan <b>Database (.SQL)</b> maupun <b>Full Aplikasi (.tar.gz)</b> untuk keamanan data serta memulihkan database kapan saja jika terjadi pergantian perangkat.</td>
    </tr>
    <tr>
      <td><b>20. Tool Sistem</b></td>
      <td>Khusus Owner</td>
      <td>Pusat pengaturan identitas aplikasi: menautkan <b>QR WhatsApp Gateway</b>, mengatur nomor rekening/QRIS pembayaran, serta mengunggah <b>Logo Ikon Aplikasi</b> & <b>Background Halaman Login</b>.</td>
    </tr>
  </table>
  </div>
</div>

<script>
function filterPnd(){
  let q = document.getElementById('cariPnd').value.toLowerCase();
  document.querySelectorAll('#tblPnd tr').forEach((r,i) => {
    if (i > 0) r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
