<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Tangani preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$db_name = "gerai_fox_db"; // Ganti dengan nama database Anda
$username = "root";        // Ganti dengan username database Anda
$password = "";            // Ganti dengan password database Anda

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    // Jika tidak ada database, kita kirim error 500
    // Note: Untuk keperluan testing lokal tanpa database, script frontend (index.html) 
    // telah dilengkapi dengan sistem Mock/Fallback agar UI tetap berjalan.
    http_response_code(500);
    echo json_encode(["message" => "Connection error: " . $exception->getMessage()]);
    exit();
}

// Mendapatkan method HTTP (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

// Membaca raw JSON input untuk POST, PUT, DELETE
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    // ==========================================
    // 1. GET - Load / Read Data
    // ==========================================
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT * FROM menus ORDER BY id DESC");
            $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode($menus);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Gagal mengambil data", "error" => $e->getMessage()]);
        }
        break;

    // ==========================================
    // 2. POST - Tambah Data (Create)
    // ==========================================
    case 'POST':
        if (!empty($input['nama']) && !empty($input['harga']) && !empty($input['kategori'])) {
            try {
                $query = "INSERT INTO menus (nama, harga, kategori, deskripsi, gambar) 
                          VALUES (:nama, :harga, :kategori, :deskripsi, :gambar)";
                
                $stmt = $pdo->prepare($query);
                
                // Bind parameter & hindari XSS
                $stmt->bindParam(':nama', htmlspecialchars(strip_tags($input['nama'])));
                $stmt->bindParam(':harga', $input['harga']);
                $stmt->bindParam(':kategori', htmlspecialchars(strip_tags($input['kategori'])));
                $stmt->bindParam(':deskripsi', htmlspecialchars(strip_tags($input['deskripsi'] ?? '')));
                $stmt->bindParam(':gambar', htmlspecialchars(strip_tags($input['gambar'] ?? '')));
                
                if ($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(["message" => "Menu berhasil ditambahkan.", "id" => $pdo->lastInsertId()]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Gagal menambahkan menu."]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error Server", "error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap. Nama, harga, dan kategori wajib diisi."]);
        }
        break;

    // ==========================================
    // 3. PUT - Edit/Update Data
    // ==========================================
    case 'PUT':
        if (!empty($input['id']) && !empty($input['nama']) && !empty($input['harga']) && !empty($input['kategori'])) {
            try {
                $query = "UPDATE menus 
                          SET nama = :nama, harga = :harga, kategori = :kategori, 
                              deskripsi = :deskripsi, gambar = :gambar 
                          WHERE id = :id";
                
                $stmt = $pdo->prepare($query);
                
                $stmt->bindParam(':id', $input['id']);
                $stmt->bindParam(':nama', htmlspecialchars(strip_tags($input['nama'])));
                $stmt->bindParam(':harga', $input['harga']);
                $stmt->bindParam(':kategori', htmlspecialchars(strip_tags($input['kategori'])));
                $stmt->bindParam(':deskripsi', htmlspecialchars(strip_tags($input['deskripsi'] ?? '')));
                $stmt->bindParam(':gambar', htmlspecialchars(strip_tags($input['gambar'] ?? '')));
                
                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode(["message" => "Menu berhasil diperbarui."]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Gagal memperbarui menu."]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error Server", "error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Data tidak lengkap atau ID tidak valid."]);
        }
        break;

    // ==========================================
    // 4. DELETE - Hapus Data
    // ==========================================
    case 'DELETE':
        // Cek ID baik dari body raw JSON atau URL query string (fallback)
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!empty($id)) {
            try {
                $query = "DELETE FROM menus WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode(["message" => "Menu berhasil dihapus."]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Gagal menghapus menu."]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error Server", "error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID Menu tidak ditemukan."]);
        }
        break;

    // ==========================================
    // Handler Jika Method Tidak Dikenal
    // ==========================================
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["message" => "Metode HTTP tidak didukung."]);
        break;
}
?>