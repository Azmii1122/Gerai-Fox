<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

// Ganti "menus" dengan nama database utama Anda jika sudah berubah
$conn = mysqli_connect("localhost", "root", "", "menus");

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_connect_error()]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER['REQUEST_METHOD'];

// Simulasi user yang sedang login (ID = 1)
$user_id = 1;

// --- 1. METHOD GET ---
if ($method === 'GET') {
    // JOIN tabel users dan merchants untuk mengambil email sekaligus data warung
    $query = "SELECT m.*, u.email FROM users u LEFT JOIN merchants m ON u.user_id = m.user_id WHERE u.user_id = $user_id";
    $res = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        if ($data['merchant_id']) {
            echo json_encode(["status" => "success", "data" => $data]);
        } else {
            // User ada, tapi belum membuat profil warung
            echo json_encode(["status" => "empty", "message" => "Profil belum diisi", "email" => $data['email']]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User tidak ditemukan."]);
    }

// --- 2. METHOD POST (Buat Baru) ---    
// --- 2. METHOD POST (Buat Baru) ---    
} elseif ($method === 'POST') {
    $cek = mysqli_query($conn, "SELECT merchant_id FROM merchants WHERE user_id=$user_id");
    if(mysqli_num_rows($cek) > 0) {
        echo json_encode(["status" => "error", "message" => "Data sudah ada, gunakan metode PUT."]);
        exit();
    }

    $res_user = mysqli_query($conn, "SELECT email FROM users WHERE user_id=$user_id");
    $user_email = mysqli_fetch_assoc($res_user)['email'];

    $store_name = mysqli_real_escape_string($conn, $input['store_name'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
    $foto_warung = mysqli_real_escape_string($conn, $input['foto_warung'] ?? '');

    // allow_delivery dan allow_priority dikunci paksa ke nilai 0 (Menunggu Admin)
    $sql = "INSERT INTO merchants (user_id, user_email, store_name, is_open, live_crowd_status, allow_delivery, allow_priority, wallet_balance, deskripsi, foto_warung) 
            VALUES ($user_id, '$user_email', '$store_name', 0, 'hijau', 0, 0, 0.00, '$deskripsi', '$foto_warung')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Profil warung berhasil dibuat."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }

// --- 3. METHOD PUT (Edit Data) ---
} elseif ($method === 'PUT') {
    $store_name = mysqli_real_escape_string($conn, $input['store_name'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
    $foto_warung = mysqli_real_escape_string($conn, $input['foto_warung'] ?? '');

    // Query UPDATE tidak lagi mengizinkan perubahan status delivery & priority
    $sql = "UPDATE merchants SET 
            store_name='$store_name', 
            deskripsi='$deskripsi',
            foto_warung='$foto_warung'
            WHERE user_id=$user_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Profil berhasil diperbarui."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }
}

mysqli_close($conn);
exit();
?>