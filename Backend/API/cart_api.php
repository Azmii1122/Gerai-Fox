<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
ob_start(); 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../db_connect.php'; 
mysqli_report(MYSQLI_REPORT_OFF); 
ob_clean(); // Bersihkan sisa output agar JSON tidak rusak

// 1. Cek sesi login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sesi login tidak terdeteksi. Silakan login terlebih dahulu."]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// =========================================================================
// FIX FOREIGN KEY: KITA AMBIL EMAIL ASLI LANGSUNG DARI TABEL 'users'
// =========================================================================
$email_query = mysqli_query($conn, "SELECT email FROM users WHERE user_id = $user_id");
if ($email_query && mysqli_num_rows($email_query) > 0) {
    $user_email = mysqli_fetch_assoc($email_query)['email'];
} else {
    // Jika ID di sesi tidak ditemukan di tabel users
    echo json_encode(["status" => "error", "message" => "Gagal: User ID tidak terdaftar di database utama."]);
    exit;
}
// =========================================================================

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // AMBIL ISI KERANJANG
    $query = "SELECT c.quantity, p.product_id, p.name as product_name, p.price, m.store_name 
              FROM cart_items c 
              JOIN products p ON c.product_id = p.product_id 
              JOIN merchants m ON p.merchant_id = m.merchant_id
              WHERE c.buyer_id = $user_id"; 
              
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode(["status" => "error", "message" => "SQL Error: " . mysqli_error($conn)]);
        exit;
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : 'add';
    $p_id = (int)($input['product_id'] ?? 0);

    if ($p_id === 0) {
        echo json_encode(["status" => "error", "message" => "Product ID hilang"]);
        exit;
    }

    if ($action === 'add') {
        // ATURAN GOFOOD: Hanya bisa pesan dari 1 gerai sekaligus
        $target_res = mysqli_query($conn, "SELECT merchant_id FROM products WHERE product_id = $p_id");
        if ($target_res && mysqli_num_rows($target_res) > 0) {
            $target_m = mysqli_fetch_assoc($target_res)['merchant_id'];
            
            // Cek isi keranjang saat ini
            $check_m = mysqli_query($conn, "SELECT p.merchant_id FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.buyer_id = $user_id LIMIT 1");
            $exist_m = mysqli_fetch_assoc($check_m);

            if ($exist_m && $exist_m['merchant_id'] != $target_m) {
                echo json_encode(["status" => "error", "message" => "Hanya bisa pesan dari 1 gerai sekaligus! Kosongkan keranjang sebelumnya."]);
                exit;
            }
        }

        // Cek apakah barang sudah ada di keranjang
        $cek = mysqli_query($conn, "SELECT quantity FROM cart_items WHERE buyer_id = $user_id AND product_id = $p_id");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $update = mysqli_query($conn, "UPDATE cart_items SET quantity = quantity + 1 WHERE buyer_id = $user_id AND product_id = $p_id");
            if (!$update) {
                echo json_encode(["status" => "error", "message" => "Update Error: " . mysqli_error($conn)]);
                exit;
            }
        } else {
            // INSERT SEKARANG MENGGUNAKAN $user_email YANG DIJAMIN 100% SAMA DENGAN TABEL USERS
            $insert = mysqli_query($conn, "INSERT INTO cart_items (buyer_id, buyer_email, product_id, quantity) VALUES ($user_id, '$user_email', $p_id, 1)");
            if (!$insert) {
                echo json_encode(["status" => "error", "message" => "Insert Error: " . mysqli_error($conn)]);
                exit;
            }
        }
        echo json_encode(["status" => "success"]);

    } elseif ($action === 'update_qty') {
        $change = (int)$input['change']; // +1 atau -1
        $cek = mysqli_query($conn, "SELECT quantity FROM cart_items WHERE buyer_id = $user_id AND product_id = $p_id");
        $row = mysqli_fetch_assoc($cek);
        
        if ($row) {
            $new_qty = $row['quantity'] + $change;
            if ($new_qty <= 0) {
                mysqli_query($conn, "DELETE FROM cart_items WHERE buyer_id = $user_id AND product_id = $p_id");
            } else {
                mysqli_query($conn, "UPDATE cart_items SET quantity = $new_qty WHERE buyer_id = $user_id AND product_id = $p_id");
            }
        }
        echo json_encode(["status" => "success"]);
    }

} elseif ($method === 'DELETE') {
    // KOSONGKAN KERANJANG
    mysqli_query($conn, "DELETE FROM cart_items WHERE buyer_id = $user_id");
    echo json_encode(["status" => "success"]);
}
?>