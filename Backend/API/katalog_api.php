<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db_connect.php'; 

// 1. Cek API Key
$headers = getallheaders();
$api_key = $headers['X-API-KEY'] ?? '';

if ($api_key !== '12345') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "API Key Tidak Valid"]); 
    exit;
}

// 2. Cek Sesi Pembeli
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesi login tidak terdeteksi. Silakan login ulang."]); 
    exit;
}

// 3. Cek Method & Tarik Data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $merchant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Tarik data spesifik warung
    $q_merchant = mysqli_query($conn, "SELECT * FROM merchants WHERE merchant_id = $merchant_id");
    $merchant = mysqli_fetch_assoc($q_merchant);

    if (!$merchant) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Kantin tidak ditemukan di database!"]); 
        exit;
    }

    // Tarik data spesifik menu milik warung tersebut
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

mysqli_close($conn);
?>