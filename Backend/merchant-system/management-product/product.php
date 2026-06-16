<?php
session_start();

// Matikan error PHP bawaan agar tidak merusak format balasan JSON ke Frontend
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// 1. Pengecekan Sesi Login
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'seller') {
    echo json_encode(["status" => "error", "message" => "Akses Ditolak. Harap login sebagai penjual."]);
    exit;
}

include '../../db_connect.php'; // Sesuaikan path ini dengan lokasimu

// 2. Cari merchant_id dari user yang sedang login
$user_id = (int) $_SESSION['user_id'];
$query_sql = "SELECT merchant_id FROM merchants WHERE user_id = $user_id";
$q_merchant = mysqli_query($conn, $query_sql);

// Jika warungnya belum didaftarkan oleh admin
if (!$q_merchant || mysqli_num_rows($q_merchant) == 0) {
    echo json_encode(["status" => "error", "message" => "Akun Anda belum didaftarkan sebagai Warung oleh Admin."]);
    exit;
}

$merchant_data = mysqli_fetch_assoc($q_merchant);
$merchant_id = (int) $merchant_data['merchant_id'];

// 3. Tangkap Request dari Frontend HTML
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// =====================================
// PROSES CRUD
// =====================================
if ($method === 'GET') {
    $res = mysqli_query($conn, "SELECT * FROM products WHERE merchant_id = $merchant_id ORDER BY product_id DESC");
    $data = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
} elseif ($method === 'POST') {
    // MENGGUNAKAN $_POST KARENA KITA MENGIRIM FORMDATA (FILE + TEKS)
    $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $harga = (int) ($_POST['harga'] ?? 0);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'Lainnya');
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $gambar_url = mysqli_real_escape_string($conn, $_POST['gambar'] ?? '');

    $gambar_final = $gambar_url; // Default menggunakan Opsi 2 (URL)

    // PROSES UPLOAD FILE (Opsi 1)
    if (isset($_FILES['gambarFile']) && $_FILES['gambarFile']['error'] === UPLOAD_ERR_OK) {
        
        // 1. Jalur fisik untuk menyimpan file ke folder Laragon (mundur 3 langkah ke folder utama Gerai-Fox)
        $upload_dir = '../../../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Membersihkan nama file agar tidak error kalau ada spasi
        $nama_file_asli = str_replace(' ', '_', basename($_FILES['gambarFile']['name']));
        $file_name = time() . '_' . $nama_file_asli;
        
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['gambarFile']['tmp_name'], $target_path)) {
            // 2. JALUR URL (Ini yang disimpan ke database!) 
            // Kita pakai jalur absolut dari root localhost agar HTML tidak nyasar
            $gambar_final = '/Gerai-Fox/uploads/' . $file_name; 
        }
    }

    if ($id > 0) {
        // PROSES EDIT (UPDATE)
        $update_gambar = "";
        // Jika ada gambar baru (dari URL atau File), update kolom gambarnya. Jika tidak, biarkan gambar lama.
        if ($gambar_final !== '') {
            $update_gambar = ", image='$gambar_final'";
        }

        $sql = "UPDATE products SET name='$nama', price=$harga, category='$kategori', description='$deskripsi' $update_gambar 
                WHERE product_id=$id AND merchant_id=$merchant_id";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "message" => "Menu berhasil diperbarui!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal update: " . mysqli_error($conn)]);
        }
    } else {
        // PROSES TAMBAH BARU (INSERT)
        $is_available = 1;
        $sql = "INSERT INTO products (merchant_id, category, name, price, description, image, is_available) 
                VALUES ($merchant_id, '$kategori', '$nama', $harga, '$deskripsi', '$gambar_final', $is_available)";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "message" => "Menu berhasil ditambahkan!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal: " . mysqli_error($conn)]);
        }
    }
    exit;
} elseif ($method === 'DELETE') {
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
    exit;
}
?>