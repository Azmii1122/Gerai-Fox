<?php
// Set header agar mengembalikan response dalam format JSON
header("Content-Type: application/json");

// Memulai session untuk menyimpan data keranjang secara sementara
session_start();

// Inisialisasi keranjang jika belum ada di session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Mendapatkan method HTTP yang digunakan (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // METHOD POST: Digunakan untuk menambahkan barang baru ke keranjang
        
        // Membaca data JSON yang dikirim dari JavaScript (fetch API)
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE);

        if (isset($input['product_id']) && isset($input['quantity'])) {
            $product_id = $input['product_id'];
            $quantity = (int)$input['quantity'];

            // Jika produk sudah ada di keranjang, tambahkan jumlahnya
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                // Jika belum, buat entry baru
                $_SESSION['cart'][$product_id] = $quantity;
            }

            // Menghitung total barang di keranjang
            $total_items = array_sum($_SESSION['cart']);

            echo json_encode([
                "status" => "success",
                "message" => "Produk berhasil ditambahkan",
                "total_items" => $total_items,
                "cart" => $_SESSION['cart']
            ]);
        } else {
            http_response_code(400); // Bad Request
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
        }
        break;

    case 'GET':
        // METHOD GET: Digunakan untuk mengambil daftar isi keranjang saat ini
        
        $total_items = array_sum($_SESSION['cart']);
        
        echo json_encode([
            "status" => "success",
            "total_items" => $total_items,
            "cart" => $_SESSION['cart']
        ]);
        break;

    default:
        // Method selain POST dan GET akan ditolak untuk endpoint ini
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "message" => "Method tidak diizinkan"]);
        break;
}
?>