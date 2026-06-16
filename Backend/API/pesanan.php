<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(0); // Matikan pesan error bawaan PHP
$conn = mysqli_connect("localhost", "root", "", "gerai_fox");

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database tidak terhubung"]);
    exit();
}

$merchant_id = 1; 
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        $query = "
            SELECT 
                o.order_id as id, 
                COALESCE(u.username, o.buyer_email) as customer_name, 
                (SELECT COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), MAX(oi.notes)) 
                 FROM order_items oi 
                 LEFT JOIN products p ON oi.product_id = p.product_id 
                 WHERE oi.order_id = o.order_id) as menu_name, 
                DATE(o.created_at) as order_date, 
                o.status as db_status, 
                o.total_amount as total_price 
            FROM orders o 
            LEFT JOIN users u ON o.buyer_id = u.user_id 
            WHERE o.merchant_id = $merchant_id 
            ORDER BY o.order_id DESC
        ";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            echo json_encode(["status" => "error", "message" => "Error GET: " . mysqli_error($conn)]);
            exit();
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $ui_status = 'Processed'; 
            if($row['db_status'] == 'completed') $ui_status = 'Delivered';
            if($row['db_status'] == 'cancel') $ui_status = 'Cancelled';
            
            $row['status'] = $ui_status; 
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'POST':
        $customer = mysqli_real_escape_string($conn, $input['customer_name']);
        $menu = mysqli_real_escape_string($conn, $input['menu_name']);
        $harga = mysqli_real_escape_string($conn, $input['total_price']);
        
        // PERBAIKAN: Tambahkan payment_method='cash' karena DB wajib diisi (NOT NULL)
        $q_order = "INSERT INTO orders (merchant_id, buyer_email, order_mode, payment_method, status, total_amount) 
                    VALUES ($merchant_id, '$customer', 'takeaway', 'cash', 'pending', '$harga')";
        
        if (mysqli_query($conn, $q_order)) {
            $new_order_id = mysqli_insert_id($conn); 
            
            // PERBAIKAN: Tambahkan quantity=1 karena DB wajib diisi (NOT NULL)
            $q_item = "INSERT INTO order_items (order_id, quantity, notes) VALUES ($new_order_id, 1, '$menu')";
            mysqli_query($conn, $q_item);
            
            echo json_encode(["status" => "success", "message" => "Pesanan baru berhasil masuk!"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;

    case 'PUT':
        $id = mysqli_real_escape_string($conn, $input['id']);
        // Langsung terima status murni ('cooking', 'ready', 'completed', 'cancel')
        $db_status = mysqli_real_escape_string($conn, $input['status']); 

        $query = "UPDATE orders SET status = '$db_status' WHERE order_id = '$id'";
        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;
        break;
}
mysqli_close($conn);
?>