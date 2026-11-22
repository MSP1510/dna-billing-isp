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
