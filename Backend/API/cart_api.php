<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
ob_start(); // Mulai output buffer untuk menyaring error

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle Preflight Request dari Browser (Mencegah Error Failed to Fetch pada fungsi Add to Cart)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../db_connect.php'; 
mysqli_report(MYSQLI_REPORT_OFF); // Mencegah PHP 8 melempar Fatal Error (HTML) ke frontend
ob_clean(); // Bersihkan seluruh sisa output sebelum mencetak JSON

// Gunakan session asli (dinamis) dari login teman kamu
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sesi login tidak terdeteksi. Silakan login terlebih dahulu."]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'buyer@hubbite.com'; // Tambahkan variabel email
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // AMBIL ISI KERANJANG
    // Menggunakan c.buyer_id sesuai dengan struktur tabel database
    $query = "SELECT c.quantity, p.product_id, p.name as product_name, p.price, m.store_name 
              FROM cart_items c 
              JOIN products p ON c.product_id = p.product_id 
              JOIN merchants m ON p.merchant_id = m.merchant_id
              WHERE c.buyer_id = $user_id"; 
              
    $result = mysqli_query($conn, $query);
    
    // Menangkap error SQL agar tampil rapi sebagai JSON
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
            
            // Cek isi keranjang saat ini menggunakan buyer_id
            $check_m = mysqli_query($conn, "SELECT p.merchant_id FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.buyer_id = $user_id LIMIT 1");
            $exist_m = mysqli_fetch_assoc($check_m);

            if ($exist_m && $exist_m['merchant_id'] != $target_m) {
                echo json_encode(["status" => "error", "message" => "Hanya bisa pesan dari 1 gerai sekaligus! Kosongkan keranjang sebelumnya."]);
                exit;
            }
        }

        // Cek apakah barang sudah ada di keranjang menggunakan buyer_id
        $cek = mysqli_query($conn, "SELECT quantity FROM cart_items WHERE buyer_id = $user_id AND product_id = $p_id");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $update = mysqli_query($conn, "UPDATE cart_items SET quantity = quantity + 1 WHERE buyer_id = $user_id AND product_id = $p_id");
            if (!$update) {
                echo json_encode(["status" => "error", "message" => "Update Error: " . mysqli_error($conn)]);
                exit;
            }
        } else {
            // Insert menggunakan kolom buyer_id DAN buyer_email (Sesuai dengan struktur database kamu yang menolak NULL)
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
    // KOSONGKAN KERANJANG menggunakan buyer_id
    mysqli_query($conn, "DELETE FROM cart_items WHERE buyer_id = $user_id");
    echo json_encode(["status" => "success"]);
}
?>