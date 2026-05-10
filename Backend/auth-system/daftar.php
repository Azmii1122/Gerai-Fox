<?php
include 'db_connect.php';

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['fullname']);
    $password = hash('sha256', $_POST['password']); // Enkripsi password menggunakan SHA256// Flag konsumen

    $query = "INSERT INTO users (username, email, password, nama) 
              VALUES ('$username', '$email', '$password', '$nama_lengkap')";
              
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='login.html';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "'); window.location='signup.html';</script>";
    }
}
?>