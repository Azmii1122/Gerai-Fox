<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

error_reporting(0);
include '../db_connect.php'; 
define('API_KEY', '12345');
$headers = getallheaders();
$user_api_key = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : '';

if ($user_api_key !== API_KEY) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "API Key Tidak Valid atau Tidak Menyertakan Kredensial!"]);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    echo json_encode(["status" => "error", "message" => "Akses Ditolak. Sesi habis."]);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$q_merchant = mysqli_query($conn, "SELECT merchant_id FROM merchants WHERE user_id = $user_id");
if (mysqli_num_rows($q_merchant) == 0) {
    echo json_encode(["status" => "error", "message" => "Toko tidak ditemukan."]);
    exit();
}
$merchant_data = mysqli_fetch_assoc($q_merchant);
$merchant_id = (int)$merchant_data['merchant_id'];

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // Mengambil seluruh promo aktif milik warung ini saja
        $query = "SELECT * FROM promos WHERE merchant_id = $merchant_id ORDER BY promo_id DESC";
        $result = mysqli_query($conn, $query);
        
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'POST':
        // Menerbitkan kupon promo baru oleh Seller
        $code = strtoupper(mysqli_real_escape_string($conn, $input['promo_code']));
        $discount = (float)$input['discount_amount'];
        $min_buy = (float)$input['min_purchase'];

        $q_insert = "INSERT INTO promos (merchant_id, code, discount_amount, min_purchase) 
                     VALUES ($merchant_id, '$code', $discount, $min_buy)";

        if (mysqli_query($conn, $q_insert)) {
            echo json_encode(["status" => "success", "message" => "Kupon Diskon baru berhasil diterbitkan!"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;
}
mysqli_close($conn);
?>