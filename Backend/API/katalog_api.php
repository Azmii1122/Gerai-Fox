<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

include '../db_connect.php'; 

if ((getallheaders()['X-API-KEY'] ?? '') !== '12345') {
    echo json_encode(["status" => "error", "message" => "API Key Invalid"]); exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]); exit;
}

$merchant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q_merchant = mysqli_query($conn, "SELECT * FROM merchants WHERE merchant_id = $merchant_id");
$merchant = mysqli_fetch_assoc($q_merchant);

if (!$merchant) {
    echo json_encode(["status" => "error", "message" => "Warung tidak ditemukan!"]); exit;
}

$q_products = mysqli_query($conn, "SELECT product_id, name, price, description, image FROM products WHERE merchant_id = $merchant_id");
$products = [];
while ($row = mysqli_fetch_assoc($q_products)) {
    $products[] = $row;
}

echo json_encode(["status" => "success", "merchant" => $merchant, "products" => $products]);
mysqli_close($conn);
?>