<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../../Frontend/auth/login.html"); 
    exit; 
}
include '../db_connect.php';

// Data Query
$tot_user = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='buyer'"));
$tot_merchant = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='merchant'"));
$tot_akun = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gerai.Fox</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF8C00;
            --secondary-orange: #FFA500;
            --bg-body: #F4F7FE;
            --bg-sidebar: #FFFFFF;
            --text-dark: #1B2559;
            --text-muted: #A3AED0;
            --glass-bg: rgba(255, 255, 255, 0.7);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            display: flex;
            background-color: var(--bg-body);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* SIDEBAR (Mirip SMKSPACE) */
        .sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #E9EDF7;
        }

        .brand {
            font-weight: 800;
            font-size: 24px;
            color: var(--primary-orange);
            text-align: center;
            margin-bottom: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            margin-bottom: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            border-radius: 15px;
            transition: 0.3s;
        }

        .nav-links a.active {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: white;
            box-shadow: 0px 10px 20px rgba(255, 140, 0, 0.2);
        }

        .nav-links a:not(.active):hover {
            background: #F4F7FE;
            color: var(--primary-orange);
        }

        .logout-btn {
            margin-top: auto;
            color: #EE5D50 !important;
            border: 1px solid #FEEFEE;
            background: #FFF5F5;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .header-section {
            margin-bottom: 40px;
        }

        .header-section h1 {
            font-size: 28px;
            font-weight: 800;
        }

        .header-section span {
            color: var(--primary-orange);
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 5px;
        }

        /* CARDS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            border: 1px solid #E9EDF7;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: var(--text-muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .stat-card .value {
            font-size: 42px;
            font-weight: 800;
            color: var(--text-dark);
            position: relative;
            z-index: 2;
        }

        /* Decorative Pulse Line (mirip referensi) */
        .stat-card::after {
            content: " ";
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 40'%3E%3Cpath d='M0 30 Q 25 10 50 30 T 100 30' fill='none' stroke='%23FF8C00' stroke-width='2' opacity='0.1'/%3E%3C/svg%3E") no-repeat center;
            opacity: 0.5;
        }

        /* Variants */
        .card-buyer { border-left: 6px solid #4318FF; }
        .card-merchant { border-left: 6px solid #05CD99; }
        .card-total { 
            background: linear-gradient(135deg, #FF8C00, #FFB800); 
            border: none;
        }
        .card-total h3, .card-total .value { color: white; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <span>🦊</span> GERAI.FOX
        </div>
        
        <nav class="nav-links">
            <a href="dashboard.php" class="active">
                <span style="margin-right: 15px;">📊</span> Dashboard
            </a>
            <a href="management_user.php">
                <span style="margin-right: 15px;">👥</span> Kelola User
            </a>
            <a href="../auth-system/logout.php" class="logout-btn">
                <span style="margin-right: 15px;">🚪</span> Keluar
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header-section">
            <h1>Selamat Datang, <span><?= htmlspecialchars($_SESSION['username']) ?></span> 👋</h1>
            <p class="subtitle">SISTEM MONITORING & MANAJEMEN GERAI.FOX</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card card-buyer">
                <h3>Total Akun Pembeli</h3>
                <div class="value"><?= $tot_user ?></div>
            </div>

            <div class="stat-card card-merchant">
                <h3>Total Akun Merchant</h3>
                <div class="value"><?= $tot_merchant ?></div>
            </div>

            <div class="stat-card card-total">
                <h3>Total Seluruh Akun</h3>
                <div class="value"><?= $tot_akun ?></div>
            </div>
        </div>

        <div style="margin-top: 50px; padding: 20px; background: white; border-radius: 20px; border: 1px dashed #E9EDF7; text-align: center; color: var(--text-muted);">
            Progres sistem admin sedang berjalan...
        </div>
    </main>

</body>
</html>