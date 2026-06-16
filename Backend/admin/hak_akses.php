<?php
session_start();
// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../Frontend/auth/login.html");
    exit;
}

include '../db_connect.php';
$message = "";
$messageType = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

if (isset($_POST['update_access'])) {
    $merchant_id = (int) $_POST['merchant_id'];
    $delivery = isset($_POST['akses_delivery']) ? 1 : 0;
    $priority = isset($_POST['akses_priority']) ? 1 : 0;
    $query = "UPDATE merchants SET allow_delivery = $delivery, allow_priority = $priority WHERE merchant_id = $merchant_id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['flash_message'] = "Hak Akses Berhasil di perbarui!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Gagal memperbarui hak akses: " . mysqli_error($conn);
        $_SESSION['flash_type'] = "error";
    }
    
    header("Location: hak_akses.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hak Akses Warung - HubBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF8C00;
            --secondary-orange: #FFA500;
            --bg-body: #F4F7FE;
            --bg-sidebar: #FFFFFF;
            --text-dark: #1B2559;
            --text-muted: #A3AED0;
            --danger: #EE5D50;
            --success: #05CD99;
            --card-bg: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { display: flex; background-color: var(--bg-body); color: var(--text-dark); min-height: 100vh; }

        .sidebar { width: 280px; background: var(--bg-sidebar); padding: 40px 20px; display: flex; flex-direction: column; border-right: 1px solid #E9EDF7; position: fixed; height: 100vh; }
        .brand { font-weight: 800; font-size: 24px; color: var(--primary-orange); text-align: center; margin-bottom: 50px; }
        .nav-links { list-style: none; display: flex; flex-direction: column; height: 100%; }
        .nav-links a { display: flex; align-items: center; padding: 16px 20px; margin-bottom: 8px; text-decoration: none; color: var(--text-muted); font-weight: 600; border-radius: 15px; transition: 0.3s; }
        .nav-links a.active { background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange)); color: white; box-shadow: 0px 10px 20px rgba(255, 140, 0, 0.2); }
        .nav-links a:not(.active):hover { background: #F4F7FE; color: var(--primary-orange); }
        .logout-btn { margin-top: auto; color: var(--danger) !important; border: 1px solid #FEEFEE; background: #FFF5F5; }

        .main-content { margin-left: 280px; flex: 1; padding: 40px; }
        .header-section { margin-bottom: 30px; }
        .header-section h1 { font-size: 28px; font-weight: 800; }
        .header-section span { color: var(--primary-orange); }

        .card { background: var(--card-bg); padding: 30px; border-radius: 20px; border: 1px solid #E9EDF7; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .card h2 { font-size: 18px; font-weight: 800; margin-bottom: 20px; border-left: 4px solid var(--primary-orange); padding-left: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #E9EDF7; }
        td { padding: 15px; border-bottom: 1px solid #F4F7FE; font-size: 14px; font-weight: 600; vertical-align: middle; }

        .switch { position: relative; display: inline-block; width: 44px; height: 24px; margin-right: 10px; vertical-align: middle; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(20px); }
        
        .feature-label { display: flex; align-items: center; font-size: 13px; color: var(--text-dark); margin-bottom: 8px; cursor: pointer; }

        .btn-save { background: #E9EDFF; color: #4318FF; padding: 10px 16px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background: #4318FF; color: white; }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 700; }
        .alert-success { background: #E6F9F0; color: #05CD99; }
        .alert-error { background: #FEEFEE; color: var(--danger); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">HubBite</div>
        <nav class="nav-links">
            <a href="dashboard.php"><span>📊</span> Dashboard</a>
            <a href="management_user.php"><span>👥</span> Kelola User</a>
            <a href="management_merchant.php" class="active"><span>🏪</span> Hak Akses Fitur</a>
            <a href="../auth-system/logout.php" class="logout-btn"><span>🚪</span> Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header-section">
            <h1>Hak Akses <span>Fitur Warung</span></h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-top:5px;">Atur Akses Delivery & Priority Order</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $messageType == 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Daftar Izin</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Info Warung</th>
                        <th>Akses Delivery</th>
                        <th>Akses Priority Order</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // SESUAIKAN NAMA KOLOM allow_delivery & allow_priority DENGAN DATABASEMU
                    $q_merchants = "
                        SELECT m.merchant_id, m.store_name, m.allow_delivery, m.allow_priority, u.username 
                        FROM merchants m 
                        JOIN users u ON m.user_id = u.user_id 
                        ORDER BY m.merchant_id ASC
                    ";
                    $result = mysqli_query($conn, $q_merchants);

                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)): 
                    ?>
                    <tr>
                        <td>#<?= $row['merchant_id']; ?></td>
                        <td>
                            <div style="font-size: 15px; font-weight:800;"><?= htmlspecialchars($row['store_name']); ?></div>
                            <div style="font-size: 12px; color: var(--text-muted); font-weight: 400;">Akun: @<?= htmlspecialchars($row['username']); ?></div>
                        </td>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="merchant_id" value="<?= $row['merchant_id']; ?>">
                            
                            <td>
                                <label class="feature-label">
                                    <div class="switch">
                                        <input type="checkbox" name="akses_delivery" <?= $row['allow_delivery'] == 1 ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </div>
                                    Aktif
                                </label>
                            </td>

                            <td>
                                <label class="feature-label">
                                    <div class="switch">
                                        <input type="checkbox" name="akses_priority" <?= $row['allow_priority'] == 1 ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </div>
                                    Aktif
                                </label>
                            </td>

                            <td>
                                <button type="submit" name="update_access" class="btn-save">Simpan Perubahan</button>
                            </td>
                        </form>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada mitra warung terdaftar.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>