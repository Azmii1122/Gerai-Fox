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
        <a href="dashboard.php">Dashboard</a> | 
        <a href="kelola_produk.php">Kelola Produk</a> | 
        <a href="kelola_user.php">Kelola User</a> | 
        <a href="../logout.php">Logout</a>
    </nav>
    <hr>
    <h3>Ringkasan Toko</h3>
    <!-- Query dasar untuk menghitung total produk & user -->
    <?php
        $tot_user = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='buyer'"));
    ?>
    <p>Total Konsumen: <?= $tot_user ?></p>
</body>
</html>