<?php
session_start();

// 1. Pengecekan Sesi Login (Dengan penyesuaian jika diakses via browser)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'seller') {
    if (isset($_GET['action']) && $_GET['action'] == 'api') {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["status" => "error", "message" => "Akses Ditolak. Harap login sebagai penjual."]);
        exit;
    } else {
        // Jika diakses via browser, arahkan ke login
        echo "<script>alert('Akses Ditolak. Harap login sebagai penjual.'); window.location.href='../../Frontend/auth/login.html';</script>";
        exit;
    }
}

include '../../db_connect.php'; // Sesuaikan path ini dengan lokasimu

// 2. Cari merchant_id dari user yang sedang login
$user_id = (int) $_SESSION['user_id'];
$query_sql = "SELECT merchant_id FROM merchants WHERE user_id = $user_id";
$q_merchant = mysqli_query($conn, $query_sql);

// Jika warungnya belum didaftarkan oleh admin
if (!$q_merchant || mysqli_num_rows($q_merchant) == 0) {
    if (isset($_GET['action']) && $_GET['action'] == 'api') {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["status" => "error", "message" => "Akun Anda belum didaftarkan sebagai Warung oleh Admin."]);
        exit;
    } else {
        echo "<script>alert('Akun Anda belum didaftarkan sebagai Warung oleh Admin.'); window.history.back();</script>";
        exit;
    }
}

$merchant_data = mysqli_fetch_assoc($q_merchant);
$merchant_id = (int) $merchant_data['merchant_id'];


// =========================================================================
// BLOK API CRUD (Hanya dieksekusi jika JS memanggil dengan ?action=api)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'api') {
    
    // Matikan error PHP bawaan agar tidak merusak format balasan JSON ke Frontend
    ini_set('display_errors', 0);
    error_reporting(0);

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    // Tangkap Request dari Frontend HTML
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents("php://input"), true);
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

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
}
// =========================================================================
// AKHIR BLOK API CRUD
// =========================================================================
// JIKA DIAKSES BROWSER BIASA (TIDAK ADA ?action=api), TAMPILKAN HTML DI BAWAH
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog - Gerai Fox</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --fox-50: #fff3e0;
            --fox-100: #ffe0b2;
            --fox-500: #f97316;
            --fox-600: #ea580c;
            --fox-900: #7c2d12;

            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;

            --red-50: #fef2f2;
            --red-100: #fee2e2;
            --red-500: #ef4444;
            --red-600: #dc2626;

            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            color: var(--slate-800);
            background-color: var(--slate-50);
            -webkit-font-smoothing: antialiased;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 256px;
            background-color: #ffffff;
            border-right: 1px solid var(--slate-200);
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            z-index: 10;
        }

        .sidebar-header {
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            border-bottom: 1px solid var(--slate-100);
            gap: 0.5rem;
            color: var(--fox-500);
        }

        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--slate-900);
            letter-spacing: -0.025em;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            overflow-y: auto;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: 0.5rem;
            color: var(--slate-600);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--fox-600);
            background-color: var(--fox-50);
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background-color: var(--slate-50);
        }

        .top-header {
            height: 64px;
            background-color: #ffffff;
            border-bottom: 1px solid var(--slate-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 10;
        }

        .mobile-brand {
            display: none;
            align-items: center;
            gap: 0.5rem;
            color: var(--fox-500);
            font-weight: 700;
            font-size: 1.125rem;
        }

        .header-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--slate-800);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-bell {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--slate-100);
            border: none;
            color: var(--slate-500);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .btn-bell:hover {
            background-color: var(--slate-200);
        }

        .profile-pic {
            height: 32px;
            width: 32px;
            border-radius: 50%;
            background-color: var(--fox-100);
            border: 2px solid var(--fox-500);
            overflow: hidden;
        }

        .profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .content-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .action-bar {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--slate-900);
        }

        .page-title p {
            font-size: 0.875rem;
            color: var(--slate-500);
            margin-top: 0.25rem;
        }

        .action-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .search-wrapper {
            position: relative;
            width: 100%;
        }

        .search-wrapper i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--fox-500);
            box-shadow: 0 0 0 2px var(--fox-100);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--fox-500);
            color: white;
            box-shadow: 0 2px 4px rgba(249, 115, 22, 0.3);
        }

        .btn-primary:hover {
            background-color: var(--fox-600);
        }

        .btn-outline {
            background-color: white;
            border-color: var(--slate-300);
            color: var(--slate-700);
        }

        .btn-outline:hover {
            background-color: var(--slate-50);
        }

        .btn-danger {
            background-color: var(--red-500);
            color: white;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            background-color: var(--red-600);
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .card {
            background-color: white;
            border-radius: 0.75rem;
            border: 1px solid var(--slate-200);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .card-img-wrapper {
            position: relative;
            height: 192px;
            background-color: var(--slate-100);
            overflow: hidden;
        }

        .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .card:hover .card-img-wrapper img {
            transform: scale(1.05);
        }

        .card-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
            box-shadow: var(--shadow-sm);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
        }

        .badge-makanan {
            background-color: #ffedd5;
            color: #c2410c;
        }

        .badge-minuman {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .badge-cemilan {
            background-color: #fef08a;
            color: #a16207;
        }

        .card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-price {
            color: var(--fox-600);
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .card-desc {
            font-size: 0.875rem;
            color: var(--slate-500);
            flex: 1;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-footer {
            display: flex;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--slate-100);
        }

        .btn-card-edit {
            flex: 1;
            padding: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--slate-700);
            background-color: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-card-edit:hover {
            background-color: var(--slate-100);
        }

        .btn-card-delete {
            padding: 0.5rem 0.75rem;
            color: var(--red-600);
            background-color: var(--red-50);
            border: 1px solid var(--red-100);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-card-delete:hover {
            background-color: var(--red-100);
        }

        .hidden {
            display: none !important;
        }

        .empty-state,
        .loading-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 5rem 1rem;
            text-align: center;
        }

        .empty-state {
            background-color: white;
            border-radius: 1rem;
            border: 1px dashed var(--slate-300);
        }

        .empty-icon {
            width: 5rem;
            height: 5rem;
            background-color: var(--fox-50);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.875rem;
            color: var(--fox-500);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--slate-900);
            margin-bottom: 0.25rem;
        }

        .empty-state p {
            color: var(--slate-500);
            font-size: 0.875rem;
            margin-bottom: 1rem;
            max-width: 24rem;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: white;
            border-radius: 1rem;
            width: 100%;
            max-width: 28rem;
            box-shadow: var(--shadow-xl);
            transform: scale(0.95);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--slate-100);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .btn-close {
            background: none;
            border: none;
            color: var(--slate-400);
            font-size: 1.25rem;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover {
            background-color: var(--slate-100);
            color: var(--slate-600);
        }

        .modal-body {
            padding: 1.25rem;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--slate-700);
            margin-bottom: 0.25rem;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            transition: all 0.2s;
            background: white;
        }

        .form-control:focus {
            border-color: var(--fox-500);
            box-shadow: 0 0 0 2px var(--fox-100);
        }

        textarea.form-control {
            resize: none;
        }

        .upload-box {
            padding: 0.75rem;
            border: 1px solid var(--slate-200);
            background-color: var(--slate-50);
            border-radius: 0.5rem;
        }

        .upload-divider {
            display: flex;
            align-items: center;
            margin: 0.5rem 0;
        }

        .upload-divider::before,
        .upload-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--slate-300);
        }

        .upload-divider span {
            padding: 0 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--slate-400);
        }

        input[type="file"] {
            width: 100%;
            padding: 0.25rem;
            background-color: white;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: var(--slate-500);
            cursor: pointer;
        }

        input[type="file"]::file-selector-button {
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            background-color: var(--fox-500);
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
        }

        .modal-footer {
            padding-top: 1.5rem;
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-xl);
            border: 1px solid;
            animation: slideInUp 0.3s ease-out forwards;
        }

        .toast.success {
            border-color: #bbf7d0;
            background-color: #f0fdf4;
            color: #166534;
        }

        .toast.error {
            border-color: #fecaca;
            background-color: #fef2f2;
            color: #991b1b;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (min-width: 640px) {
            .action-bar {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .search-wrapper {
                width: 16rem;
            }

            .grid-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-body {
                padding: 1.5rem 2rem;
            }
        }

        @media (min-width: 1024px) {
            .grid-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .grid-container {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .mobile-brand {
                display: flex;
            }

            .header-title {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-fox fa-lg"></i>
                <h2>HubBite</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="../dashboard.html" class="nav-link">
                    <i class="fa-solid fa-chart-pie w-5"></i> Dashboard
                </a>
                <a href="product.php" class="nav-link active">
                    <i class="fa-solid fa-book-open w-5"></i> Katalog Menu
                </a>
                <a href="#" class="nav-link">
                    <i class="fa-solid fa-receipt w-5"></i> Pesanan
                </a>
                <a href="profilewrg.html" class="nav-link">
                    <i class="fa-solid fa-gear w-5"></i> Pengaturan
                </a>
                <a href="/Gerai-Fox/Frontend/merchant/laporan/laporan.html" class="nav-link">
                    <i class="fa-solid fa-file-invoice-dollar w-5"></i>Laporan Keuangan
                </a>
                <a href="../../../Backend/auth-system/logout.php" class="nav-link">
                    <i class="fa-solid fa-right-from-bracket w-5"></i> Logout
                </a>
            </nav>
        </aside>

        <main class="main-wrapper">
            <header class="top-header">
                <div class="mobile-brand">
                    <i class="fa-solid fa-fox"></i> Gerai Fox
                </div>
                <h1 class="header-title">Manajemen Katalog</h1>
                <div class="header-actions">
                    <button class="btn-bell">
                        <i class="fa-solid fa-bell"></i>
                    </button>
                    <div class="profile-pic">
                        <img src="https://placehold.co/100x100/ea580c/white?text=S" alt="Seller">
                    </div>
                </div>
            </header>

            <div class="content-body">
                <div class="action-bar">
                    <div class="page-title">
                        <h1>Daftar Menu</h1>
                        <p>Kelola hidangan dan minuman di warung Anda.</p>
                    </div>
                    <div class="action-controls">
                        <div class="search-wrapper">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="searchInput" class="search-input" placeholder="Cari menu...">
                        </div>
                        <button onclick="openModal('add')" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Tambah Menu
                        </button>
                    </div>
                </div>

                <div id="loadingState" class="loading-state hidden">
                    <i class="fa-solid fa-circle-notch fa-spin fa-3x"
                        style="color: var(--fox-500); margin-bottom: 1rem;"></i>
                    <p style="color: var(--slate-500);">Memuat katalog menu...</p>
                </div>

                <div id="emptyState" class="empty-state hidden">
                    <div class="empty-icon">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <h3>Belum Ada Menu</h3>
                    <p>Katalog Anda masih kosong. Mulai tambahkan menu makanan atau minuman untuk pelanggan Anda.</p>
                    <button onclick="openModal('add')" class="btn btn-outline"
                        style="color: var(--fox-600); border-color: var(--fox-200);">
                        <i class="fa-solid fa-plus"></i> Tambah Menu Sekarang
                    </button>
                </div>

                <div id="menuGrid" class="grid-container"></div>
            </div>
        </main>
    </div>

    <div id="formModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Menu Baru</h3>
                <button type="button" class="btn-close" onclick="closeModal('formModal')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="menuForm" onsubmit="handleFormSubmit(event)">
                    <input type="hidden" id="menuId" value="0">

                    <div class="form-group">
                        <label class="form-label">Nama Menu <span style="color: var(--red-500)">*</span></label>
                        <input type="text" id="nama" class="form-control" required placeholder="Contoh: Nasi Goreng">
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label">Harga <span style="color: var(--red-500)">*</span></label>
                            <input type="number" id="harga" class="form-control" required min="0" placeholder="25000">
                        </div>
                        <div>
                            <label class="form-label">Kategori <span style="color: var(--red-500)">*</span></label>
                            <select id="kategori" class="form-control" required>
                                <option value="" disabled selected>Pilih...</option>
                                <option value="Makanan">Makanan</option>
                                <option value="Minuman">Minuman</option>
                                <option value="Cemilan">Cemilan</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi Singkat</label>
                        <textarea id="deskripsi" rows="3" class="form-control"
                            placeholder="Isi bahan atau penjelasan..."></textarea>
                    </div>

                    <div class="upload-box">
                        <label class="form-label" style="color: var(--slate-900)">Gambar Menu</label>
                        <div style="margin-top: 0.5rem;">
                            <label
                                style="font-size: 0.75rem; color: var(--slate-500); display: block; margin-bottom: 0.25rem;">Opsi
                                1: Dari Galeri/Komputer (Maks 1MB)</label>
                            <input type="file" id="gambarFile" accept="image/*">
                        </div>
                        <div class="upload-divider">
                            <span>ATAU</span>
                        </div>
                        <div>
                            <label
                                style="font-size: 0.75rem; color: var(--slate-500); display: block; margin-bottom: 0.25rem;">Opsi
                                2: Link URL</label>
                            <input type="url" id="gambar" class="form-control"
                                placeholder="https://contoh.com/gambar.jpg">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeModal('formModal')">Batal</button>
                        <button type="submit" id="btnSubmit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Simpan Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 24rem; text-align: center;">
            <div class="modal-body" style="padding: 2rem 1.5rem;">
                <div
                    style="width: 4rem; height: 4rem; border-radius: 50%; background: var(--red-100); color: var(--red-500); font-size: 1.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">Hapus Menu?</h3>
                <p style="color: var(--slate-500); font-size: 0.875rem; margin-bottom: 1.5rem;">
                    Yakin menghapus <strong id="deleteItemName" style="color: var(--slate-800)"></strong>?
                </p>
                <div style="display: flex; gap: 0.75rem;">
                    <button class="btn btn-outline" style="flex: 1; justify-content: center;"
                        onclick="closeModal('deleteModal')">Batal</button>
                    <button class="btn btn-danger" style="flex: 1; justify-content: center;"
                        onclick="executeDelete()">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <script>
        // PENYESUAIAN: API_URL sekarang menunjuk ke parameter di file ini sendiri
        const API_URL = 'product.php?action=api';
        
        let menus = [];
        let itemToDeleteId = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadProducts();
        });

        async function loadProducts() {
            const loader = document.getElementById('loadingState');
            const grid = document.getElementById('menuGrid');
            const emptyState = document.getElementById('emptyState');

            loader.classList.remove('hidden');
            grid.innerHTML = '';
            emptyState.classList.add('hidden');

            try {
                const response = await fetch(API_URL);
                const result = await response.json();

                if (result.status === 'success') {
                    menus = result.data;
                    renderGrid(menus);
                } else {
                    showToast('Gagal memuat data: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan koneksi saat memuat menu.', 'error');
            } finally {
                loader.classList.add('hidden');
            }
        }

        function renderGrid(data) {
            const grid = document.getElementById('menuGrid');
            const emptyState = document.getElementById('emptyState');

            grid.innerHTML = '';

            if (data.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            }

            emptyState.classList.add('hidden');

            data.forEach(p => {
                let imgHtml = p.image
                    ? `<img src="${p.image}" alt="Gambar" style="width:100%; height:100%; object-fit:cover;">`
                    : `<div style="height:100%; display:flex; align-items:center; justify-content:center; color:var(--slate-400);"><i class="fas fa-image fa-3x"></i></div>`;

                let hargaRp = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(p.price);

                let badgeClass = 'badge-makanan';
                let kat = p.category.toLowerCase();
                if (kat === 'minuman') badgeClass = 'badge-minuman';
                else if (kat === 'cemilan' || kat === 'snack') badgeClass = 'badge-cemilan';

                grid.innerHTML += `
                    <div class="card">
                        <div class="card-img-wrapper">
                            ${imgHtml}
                            <div class="card-badge ${badgeClass}">${p.category}</div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">${p.name}</h3>
                            <div class="card-price">${hargaRp}</div>
                            <p class="card-desc">${p.description || 'Tidak ada deskripsi'}</p>
                            <div class="card-footer">
                                <button class="btn-card-edit" onclick="editMenu(${p.product_id}, '${p.name.replace(/'/g, "\\'")}', ${p.price}, '${p.category}', '${(p.description || '').replace(/'/g, "\\'")}', '${(p.image || '')}')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-card-delete" onclick="confirmDelete(${p.product_id}, '${p.name.replace(/'/g, "\\'")}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const keyword = e.target.value.toLowerCase();
                const filtered = menus.filter(m =>
                    m.name.toLowerCase().includes(keyword) ||
                    m.category.toLowerCase().includes(keyword)
                );
                renderGrid(filtered);
            });
        }

        function openModal(type) {
            if (type === 'add') {
                document.getElementById('menuForm').reset();
                document.getElementById('menuId').value = "0";
                document.getElementById('modalTitle').innerText = "Tambah Menu Baru";
            }
            document.getElementById('formModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function editMenu(id, nama, harga, kategori, deskripsi, gambar) {
            document.getElementById('menuId').value = id;
            document.getElementById('nama').value = nama;
            document.getElementById('harga').value = harga;
            document.getElementById('kategori').value = kategori;
            document.getElementById('deskripsi').value = deskripsi !== 'undefined' ? deskripsi : '';
            document.getElementById('gambar').value = gambar !== 'undefined' ? gambar : '';

            document.getElementById('modalTitle').innerText = "Edit Menu";
            document.getElementById('formModal').classList.add('active');
        }

        async function handleFormSubmit(e) {
            e.preventDefault();
            
            const id = document.getElementById('menuId').value;
            const method = 'POST'; // KITA SELALU PAKAI POST AGAR BISA UPLOAD GAMBAR
            // PENYESUAIAN URL: Gunakan &id= bukan ?id= karena API_URL sudah memuat tanda tanya
            const url = (id === "0" || id === "") ? API_URL : `${API_URL}&id=${id}`;

            // GUNAKAN FORMDATA UNTUK MENGANGKUT FILE + TEKS
            const formData = new FormData();
            formData.append('nama', document.getElementById('nama').value);
            formData.append('harga', document.getElementById('harga').value);
            formData.append('kategori', document.getElementById('kategori').value);
            formData.append('deskripsi', document.getElementById('deskripsi').value);
            formData.append('gambar', document.getElementById('gambar').value); // Opsi 2 (URL)
            
            // Cek apakah ada file fisik yang dipilih di Opsi 1
            const fileInput = document.getElementById('gambarFile');
            if (fileInput.files.length > 0) {
                formData.append('gambarFile', fileInput.files[0]);
            }

            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            try {
                const response = await fetch(url, {
                    method: method,
                    body: formData // PENTING: Jangan pasang header 'Content-Type' jika pakai FormData
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    closeModal('formModal');
                    showToast(result.message, 'success');
                    loadProducts(); 
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Gagal menyimpan data.', 'error');
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Simpan Menu';
            }
        }

        function confirmDelete(id, name) {
            itemToDeleteId = id;
            document.getElementById('deleteItemName').innerText = name;
            document.getElementById('deleteModal').classList.add('active');
        }

        async function executeDelete() {
            if (!itemToDeleteId) return;

            try {
                // PENYESUAIAN URL: Gunakan &id=
                const response = await fetch(`${API_URL}&id=${itemToDeleteId}`, { method: 'DELETE' });
                const result = await response.json();
                if (result.status === 'success') {
                    closeModal('deleteModal');
                    showToast(result.message, 'success');
                    loadProducts();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Gagal menghapus data.', 'error');
            }
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark';
            toast.innerHTML = `<i class="fa-solid ${icon} fa-lg"></i> <p style="font-weight: 500; font-size: 0.875rem; margin:0;">${message}</p>`;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>