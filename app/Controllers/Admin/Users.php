<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Users extends BaseController
{
    private const MAX_USERS = 3;
    private const ROLES = ['admin', 'operator', 'pimpinan'];

    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        return view('admin/users/index', [
            'users' => $this->userModel->orderBy('id', 'ASC')->findAll(),
            'max_users' => self::MAX_USERS,
            'role_labels' => [
                'admin' => 'Administrator',
                'operator' => 'Operator',
                'pimpinan' => 'Pimpinan',
            ],
        ]);
    }

    public function create()
    {
        if ($this->userModel->countAllResults() >= self::MAX_USERS) {
            return redirect()->back()->with('error', 'Maksimal hanya tiga pengguna yang dapat dibuat.');
        }

        $username = strtolower(trim((string)$this->request->getPost('username')));
        $name = trim((string)$this->request->getPost('name'));
        $role = strtolower(trim((string)$this->request->getPost('role')));
        $password = (string)$this->request->getPost('password');

        if (!preg_match('/^[a-z0-9._-]{4,40}$/', $username)) {
            return redirect()->back()->withInput()->with('error', 'Username harus 4–40 karakter dan hanya boleh berisi huruf kecil, angka, titik, garis bawah, atau tanda hubung.');
        }
        if (strlen($name) < 3 || strlen($name) > 150) {
            return redirect()->back()->withInput()->with('error', 'Nama pengguna harus 3–150 karakter.');
        }
        if (!in_array($role, self::ROLES, true)) {
            return redirect()->back()->withInput()->with('error', 'Level pengguna tidak valid.');
        }
        if (!$this->isStrongPassword($password)) {
            return redirect()->back()->withInput()->with('error', 'Password minimal 10 karakter dan harus mengandung huruf besar, huruf kecil, angka, serta simbol.');
        }
        if ($this->userModel->where('username', $username)->first()) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan.');
        }

        $pin = trim((string)$this->request->getPost('pin'));
        if ($pin !== '') {
            if (!preg_match('/^\d{6}$/', $pin)) {
                return redirect()->back()->withInput()->with('error', 'PIN harus berupa tepat 6 digit angka.');
            }
            if ($this->userModel->where('login_token', $pin)->first()) {
                return redirect()->back()->withInput()->with('error', 'PIN tersebut sudah digunakan oleh pengguna lain. Silakan pilih PIN 6 digit yang berbeda.');
            }
        } else {
            $pin = $this->userModel->generateUniquePin(6);
        }

        $this->userModel->insert([
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name'          => $name,
            'role'          => $role,
            'login_token'   => $pin,
            'is_active'     => 1,
        ]);

        return redirect()->to(base_url('admin/users'))->with('success', 'Pengguna baru berhasil ditambahkan dengan PIN: ' . $pin);
    }

    public function update(int $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        $name = trim((string)$this->request->getPost('name'));
        $role = strtolower(trim((string)$this->request->getPost('role')));
        $isActive = $this->request->getPost('is_active') === '1' ? 1 : 0;
        if (strlen($name) < 3 || strlen($name) > 150 || !in_array($role, self::ROLES, true)) {
            return redirect()->back()->with('error', 'Nama atau level pengguna tidak valid.');
        }

        if ((int)$user['id'] === (int)session()->get('user_id') && ($role !== 'admin' || $isActive !== 1)) {
            return redirect()->back()->with('error', 'Anda tidak dapat menurunkan level atau menonaktifkan akun sendiri.');
        }
        if ($user['role'] === 'admin' && ($role !== 'admin' || $isActive !== 1) && $this->activeAdminCount() <= 1) {
            return redirect()->back()->with('error', 'Minimal satu Administrator aktif harus tetap tersedia.');
        }

        $this->userModel->update($id, ['name' => $name, 'role' => $role, 'is_active' => $isActive]);
        return redirect()->to(base_url('admin/users'))->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function resetPassword(int $id)
    {
        $user = $this->userModel->find($id);
        $password = (string)$this->request->getPost('new_password');
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }
        if ((int)$user['id'] === (int)session()->get('user_id')) {
            return redirect()->back()->with('error', 'Gunakan menu Profil & Password untuk mengganti password akun sendiri.');
        }
        if (!$this->isStrongPassword($password)) {
            return redirect()->back()->with('error', 'Password minimal 10 karakter dan harus mengandung huruf besar, huruf kecil, angka, serta simbol.');
        }

        $this->userModel->update($id, ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        return redirect()->to(base_url('admin/users'))->with('success', 'Password pengguna berhasil direset.');
    }

    public function updatePin(int $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        $pin = trim((string)$this->request->getPost('pin'));
        if (!preg_match('/^\d{6}$/', $pin)) {
            return redirect()->back()->with('error', 'PIN harus berupa tepat 6 digit angka (contoh: 123456).');
        }

        $existing = $this->userModel->where('login_token', $pin)->where('id !=', $id)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'PIN ' . esc($pin) . ' sudah digunakan oleh pengguna ' . esc($existing['name']) . '. Gunakan PIN 6 digit yang berbeda.');
        }

        $this->userModel->update($id, ['login_token' => $pin]);

        return redirect()->to(base_url('admin/users'))->with('success', 'PIN login untuk ' . esc($user['name']) . ' (@' . esc($user['username']) . ') berhasil diubah menjadi: ' . $pin);
    }

    public function generatePin(int $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        $newPin = $this->userModel->generateUniquePin(6);
        $this->userModel->update($id, ['login_token' => $newPin]);

        return redirect()->to(base_url('admin/users'))->with('success', 'PIN login 6-digit untuk ' . esc($user['name']) . ' (@' . esc($user['username']) . ') berhasil dibuat: ' . $newPin);
    }

    public function generateToken(int $id)
    {
        return $this->generatePin($id);
    }

    public function delete(int $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }
        if ((int)$user['id'] === (int)session()->get('user_id')) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }
        if ($user['role'] === 'admin' && $this->activeAdminCount() <= 1) {
            return redirect()->back()->with('error', 'Administrator aktif terakhir tidak dapat dihapus.');
        }

        $this->userModel->delete($id);
        return redirect()->to(base_url('admin/users'))->with('success', 'Pengguna berhasil dihapus.');
    }

    private function activeAdminCount(): int
    {
        return $this->userModel->where('role', 'admin')->where('is_active', 1)->countAllResults();
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^a-zA-Z0-9]/', $password);
    }
}
