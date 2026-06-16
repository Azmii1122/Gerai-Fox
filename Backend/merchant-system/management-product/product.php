<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'seller') {
    header("Location: ../../Frontend/auth/login.html");
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        echo json_encode(["status" => "error", "message" => "System Error: " . $error['message']]);
        exit;
    }
});

include '../../db_connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; 

if ($method === 'GET') {
    $res = mysqli_query($conn, "SELECT * FROM products ORDER BY product_id DESC");
    $data = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) { $data[] = $row; }
    }
    echo json_encode(["status" => "success", "data" => $data]);
    
} elseif ($method === 'POST') {
    $nama = mysqli_real_escape_string($conn, $input['name'] ?? '');
    $harga = (int)($input['price'] ?? 0);
    $kategori = mysqli_real_escape_string($conn, $input['category'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $input['description'] ?? '');
    $gambar = mysqli_real_escape_string($conn, $input['image'] ?? '');

    $sql = "INSERT INTO products (nama, harga, kategori, deskripsi, gambar) VALUES ('$nama', $harga, '$kategori', '$deskripsi', '$gambar')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Product berhasil ditambahkan."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }

} elseif ($method === 'PUT') {
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "Gagal edit: ID Product tidak ditemukan."]);
        exit();
    }

    $nama = mysqli_real_escape_string($conn, $input['name'] ?? '');
    $harga = (int)($input['price'] ?? 0);
    $kategori = mysqli_real_escape_string($conn, $input['category'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $input['description'] ?? '');
    $gambar = mysqli_real_escape_string($conn, $input['image'] ?? '');

    $sql = "UPDATE products SET nama='$nama', harga=$harga, kategori='$kategori', deskripsi='$deskripsi', gambar='$gambar' WHERE product_id=$id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Product berhasil diperbarui."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }

} elseif ($method === 'DELETE') {
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "Gagal hapus: ID Product tidak ditemukan."]);
        exit();
    }

    $sql = "DELETE FROM products WHERE product_id=$id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Product berhasil dihapus."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }
}

mysqli_close($conn);
exit();
?>