<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../Frontend/auth/login.html");
    exit;
}
include '../db_connect.php';

$tot_user = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='buyer'"));
$tot_merchant = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='seller'"));
$tot_akun = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users"));
$tot_penjualan = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM orders WHERE status='completed'"));
$query_omset = mysqli_query($conn, "SELECT SUM(total_amount) as omset FROM orders WHERE status='completed'");
$row_omset = mysqli_fetch_assoc($query_omset);
$total_omset = $row_omset['omset'] ? $row_omset['omset'] : 0;
$tot_warung_buka = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM merchants WHERE is_open=1"));

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HubBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF8C00;
            --secondary-orange: #FFA500;
            --bg-body: #F4F7FE;
            --bg-sidebar: #FFFFFF;
            --text-dark: #1B2559;
            --text-muted: #A3AED0;
            --success: #05CD99;
            --card-bg: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { display: flex; background-color: var(--bg-body); color: var(--text-dark); min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 280px; background: var(--bg-sidebar); padding: 40px 20px;
            display: flex; flex-direction: column; border-right: 1px solid #E9EDF7;
            position: fixed; height: 100vh;
        }
        .brand { font-weight: 800; font-size: 24px; color: var(--primary-orange); text-align: center; margin-bottom: 50px; }
        .nav-links { list-style: none; display: flex; flex-direction: column; height: 100%; }
        .nav-links a {
            display: flex; align-items: center; padding: 16px 20px; margin-bottom: 8px;
            text-decoration: none; color: var(--text-muted); font-weight: 600; border-radius: 15px; transition: 0.3s;
        }
        .nav-links a.active {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: white; box-shadow: 0px 10px 20px rgba(255, 140, 0, 0.2);
        }
        .nav-links a:not(.active):hover { background: #F4F7FE; color: var(--primary-orange); }
        .logout-btn { margin-top: auto; color: #EE5D50 !important; border: 1px solid #FEEFEE; background: #FFF5F5; }

        /* MAIN CONTENT */
        .main-content { margin-left: 280px; flex: 1; padding: 40px; }
        .header-section { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        .header-section h1 { font-size: 28px; font-weight: 800; }
        .header-section span { color: var(--primary-orange); }
        .subtitle { color: var(--text-muted); font-size: 14px; margin-top: 5px; }

        /* STATS GRID */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 30px;
        }
        .stat-card {
            background: var(--card-bg); padding: 25px; border-radius: 20px; position: relative; overflow: hidden;
            border: 1px solid #E9EDF7; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .stat-card h3 { color: var(--text-muted); font-size: 13px; text-transform: uppercase; margin-bottom: 10px; font-weight: 700; }
        .stat-card .value { font-size: 32px; font-weight: 800; color: var(--text-dark); }
        
        .icon-box {
            position: absolute; right: 20px; top: 20px; width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 24px;
        }
        .bg-blue { background: #E9EDFF; color: #4318FF; }
        .bg-green { background: #E6F9F0; color: #05CD99; }
        .bg-orange { background: #FFF3E0; color: var(--primary-orange); }
        .bg-purple { background: #F3E8FF; color: #7B61FF; }

        /* RECENT ORDERS TABLE */
        .card-table {
            background: var(--card-bg); padding: 30px; border-radius: 20px; border: 1px solid #E9EDF7;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .card-table h2 { font-size: 18px; font-weight: 800; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #E9EDF7; }
        td { padding: 15px; border-bottom: 1px solid #F4F7FE; font-size: 14px; font-weight: 600; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; color: white; }
        .st-completed { background: var(--success); }
        .st-pending { background: var(--secondary-orange); }
        .st-processing { background: #4318FF; }
        .st-cancel { background: #EE5D50; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">HubBite</div>
        <nav class="nav-links">
            <a href="dashboard.php" class="active"><span>📊</span> Dashboard</a>
            <a href="management_user.php"><span>👥</span> Kelola User</a>
            <a href="hak_akses.php"><span>🏪</span> Kelola Warung</a>
            <a href="all-product.php"><span>🏪</span> Kelola Product</a>
            <a href="../auth-system/logout.php" class="logout-btn"><span>🚪</span> Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header-section">
            <div>
                <h1>Dashboard <span>Admin</span></h1>
                <p class="subtitle">Sistem Monitoring Pusat HubBite</p>
            </div>
            <div style="font-weight: 700; color: var(--text-muted);">
                 <?= date('d M Y') ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-box bg-orange">💰</div>
                <h3>Total Transaksi</h3>
                <div class="value">Rp<?= number_format($total_omset, 0, ',', '.') ?></div>
            </div>

            <div class="stat-card">
                <div class="icon-box bg-blue">🛒</div>
                <h3>Total Pesanan (Selesai)</h3>
                <div class="value"><?= $tot_penjualan ?></div>
            </div>

            <div class="stat-card">
                <div class="icon-box bg-green">🏪</div>
                <h3>Warung Sedang Buka</h3>
                <div class="value"><?= $tot_warung_buka ?> / <?= $tot_merchant ?></div>
            </div>

            <div class="stat-card">
                <div class="icon-box bg-purple">👨‍🎓</div>
                <h3>Total Akun Pembeli</h3>
                <div class="value"><?= $tot_user ?></div>
            </div>
        </div>

        <div class="card-table">
            <h2>Riwayat Pesanan Terbaru</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Order</th>
                        <th>Pembeli</th>
                        <th>Metode Bayar</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $order = mysqli_query($conn, "SELECT * FROM orders ORDER BY order_id DESC LIMIT 10");
                    
                    if (mysqli_num_rows($order) > 0) {
                        while ($row = mysqli_fetch_assoc($order)) {
                            $st_class = 'st-pending';
                            if ($row['status'] == 'completed') $st_class = 'st-completed';
                            if ($row['status'] == 'processing') $st_class = 'st-processing';
                            if ($row['status'] == 'cancel') $st_class = 'st-cancel';
                            
                            echo "<tr>";
                            echo "<td>#ORD-" . $row['order_id'] . "</td>";
                            echo "<td style='color:var(--text-muted);'>" . htmlspecialchars($row['buyer_email']) . "</td>";
                            echo "<td style='text-transform:uppercase;'>" . $row['payment_method'] . "</td>";
                            echo "<td style='color:var(--primary-orange);'>Rp" . number_format($row['total_amount'], 0, ',', '.') . "</td>";
                            echo "<td><span class='status-badge $st_class'>" . ucfirst($row['status']) . "</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:var(--text-muted);'>Belum ada transaksi</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>