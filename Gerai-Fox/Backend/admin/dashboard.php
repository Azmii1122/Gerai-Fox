<?php
session_start();
if($_SESSION['role'] != 'admin') { header("Location: ../auth/login.php"); exit; }

include '../db_connect.php';
?>
<!DOCTYPE html>
<html>
<body>
    <h2>Selamat Datang, Admin <?= $_SESSION['username'] ?></h2>
    <nav>
        <a href="dashboard.php">Dashboard</a> |  | 
        <a href="management_user.php">Kelola User</a> | 
        <a href="../auth-system/logout.php">Logout</a>
    </nav>
    <hr>
    <h3>Ringkasan Akun User</h3>
    <!-- Query untuk semua akun yang udah terdaftar -->
    <?php
        $tot_user = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='buyer'"));
        $tot_merchant = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='merchant'"));
        $tot_akun = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users"));
    ?>
    <p>Total Akun pembeli: <?= $tot_user ?></p>
    <p>Total Akun merchant: <?= $tot_merchant ?></p>
    <p>Total Seluruh Akun: <?= $tot_akun ?></p>
</body>
</html>