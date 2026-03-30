<?php
// 1. Masukkan API Key Sellauth kamu di sini
$api_key = "aa_DmdgIhjyOkFwyEhmKLRueDdJWmaXBXoWcCivzowQiQriLSQeuiTxHHKIPlJHgUQp";

// 2. Ambil data otomatis dari Sellauth saat ada yang beli
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 3. Cek produk apa yang dibeli (Misal: Shika)
if ($data['product_id'] == "ID_PRODUK_KAMU") {
    
    // DI SINI: Kamu ambil 1 kunci dari database/catatan kamu
    $kode_kunci = "KUNCI-GAME-ABC-123"; 

    // 4. Kirim balik kuncinya ke Sellauth untuk ditampilkan ke pembeli
    echo json_encode([
        "delivered_info" => $kode_kunci
    ]);
}
?>