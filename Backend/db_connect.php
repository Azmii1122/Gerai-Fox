<?php
$server="localhost";
$username="root";
$password="";
$dbname="db_gerai.fox";

if (!$conn = mysqli_connect($server, $username, $password, $dbname)) {
    die ("Koneksi gagal: " . mysqli_connect_error());
}
?>
