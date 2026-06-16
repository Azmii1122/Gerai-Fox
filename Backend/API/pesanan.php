<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(0); // Matikan pesan error bawaan PHP
include '../../db_connect.php'; // Sesuaikan path ini dengan lokasimu

// =========================================================================
// 1. KEAMANAN: Ambil merchant_id dari akun penjual yang sedang Login
// =========================================================================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    echo json_encode(["status" => "error", "message" => "Akses Ditolak."]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$q_merchant = mysqli_query($conn, "SELECT merchant_id FROM merchants WHERE user_id = $user_id");
if (mysqli_num_rows($q_merchant) == 0) {
    echo json_encode(["status" => "error", "message" => "Toko tidak ditemukan."]);
    exit;
}
$merchant_data = mysqli_fetch_assoc($q_merchant);
$merchant_id = (int)$merchant_data['merchant_id'];
// =========================================================================

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // SEDERHANAKAN QUERY: Gabungkan nama produk dan jumlah porsi dari tabel order_items
        $query = "
            SELECT 
                o.order_id as id, 
                COALESCE(u.username, o.buyer_email) as customer_name, 
                (SELECT GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') 
                 FROM order_items oi 
                 LEFT JOIN products p ON oi.product_id = p.product_id 
                 WHERE oi.order_id = o.order_id) as menu_name, 
                DATE(o.created_at) as order_date, 
                o.status as db_status, 
                o.total_amount as total_price 
            FROM orders o 
            LEFT JOIN users u ON o.buyer_id = u.user_id 
            WHERE o.merchant_id = $merchant_id 
            ORDER BY o.order_id DESC
        ";

        $result = mysqli_query($conn, $query);
        $data = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Aturan UI Status bawaanmu
                $ui_status = 'Processed';
                if ($row['db_status'] == 'completed') $ui_status = 'Delivered';
                if ($row['db_status'] == 'cancel') $ui_status = 'Cancelled';

                $row['status'] = $ui_status;
                $data[] = $row;
            }
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'POST':
        // UNTUK PESANAN MANUAL OLEH KASIR (Seller)
        $customer = mysqli_real_escape_string($conn, $input['customer_name']);
        $menu = mysqli_real_escape_string($conn, $input['menu_name']);
        $harga = (float)$input['total_price'];

        $q_order = "INSERT INTO orders (merchant_id, buyer_email, order_mode, payment_method, status, total_amount) 
                    VALUES ($merchant_id, '$customer', 'takeaway', 'cash', 'accepted', $harga)";

        if (mysqli_query($conn, $q_order)) {
            $new_order_id = mysqli_insert_id($conn);

            // Karena product_id tidak boleh kosong di database, kita set 0 untuk pesanan custom manual
            // Dan teks nama menu yang diketik kasir kita masukkan ke kolom 'notes'
            $q_item = "INSERT INTO order_items (order_id, product_id, quantity, notes) 
                       VALUES ($new_order_id, 0, 1, 'Pesanan Manual: $menu')";
            mysqli_query($conn, $q_item);

            echo json_encode(["status" => "success", "message" => "Pesanan manual berhasil ditambahkan!"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;

    case 'PUT':
        // UPDATE STATUS PESANAN
        $id = (int)$input['id'];
        $db_status = mysqli_real_escape_string($conn, $input['status']);

        // Keamanan ekstra: Pastikan yang diupdate hanya pesanan milik warung ini saja
        $query = "UPDATE orders SET status = '$db_status' WHERE order_id = $id AND merchant_id = $merchant_id";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;
}
mysqli_close($conn);
?>