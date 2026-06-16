<?php
// MATIKAN SEMUA ERROR DISPLAY BERBENTUK HTML
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

// ID warung (asumsi 1 warung untuk dashboard ini)
$id_warung = 1;

if ($method === 'GET') {
    $res = mysqli_query($conn, "SELECT * FROM profil_warung WHERE id=$id_warung");
    
    // Jika belum ada data profil, buatkan profil default
    if (mysqli_num_rows($res) == 0) {
        mysqli_query($conn, "INSERT INTO profil_warung (id, nama_warung, nama_pemilik, nomor_telepon, lokasi, deskripsi) 
                             VALUES ($id_warung, 'Warung HubBite', 'Admin', '0812xxxx', 'Area Kampus', 'Deskripsi warung belum diisi')");
        $res = mysqli_query($conn, "SELECT * FROM profil_warung WHERE id=$id_warung");
    }
    
    $data = mysqli_fetch_assoc($res);
    echo json_encode(["status" => "success", "data" => $data]);
    
} elseif ($method === 'PUT') {
    $nama_warung = mysqli_real_escape_string($conn, $input['nama_warung'] ?? '');
    $nama_pemilik = mysqli_real_escape_string($conn, $input['nama_pemilik'] ?? '');
    $nomor_telepon = mysqli_real_escape_string($conn, $input['nomor_telepon'] ?? '');
    $lokasi = mysqli_real_escape_string($conn, $input['lokasi'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
    $foto_warung = mysqli_real_escape_string($conn, $input['foto_warung'] ?? '');

    $sql = "UPDATE profil_warung SET 
            nama_warung='$nama_warung', 
            nama_pemilik='$nama_pemilik', 
            nomor_telepon='$nomor_telepon', 
            lokasi='$lokasi', 
            deskripsi='$deskripsi', 
            foto_warung='$foto_warung' 
            WHERE id=$id_warung";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Profil identitas warung berhasil diperbarui."]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . mysqli_error($conn)]);
    }
}

mysqli_close($conn);
exit();
?>