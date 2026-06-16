<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'seller') {
    header("Location: ../../Frontend/auth/login.html");
    exit;
}

include '../../Backend/db_connect.php';


$user_id = $_SESSION['user_id']; 
$user_email = $_SESSION['email'];

$message = "";
$messageType = "";

// 1. CEK ATAU BUAT PROFIL WARUNG OTOMATIS
// Jika penjual baru pertama kali login, buatkan data warung default
$query_check_merchant = "SELECT * FROM merchants WHERE user_id = $user_id";
$result_merchant = mysqli_query($conn, $query_check_merchant);

if (mysqli_num_rows($result_merchant) == 0) {
    $insert_merchant = "INSERT INTO merchants (user_id, user_email, store_name, is_open, live_crowd_status) 
                        VALUES ($user_id, '$user_email', 'Warung Baru', 0, 'hijau')";
    mysqli_query($conn, $insert_merchant);
    // Ambil ulang datanya
    $result_merchant = mysqli_query($conn, $query_check_merchant);
}
$merchant_data = mysqli_fetch_assoc($result_merchant);
$merchant_id = $merchant_data['merchant_id'];

// ==========================================
// 2. HANDLE UPDATE STATUS WARUNG
// ==========================================
if (isset($_POST['update_store'])) {
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    $is_open = isset($_POST['is_open']) ? 1 : 0;
    $live_crowd = $_POST['live_crowd_status'];

    $q_update = "UPDATE merchants SET store_name='$store_name', is_open=$is_open, live_crowd_status='$live_crowd' WHERE merchant_id=$merchant_id";
    
    if (mysqli_query($conn, $q_update)) {
        $_SESSION['flash_message'] = "Status warung berhasil diperbarui!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Gagal memperbarui warung.";
        $_SESSION['flash_type'] = "error";
    }
    header("Location: dashboard.php");
    exit;
}

// ==========================================
// 3. HANDLE TAMBAH MENU (PRODUK)
// ==========================================
if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = (float) $_POST['price'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    $q_add = "INSERT INTO products (merchant_id, name, price, is_available) VALUES ($merchant_id, '$name', $price, $is_available)";
    
    if (mysqli_query($conn, $q_add)) {
        $_SESSION['flash_message'] = "Menu baru berhasil ditambahkan!";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: dashboard.php");
    exit;
}

// ==========================================
// 4. HANDLE HAPUS MENU
// ==========================================
if (isset($_POST['delete_product'])) {
    $product_id = (int) $_POST['delete_id'];
    $q_del = "DELETE FROM products WHERE product_id=$product_id AND merchant_id=$merchant_id";
    
    if (mysqli_query($conn, $q_del)) {
        $_SESSION['flash_message'] = "Menu berhasil dihapus!";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: dashboard.php");
    exit;
}

// Ambil pesan dari session (Post/Redirect/Get Pattern)
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Merchant - HubBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF8C00;
            --bg-body: #F4F7FE;
            --text-dark: #1B2559;
            --text-muted: #A3AED0;
            --success: #05CD99;
            --danger: #EE5D50;
            --warning: #FFCE20;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { display: flex; background-color: var(--bg-body); color: var(--text-dark); min-height: 100vh; }
        
        /* SIDEBAR (Mirip Admin) */
        .sidebar { width: 260px; background: white; padding: 30px 20px; border-right: 1px solid #E9EDF7; position: fixed; height: 100vh; }
        .brand { font-weight: 800; font-size: 22px; color: var(--primary); text-align: center; margin-bottom: 40px; }
        .nav-links a { display: block; padding: 15px; margin-bottom: 10px; text-decoration: none; color: var(--text-muted); font-weight: 600; border-radius: 12px; }
        .nav-links a.active { background: var(--primary); color: white; }
        
        /* MAIN CONTENT */
        .main-content { margin-left: 260px; flex: 1; padding: 40px; }
        .header-title { font-size: 26px; font-weight: 800; margin-bottom: 5px; }
        
        /* CARDS */
        .card { background: white; padding: 25px; border-radius: 16px; border: 1px solid #E9EDF7; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .card h3 { margin-bottom: 15px; font-size: 18px; border-left: 4px solid var(--primary); padding-left: 10px; }
        
        /* FORM & INPUTS */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ddd; outline: none; }
        input:focus, select:focus { border-color: var(--primary); }
        
        /* SWITCH TOGGLE */
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(26px); }

        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: white; background: var(--primary); }
        .btn-danger { background: #FEEFEE; color: var(--danger); }
        
        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { font-size: 13px; color: var(--text-muted); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; color: white; }
        .alert-success { background-color: var(--success); }
        .alert-error { background-color: var(--danger); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">🏪 Dashboard Toko</div>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">🏠 Profil & Menu</a>
            <a href="#">📝 Pesanan Masuk (Segera)</a>
            <a href="../../Backend/auth-system/logout.php" style="color: var(--danger); margin-top: 50px;">🚪 Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header-title">Halo, Penjual! 👋</div>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Atur ketersediaan warung dan menu makananmu di sini.</p>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $messageType == 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Pengaturan Status Warung</h3>
            <form method="POST" action="">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nama Warung</label>
                        <input type="text" name="store_name" value="<?= htmlspecialchars($merchant_data['store_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Status Kepadatan (Live Crowd)</label>
                        <select name="live_crowd_status">
                            <option value="hijau" <?= $merchant_data['live_crowd_status'] == 'hijau' ? 'selected' : ''; ?>>🟢 Hijau - Sepi Antrean</option>
                            <option value="kuning" <?= $merchant_data['live_crowd_status'] == 'kuning' ? 'selected' : ''; ?>>🟡 Kuning - Lumayan Ramai</option>
                            <option value="merah" <?= $merchant_data['live_crowd_status'] == 'merah' ? 'selected' : ''; ?>>🔴 Merah - Antre Panjang</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 15px; margin-top:10px;">
                    <label style="margin:0;">Status Buka/Tutup Warung:</label>
                    <label class="switch">
                        <input type="checkbox" name="is_open" <?= $merchant_data['is_open'] == 1 ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <button type="submit" name="update_store" class="btn">Update Profil Warung</button>
            </form>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3>Tambah Menu Baru</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Nama Makanan / Minuman</label>
                        <input type="text" name="name" placeholder="Cth: Ayam Geprek Level 5" required>
                    </div>
                    <div class="form-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="price" placeholder="Cth: 15000" required>
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 15px;">
                        <label style="margin:0;">Tersedia (Ready)?</label>
                        <label class="switch">
                            <input type="checkbox" name="is_available" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <button type="submit" name="add_product" class="btn" style="width:100%; margin-top:10px;">+ Tambah Menu</button>
                </form>
            </div>

            <div class="card" style="overflow-y: auto; max-height: 400px;">
                <h3>Daftar Menu Kamu</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Menu</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_products = "SELECT * FROM products WHERE merchant_id = $merchant_id ORDER BY product_id DESC";
                        $r_products = mysqli_query($conn, $q_products);
                        if (mysqli_num_rows($r_products) > 0) {
                            while ($row = mysqli_fetch_assoc($r_products)):
                        ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['name']); ?></td>
                            <td style="color:var(--primary);">Rp<?= number_format($row['price'], 0, ',', '.'); ?></td>
                            <td><?= $row['is_available'] ? '✅ Ready' : '❌ Habis'; ?></td>
                            <td>
                                <form method="POST" action="" style="margin:0;">
                                    <input type="hidden" name="delete_id" value="<?= $row['product_id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger" onclick="return confirm('Hapus menu ini?');">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center;'>Belum ada menu. Silakan tambah!</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>