<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../db_connect.php'; 

$user_id = 1; 
$buyer_email = 'buyer@hubbite.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    mysqli_begin_transaction($conn);
    try {
        // Ambil data keranjang
        $resCart = mysqli_query($conn, "SELECT c.*, p.price, p.merchant_id FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = $user_id");
        $subtotal = 0; 
        $items = [];
        
        while ($r = mysqli_fetch_assoc($resCart)) { 
            $subtotal += ($r['price'] * $r['quantity']); 
            $items[] = $r; 
        }
        
        if (empty($items)) throw new Exception("Keranjang belanja kosong!");

        // Parameter Checkout
        $mid = $items[0]['merchant_id'];
        $order_mode = mysqli_real_escape_string($conn, $input['order_mode']); 
        $payment_method = mysqli_real_escape_string($conn, $input['payment_method']); 
        $is_priority = isset($input['is_priority']) && $input['is_priority'] ? 1 : 0;
        
        // Kalkulasi Total (Biaya Ongkir/Prioritas)
        $ongkir = ($order_mode == 'dine_in_qr') ? 0 : 4000;
        $biaya_prioritas = $is_priority ? 2000 : 0;
        $total_amount = $subtotal + $ongkir + $biaya_prioritas;
        
        $status = ($payment_method == 'qris' ? 'paid' : 'pending');

        // Insert ke tabel orders
        $qOrder = "INSERT INTO orders (buyer_id, buyer_email, merchant_id, order_mode, payment_method, status, total_amount) 
                   VALUES ($user_id, '$buyer_email', $mid, '$order_mode', '$payment_method', '$status', $total_amount)";
                   
        if(!mysqli_query($conn, $qOrder)) throw new Exception("Gagal membuat data pesanan utama.");
        $oid = mysqli_insert_id($conn);

        // Insert ke tabel order_items
        foreach ($items as $it) {
            $pid = $it['product_id'];
            $qty = $it['quantity'];
            $notes = mysqli_real_escape_string($conn, $it['notes'] ?? '');
            mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, notes) VALUES ($oid, $pid, $qty, '$notes')");
        }
        
        // Bersihkan keranjang belanja
        mysqli_query($conn, "DELETE FROM cart_items WHERE user_id = $user_id");
        
        mysqli_commit($conn);
        echo json_encode(["status" => "success", "order_id" => $oid]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>