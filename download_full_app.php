<?php
require 'core/config.php';
if (($_SESSION['role'] ?? '') !== 'owner') die("Akses ditolak.");

$tgl = date('Ymd_Hi');
$sql_tmp = "/tmp/dna_billing_db.sql";
$tar_tmp = "/tmp/FULL_APP_DNA_Billing_$tgl.tar.gz";

shell_exec("mysqldump -u dna_admin -pDnaBilling2026 dna_billing > $sql_tmp");
shell_exec("tar -czf $tar_tmp /var/www/html /opt/dna-wa-gateway $sql_tmp 2>/dev/null");

header("Content-Type: application/gzip");
header("Content-Disposition: attachment; filename=\"FULL_APP_DNA_Billing_$tgl.tar.gz\"");
header("Content-Length: " . filesize($tar_tmp));
readfile($tar_tmp);
@unlink($sql_tmp);
@unlink($tar_tmp);
