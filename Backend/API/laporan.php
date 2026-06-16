<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$conn = mysqli_connect("localhost", "root", "", "gerai_fox");
$merchant_id = 1;

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // Hitung Pendapatan
        $query_rev = "SELECT SUM(total_amount) AS total_rev FROM orders WHERE status = 'completed' AND merchant_id = $merchant_id";
        $res_rev = mysqli_query($conn, $query_rev);
        
        if (!$res_rev) {
            echo json_encode(["status" => "error", "message" => "Error cek pesanan: " . mysqli_error($conn)]);
            exit();
        }
        
        $row_rev = mysqli_fetch_assoc($res_rev);
        $revenue = $row_rev['total_rev'] ? $row_rev['total_rev'] : 0;

        // Hitung Pengeluaran
        $query_exp = "SELECT * FROM expenses ORDER BY expense_date DESC, id DESC";
        $res_exp = mysqli_query($conn, $query_exp);
        
        if (!$res_exp) {
            echo json_encode(["status" => "error", "message" => "Tabel Expenses hilang! " . mysqli_error($conn)]);
            exit();
        }

        $expenses_data = [];
        $total_expense = 0;
        while ($row = mysqli_fetch_assoc($res_exp)) {
            $expenses_data[] = $row;
            $total_expense += $row['amount'];
        }

        $profit = $revenue - $total_expense;

        echo json_encode([
            "status" => "success",
            "data" => [
                "revenue" => $revenue,
                "total_expense" => $total_expense,
                "profit" => $profit,
                "expenses_list" => $expenses_data
            ]
        ]);
        break;

    case 'POST':
        $desc = mysqli_real_escape_string($conn, $input['description']);
        $amount = mysqli_real_escape_string($conn, $input['amount']);
        
        $query = "INSERT INTO expenses (description, amount, expense_date) VALUES ('$desc', '$amount', CURDATE())";
        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;
}
mysqli_close($conn);
?>