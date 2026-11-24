<?php
function kirimWA($conn, $no, $pesan, $img = '') {
    $cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan WHERE id=1"));
    $no = preg_replace('/[^0-9]/', '', $no);
    if (substr($no, 0, 1) === '0') $no = '62' . substr($no, 1);
    $payload = ["api_key" => $cfg['wa_api_key'], "number" => $no, "message" => $pesan];
    if (!empty($img)) $payload["image_url"] = $img;
    $ch = curl_init($cfg['wa_endpoint']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    $res = curl_exec($ch); curl_close($ch);
    return $res ?: '{"status":false,"msg":"Service WA Gateway belum aktif"}';
}
?>
