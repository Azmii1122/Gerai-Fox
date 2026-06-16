<?php
session_start();

// Cek apakah yang akses benar-benar seller
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'seller') {
    echo json_encode(["status" => "error", "message" => "Akses Ditolak. Harap login."]);
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Sesuaikan path db_connect.php jika posisinya berbeda
include '../../db_connect.php'; 

// 1. Dapatkan merchant_id dari tabel merchants berdasarkan user_id session
$user_id = (int)$_SESSION['user_id'];
$q_merchant = mysqli_query($conn, "SELECT merchant_id FROM merchants WHERE user_id = $user_id");

if (mysqli_num_rows($q_merchant) == 0) {
    echo json_encode(["status" => "error", "message" => "Toko tidak ditemukan untuk user ini."]);
    exit;
}
$merchant_data = mysqli_fetch_assoc($q_merchant);
$merchant_id = (int)$merchant_data['merchant_id'];

// 2. Tangkap Request
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// =====================================
// PROSES API
// =====================================
if ($method === 'GET') {
    // Tampilkan HANYA produk milik merchant yang sedang login
    $res = mysqli_query($conn, "SELECT * FROM products WHERE merchant_id = $merchant_id ORDER BY product_id DESC");
    $data = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) { $data[] = $row; }
    }
    echo json_encode(["status" => "success", "data" => $data]);

} elseif ($method === 'POST') {
    // Insert Menu Baru
    $nama = mysqli_real_escape_string($conn, $input['nama'] ?? '');
    $harga = (int)($input['harga'] ?? 0);
    $kategori = mysqli_real_escape_string($conn, $input['kategori'] ?? 'Lainnya');
    $deskripsi = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
    $gambar = mysqli_real_escape_string($conn, $input['gambar'] ?? '');
    $is_available = 1;

    $sql = "INSERT INTO products (merchant_id, category, name, price, description, image, is_available) 
            VALUES ($merchant_id, '$kategori', '$nama', $harga, '$deskripsi', '$gambar', $is_available)";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Menu berhasil ditambahkan!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan: " . mysqli_error($conn)]);
    }

} elseif ($method === 'PUT') {
    // Update Menu
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID tidak valid."]);
        exit;
    }

    $nama = mysqli_real_escape_string($conn, $input['nama'] ?? '');
    $harga = (int)($input['harga'] ?? 0);
    $kategori = mysqli_real_escape_string($conn, $input['kategori'] ?? 'Lainnya');
    $deskripsi = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
    $gambar = mysqli_real_escape_string($conn, $input['gambar'] ?? '');

    $sql = "UPDATE products SET name='$nama', price=$harga, category='$kategori', description='$deskripsi', image='$gambar' 
            WHERE product_id=$id AND merchant_id=$merchant_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Menu berhasil diperbarui!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update: " . mysqli_error($conn)]);
    }

} elseif ($method === 'DELETE') {
    // Delete Menu
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID tidak valid."]);
        exit;
    }

    $sql = "DELETE FROM products WHERE product_id=$id AND merchant_id=$merchant_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Menu berhasil dihapus!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal hapus: " . mysqli_error($conn)]);
    }
}
?>