<?php
session_start(); 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Koneksi Database
include '../db_connect.php'; 

$action = isset($_GET['action']) ? $_GET['action'] : '';

// FIX SINKRONISASI: Kita tetapkan hardcode user_id = 1 untuk testing. 
// Jadi dari dashboard maupun checkout, sistem membaca keranjang milik orang yang sama.
$buyer_id = 1; 
$buyer_email = 'buyer@hubbite.com';

switch ($action) {
    // 1. DASHBOARD: Tampil Menu
    case 'get_all_products':
        $res = mysqli_query($conn, "SELECT p.*, m.store_name FROM products p JOIN merchants m ON p.merchant_id = m.merchant_id WHERE p.is_available = 1");
        $data = [];
        while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    // 2. GLOBAL: Ambil Isi Keranjang (Dipanggil di Dashboard & Checkout)
    case 'get_cart':
        $query = "SELECT c.*, p.name as product_name, p.price, m.store_name, m.merchant_id 
                  FROM cart_items c 
                  JOIN products p ON c.product_id = p.product_id 
                  JOIN merchants m ON p.merchant_id = m.merchant_id
                  WHERE c.buyer_id = $buyer_id";
        $res = mysqli_query($conn, $query);
        
        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    // 3. DASHBOARD: Tambah ke Keranjang
    case 'add_to_cart':
        $input = json_decode(file_get_contents('php://input'), true);
        $pid = (int)$input['product_id'];

        // Aturan GoFood: 1 Gerai saja
        $target_res = mysqli_query($conn, "SELECT merchant_id FROM products WHERE product_id = $pid");
        $target_m = mysqli_fetch_assoc($target_res)['merchant_id'];

        $check_m = mysqli_query($conn, "SELECT p.merchant_id FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.buyer_id = $buyer_id LIMIT 1");
        $exist_m = mysqli_fetch_assoc($check_m);

        if ($exist_m && $exist_m['merchant_id'] != $target_m) {
            echo json_encode(["status" => "error", "message" => "Hanya bisa pesan dari 1 gerai sekaligus!"]);
            exit;
        }

        $exist_item = mysqli_query($conn, "SELECT cart_id, quantity FROM cart_items WHERE buyer_id = $buyer_id AND product_id = $pid");
        $row = mysqli_fetch_assoc($exist_item);

        if ($row) {
            $new_qty = $row['quantity'] + 1;
            mysqli_query($conn, "UPDATE cart_items SET quantity = $new_qty WHERE cart_id = ".$row['cart_id']);
        } else {
            mysqli_query($conn, "INSERT INTO cart_items (buyer_id, buyer_email, product_id, quantity) VALUES ($buyer_id, '$buyer_email', $pid, 1)");
        }
        echo json_encode(["status" => "success"]);
        break;

    // 4. CHECKOUT: Eksekusi Pesanan
    case 'checkout':
        $input = json_decode(file_get_contents('php://input'), true);
        
        mysqli_begin_transaction($conn);
        try {
            // Ambil ulang keranjang
            $resCart = mysqli_query($conn, "SELECT c.*, p.price, p.merchant_id FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.buyer_id = $buyer_id");
            $subtotal = 0; $items = [];
            while ($r = mysqli_fetch_assoc($resCart)) { 
                $subtotal += ($r['price'] * $r['quantity']); 
                $items[] = $r; 
            }
            
            if (empty($items)) throw new Exception("Keranjang belanja kosong!");

            // Kalkulasi Total
            $ongkir = ($input['shipping_type'] == 'express') ? 5000 : 3000;
            $total_amount = $subtotal + $ongkir;
            $mid = $items[0]['merchant_id'];
            $mode = mysqli_real_escape_string($conn, $input['order_mode']); 
            $pay = mysqli_real_escape_string($conn, $input['payment_method']); 
            $status = ($pay == 'qris' ? 'paid' : 'pending');
            $notes = mysqli_real_escape_string($conn, $input['notes']);

            // Insert ke orders
            $qOrder = "INSERT INTO orders (buyer_id, buyer_email, merchant_id, order_mode, payment_method, status, total_amount) 
                       VALUES ($buyer_id, '$buyer_email', $mid, '$mode', '$pay', '$status', $total_amount)";
            if(!mysqli_query($conn, $qOrder)) throw new Exception("Gagal buat pesanan utama.");
            $oid = mysqli_insert_id($conn);

            // Insert ke order_items
            foreach ($items as $it) {
                mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, notes) VALUES ($oid, ".$it['product_id'].", ".$it['quantity'].", '$notes')");
            }
            
            // Hapus keranjang
            mysqli_query($conn, "DELETE FROM cart_items WHERE buyer_id = $buyer_id");
            
            mysqli_commit($conn);
            echo json_encode(["status" => "success"]);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
}
?>