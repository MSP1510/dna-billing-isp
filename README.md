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

## Cara Mengaktifkan Fitur Instal Aplikasi ke HP Android (PWA HTTPS)
1. Pasang Cloudflare Tunnel di STB Armbian:
```bash
curl -L https://github.com -o /tmp/cf.deb
dpkg -i /tmp/cf.deb
nohup cloudflared tunnel --url http://localhost:8080 > /var/log/cf-tunnel.log 2>&1 &
sleep 8 && grep -o 'https://[-a-z0-9]*\.trycloudflare\.com' /var/log/cf-tunnel.log | head -n 1
```
2. Buka link `https://...trycloudflare.com` di Google Chrome HP Android -> tekan titik tiga (`⋮`) -> pilih **Instal aplikasi**.
