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
        if (isset($_GET['user_id'])) {
            $user_id = (int) $_GET['user_id'];
            $query = "SELECT * FROM users WHERE id=$user_id";
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

        if (!$input || !isset($input['email'], $input['username'], $input['password'], $input['role'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
            exit();
        }

        $email = mysqli_real_escape_string($conn, $input['email']);
        $username = mysqli_real_escape_string($conn, $input['username']);
        $password = hash('sha256', $input['password']);
        $role = isset($input['role']) ? mysqli_real_escape_string($conn, $input['role']) : 'buyer';

        $query = "INSERT INTO users (email, username, password, role) VALUES ('$email', '$username', '$password', '$role')";

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

        $id = (int) $input['user_id'];
        $email = mysqli_real_escape_string($conn, $input['email']); // Tambahkan ini
        $username = mysqli_real_escape_string($conn, $input['username']);
        $role = mysqli_real_escape_string($conn, $input['role']);

        // Update semua kolom termasuk email
        $query = "UPDATE users SET email='$email', username='$username', role='$role' WHERE user_id=$id";

        if (!empty($input['password'])) {
            $pass = hash('sha256', $input['password']);
            $query = "UPDATE users SET email='$email', username='$username', password='$pass', role='$role' WHERE user_id=$id";
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
        if (!$input || !isset($input['user_id'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID user wajib disertakan"]);
            exit();
        }

        $id = (int) $input['user_id'];
        $query = "DELETE FROM users WHERE user_id=$id";
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