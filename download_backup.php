<?php
require 'core/config.php';
if (($_SESSION['role'] ?? '') !== 'owner') die("Akses ditolak.");
$fname = "Backup_DNA_Billing_" . date('Ymd_Hi') . ".sql";
header("Content-Type: application/sql");
header("Content-Disposition: attachment; filename=\"$fname\"");
passthru("mysqldump -u dna_admin -pDnaBilling2026 dna_billing");
