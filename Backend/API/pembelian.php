<?php
// 1. Inisialisasi Session untuk keamanan identitas Buyer
session_start(); 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 2. Import Koneksi Database milik teman Anda
include '../db_connect.php'; 

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Middleware Keamanan: Fungsi untuk memastikan Buyer sudah login
function check_auth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
        echo json_encode(["status" => "error", "message" => "Sesi berakhir. Silakan login kembali."]);
        exit;
    }
}

switch ($action) {
    // ==========================================
    // READ: DASHBOARD & KATALOG
    // ==========================================

    case 'get_merchants':
        $query = "SELECT * FROM merchants WHERE is_open = 1";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'get_merchant_detail':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $query = "SELECT * FROM merchants WHERE merchant_id = $id";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'get_all_products':
        $query = "SELECT p.*, m.store_name FROM products p 
                  JOIN merchants m ON p.merchant_id = m.merchant_id 
                  WHERE p.is_available = 1";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'get_products_by_merchant':
        $m_id = isset($_GET['merchant_id']) ? (int)$_GET['merchant_id'] : 0;
        $query = "SELECT * FROM products WHERE merchant_id = $m_id AND is_available = 1";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    // ==========================================
    // CART SYSTEM (Create, Read, Update, Delete)
    // ==========================================

    case 'get_cart':
        check_auth();
        $buyer_id = (int)$_SESSION['user_id'];
        $query = "SELECT c.*, p.name as product_name, p.price, m.store_name 
                  FROM cart_items c 
                  JOIN products p ON c.product_id = p.product_id 
                  JOIN merchants m ON p.merchant_id = m.merchant_id
                  WHERE c.buyer_id = $buyer_id";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'add_to_cart':
        check_auth();
        $buyer_id = (int)$_SESSION['user_id'];
        $buyer_email = mysqli_real_escape_string($conn, $_SESSION['email']);
        $input = json_decode(file_get_contents('php://input'), true);
        $product_id = (int)$input['product_id'];

        // Cek Merchant produk ini
        $resProd = mysqli_query($conn, "SELECT merchant_id FROM products WHERE product_id = $product_id");
        $target_merchant = mysqli_fetch_assoc($resProd)['merchant_id'];

        // Validasi Aturan 1 Gerai (GoFood Style)
        $resCheck = mysqli_query($conn, "SELECT p.merchant_id FROM cart_items c 
                                         JOIN products p ON c.product_id = p.product_id 
                                         WHERE c.buyer_id = $buyer_id LIMIT 1");
        $existing = mysqli_fetch_assoc($resCheck);

        if ($existing && $existing['merchant_id'] != $target_merchant) {
            echo json_encode(["status" => "error", "message" => "Kamu hanya bisa memesan dari 1 gerai sekaligus."]);
            exit;
        }

        // Cek jika barang sudah ada, tambah qty saja
        $resItem = mysqli_query($conn, "SELECT cart_id, quantity FROM cart_items WHERE buyer_id = $buyer_id AND product_id = $product_id");
        $rowItem = mysqli_fetch_assoc($resItem);

        if ($rowItem) {
            $new_qty = $rowItem['quantity'] + 1;
            $cart_id = $rowItem['cart_id'];
            mysqli_query($conn, "UPDATE cart_items SET quantity = $new_qty WHERE cart_id = $cart_id");
        } else {
            mysqli_query($conn, "INSERT INTO cart_items (buyer_id, buyer_email, product_id, quantity) 
                                 VALUES ($buyer_id, '$buyer_email', $product_id, 1)");
        }
        echo json_encode(["status" => "success", "message" => "Berhasil masuk keranjang"]);
        break;

    case 'update_cart':
        check_auth();
        $input = json_decode(file_get_contents('php://input'), true);
        $cart_id = (int)$input['cart_id'];
        $qty = (int)$input['quantity'];

        if ($qty <= 0) {
            mysqli_query($conn, "DELETE FROM cart_items WHERE cart_id = $cart_id");
        } else {
            mysqli_query($conn, "UPDATE cart_items SET quantity = $qty WHERE cart_id = $cart_id");
        }
        echo json_encode(["status" => "success"]);
        break;

    // ==========================================
    // TRANSAKSI CHECKOUT (Atomic Transaction)
    // ==========================================

    case 'checkout':
        check_auth();
        $buyer_id = (int)$_SESSION['user_id'];
        $buyer_email = mysqli_real_escape_string($conn, $_SESSION['email']);
        $input = json_decode(file_get_contents('php://input'), true);
        
        $order_mode = mysqli_real_escape_string($conn, $input['order_mode']);
        $payment_method = mysqli_real_escape_string($conn, $input['payment_method']);
        $shipping_type = mysqli_real_escape_string($conn, $input['shipping_type']);
        $notes = mysqli_real_escape_string($conn, $input['notes']);

        // Mulai Transaksi
        mysqli_begin_transaction($conn);

        try {
            // 1. Ambil data keranjang untuk hitung total real (server-side calculation)
            $resCart = mysqli_query($conn, "SELECT c.*, p.price, p.merchant_id FROM cart_items c 
                                            JOIN products p ON c.product_id = p.product_id 
                                            WHERE c.buyer_id = $buyer_id");
            
            $subtotal = 0;
            $items = [];
            while ($row = mysqli_fetch_assoc($resCart)) {
                $subtotal += ($row['price'] * $row['quantity']);
                $items[] = $row;
            }

            if (empty($items)) throw new Exception("Keranjang kosong.");

            $merchant_id = $items[0]['merchant_id'];
            
            // Logika Biaya Ongkir
            $ongkir = ($order_mode === 'delivery') ? ($shipping_type === 'express' ? 5000 : 2000) : 0;
            $total_amount = $subtotal + $ongkir;

            // 2. Masukkan ke tabel orders
            // Status diset 'paid' jika QRIS, 'pending' jika Tunai
            $status = ($payment_method === 'qris') ? 'paid' : 'pending';
            
            $qOrder = "INSERT INTO orders (buyer_id, buyer_email, merchant_id, order_mode, payment_method, status, total_amount) 
                       VALUES ($buyer_id, '$buyer_email', $merchant_id, '$order_mode', '$payment_method', '$status', $total_amount)";
            
            if (!mysqli_query($conn, $qOrder)) throw new Exception("Gagal simpan order.");
            $order_id = mysqli_insert_id($conn);

            // 3. Masukkan ke tabel order_items
            foreach ($items as $it) {
                $p_id = $it['product_id'];
                $qty = $it['quantity'];
                mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, notes) 
                                     VALUES ($order_id, $p_id, $qty, '$notes')");
            }

            // 4. Kosongkan keranjang
            mysqli_query($conn, "DELETE FROM cart_items WHERE buyer_id = $buyer_id");

            mysqli_commit($conn);
            echo json_encode(["status" => "success", "message" => "Pesanan #$order_id berhasil!", "order_id" => $order_id]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Action tidak valid"]);
        break;
}
?>