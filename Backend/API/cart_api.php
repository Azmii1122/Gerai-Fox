<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

include '../db_connect.php'; 

if ((getallheaders()['X-API-KEY'] ?? '') !== '12345') {
    echo json_encode(["status" => "error", "message" => "API Key Invalid"]); exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]); exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT c.cart_id, c.quantity, p.product_id, p.name, p.price 
              FROM cart_items c 
              JOIN products p ON c.product_id = p.product_id 
              WHERE c.user_id = $user_id";
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = (int)$input['product_id'];
    $merchant_id = (int)$input['merchant_id'];

    $cek = mysqli_query($conn, "SELECT cart_id, quantity FROM cart_items WHERE user_id = $user_id AND product_id = $product_id");
    if (mysqli_num_rows($cek) > 0) {
        $row = mysqli_fetch_assoc($cek);
        $new_qty = $row['quantity'] + 1;
        mysqli_query($conn, "UPDATE cart_items SET quantity = $new_qty WHERE cart_id = " . $row['cart_id']);
    } else {
        mysqli_query($conn, "INSERT INTO cart_items (user_id, merchant_id, product_id, quantity) VALUES ($user_id, $merchant_id, $product_id, 1)");
    }
    echo json_encode(["status" => "success"]);
}
mysqli_close($conn);
?>