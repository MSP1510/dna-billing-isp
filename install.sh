#!/bin/bash
set -e
echo "=== Memulai Instalasi Otomatis D.N.A_Billing ==="
apt update && apt install -y nginx mariadb-server php-fpm php-mysql php-curl php-gd php-mbstring php-xml freeradius freeradius-mysql nodejs npm curl

mkdir -p /var/www/html /opt/dna-wa-gateway
cp -rf ./* /var/www/html/
if [ -d "./wa-gateway-backup" ]; then
    cp -rf ./wa-gateway-backup/* /opt/dna-wa-gateway/
fi

mysql -e "CREATE DATABASE IF NOT EXISTS dna_billing;"
mysql -e "CREATE USER IF NOT EXISTS 'dna_admin'@'localhost' IDENTIFIED BY 'DnaBilling2026';"
mysql -e "GRANT ALL PRIVILEGES ON dna_billing.* TO 'dna_admin'@'localhost'; FLUSH PRIVILEGES;"
if [ -f "/var/www/html/schema_awal.sql" ]; then
    mysql dna_billing < /var/www/html/schema_awal.sql
fi

chmod -R 777 /var/www/html/uploads /var/www/html/assets 2>/dev/null || true
chown -R www-data:www-data /var/www/html/
systemctl restart nginx mariadb php*-fpm freeradius
echo "=== Instalasi D.N.A_Billing Selesai! Silakan Restore Database .SQL Anda ==="
