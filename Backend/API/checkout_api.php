<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
ob_start(); // Mulai output buffer untuk menyaring error PHP bawaan

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle Preflight Request (CORS) dari AJAX fetch frontend
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../db_connect.php'; 
mysqli_report(MYSQLI_REPORT_OFF); // Mencegah Fatal Error HTML yang dapat merusak JSON
ob_clean(); // Bersihkan sisa output

// Cek sesi login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sesi login tidak terdeteksi. Silakan login terlebih dahulu."]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// =========================================================================
// Ambil email asli dari tabel 'users' untuk mencegah Foreign Key Error 
// saat melakukan insert ke tabel 'orders'
// =========================================================================
$email_query = mysqli_query($conn, "SELECT email FROM users WHERE user_id = $user_id");
if ($email_query && mysqli_num_rows($email_query) > 0) {
    $buyer_email = mysqli_fetch_assoc($email_query)['email'];
} else {
    echo json_encode(["status" => "error", "message" => "Gagal: User ID tidak terdaftar di database utama."]);
    exit;
}
// =========================================================================


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Mulai Transaksi Database (agar jika salah satu insert gagal, semuanya dibatalkan/rollback)
    mysqli_begin_transaction($conn);
    try {
        // 1. Ambil data keranjang berdasarkan buyer_id
        $query_cart = "SELECT c.*, p.price, p.merchant_id 
                       FROM cart_items c 
                       JOIN products p ON c.product_id = p.product_id 
                       WHERE c.buyer_id = $user_id";
        $resCart = mysqli_query($conn, $query_cart);
        
        if (!$resCart) {
            throw new Exception("Gagal membaca keranjang: " . mysqli_error($conn));
        }

        $subtotal = 0; 
        $items = [];
        
        while ($r = mysqli_fetch_assoc($resCart)) { 
            $subtotal += ($r['price'] * $r['quantity']); 
            $items[] = $r; 
        }
        
        if (empty($items)) {
            throw new Exception("Keranjang belanja kosong!");
        }

        // 2. Siapkan Parameter Checkout
        $mid = $items[0]['merchant_id'];
        $order_mode = mysqli_real_escape_string($conn, $input['order_mode'] ?? 'delivery'); 
        $payment_method = mysqli_real_escape_string($conn, $input['payment_method'] ?? 'qris'); 
        $is_priority = isset($input['is_priority']) && $input['is_priority'] ? 1 : 0;
        
        // Kalkulasi Total
        $ongkir = ($order_mode == 'dine_in_qr') ? 0 : 4000;
        $biaya_prioritas = $is_priority ? 2000 : 0;
        $total_amount = $subtotal + $ongkir + $biaya_prioritas;
        
        $status = ($payment_method == 'qris' ? 'paid' : 'pending');

        // 3. Insert ke tabel orders (Gunakan $user_id dan $buyer_email)
        $qOrder = "INSERT INTO orders (buyer_id, buyer_email, merchant_id, order_mode, payment_method, status, total_amount) 
                   VALUES ($user_id, '$buyer_email', $mid, '$order_mode', '$payment_method', '$status', $total_amount)";
                   
        if(!mysqli_query($conn, $qOrder)) {
            throw new Exception("Gagal membuat data pesanan utama: " . mysqli_error($conn));
        }
        $oid = mysqli_insert_id($conn);

        // 4. Insert ke tabel order_items
        foreach ($items as $it) {
            $pid = $it['product_id'];
            $qty = $it['quantity'];
            $notes = mysqli_real_escape_string($conn, $it['notes'] ?? '');
            
            $qItem = "INSERT INTO order_items (order_id, product_id, quantity, notes) 
                      VALUES ($oid, $pid, $qty, '$notes')";
            
            if(!mysqli_query($conn, $qItem)) {
                throw new Exception("Gagal merekam detail item: " . mysqli_error($conn));
            }
        }
        
        // 5. Bersihkan keranjang belanja berdasarkan buyer_id
        if(!mysqli_query($conn, "DELETE FROM cart_items WHERE buyer_id = $user_id")) {
            throw new Exception("Gagal membersihkan keranjang: " . mysqli_error($conn));
        }
        
        // Setujui semua perubahan ke database
        mysqli_commit($conn);
        echo json_encode(["status" => "success", "order_id" => $oid]);

    } catch (Exception $e) {
        // Batalkan semua insert/delete jika terjadi satu saja error di tengah jalan
        mysqli_rollback($conn);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>