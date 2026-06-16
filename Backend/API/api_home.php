<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Sesuaikan path ke koneksi database kamu
include '../db_connect.php'; 

$response = [
    "status" => "success",
    "merchants" => [],
    "products" => []
];

// 1. Ambil Data Warung (Rekomendasi / Yang Sedang Buka)
$queryMerchants = "SELECT merchant_id, store_name, live_crowd_status FROM merchants WHERE is_open = 1 LIMIT 5";
$resultMerchants = mysqli_query($conn, $queryMerchants);
if ($resultMerchants) {
    while ($row = mysqli_fetch_assoc($resultMerchants)) {
        $response["merchants"][] = $row;
    }
}

// 2. Ambil Data Produk (Menu Tersedia)
// Kita JOIN dengan merchants agar tahu menu ini dari warung mana
$queryProducts = "
    SELECT p.product_id, p.name, p.price, m.store_name 
    FROM products p 
    JOIN merchants m ON p.merchant_id = m.merchant_id 
    WHERE p.is_available = 1 LIMIT 10
";
$resultProducts = mysqli_query($conn, $queryProducts);
if ($resultProducts) {
    while ($row = mysqli_fetch_assoc($resultProducts)) {
        $response["products"][] = $row;
    }
}

// Kembalikan data dalam bentuk JSON
echo json_encode($response);
exit;
?>