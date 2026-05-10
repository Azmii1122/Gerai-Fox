<?php
include '../../Backend/db_connect.php';

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    mysqli_query($conn, "DELETE FROM customer_orders WHERE id=$id");

    header("Location: index.php");
    exit;
}

$total_orders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM customer_orders")
)['total'];

$total_revenue = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT SUM(total_price) as total FROM customer_orders")
)['total'];

$delivered = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM customer_orders WHERE status='Delivered'")
)['total'];

$processed = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM customer_orders WHERE status='Processed'")
)['total'];

$orders = mysqli_query($conn, "SELECT * FROM customer_orders ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Penjualan Fox</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial;
}

body{
    background:#fdf0e4;
}

.dashboard{
    display:flex;
    min-height:100vh;
}

/* SIDEBAR */

.sidebar{
    width:240px;
    background:white;
    padding:25px;
}

.brand{
    font-size:28px;
    font-weight:bold;
    margin-bottom:40px;
    color:#ff7300;
}

.brand span{
    color:black;
}

.sidebar a{
    display:block;
    text-decoration:none;
    padding:15px;
    margin-bottom:10px;
    border-radius:12px;
    color:black;
    transition:0.3s;
}

.sidebar a.active,
.sidebar a:hover{
    background:#ff7300;
    color:white;
}

/* CONTENT */

.content{
    flex:1;
    padding:30px;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.topbar h1{
    font-size:55px;
}

.topbar p{
    color:#555;
    margin-top:5px;
}

.date-box{
    background:white;
    padding:15px 25px;
    border-radius:15px;
    font-weight:bold;
    color:#ff7300;
}

/* CARDS */

.cards{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    margin-bottom:30px;
}

.card{
    background:white;
    padding:25px;
    border-radius:20px;
}

.card.orange{
    background:linear-gradient(135deg,#ff9b42,#ff7300);
    color:white;
}

.card h3{
    margin-bottom:15px;
}

.card p{
    font-size:45px;
    font-weight:bold;
}

/* TABLE */

.table-section{
    background:white;
    border-radius:25px;
    padding:25px;
}

.section-title{
    display:flex;
    justify-content:space-between;
    margin-bottom:20px;
}

.section-title h2{
    font-size:45px;
}

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#fff3e8;
}

th,td{
    padding:16px;
    text-align:left;
}

tr{
    border-bottom:1px solid #eee;
}

.badge{
    padding:8px 14px;
    border-radius:50px;
    color:white;
    font-size:13px;
}

.delivered{
    background:green;
}

.processed{
    background:orange;
}

.cancelled{
    background:red;
}

.btn-delete{
    background:red;
    color:white;
    padding:10px 15px;
    border-radius:10px;
    text-decoration:none;
}

.btn-delete:hover{
    opacity:0.8;
}

</style>
</head>
<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            Gerai<span>Fox</span>
        </div>

        <a href="#" class="active">Dashboard</a>
        <a href="#">Orders</a>
        <a href="#">Customers</a>
        <a href="#">Reports</a>
        <a href="#">Settings</a>

    </aside>

    <!-- CONTENT -->
    <main class="content">

        <div class="topbar">

            <div>
                <h1>Dashboard Penjualan</h1>
                <p>Gerai Fox - Native PHP</p>
            </div>

            <div class="date-box">
                <?php echo date('d M Y'); ?>
            </div>

        </div>

        <!-- CARDS -->
        <section class="cards">

            <div class="card orange">
                <h3>Total Orders</h3>
                <p><?php echo $total_orders; ?></p>
            </div>

            <div class="card">
                <h3>Total Revenue</h3>
                <p>Rp <?php echo number_format($total_revenue,0,',','.'); ?></p>
            </div>

            <div class="card">
                <h3>Delivered</h3>
                <p><?php echo $delivered; ?></p>
            </div>

            <div class="card">
                <h3>Processed</h3>
                <p><?php echo $processed; ?></p>
            </div>

        </section>

        <!-- TABLE -->
        <section class="table-section">

            <div class="section-title">
                <h2>Customer Order</h2>
                <span>Data yang bisa dihapus jika salah / fiktif</span>
            </div>

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Menu</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php while($row = mysqli_fetch_assoc($orders)) { ?>

                    <tr>

                        <td><?php echo $row['id']; ?></td>

                        <td><?php echo $row['customer_name']; ?></td>

                        <td><?php echo $row['menu_name']; ?></td>

                        <td><?php echo $row['order_date']; ?></td>

                        <td>
                            <span class="badge <?php echo strtolower($row['status']); ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>

                        <td>
                            Rp <?php echo number_format($row['total_price'],0,',','.'); ?>
                        </td>

                        <td>
                            <a class="btn-delete"
                               href="index.php?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Yakin ingin menghapus data ini?')">
                               🗑 Hapus
                            </a>
                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

</body>
</html>