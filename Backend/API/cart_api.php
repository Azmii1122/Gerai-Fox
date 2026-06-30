<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, DELETE");

include '../db_connect.php'; 

// Hardcode user untuk keperluan testing (sesuai arahan file pembelian.php)
$user_id = 1; 
$user_email = 'buyer@hubbite.com';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // AMBIL ISI KERANJANG BESERTA DETAIL PRODUKNYA
    $query = "SELECT c.cart_id, c.quantity, c.notes, p.product_id, p.name as product_name, p.price, p.image, m.merchant_id, m.store_name 
              FROM cart_items c 
              JOIN products p ON c.product_id = p.product_id 
              JOIN merchants m ON p.merchant_id = m.merchant_id
              WHERE c.user_id = $user_id"; // Ubah c.user_id atau c.buyer_id sesuai nama kolom di database temanmu
              
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);

} elseif ($method === 'POST') {
    // TAMBAH / UPDATE KERANJANG
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Cek apakah ini aksi UPDATE QTY atau ADD TO CART
    $action = isset($input['action']) ? $input['action'] : 'add';
    $p_id = (int)($input['product_id'] ?? 0);

    if ($p_id === 0) {
        echo json_encode(["status" => "error", "message" => "Product ID hilang"]);
        exit;
    }

    if ($action === 'add') {
        // ATURAN GOFOOD: Hanya bisa 1 gerai
        $target_res = mysqli_query($conn, "SELECT merchant_id FROM products WHERE product_id = $p_id");
        $target_m = mysqli_fetch_assoc($target_res)['merchant_id'];

        // Cek isi keranjang saat ini
        $check_m = mysqli_query($conn, "SELECT p.merchant_id FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = $user_id LIMIT 1");
        $exist_m = mysqli_fetch_assoc($check_m);

        if ($exist_m && $exist_m['merchant_id'] != $target_m) {
            echo json_encode(["status" => "error", "message" => "Hanya bisa pesan dari 1 gerai sekaligus! Kosongkan keranjang sebelumnya."]);
            exit;
        }

        // Cek apakah barang sudah ada di keranjang
        $cek = mysqli_query($conn, "SELECT quantity FROM cart_items WHERE user_id = $user_id AND product_id = $p_id");
        if ($cek && mysqli_num_rows($cek) > 0) {
            mysqli_query($conn, "UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $p_id");
        } else {
            mysqli_query($conn, "INSERT INTO cart_items (user_id, product_id, quantity) VALUES ($user_id, $p_id, 1)");
        }
        echo json_encode(["status" => "success"]);

    } elseif ($action === 'update_qty') {
        $change = (int)$input['change']; // +1 atau -1
        $cek = mysqli_query($conn, "SELECT quantity FROM cart_items WHERE user_id = $user_id AND product_id = $p_id");
        $row = mysqli_fetch_assoc($cek);
        
        if ($row) {
            $new_qty = $row['quantity'] + $change;
            if ($new_qty <= 0) {
                mysqli_query($conn, "DELETE FROM cart_items WHERE user_id = $user_id AND product_id = $p_id");
            } else {
                mysqli_query($conn, "UPDATE cart_items SET quantity = $new_qty WHERE user_id = $user_id AND product_id = $p_id");
            }
        }
        echo json_encode(["status" => "success"]);
    }

} elseif ($method === 'DELETE') {
    // KOSONGKAN KERANJANG
    mysqli_query($conn, "DELETE FROM cart_items WHERE user_id = $user_id");
    echo json_encode(["status" => "success"]);
}
?>