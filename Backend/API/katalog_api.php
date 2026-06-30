<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include '../db_connect.php'; 

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    // Menampilkan semua warung/merchant di halaman depan (Dashboard)
    case 'get_merchants':
        $query = "SELECT * FROM merchants WHERE is_open = 1";
        $res = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    // Menampilkan detail warung dan produk-produknya (Halaman Katalog)
    case 'get_catalog':
        $merchant_id = isset($_GET['merchant_id']) ? (int)$_GET['merchant_id'] : 0;
        
        // Data Warung
        $q_merchant = mysqli_query($conn, "SELECT * FROM merchants WHERE merchant_id = $merchant_id");
        $merchant = mysqli_fetch_assoc($q_merchant);
        
        if (!$merchant) {
            echo json_encode(["status" => "error", "message" => "Warung tidak ditemukan!"]);
            exit;
        }

        // Data Produk Warung Tersebut
        $q_products = mysqli_query($conn, "SELECT * FROM products WHERE merchant_id = $merchant_id AND is_available = 1");
        $products = [];
        while ($row = mysqli_fetch_assoc($q_products)) {
            $products[] = $row;
        }

        echo json_encode([
            "status" => "success", 
            "merchant" => $merchant, 
            "products" => $products
        ]);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Action tidak valid"]);
        break;
}
?>