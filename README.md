# D.N.A_Billing — Sistem Manajemen ISP & MikroTik
**D.N.A_NETWORK by MSP_NET**

## Cara Instalasi Ulang di STB Armbian Baru
1. Masuk ke terminal SSH STB Armbian baru sebagai `root`, lalu jalankan:
```bash
apt update && apt install -y git
rm -rf /var/www/html/*
git clone https://github.com /var/www/html
bash /var/www/html/install.sh
```
2. Buka aplikasi web D.N.A_Billing -> login sebagai `owner` -> masuk ke menu **Backup & Restore** -> unggah file cadangan database `.sql` terakhir Anda.

## Cara Kloning & Ganti Nama Usaha (Rebranding untuk Teman / Klien)
Setelah menjalankan `install.sh` di STB baru, jalankan perintah berikut untuk mengubah seluruh identitas aplikasi & template voucher:
```bash
NAMA_BARU="NAMA_WIFI_TEMAN"
NO_CS="0812 3456 7890"
grep -rl "D.N.A_Billing" /var/www/html/ | xargs sed -i "s/D.N.A_Billing/\${NAMA_BARU}_Billing/g"
grep -rl "D.N.A_NETWORK" /var/www/html/ | xargs sed -i "s/D.N.A_NETWORK/\${NAMA_BARU}/g"
grep -rl "D.N.A_NET" /var/www/html/ | xargs sed -i "s/D.N.A_NET/\${NAMA_BARU}/g"
sed -i "s/0895 6307 20504/\${NO_CS}/g" /var/www/html/*.php /var/www/html/pages/*.php
```
