<?php
session_start();
// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../Frontend/auth/login.html");
    exit;
}

include '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Gerai.Fox Admin</title>
    <!-- Google Fonts -->
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

        .badge-merchant {
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
            <a href="../auth-system/logout.php" style="margin-top:auto; color: var(--danger);"><span>🚪</span>
                Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header-section">
            <h1>Manajemen <span>User</span></h1>
            <p style="color: var(--text-muted); font-size: 14px;">Tambah, edit, atau hapus akses pengguna sistem.</p>
        </div>

        <!-- Form Section -->
        <div class="glass-card">
            <h3 class="form-title" id="formtitle">Tambah User Baru</h3>
            <div id="alert-container"></div>

            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                <div class="grid-inputs">
                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" placeholder="Contoh: Habib Azmi" required>
                    </div>
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
                            <option value="merchant">Pedagang (Merchant)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" id="btnSubmit" class="btn btn-primary">Simpan User</button>
                <button type="button" onclick="resetForm()" id="btnCancel" class="btn btn-cancel"
                    style="display:none;">Batal</button>
            </form>
        </div>

        <!-- Table Section -->
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
                        <!-- Data dikelola oleh JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const API_URL = '../api/user.php';

        document.addEventListener('DOMContentLoaded', getUsers);

        function showAlert(message, type = 'success') {
            const container = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            container.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 3000);
        }

        function resetForm() {
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('formtitle').textContent = 'Tambah User Baru';
            document.getElementById('btnSubmit').textContent = 'Simpan User';
            document.getElementById('btnCancel').style.display = 'none';
            document.getElementById('password').required = true;
        }

        async function getUsers() {
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Memuat data pengguna...</td></tr>';

            try {
                const response = await fetch(API_URL);
                const result = await response.json();

                if (result.status === 'success') {
                    renderUserTable(result.data);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Gagal terhubung ke API', 'error');
            }
        }

        function renderUserTable(users) {
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '';

            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Belum ada user terdaftar.</td></tr>';
                return;
            }

            users.forEach(user => {
                const roleClass = user.role === 'buyer' ? 'badge-buyer' : 'badge-merchant';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${user.id}</td>
                    <td>
                        <div style="font-weight:700;">${user.nama}</div>
                        <div style="font-size:12px; color:var(--text-muted);">${user.email}</div>
                    </td>
                    <td><code style="background:#F4F7FE; padding:4px 8px; border-radius:5px;">${user.username}</code></td>
                    <td><span class="role-badge ${roleClass}">${user.role}</span></td>
                    <td>
                        <button class="action-btn edit-btn" onclick="editUser(${user.id})">Edit</button>
                        <button class="action-btn delete-btn" onclick="deleteUser(${user.id})">Hapus</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            const method = data.id ? 'PUT' : 'POST';

            try {
                const response = await fetch(API_URL, {
                    method: method, // Dinamis sesuai kebutuhan
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert(result.message);
                    resetForm();
                    getUsers();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Gagal memproses data', 'error');
            }
        });

        async function editUser(id) {
            try {
                const response = await fetch(`${API_URL}?id=${id}`);
                const result = await response.json();

                if (result.status === 'success') {
                    const user = result.data;
                    document.getElementById('userId').value = user.id;
                    document.getElementById('email').value = user.email;
                    document.getElementById('nama').value = user.nama;
                    document.getElementById('username').value = user.username;
                    document.getElementById('role').value = user.role;
                    document.getElementById('password').required = false;
                    document.getElementById('password').placeholder = "Kosongkan jika tidak ingin ganti";

                    document.getElementById('formtitle').textContent = 'Edit Pengguna';
                    document.getElementById('btnSubmit').textContent = 'Update User';
                    document.getElementById('btnCancel').style.display = 'inline-block';

                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } catch (error) {
                showAlert('Gagal mengambil detail user', 'error');
            }
        }

        async function deleteUser(id) {
            if (!confirm('Data user akan dihapus permanen. Lanjutkan?')) return;

            try {
                const response = await fetch(API_URL, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    showAlert(result.message);
                    getUsers();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Gagal menghapus user', 'error');
            }
        }
    </script>
</body>

</html>