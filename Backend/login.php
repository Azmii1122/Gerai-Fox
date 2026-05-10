<?php
include 'db_connect.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']); // Enkripsi password menggunakan SHA256

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        session_start();
        $_SESSION['username'] = $username;
        echo "<script>alert('Login berhasil!'); window.location='home.html';</script>";
    } else {
        echo "<script>alert('Login gagal! Periksa username dan password Anda.');</script>";
    }
}

?>