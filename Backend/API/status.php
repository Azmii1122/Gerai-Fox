<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Matikan pesan error bawaan PHP yang formatnya HTML biar nggak ngerusak JSON Javascript
error_reporting(0);

$conn = mysqli_connect("localhost", "root", "", "gerai_fox");

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Koneksi database gagal"]);
    exit();
}

// Targetin warung lu (merchant_id = 1) sesuai data dari temen lu
$merchant_id = 1; 

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        $query = "SELECT is_open FROM merchants WHERE merchant_id = $merchant_id";
        $result = mysqli_query($conn, $query);
        
        // Deteksi error kalau tabel bermasalah
        if (!$result) {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
            exit();
        }

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            // Terjemahin angka 1 (Buka) dan 0 (Tutup) dari tabel merchants
            $status_teks = ($row['is_open'] == 1) ? 'Buka' : 'Tutup';
            echo json_encode(["status" => "success", "status_warung" => $status_teks]);
        } else {
            echo json_encode(["status" => "success", "status_warung" => "Buka"]);
        }
        break;

    case 'PUT':
        if (!isset($input['status_warung'])) {
            echo json_encode(["status" => "error", "message" => "Status tidak valid"]);
            exit();
        }

        $status_baru = $input['status_warung'];
        // Terjemahin teks balik ke angka buat disimpen ke tabel merchants
        $is_open = ($status_baru === 'Buka') ? 1 : 0;
        
        $query = "UPDATE merchants SET is_open = $is_open WHERE merchant_id = $merchant_id";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success", "message" => "Status warung diupdate jadi " . $status_baru]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;

    case 'OPTIONS':
        http_response_code(200);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method tidak diizinkan"]);
        break;
}

mysqli_close($conn);
?>