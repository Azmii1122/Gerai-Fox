<?php
session_start();
ob_clean(); // Mencegah karakter error HTML masuk ke dalam JSON
header("Content-Type: application/json");

include '../db_connect.php'; 

// Cek Sesi Pembeli (Tanpa API Key)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesi login tidak terdeteksi. Silakan login ulang."]); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $merchant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $q_merchant = mysqli_query($conn, "SELECT * FROM merchants WHERE merchant_id = $merchant_id");
    $merchant = mysqli_fetch_assoc($q_merchant);

    if (!$merchant) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Kantin tidak ditemukan!"]); 
        exit;
    }

    $q_products = mysqli_query($conn, "SELECT * FROM products WHERE merchant_id = $merchant_id ORDER BY product_id ASC");
    $products = [];
    while ($row = mysqli_fetch_assoc($q_products)) {
        $products[] = $row;
    }

    echo json_encode([
        "status" => "success", 
        "merchant" => $merchant, 
        "products" => $products
    ]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
}
exit;
?>