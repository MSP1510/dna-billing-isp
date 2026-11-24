<?php
function kirimWA($conn, $no, $pesan) {
    $cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan WHERE id=1"));
    $no = preg_replace('/[^0-9]/', '', $no);
    if (substr($no, 0, 1) === '0') $no = '62' . substr($no, 1);
    $ch = curl_init($cfg['wa_endpoint']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["api_key" => $cfg['wa_api_key'], "number" => $no, "message" => $pesan]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch); curl_close($ch);
    return $res ?: 'Gateway belum aktif';
}
?>
