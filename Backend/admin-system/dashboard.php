<?php
session_start();
if($_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit; }

include '../db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
    
</body>
</html>