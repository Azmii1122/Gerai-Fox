<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include '../db_connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    // 1. Ambil Semua Data Warung (Untuk Dashboard)
    case 'get_merchants':
        $stmt = $db->query("SELECT * FROM merchants WHERE is_open = 1");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
        break;

    // 2. Ambil Detail 1 Warung (Untuk Header Katalog)
    case 'get_merchant_detail':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $stmt = $db->prepare("SELECT * FROM merchants WHERE merchant_id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "data" => $stmt->fetch()]);
        break;

    // 3. Ambil Semua Produk (Untuk Best Seller di Dashboard)
    case 'get_all_products':
        $stmt = $db->query("SELECT p.*, m.store_name FROM products p JOIN merchants m ON p.merchant_id = m.merchant_id WHERE p.is_available = 1 LIMIT 12");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
        break;

    // 4. Ambil Produk Berdasarkan Merchant (Untuk Katalog Gerai)
    case 'get_products_by_merchant':
        $merchant_id = isset($_GET['merchant_id']) ? intval($_GET['merchant_id']) : 0;
        $stmt = $db->prepare("SELECT * FROM products WHERE merchant_id = ? AND is_available = 1");
        $stmt->execute([$merchant_id]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
        break;

    // 5. Ambil Isi Keranjang Belanja Pembeli Aktif
    case 'get_cart':
        $buyer_id = isset($_GET['buyer_id']) ? intval($_GET['buyer_id']) : 1;
        $stmt = $db->prepare("
            SELECT c.cart_id, c.buyer_id, c.product_id, c.quantity, c.notes,
                   p.name AS product_name, p.price, p.merchant_id, m.store_name 
            FROM cart_items c 
            JOIN products p ON c.product_id = p.product_id 
            JOIN merchants m ON p.merchant_id = m.merchant_id
            WHERE c.buyer_id = ?
        ");
        $stmt->execute([$buyer_id]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
        break;

    // 6. Tambah Ke Keranjang (Validasi 1 Gerai)
    case 'add_to_cart':
        $input = json_decode(file_get_contents("php://input"), true);
        $buyer_id = 1; // Simulated Auth User ID
        $buyer_email = 'buyer@hubbite.com';
        $product_id = $input['product_id'];
        
        // Cari merchant_id dari produk yang mau ditambah
        $stmtProd = $db->prepare("SELECT merchant_id FROM products WHERE product_id = ?");
        $stmtProd->execute([$product_id]);
        $target_merchant = $stmtProd->fetchColumn();

        // Validasi Keranjang Saat Ini (Apakah dari merchant yang sama?)
        $stmtCheckCart = $db->prepare("
            SELECT p.merchant_id FROM cart_items c 
            JOIN products p ON c.product_id = p.product_id 
            WHERE c.buyer_id = ? LIMIT 1
        ");
        $stmtCheckCart->execute([$buyer_id]);
        $existing_merchant = $stmtCheckCart->fetchColumn();

        if ($existing_merchant && $existing_merchant != $target_merchant) {
            echo json_encode(["status" => "error", "message" => "Kamu hanya bisa memesan dari 1 gerai sekaligus. Kosongkan keranjang terlebih dahulu."]);
            exit();
        }

        // Cek apakah item sudah ada di keranjang
        $stmtCheckItem = $db->prepare("SELECT cart_id, quantity FROM cart_items WHERE buyer_id = ? AND product_id = ?");
        $stmtCheckItem->execute([$buyer_id, $product_id]);
        $existingItem = $stmtCheckItem->fetch();

        if ($existingItem) {
            $newQty = $existingItem['quantity'] + 1;
            $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ?")->execute([$newQty, $existingItem['cart_id']]);
        } else {
            $db->prepare("INSERT INTO cart_items (buyer_id, buyer_email, product_id, quantity) VALUES (?, ?, ?, ?)")->execute([$buyer_id, $buyer_email, $product_id, 1]);
        }
        
        echo json_encode(["status" => "success", "message" => "Berhasil ditambahkan ke keranjang!"]);
        break;

    // 7. Ubah Kuantitas atau Hapus Item Keranjang
    case 'update_cart':
        $input = json_decode(file_get_contents("php://input"), true);
        $cart_id = $input['cart_id'];
        $qty = $input['quantity'];

        if ($qty <= 0) {
            $db->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cart_id]);
        } else {
            $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ?")->execute([$qty, $cart_id]);
        }
        echo json_encode(["status" => "success"]);
        break;

    // 8. Proses Checkout
    case 'checkout':
        $input = json_decode(file_get_contents("php://input"), true);
        $buyer_id = 1;
        $order_mode = $input['order_mode'];
        $payment_method = $input['payment_method'];

        try {
            $db->beginTransaction();

            // Ambil item keranjang
            $stmtCart = $db->prepare("SELECT c.*, p.price, p.merchant_id FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.buyer_id = ?");
            $stmtCart->execute([$buyer_id]);
            $items = $stmtCart->fetchAll();

            if (count($items) == 0) throw new Exception("Keranjang kosong");

            $subtotal = 0;
            $merchant_id = $items[0]['merchant_id'];
            foreach ($items as $item) $subtotal += ($item['price'] * $item['quantity']);

            $total_amount = $subtotal + ($order_mode === 'dine_in_qr' ? 0 : 4000); // 4000 ongkir

            // Insert Table Orders
            $stmtOrder = $db->prepare("INSERT INTO orders (buyer_id, buyer_email, merchant_id, order_mode, payment_method, status, total_amount) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmtOrder->execute([$buyer_id, $items[0]['buyer_email'], $merchant_id, $order_mode, $payment_method, $total_amount]);
            $order_id = $db->lastInsertId();

            // Insert Table Order_items
            $stmtItem = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, notes) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $item['notes']]);
            }

            // Hapus isi keranjang
            $db->prepare("DELETE FROM cart_items WHERE buyer_id = ?")->execute([$buyer_id]);

            $db->commit();
            echo json_encode(["status" => "success", "message" => "Pesanan #$order_id berhasil dibuat!"]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 9. Kosongkan Keranjang
    case 'clear_cart':
        $buyer_id = 1;
        $db->prepare("DELETE FROM cart_items WHERE buyer_id = ?")->execute([$buyer_id]);
        echo json_encode(["status" => "success"]);
        break;
}
?>