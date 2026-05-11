<?php
// Izinkan akses API dan format output sebagai JSON
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

$host = "localhost";
$user = "root"; 
$pass = "";     
$db   = "db_checkout";

// Membuat koneksi menggunakan mysqli
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi Database Gagal: " . $conn->connect_error]));
}

// Mengambil data produk langsung dari Database MySQL
$db_products = [];
$query_products = "SELECT * FROM products";
$result = $conn->query($query_products);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Menyimpan data dari tabel ke array agar formatnya sama persis 
        // dengan sistem keranjang kita sebelumnya
        $db_products[$row['id']] = [
            "name" => $row['name'],
            "price" => $row['price'],
            "img" => $row['image_url'] // pastikan nama kolom ini sama dengan di databasemu
        ];
    }
}

// Inisialisasi data keranjang bawaan jika session kosong (Simulasi)
if (!isset($_SESSION['checkout_cart'])) {
    $_SESSION['checkout_cart'] = [
        1 => 2, // ID 1, qty 2
        2 => 1  // ID 2, qty 1
    ];
}

// Mendapatkan method HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Membaca Input JSON
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

// Routing berdasarkan Method HTTP
switch ($method) {
    
    // ==========================================
    // 1. GET: Mengambil Data Keranjang Checkout
    // ==========================================
    case 'GET':
        $cart_items = [];
        $subtotal = 0;

        foreach ($_SESSION['checkout_cart'] as $id => $qty) {
            if(isset($db_products[$id])) {
                $item = $db_products[$id];
                $item['id'] = $id;
                $item['qty'] = $qty;
                $cart_items[] = $item;
                $subtotal += ($item['price'] * $qty);
            }
        }

        echo json_encode([
            "status" => "success",
            "cart" => $cart_items,
            "subtotal" => $subtotal
        ]);
        break;

    // ==========================================
    // 2. PUT: Mengubah Kuantitas Barang
    // ==========================================
    case 'PUT':
        if (isset($input['id']) && isset($input['qty'])) {
            $id = $input['id'];
            $qty = (int)$input['qty'];

            if ($qty > 0 && isset($_SESSION['checkout_cart'][$id])) {
                $_SESSION['checkout_cart'][$id] = $qty;
                echo json_encode(["status" => "success", "message" => "Kuantitas diperbarui"]);
            } else {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Data tidak valid"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Parameter kurang lengkap"]);
        }
        break;

    // ==========================================
    // 3. DELETE: Menghapus Barang
    // ==========================================
    case 'DELETE':
        if (isset($input['id'])) {
            $id = $input['id'];
            if (isset($_SESSION['checkout_cart'][$id])) {
                unset($_SESSION['checkout_cart'][$id]);
                echo json_encode(["status" => "success", "message" => "Barang dihapus"]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Barang tidak ditemukan"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID tidak diberikan"]);
        }
        break;

    // ==========================================
    // 4. POST: Memproses Checkout Akhir
    // ==========================================
    case 'POST':
        if (isset($input['customer'])) {
            $customer = $input['customer'];
            
            // Logika menyimpan pesanan ke Database bisa diletakkan di sini
            // ...
            
            // Setelah pesanan berhasil dicatat, kosongkan keranjang
            $_SESSION['checkout_cart'] = [];
            
            // Buat ID Pesanan dummy
            $order_id = "FOX-" . rand(10000, 99999);

            echo json_encode([
                "status" => "success", 
                "message" => "Pesanan berhasil diproses",
                "order_id" => $order_id
            ]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data pelanggan tidak lengkap"]);
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "message" => "Method tidak didukung"]);
        break;
}
?>