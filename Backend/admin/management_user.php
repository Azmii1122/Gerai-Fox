<?php
session_start();
// Proteksi halaman admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../auth/login.php"); 
    exit; 
}

include '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Kelola User</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .alert { padding: 10px; margin-bottom: 10px; border-radius: 5px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .form-container { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
    </style>
</head>
<body>
    <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['username']) ?></h2>
    <nav>
        <a href="dashboard.php">Dashboard</a> | 
        <a href="management_user.php">Kelola User</a> | 
        <a href="../auth-system/logout.php">Logout</a>
    </nav>
    <hr>

    <div class="form-container">
        <h3 id="formtitle">Tambah User Baru</h3>
        <div id="alert-container"></div>
        
        <form id="userForm">
            <input type="hidden" id="userId" name="id">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Email:</label><br>
                    <input type="email" id="email" name="email" required style="width:100%">
                </div>
                <div>
                    <label>Nama Lengkap:</label><br>
                    <input type="text" id="nama" name="nama" required style="width:100%">
                </div>
                <div>
                    <label>Username:</label><br>
                    <input type="text" id="username" name="username" required style="width:100%">
                </div>
                <div>
                    <label>Password:</label><br>
                    <input type="password" id="password" name="password" required style="width:100%">
                </div>
                <div>
                    <label>Role:</label><br>
                    <select id="role" name="role" required style="width:100%">
                        <option value="">Pilih Role</option>
                        <option value="buyer">Pembeli</option>
                        <option value="merchant">Merchant</option>
                    </select>
                </div>
            </div>
            <br>
            <button type="submit" id="btnSubmit">Simpan User</button>
            <button type="button" onclick="resetForm()" id="btnCancel" style="display:none;">Batal</button>
        </form>
    </div>

    <h3>Daftar User Terdaftar</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="userTableBody">
            </tbody>
    </table>

    <script>
        const API_URL = '../api/user.php';

        // 1. Ambil Data Saat Halaman Load
        document.addEventListener('DOMContentLoaded', getUsers);

        function showAlert(message, type='success') {
            const container = document.getElementById('alert-container');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
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
            tbody.innerHTML = '<tr><td colspan="6">Memuat data...</td></tr>';
            
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
                tbody.innerHTML = '<tr><td colspan="6">Belum ada user.</td></tr>';
                return;
            }

            users.forEach(user => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.email}</td>
                    <td>${user.nama}</td>
                    <td>${user.username}</td>
                    <td><strong>${user.role}</strong></td>
                    <td>
                        <button onclick="editUser(${user.id})">Edit</button>
                        <button onclick="deleteUser(${user.id})" style="color:red">Hapus</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Logic Simpan (Tambah/Edit)
        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if(result.status === 'success') {
                    showAlert(result.message);
                    resetForm();
                    getUsers();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Gagal menyimpan data', 'error');
            }
        });

        // Placeholder fungsi delete (nanti sesuaikan dengan API-mu)
        async function deleteUser(id) {
            if(!confirm('Yakin ingin menghapus user ini?')) return;
        }

        async function createUser(data) {
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                return result;
            } catch (error) {
                return { status: 'error', message: 'Gagal terhubung ke API' };
            }
        }

        async function editUser(id) {
            try {
                const response = await fetch(`${API_URL}?id=${id}`);
                const result = await response.json();

                if(result.status === 'success') {
                    const user = result.data;
                    document.getElementById('userId').value = user.id;
                    document.getElementById('email').value = user.email;
                    document.getElementById('nama').value = user.nama;
                    document.getElementById('username').value = user.username;
                    document.getElementById('role').value = user.role;
                    document.getElementById('password').required = false;
                    document.getElementById('formtitle').textContent = 'Edit User';
                    document.getElementById('btnSubmit').textContent = 'Update User';
                    document.getElementById('btnCancel').style.display = 'inline-block';
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Gagal terhubung ke API', 'error');
            }
        }

        async function deleteUser(id) {
            if(!confirm('Yakin ingin menghapus user ini?')) return;

            try {
                const response = await fetch(API_URL, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const result = await response.json();

                if(result.status === 'success') {
                    showAlert(result.message);
                    getUsers();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Gagal terhubung ke API', 'error');
            }
        }
    </script>
</body>
</html>