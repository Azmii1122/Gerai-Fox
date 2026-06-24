<?php
session_start();
ob_clean();
header("Content-Type: application/json");

include '../db_connect.php'; 

function sendError($message) {
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

if (!isset($_SESSION['user_id'])) sendError("Unauthorized");

$user_id = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // KITA HAPUS cart_id DARI PENCARIAN AGAR TIDAK ERROR NAMA KOLOM
    $result = mysqli_query($conn, "SELECT c.quantity, p.name, p.price 
                                   FROM cart_items c 
                                   JOIN products p ON c.product_id = p.product_id 
                                   WHERE c.user_id = $user_id");
    if (!$result) sendError(mysqli_error($conn));
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    echo json_encode(["status" => "success", "data" => $data]);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $p_id = (int)($input['product_id'] ?? 0);

    if ($p_id === 0) sendError("Product ID hilang");

    // CEK BARANG TANPA MEMANGGIL NAMA ID KERANJANG
    $cek = mysqli_query($conn, "SELECT quantity FROM cart_items WHERE user_id = $user_id AND product_id = $p_id");
    
    if ($cek && mysqli_num_rows($cek) > 0) {
        // UPDATE BERDASARKAN USER_ID DAN PRODUCT_ID (Ini sangat aman!)
        $update = mysqli_query($conn, "UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $p_id");
        if (!$update) sendError(mysqli_error($conn));
    } else {
        // INSERT DATA BARU
        $insert = mysqli_query($conn, "INSERT INTO cart_items (user_id, product_id, quantity) VALUES ($user_id, $p_id, 1)");
        if (!$insert) sendError(mysqli_error($conn));
    }
    
    echo json_encode(["status" => "success"]);
}
exit;
?>