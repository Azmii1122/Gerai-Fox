<?php
session_start();
header("Content-Type: application/json");

// 1. Tangkap API Key dengan cara yang lebih aman di semua server
$api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';

if ($api_key !== '12345') {
    echo json_encode(["status" => "error", "message" => "API Key Tidak Valid"]); 
    exit;
}

// 2. Cek Sesi
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sesi login habis."]); 
    exit;
}

include '../db_connect.php'; 
$user_id = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ========================================================
// LOGIKA GET: LOAD KERANJANG
// ========================================================
if ($method === 'GET') {
    // PENTING: Cek nama kolom di sini. Apakah benar 'user_id' dan 'cart_id'? 
    // Kalau di databasemu namanya 'buyer_id', ganti 'user_id' jadi 'buyer_id'.
    $query = "SELECT c.*, p.name, p.price 
              FROM cart_items c 
              JOIN products p ON c.product_id = p.product_id 
              WHERE c.user_id = $user_id";
              
    $result = mysqli_query($conn, $query);
    
    // PENANGKAP ERROR SQL: Kalau ada kolom yg salah ketik, tampilkan pesan ini
    if (!$result) {
        echo json_encode(["status" => "error", "message" => "Error SQL Tarik Keranjang: " . mysqli_error($conn)]); 
        exit;
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);

// ========================================================
// LOGIKA POST: PUSH KE KERANJANG
// ========================================================
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
    $merchant_id = isset($input['merchant_id']) ? (int)$input['merchant_id'] : 0;

    // Cek apakah barang sudah ada
    $query_cek = "SELECT * FROM cart_items WHERE user_id = $user_id AND product_id = $product_id";
    $cek = mysqli_query($conn, $query_cek);
    
    if (!$cek) {
        echo json_encode(["status" => "error", "message" => "Error SQL Cek Barang: " . mysqli_error($conn)]); 
        exit;
    }

    if (mysqli_num_rows($cek) > 0) {
        // Jika sudah ada, UPDATE kuantitasnya
        $row = mysqli_fetch_assoc($cek);
        $new_qty = $row['quantity'] + 1;
        
        // Pastikan Primary Key tabel cart_items kamu bernama 'cart_id' (atau 'id')
        $cart_id = $row['cart_id']; 
        $update = mysqli_query($conn, "UPDATE cart_items SET quantity = $new_qty WHERE cart_id = $cart_id");
        
        if (!$update) {
            echo json_encode(["status" => "error", "message" => "Error SQL Update Kuantitas: " . mysqli_error($conn)]); 
            exit;
        }
    } else {
        // Jika barang baru, INSERT ke tabel
        $query_insert = "INSERT INTO cart_items (user_id, merchant_id, product_id, quantity) 
                         VALUES ($user_id, $merchant_id, $product_id, 1)";
        $insert = mysqli_query($conn, $query_insert);
        
        if (!$insert) {
            echo json_encode(["status" => "error", "message" => "Error SQL Insert Barang Baru: " . mysqli_error($conn)]); 
            exit;
        }
    }
    
    echo json_encode(["status" => "success", "message" => "Berhasil"]);
}

mysqli_close($conn);
?>