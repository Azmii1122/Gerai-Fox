<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

// Tampilkan error database ke layar JSON jika ada salah ketik nama kolom
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

// 2. Cek Sesi Login Pembeli
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesi login tidak terdeteksi. Silakan login ulang."]); 
    exit;
}

// 3. Tarik Data Murni dari Database
$query = "SELECT * FROM merchants ORDER BY merchant_id ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error MySQL: " . mysqli_error($conn)]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(["status" => "success", "data" => $data]);
mysqli_close($conn);
?>