<?php
session_start();
include '../db_connect.php'; // Sesuaikan path ke file database Anda

// PROTECTION: Pastikan hanya Admin yang bisa masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Akses Ditolak. Khusus Admin!'); window.location.href='../auth/login.html';</script>";
    exit;
}

// ==========================================
// 1. PROSES AKSI CRUD (HANDLE BY FORM SUBMIT)
// ==========================================

// PROSES EDIT / UPDATE PRODUCT BY ADMIN
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $product_id = (int)$_POST['product_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = (float)$_POST['price'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image = mysqli_real_escape_string($conn, $_POST['image']); // Menerima URL / Base64

    $q_update = "UPDATE products SET name='$name', price=$price, category='$category', description='$description', image='$image' WHERE product_id=$product_id";
    if (mysqli_query($conn, $q_update)) {
        echo "<script>alert('Produk berhasil dimoderasi oleh Admin!'); window.location.href='admin_products.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal update: " . mysqli_error($conn) . "');</script>";
    }
}

// PROSES DELETE PRODUCT BY ADMIN
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $q_delete = "DELETE FROM products WHERE product_id = $delete_id";
    if (mysqli_query($conn, $q_delete)) {
        echo "<script>alert('Produk berhasil dihapus dari sistem!'); window.location.href='admin_products.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menghapus: " . mysqli_error($conn) . "');</script>";
    }
}

// ==========================================
// 2. AMBIL DATA PRODUK GLOBAL (JOIN MERCHANTS)
// ==========================================
$query = "
    SELECT p.*, m.store_name as store_name 
    FROM products p
    LEFT JOIN merchants m ON p.merchant_id = m.merchant_id
    ORDER BY p.product_id DESC
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Semua Produk</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --fox-50: #fff3e0;
            --fox-500: #f97316;
            --fox-600: #ea580c;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { color: var(--slate-800); background-color: var(--slate-50); display: flex; min-h: 100vh; }

        /* SIDEBAR COMPONENT */
        .sidebar { width: 256px; background-color: #ffffff; border-right: 1px solid var(--slate-200); flex-direction: column; position: fixed; height: 100%; display: flex; padding: 2rem 1.5rem; }
        .sidebar-brand h1 { font-size: 1.5rem; font-weight: 700; color: var(--slate-900); margin-bottom: 2.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .sidebar-brand i { color: var(--fox-500); }
        .sidebar-nav { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; color: var(--slate-500); text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .nav-link:hover { background-color: var(--slate-50); color: var(--slate-900); }
        .nav-link.active { background-color: var(--fox-50); color: var(--fox-600); font-weight: 700; }
        .nav-link.logout { color: #ef4444; margin-top: 2.5rem; }
        .nav-link.logout:hover { background-color: #fef2f2; }

        /* MAIN APP WRAPPER */
        .main-wrapper { flex: 1; margin-left: 256px; padding: 2rem; }
        
        /* HEADER COMPONENT */
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: #ffffff; padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--slate-200); }
        .top-header h2 { font-size: 1.5rem; font-weight: 700; color: var(--slate-900); }
        .top-header p { font-size: 0.875rem; color: var(--slate-400); margin-top: 0.25rem; }
        .badge-admin { background-color: var(--fox-500); color: #ffffff; font-size: 0.875rem; padding: 0.5rem 1rem; border-radius: 0.75rem; font-weight: 700; }

        /* TABLE CONTAINER */
        .table-container { background: #ffffff; border-radius: 1rem; border: 1px solid var(--slate-200); overflow: hidden; }
        .table-title { padding: 1.5rem; border-bottom: 1px solid var(--slate-100); background-color: #ffffff; font-size: 1.125rem; font-weight: 700; color: var(--slate-800); }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; }
        th { background-color: var(--slate-50); color: var(--slate-700); padding: 1rem; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; tracking: 0.05em; border-bottom: 1px solid var(--slate-200); }
        td { padding: 1rem; border-bottom: 1px solid var(--slate-100); color: var(--slate-800); vertical-align: middle; }
        tr:hover { background-color: var(--slate-50); }
        
        .img-thumb { w-12; height: 3rem; width: 3rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid var(--slate-200); }
        .badge-merchant { background-color: var(--fox-50); color: var(--fox-600); padding: 0.25rem 0.625rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; border: 1px solid #ffedd5; }
        .text-desc { color: var(--slate-400); font-size: 0.75rem; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ACTION BUTTONS */
        .btn-group { display: flex; gap: 0.5rem; }
        .btn-action { padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.5rem; border: none; cursor: pointer; text-decoration: none; transition: 0.2s; color: white; }
        .btn-edit { background-color: #f59e0b; }
        .btn-edit:hover { background-color: #d97706; }
        .btn-delete { background-color: #ef4444; }
        .btn-delete:hover { background-color: #dc2626; }

        /* MODAL WINDOW STYLES */
        .modal-overlay { position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay.hidden { display: none; }
        .modal-card { background: white; border-radius: 1rem; width: 100%; max-width: 400px; overflow: hidden; border: 1px solid var(--slate-200); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
        .modal-header { padding: 1.25rem; border-bottom: 1px solid var(--slate-100); display: flex; justify-content: space-between; align-items: center; background: var(--slate-50); }
        .modal-header h3 { font-size: 1.125rem; font-weight: 700; color: var(--slate-800); }
        .modal-close { background: none; border: none; font-size: 1.25rem; color: var(--slate-400); cursor: pointer; }
        .modal-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
        
        .form-group { display: flex; flex-direction: column; gap: 0.375rem; }
        .form-label { font-size: 0.75rem; font-weight: 700; color: var(--slate-500); text-transform: uppercase; }
        .form-input { width: 100%; padding: 0.625rem 1rem; background-color: var(--slate-50); border: 1px solid var(--slate-200); border-radius: 0.75rem; font-size: 0.875rem; outline: none; transition: 0.2s; }
        .form-input:focus { border-color: var(--fox-500); background-color: white; }
        textarea.form-input { resize: none; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--slate-100); margin-top: 0.5rem; }
        .btn-modal { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.75rem; cursor: pointer; border: none; }
        .btn-cancel { background-color: var(--slate-100); color: var(--slate-700); }
        .btn-submit { background-color: var(--fox-500); color: white; }
        .btn-submit:hover { background-color: var(--fox-600); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <h1><i class="fa-solid fa-shield-halved"></i> HubBite Admin</h1>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>
            <a href="management_user.php" class="nav-link">
                <i class="fa-solid fa-users"></i> Kelola User
            </a>
            <a href="hak_akses.php" class="nav-link">
                <i class="fa-solid fa-store"></i> Hak Akses Warung
            </a>
            <a href="all-product.php" class="nav-link active">
                <i class="fa-solid fa-utensils"></i> Semua Produk
            </a>
            <a href="../../Backend/auth-system/logout.php" class="nav-link logout">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>

    <div class="main-wrapper">
        
        <header class="top-header">
            <div>
                <h2>Manajemen Produk Global</h2>
                <p>Monitoring, edit, dan moderasi seluruh menu makanan dari semua mitra warung</p>
            </div>
            <div class="badge-admin">Mode Admin</div>
        </header>

        <div class="table-container">
            <div class="table-title">Daftar Menu Seluruh Warung</div>
            <table>
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nama Menu</th>
                        <th>Pemilik Warung</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Deskripsi</th>
                        <th style="text-align: center;">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--slate-400); padding: 2rem;">Belum ada produk apa pun di database.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): 
                            $imgSrc = !empty($row['image']) ? $row['image'] : 'https://placehold.co/100x100/e2e8f0/94a3b8?text=No+Img';
                            $hargaRp = "Rp " . number_format($row['price'], 0, ',', '.');
                        ?>
                            <tr>
                                <td>
                                    <img src="<?= $imgSrc ?>" alt="Menu" class="img-thumb">
                                </td>
                                <td style="font-weight: 600; color: var(--slate-900);"><?= htmlspecialchars($row['name']) ?></td>
                                <td>
                                    <span class="badge-merchant">
                                        <?= htmlspecialchars($row['store_name'] ?? 'Tanpa Warung') ?>
                                    </span>
                                </td>
                                <td style="color: var(--slate-500); font-size: 0.75rem; font-weight: 600;"><?= htmlspecialchars($row['category']) ?></td>
                                <td style="font-weight: 700; color: var(--slate-700);"><?= $hargaRp ?></td>
                                <td><p class="text-desc"><?= htmlspecialchars($row['description']) ?></p></td>
                                <td>
                                    <div class="btn-group" style="justify-content: center;">
                                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)" class="btn-action btn-edit">
                                            <i class="fa-solid fa-edit"></i> Edit
                                        </button>
                                        <a href="admin_products.php?delete_id=<?= $row['product_id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini dari sistem?')" class="btn-action btn-delete">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal-overlay hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Moderasi / Edit Menu</h3>
                <button onclick="closeEditModal()" class="modal-close">✕</button>
            </div>
            <form action="admin_products.php" method="POST" class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="product_id" id="modal-id">
                <input type="hidden" name="image" id="modal-image">

                <div class="form-group">
                    <label class="form-label">Nama Menu</label>
                    <input type="text" name="name" id="modal-name" required class="form-input">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" name="price" id="modal-price" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="category" id="modal-category" required class="form-input">
                            <option value="Makanan">Makanan</option>
                            <option value="Minuman">Minuman</option>
                            <option value="Cemilan">Cemilan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi Menu</label>
                    <textarea name="description" id="modal-description" rows="3" class="form-input"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Ganti Foto Menu (Opsional)</label>
                    <input type="file" id="modal-file-input" accept="image/*" style="font-size: 0.75rem; cursor: pointer;">
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn-modal btn-cancel">Batal</button>
                    <button type="submit" class="btn-modal btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(productData) {
            document.getElementById('modal-id').value = productData.product_id;
            document.getElementById('modal-name').value = productData.name;
            document.getElementById('modal-price').value = productData.price;
            document.getElementById('modal-category').value = productData.category;
            document.getElementById('modal-description').value = productData.description || '';
            document.getElementById('modal-image').value = productData.image || '';
            document.getElementById('modal-file-input').value = ""; 

            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Otomatis ubah file upload jadi Base64
        document.getElementById('modal-file-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('modal-image').value = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>