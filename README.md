![Tampilan Aplikasi D.N.A_Billing](screenshots/preview-dashboard.png)

# D.N.A_Billing — Sistem Manajemen ISP, RT/RW Net & MikroTik
**D.N.A_NETWORK by MSP_NET**

Sistem manajemen billing ISP, auto-isolir MikroTik (RouterOS v6 & v7), FreeRADIUS, WhatsApp Gateway mandiri, cetak voucher A4 (42 kartu/lembar), pemetaan GIS Satelit ODP/POP real-time, dan Remote Modem ONT untuk server **STB Armbian (ARM64)**.

---

## Cara Instalasi Otomatis di STB Armbian Baru
1. Masuk ke terminal SSH STB Armbian sebagai `root`, lalu salin & tempel perintah berikut:
```bash
apt update && apt install -y git
rm -rf /var/www/html/*
git clone https://github.com /var/www/html
bash /var/www/html/install.sh
```
2. Buka aplikasi di browser (`http://IP_STB_ARMBIAN:8080`), lalu hubungi pengembang (**D.N.A_NETWORK / MSP_NET**) untuk mendapatkan kredensial login pertama kali.
3. Masuk ke menu **Router [ NAS ]** -> tambahkan IP & akun MikroTik -> buka menu **Script MikroTik** -> salin **Script Gabungan All-in-One** ke **New Terminal** Winbox.

---

## Cara Ganti Nama Usaha (Rebranding untuk Teman / Mitra ISP)
Setelah selesai menjalankan `install.sh` di STB baru milik teman Anda, jalankan perintah berikut untuk mengubah seluruh nama usaha dan nomor CS pada aplikasi & template voucher:
```bash
NAMA_BARU="NAMA_WIFI_TEMAN"
NO_CS="0812 3456 7890"
grep -rl "D.N.A_Billing" /var/www/html/ | xargs sed -i "s/D.N.A_Billing/\${NAMA_BARU}_Billing/g"
grep -rl "D.N.A_NETWORK" /var/www/html/ | xargs sed -i "s/D.N.A_NETWORK/\${NAMA_BARU}/g"
grep -rl "D.N.A_NET" /var/www/html/ | xargs sed -i "s/D.N.A_NET/\${NAMA_BARU}/g"
sed -i "s/0895 6307 20504/\${NO_CS}/g" /var/www/html/*.php /var/www/html/pages/*.php
```
Lalu masuk ke menu **Tool Sistem** di dalam aplikasi untuk mengunggah **Logo Ikon Aplikasi**, **Background Halaman Login**, **Nomor Rekening/QRIS**, serta menautkan **QR WhatsApp**.

---

## Cara Mengaktifkan RADIUS Voucher Hotspot di MikroTik
1. Pastikan STB Armbian dan MikroTik berada dalam satu jalur jaringan lokal.
2. Salin perintah berikut ke **New Terminal** Winbox/MikroTik Anda (ganti tulisan huruf kapital di dalamnya sesuai IP STB, IP Gateway MikroTik yang mengarah ke STB, dan Secret yang Anda daftarkan di menu **Router [ NAS ]**):

```rsc
/radius remove [find comment="DNA_BILLING_RADIUS"]; /radius add service=hotspot,ppp address=ISI_IP_STB_ARMBIAN src-address=ISI_IP_GATEWAY_MIKROTIK secret=ISI_SECRET_RADIUS timeout=3000ms comment="DNA_BILLING_RADIUS"; /radius incoming set accept=yes port=3799; /ip hotspot profile set [find] use-radius=yes
```

* **Contoh Pengisian:**
  * `ISI_IP_STB_ARMBIAN` : IP LAN milik STB Armbian Anda (misal `10.20.30.50`)
  * `ISI_IP_GATEWAY_MIKROTIK` : IP interface MikroTik yang satu segmen dengan STB (misal `10.20.30.1`)
  * `ISI_SECRET_RADIUS` : Secret yang sama dengan kolom Radius Secret di menu **Router [ NAS ]** aplikasi

* **Contoh Perintah yang Sudah Diisi:**
```rsc
/radius remove [find comment="DNA_BILLING_RADIUS"]; /radius add service=hotspot,ppp address=10.20.30.50 src-address=10.20.30.1 secret=dna12345 timeout=3000ms comment="DNA_BILLING_RADIUS"; /radius incoming set accept=yes port=3799; /ip hotspot profile set [find] use-radius=yes
```
