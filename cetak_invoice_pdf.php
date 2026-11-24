<?php
require_once __DIR__ . "/core/config.php";

function buatPdfInvoiceLunas($conn, $pel_id, $tgh_id = 0) {
    $pid = (int)$pel_id;
    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, pk.nama_paket, pk.harga FROM pelanggan p LEFT JOIN paket pk ON p.paket_id=pk.id WHERE p.id=$pid"));
    if (!$p) return false;

    if ($tgh_id > 0) {
        $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tagihan WHERE id=".(int)$tgh_id));
    } else {
        $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tagihan WHERE pelanggan_id=$pid ORDER BY id DESC LIMIT 1"));
    }

    $cfg = [];
    $qc = mysqli_query($conn, "SELECT * FROM pengaturan");
    while ($rc = mysqli_fetch_assoc($qc)) $cfg[$rc['kunci']] = $rc['nilai'];

    $usaha   = strtoupper($cfg['nama_usaha'] ?? 'MSP_NETWORK WIFI');
    $alamat  = $cfg['alamat_usaha'] ?? 'Layanan Internet Cepat & Stabil';
    $no_cs   = $cfg['no_cs'] ?? '-';
    $no_inv  = $t['no_invoice'] ?? ('INV-' . date('Ym') . '-' . ($p['kode_pel'] ?: $p['id']));
    $periode = $t['bulan_tahun'] ?? date('m-Y');
    $nominal = number_format((float)($t['jumlah'] ?? ($p['harga'] ?? 0)), 0, ',', '.');
    $tgl_lns = !empty($t['tgl_bayar']) ? date('d-m-Y H:i', strtotime($t['tgl_bayar'])) : date('d-m-Y H:i');
    $nama_p  = strtoupper($p['nama']);
    $kode_p  = $p['kode_pel'] ?: ('PEL-' . $p['id']);
    $paket_p = $p['nama_paket'] ?? 'Internet Bulanan';

    // Cari file logo Wi-Fi / usaha yang tersimpan (JPG/PNG) untuk disematkan ke PDF
    $logo_path = '';
    foreach ([$cfg['logo_app'] ?? '', 'uploads/logo.png', 'uploads/logo.jpg', 'assets/logo.png'] as $cand) {
        if ($cand !== '' && file_exists(__DIR__ . '/' . ltrim($cand, '/'))) {
            $logo_path = __DIR__ . '/' . ltrim($cand, '/'); break;
        }
    }

    // Susun stream vektor PDF (Ikon Sinyal Wi-Fi + Header + Data Pelanggan + Nominal + Stempel LUNAS)
    $esc = function($s) { return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], preg_replace('/[^\x20-\x7E]/', '', $s)); };

    $stream  = "q\n";
    // Bingkai luar kartu invoice
    $stream .= "0.85 0.88 0.92 RG 1.5 w 30 380 535 420 re S\n";
    // Header biru atas
    $stream .= "0.01 0.52 0.78 rg 30 735 535 65 re f\n";
    // Ikon gelombang Wi-Fi vektor di pojok kiri header
    $stream .= "1 1 1 RG 2.2 w\n";
    $stream .= "54 756 m 62 764 74 764 82 756 c S\n";
    $stream .= "48 763 m 60 775 76 775 88 763 c S\n";
    $stream .= "42 770 m 58 786 78 786 94 770 c S\n";
    $stream .= "1 1 1 rg 65 748 6 6 re f\n";

    // Teks Header Usaha
    $stream .= "BT /F2 16 Tf 1 1 1 rg 108 772 Td (" . $esc($usaha) . ") Tj ET\n";
    $stream .= "BT /F1 9 Tf 1 1 1 rg 108 756 Td (" . $esc($alamat . " | CS: " . $no_cs) . ") Tj ET\n";
    $stream .= "BT /F2 13 Tf 1 1 1 rg 415 768 Td (INVOICE LUNAS) Tj ET\n";
    $stream .= "BT /F1 9 Tf 1 1 1 rg 415 753 Td (" . $esc($no_inv) . ") Tj ET\n";

    // Detail Pelanggan & Tagihan
    $rows = [
        ["Nama Pelanggan", ": " . $nama_p],
        ["ID Pelanggan",   ": " . $kode_p],
        ["Paket Layanan",  ": " . $paket_p],
        ["Periode Tagihan",": " . $periode],
        ["Tanggal Lunas",  ": " . $tgl_lns . " WIB"],
        ["Metode / Status",": PEMBAYARAN LUNAS (AKTIF)"]
    ];
    $y = 695;
    foreach ($rows as $rw) {
        $stream .= "BT /F1 11 Tf 0.25 0.28 0.35 rg 50 $y Td (" . $esc($rw) . ") Tj ET\n";
        $stream .= "BT /F2 11 Tf 0.05 0.08 0.15 rg 185 $y Td (" . $esc($rw) . ") Tj ET\n";
        $y -= 26;
    }

    // Kotak Nominal Tagihan
    $stream .= "0.94 0.99 0.95 rg 48 475 498 52 re f\n";
    $stream .= "0.13 0.77 0.37 RG 1.5 w 48 475 498 52 re S\n";
    $stream .= "BT /F1 11 Tf 0.10 0.45 0.22 rg 65 505 Td (TOTAL NOMINAL TAGIHAN DIBAYAR) Tj ET\n";
    $stream .= "BT /F2 18 Tf 0.05 0.55 0.25 rg 65 484 Td (Rp " . $esc($nominal) . ",-) Tj ET\n";

    // Stempel LUNAS hijau di kanan bawah
    $stream .= "0.13 0.77 0.37 RG 2.5 w 425 483 108 36 re S\n";
    $stream .= "BT /F2 16 Tf 0.13 0.77 0.37 rg 448 495 Td (LUNAS) Tj ET\n";

    // Catatan kaki
    $stream .= "BT /F1 9 Tf 0.40 0.45 0.50 rg 50 420 Td (Terima kasih atas pembayaran tagihan internet Anda tepat waktu.) Tj ET\n";
    $stream .= "BT /F1 9 Tf 0.40 0.45 0.50 rg 50 405 Td (Bukti pembayaran ini sah dan diterbitkan otomatis oleh sistem " . $esc($usaha) . ".) Tj ET\n";
    $stream .= "Q\n";

    // Susun struktur file PDF 1.4 standar
    $objs = [];
    $objs = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $objs = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
    $objs = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >> endobj\n";
    $objs = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
    $objs = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n";
    $objs = "6 0 obj << /Length " . strlen($stream) . " >> stream\n" . $stream . "endstream endobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    for ($i = 1; $i <= 6; $i++) {
        $offsets[$i] = strlen($pdf);
        $pdf .= $objs[$i];
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 7\n0000000000 65535 f \n";
    for ($i = 1; $i <= 6; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer << /Size 7 /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

    $fname = "Invoice_Lunas_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $no_inv) . ".pdf";
    $fpath = __DIR__ . "/uploads/invoices/" . $fname;
    file_put_contents($fpath, $pdf);

    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
    return ['path' => $fpath, 'url' => "$proto://$host/uploads/invoices/$fname", 'file' => $fname, 'no_inv' => $no_inv, 'nominal' => $nominal, 'nama' => $nama_p];
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '') && isset($_GET['pel_id'])) {
    $res = buatPdfInvoiceLunas($conn, (int)$_GET['pel_id'], (int)($_GET['tgh_id'] ?? 0));
    if ($res && file_exists($res['path'])) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $res['file'] . '"');
        readfile($res['path']);
        exit;
    }
    echo "Invoice tidak ditemukan.";
}
