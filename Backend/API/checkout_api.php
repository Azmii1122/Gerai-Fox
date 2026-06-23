<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

include '../db_connect.php';

if ((getallheaders()['X-API-KEY'] ?? '') !== '12345') {
    echo json_encode(["status" => "error", "message" => "API Key Invalid"]); exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]); exit;
}

$user_id = (int)$_SESSION['user_id'];
$buyer_email = mysqli_real_escape_string($conn, $_SESSION['email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q_cart = mysqli_query($conn, "SELECT c.product_id, c.quantity, c.merchant_id, p.price FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = $user_id");
    
    if (mysqli_num_rows($q_cart) === 0) {
        echo json_encode(["status" => "error", "message" => "Keranjang kosong"]); exit;
    }

    $total_amount = 0; $merchant_id = 0; $items = [];
    while ($row = mysqli_fetch_assoc($q_cart)) {
        $merchant_id = $row['merchant_id'];
        $total_amount += ($row['price'] * $row['quantity']);
        $items[] = $row;
    }

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "INSERT INTO orders (merchant_id, buyer_id, buyer_email, order_mode, payment_method, status, total_amount) VALUES ($merchant_id, $user_id, '$buyer_email', 'takeaway', 'cash', 'pending', $total_amount)");
        $order_id = mysqli_insert_id($conn);

        foreach ($items as $item) {
            mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity) VALUES ($order_id, {$item['product_id']}, {$item['quantity']})");
        }
        mysqli_query($conn, "DELETE FROM cart_items WHERE user_id = $user_id");
        mysqli_commit($conn);
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
mysqli_close($conn);
?>