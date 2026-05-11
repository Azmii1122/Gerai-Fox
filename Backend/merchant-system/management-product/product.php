<?php
// Mencegah output error PHP merusak format JSON
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Tangani preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
}

// ==========================================
// 1. CEK API KEY (Sesuai Modul)
// ==========================================
$valid_api_key = "12345-RAHASIA-WEB";
$client_key = isset($_GET['api_key']) ? $_GET['api_key'] : '';

if ($client_key !== $valid_api_key) {
    http_response_code(401);
    die(json_encode(["status" => "error", "message" => "Unauthorized: API Key Salah!"]));
}

// ==========================================
// 2. KONEKSI DATABASE (MySQLi Procedural)
// ==========================================
$host = "localhost";
$db_name = "gerai_fox_db"; 
$username = "root";        
$password = "";            

$conn = mysqli_connect($host, $username, $password, $db_name);

if (!$conn) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Koneksi gagal: " . mysqli_connect_error()]));
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    // ------------------------------------------
    // GET - Load / Read Data
    // ------------------------------------------
    case 'GET':
        $sql = "SELECT * FROM menus ORDER BY id DESC";
        $result = mysqli_query($conn, $sql);
        
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        
        http_response_code(200);
        // Membungkus data sesuai format Modul
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    // ------------------------------------------
    // POST - Tambah Data
    // ------------------------------------------
    case 'POST':
        if (!empty($input['nama']) && isset($input['harga']) && !empty($input['kategori'])) {
            // Mencegah SQL Injection
            $nama = mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($input['nama'])));
            $harga = (int)$input['harga'];
            $kategori = mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($input['kategori'])));
            $deskripsi = mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($input['deskripsi'] ?? '')));
            $gambar = mysqli_real_escape_string($conn, $input['gambar'] ?? '');

            $sql = "INSERT INTO menus (nama, harga, kategori, deskripsi, gambar) 
                    VALUES ('$nama', $harga, '$kategori', '$deskripsi', '$gambar')";
            
            if (mysqli_query($conn, $sql)) {
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Menu berhasil ditambahkan.", "id" => mysqli_insert_id($conn)]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "message" => "Gagal menyimpan ke database."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
        }
        break;

    // ------------------------------------------
    // PUT - Update Data
    // ------------------------------------------
    case 'PUT':
        // Ambil ID dari URL (Sesuai Modul)
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!empty($id) && !empty($input['nama']) && isset($input['harga'])) {
            $nama = mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($input['nama'])));
            $harga = (int)$input['harga'];
            $kategori = mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($input['kategori'])));
            $deskripsi = mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($input['deskripsi'] ?? '')));
            $gambar = mysqli_real_escape_string($conn, $input['gambar'] ?? '');

            $sql = "UPDATE menus SET nama='$nama', harga=$harga, kategori='$kategori', 
                    deskripsi='$deskripsi', gambar='$gambar' WHERE id=$id";
            
            if (mysqli_query($conn, $sql)) {
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Menu diperbarui."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "message" => "Gagal memperbarui."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap / ID tidak ada."]);
        }
        break;

    // ------------------------------------------
    // DELETE - Hapus Data
    // ------------------------------------------
    case 'DELETE':
        // Ambil ID dari URL (Sesuai Modul)
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!empty($id)) {
            $sql = "DELETE FROM menus WHERE id=$id";
            if (mysqli_query($conn, $sql)) {
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Menu dihapus."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "message" => "Gagal menghapus."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID tidak ditemukan."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Metode HTTP tidak didukung."]);
        break;
}

// Tutup koneksi (Sesuai Modul)
mysqli_close($conn);
?>