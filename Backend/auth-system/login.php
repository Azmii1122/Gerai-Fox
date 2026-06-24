<?php
session_start();
include '../db_connect.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']); // Enkripsi password menggunakan SHA256

    $query = "SELECT * FROM users WHERE email='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['nama'] = $row['nama'];
        $_SESSION['role'] = $row['role'];

        if ($row['role'] == 'admin') {
            header("Location: ../admin/dashboard.php");
        } else if ($row['role'] == 'seller') {
            header("Location: ../../Frontend/merchant/dashboard.html");
        } else {
            header("Location: ../../Frontend/buyer/gabungan.html");
        }
    } else {
        header("Location: ../../Frontend/auth/login.html");
        echo "<script>alert('Login gagal: Periksa username dan password');</script>";
    }
}

?>