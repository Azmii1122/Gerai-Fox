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

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $query = "SELECT * FROM users WHERE id=$id";
            $result = mysqli_query($conn, $query);

            if ($row = mysqli_fetch_assoc($result)) {
                echo json_encode(["status" => "success", "data" => $row]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "User tidak ditemukan"]);
            }
        } else {
            $query = "SELECT * FROM users";
            $result = mysqli_query($conn, $query);
            $users = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $users]);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['email'], $input['nama'], $input['username'], $input['password'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
            exit();
        }

        $email = mysqli_real_escape_string($conn, $input['email']);
        $nama = mysqli_real_escape_string($conn, $input['nama']);
        $username = mysqli_real_escape_string($conn, $input['username']);
        $password = hash('sha256', $input['password']);
        $role = isset($input['role']) ? mysqli_real_escape_string($conn, $input['role']) : 'buyer';

        $query = "INSERT INTO users (email, nama, username, password, role) VALUES ('$email', '$nama', '$username', '$password', '$role')";

        if (mysqli_query($conn, $query)) {
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "User berhasil ditambahkan"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal: " . mysqli_error($conn)]);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID wajib ada untuk update"]);
            exit();
        }

        $id = (int) $input['id'];
        $email = mysqli_real_escape_string($conn, $input['email']); // Tambahkan ini
        $nama = mysqli_real_escape_string($conn, $input['nama']);
        $username = mysqli_real_escape_string($conn, $input['username']);
        $role = mysqli_real_escape_string($conn, $input['role']);

        // Update semua kolom termasuk email
        $query = "UPDATE users SET email='$email', nama='$nama', username='$username', role='$role' WHERE id=$id";

        if (!empty($input['password'])) {
            $pass = hash('sha256', $input['password']);
            $query = "UPDATE users SET email='$email', nama='$nama', username='$username', password='$pass', role='$role' WHERE id=$id";
        }

        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success", "message" => "User berhasil diperbarui"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal memperbarui user"]);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID user wajib disertakan"]);
            exit();
        }

        $id = (int) $input['id'];
        $query = "DELETE FROM users WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success", "message" => "User berhasil dihapus"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal menghapus user"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan"]);
        break;
}
mysqli_close($conn);
?>