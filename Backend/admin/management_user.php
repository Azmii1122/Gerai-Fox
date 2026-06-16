<?php
session_start();
// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../Frontend/auth/login.html");
    exit;
}

include '../db_connect.php';

$message = "";
$messageType = "";

// ==========================================
// 1. HANDLE DELETE USER
// ==========================================
if (isset($_POST['delete_user'])) {
    $id = (int) $_POST['delete_id'];
    $query = "DELETE FROM users WHERE user_id = $id";
    if (mysqli_query($conn, $query)) {
        $message = "User berhasil dihapus!";
        $messageType = "success";
    } else {
        $message = "Gagal menghapus user: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// ==========================================
// 2. HANDLE ADD & EDIT USER
// ==========================================
if (isset($_POST['save_user'])) {
    $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = $_POST['password'];

    if ($id > 0) {
        // UPDATE
        if (!empty($password)) {
            $pass = hash('sha256', $password);
            $query = "UPDATE users SET email='$email', username='$username', password='$pass', role='$role' WHERE user_id=$id";
        } else {
            $query = "UPDATE users SET email='$email', username='$username', role='$role' WHERE user_id=$id";
        }
    } else {
        // INSERT
        $pass = hash('sha256', $password);
        $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$pass', '$role')";
    }
    
    if (mysqli_query($conn, $query)) {
        $message = "Data berhasil disimpan!";
        $messageType = "success";
    } else {
        $message = "Gagal menyimpan data: " . mysqli_error($conn);
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Gerai.Fox Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF8C00;
            --secondary-orange: #FFA500;
            --bg-body: #F4F7FE;
            --bg-sidebar: #FFFFFF;
            --text-dark: #1B2559;
            --text-muted: #A3AED0;
            --danger: #EE5D50;
            --success: #05CD99;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            display: flex;
            background-color: var(--bg-body);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #E9EDF7;
            position: fixed;
            height: 100vh;
        }

        .brand {
            font-weight: 800;
            font-size: 24px;
            color: var(--primary-orange);
            text-align: center;
            margin-bottom: 50px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            margin-bottom: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            border-radius: 15px;
            transition: 0.3s;
        }

        .nav-links a.active {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: white;
            box-shadow: 0px 10px 20px rgba(255, 140, 0, 0.2);
        }

        .nav-links a:hover:not(.active) {
            background: #F4F7FE;
            color: var(--primary-orange);
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 40px;
        }

        .header-section {
            margin-bottom: 30px;
        }

        .header-section h1 {
            font-size: 28px;
            font-weight: 800;
        }

        /* FORM CONTAINER */
        .glass-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            border: 1px solid #E9EDF7;
            box-shadow: 0px 20px 40px rgba(0, 0, 0, 0.02);
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
            border-left: 4px solid var(--primary-orange);
            padding-left: 15px;
        }

        .grid-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-dark);
        }

        input,
        select {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #E9EDF7;
            background: #F4F7FE;
            outline: none;
            transition: 0.3s;
        }

        input:focus,
        select:focus {
            border-color: var(--primary-orange);
            background: white;
        }

        /* BUTTONS */
        .btn {
            padding: 12px 25px;
            border-radius: 12px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-primary {
            background: var(--primary-orange);
            color: white;
            margin-top: 20px;
        }

        .btn-primary:hover {
            background: #e67e00;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #F4F7FE;
            color: var(--text-muted);
            margin-left: 10px;
        }

        /* TABLE */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            text-align: left;
            padding: 15px;
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid #E9EDF7;
        }

        td {
            padding: 18px 15px;
            border-bottom: 1px solid #F4F7FE;
            font-size: 14px;
        }

        tr:hover {
            background-color: #FAFBFF;
        }

        .role-badge {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .badge-buyer {
            background: #E9EDFF;
            color: #4318FF;
        }

        .badge-seller {
            background: #E6F9F0;
            color: #05CD99;
        }

        .action-btn {
            padding: 8px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            margin-right: 5px;
        }

        .edit-btn {
            background: #E9EDFF;
            color: #4318FF;
        }

        .delete-btn {
            background: #FEEFEE;
            color: var(--danger);
        }

        /* ALERTS */
        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            animation: slideIn 0.5s ease;
        }

        .alert-success {
            background: #E6F9F0;
            color: #05CD99;
        }

        .alert-error {
            background: #FEEFEE;
            color: var(--danger);
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="brand">🦊 GERAI.FOX</div>
        <nav class="nav-links">
            <a href="dashboard.php"><span>📊</span> Dashboard</a>
            <a href="management_user.php" class="active"><span>👥</span> Kelola User</a>
            <a href="../auth-system/logout.php" style="margin-top:auto; color: var(--danger);"><span>🚪</span> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header-section">
            <h1>Manajemen <span>User</span></h1>
            <p style="color: var(--text-muted); font-size: 14px;">Tambah, edit, atau hapus akses pengguna sistem.</p>
        </div>

        <div class="glass-card">
            <h3 class="form-title" id="formtitle">Tambah User Baru</h3>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?= $messageType == 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <div class="grid-inputs">
                    <div class="input-group">
                        <label>Email</label>
                        <input type="email" id="email" name="email" placeholder="email@contoh.com" required>
                    </div>
                    <div class="input-group">
                        <label>Username</label>
                        <input type="text" id="username" name="username" placeholder="username123" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="input-group">
                        <label>Role</label>
                        <select id="role" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="buyer">Pembeli (Buyer)</option>
                            <option value="seller">Pedagang (Merchant)</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="save_user" id="btnSubmit" class="btn btn-primary">Simpan User</button>
                <button type="button" onclick="cancelEdit()" id="btnCancel" class="btn btn-cancel" style="display:none;">Batal</button>
            </form>
        </div>

        <div class="glass-card">
            <h3 class="form-title">Daftar Pengguna Terdaftar</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Info Pengguna</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php
                        // Memanggil data langsung dari Database menggunakan PHP
                        $query = "SELECT * FROM users ORDER BY user_id";
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)): 
                                // Menyesuaikan warna badge berdasarkan role
                                $roleClass = ($row['role'] == 'buyer') ? 'badge-buyer' : 'badge-seller';
                        ?>
                        <tr>
                            <td>#<?= $row['user_id']; ?></td>
                            <td>
                                <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($row['email']); ?></div>
                            </td>
                            <td><code style="background:#F4F7FE; padding:4px 8px; border-radius:5px;"><?= htmlspecialchars($row['username']); ?></code></td>
                            <td><span class="role-badge <?= $roleClass; ?>"><?= ucfirst($row['role']); ?></span></td>
                            <td>
                                <button type="button" class="action-btn edit-btn" onclick="editUser(<?= $row['user_id']; ?>, '<?= addslashes($row['username']); ?>', '<?= addslashes($row['email']); ?>', '<?= $row['role']; ?>')">Edit</button>
                                
                                <form method="POST" action="" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="delete_id" value="<?= $row['user_id']; ?>">
                                    <button type="submit" name="delete_user" class="action-btn delete-btn" onclick="return confirm('Yakin hapus data ini?');">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo '<tr><td colspan="5" style="text-align:center; padding:20px;">Belum ada user terdaftar.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function editUser(id, username, email, role) {
            document.getElementById('userId').value = id;
            document.getElementById('username').value = username;
            document.getElementById('email').value = email;
            document.getElementById('role').value = role;
            
            // Password tidak wajib saat Edit
            document.getElementById('password').required = false;
            document.getElementById('password').placeholder = "Kosongkan jika tidak ganti";

            document.getElementById('formtitle').textContent = 'Edit Pengguna';
            document.getElementById('btnSubmit').textContent = 'Update User';
            document.getElementById('btnCancel').style.display = 'inline-block';

            // Scroll otomatis ke atas
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function cancelEdit() {
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            
            // Kembalikan kewajiban password
            document.getElementById('password').required = true;
            document.getElementById('password').placeholder = "••••••••";

            document.getElementById('formtitle').textContent = 'Tambah User Baru';
            document.getElementById('btnSubmit').textContent = 'Simpan User';
            document.getElementById('btnCancel').style.display = 'none';
        }
    </script>
</body>

</html>