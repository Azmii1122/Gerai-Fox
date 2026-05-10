<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method){
    case 'GET':
        if (isset($_GET['id'])) {
            $email = $_GET['email'];
            $query = "SELECT email, nama, username, role FROM users WHERE email='$email'";
            $result = mysqli_query($conn, $query);

            if ($row = mysqli_fetch_assoc($result)){
                echo json_encode(["status" => "success", "data" => $row]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "User tidak ditemukan"]);
            }
        } else {
            $query = "SELECT email, nama, username, role FROM users";
            $result = mysqli_query($conn, $query);
            $users = [];

            while ($row = mysqli_fetch_assoc($result)){
                $users[] = $row;
            }

            echo json_encode(["status" => "success", "data" => $users]);
        }
        break;
}
?>