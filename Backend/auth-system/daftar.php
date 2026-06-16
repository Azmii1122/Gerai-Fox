<?php
include '../db_connect.php';

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = hash('sha256', $_POST['password']);
    $role = 'buyer';

    $query = "INSERT INTO users (username, email, password, role) 
              VALUES ('$username', '$email', '$password', '$role')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='../../Frontend/auth/login.html';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "'); window.location='../../Frontend/auth/signup.html';</script>";
    }
}
?>