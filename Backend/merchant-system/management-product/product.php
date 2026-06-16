<?php

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

$conn = mysqli_connect("localhost", "root", "", "menus");

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_connect_error()]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; 

if ($method === 'GET') {
    $res = mysqli_query($conn, "SELECT * FROM menus ORDER BY id DESC");
    $data = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) { $data[] = $row; }
    }
    echo json_encode(["status" => "success", "data" => $data]);
    
} elseif ($method === 'POST') {
    $nama = mysqli_real_escape_string($conn, $input['nama'] ?? '');
    $harga = (int)($input['harga'] ?? 0);
    $kategori = mysqli_real_escape_string($conn, $input['kategori'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
    $gambar = mysqli_real_escape_string($conn, $input['gambar'] ?? '');

    $sql = "INSERT INTO menus (nama, harga, kategori, deskripsi, gambar) VALUES ('$nama', $harga, '$kategori', '$deskripsi', '$gambar')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Menu berhasil ditambahkan."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }

} elseif ($method === 'PUT') {
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "Gagal edit: ID Menu tidak ditemukan."]);
        exit();
    }

    $nama = mysqli_real_escape_string($conn, $input['nama'] ?? '');
    $harga = (int)($input['harga'] ?? 0);
    $kategori = mysqli_real_escape_string($conn, $input['kategori'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
    $gambar = mysqli_real_escape_string($conn, $input['gambar'] ?? '');

    $sql = "UPDATE menus SET nama='$nama', harga=$harga, kategori='$kategori', deskripsi='$deskripsi', gambar='$gambar' WHERE id=$id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Menu berhasil diperbarui."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }

} elseif ($method === 'DELETE') {
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "Gagal hapus: ID Menu tidak ditemukan."]);
        exit();
    }

    $sql = "DELETE FROM menus WHERE id=$id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Menu berhasil dihapus."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }
}

mysqli_close($conn);
exit();
?>