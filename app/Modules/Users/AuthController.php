<?php

namespace App\Modules\Users;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth.login', [
            'title' => 'Login Staff & Manajemen',
        ], 'layouts/auth');
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $this->redirect(url('/login'), 'Silakan isi username/email dan kata sandi.', 'error');
        }

        if (Auth::attempt($username, $password)) {
            $this->logActivity('login', 'users', Auth::id(), "Pengguna {$username} berhasil login ke sistem.");
            $this->redirect(url('/dashboard'), 'Selamat datang kembali, ' . (Auth::user()['name'] ?? 'Staff') . '!');
        }

        $this->redirect(url('/login'), 'Kombinasi kredensial login tidak cocok atau akun tidak aktif.', 'error');
    }

    public function logout(): void
    {
        $userId = Auth::id();
        $this->logActivity('logout', 'users', $userId, "Pengguna logout dari sistem.");
        Auth::logout();
        $this->redirect(url('/login'), 'Anda telah berhasil keluar dari sistem.', 'info');
    }

    public function showProfile(): void
    {
        $user = Auth::user();
        $this->view('auth.profile', [
            'title' => 'Profil Pengguna',
            'activeMenu' => '',
            'user' => $user,
        ]);
    }

    public function updateProfile(): void
    {
        $user = Auth::user();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            $this->back('Nama lengkap wajib diisi.', 'error');
        }

        $this->db()->update('users', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $user['id']]);

        $this->logActivity('update', 'users', $user['id'], "Memperbarui profil akun sendiri.");
        $this->redirect(url('/profile'), 'Profil berhasil diperbarui!');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['new_password_confirmation'] ?? '';

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $this->back('Kata sandi saat ini salah.', 'error');
        }

        if (strlen($newPassword) < 6) {
            $this->back('Kata sandi baru minimal 6 karakter.', 'error');
        }

        if ($newPassword !== $confirmPassword) {
            $this->back('Konfirmasi kata sandi baru tidak cocok.', 'error');
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->db()->update('users', [
            'password_hash' => $newHash,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $user['id']]);

        $this->logActivity('change_password', 'users', $user['id'], "Mengubah kata sandi akun sendiri.");
        $this->redirect(url('/profile'), 'Kata sandi berhasil diubah!');
    }
}
