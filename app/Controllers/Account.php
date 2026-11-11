<?php

namespace App\Controllers;

use App\Models\UserModel;

class Account extends BaseController
{
    public function index()
    {
        $user = (new UserModel())->find((int)session()->get('user_id'));
        if (!$user) {
            session()->destroy();
            return redirect()->to(base_url('login'))->with('error', 'Akun tidak ditemukan. Silakan login kembali.');
        }

        return view('account/index', ['user' => $user]);
    }

    public function changePassword()
    {
        $currentPassword = (string)$this->request->getPost('current_password');
        $newPassword = (string)$this->request->getPost('new_password');
        $confirmation = (string)$this->request->getPost('new_password_confirmation');
        $userId = (int)session()->get('user_id');

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Password saat ini tidak sesuai.');
        }
        if (!$this->isStrongPassword($newPassword)) {
            return redirect()->back()->with('error', 'Password minimal 10 karakter dan harus mengandung huruf besar, huruf kecil, angka, serta simbol.');
        }
        if ($newPassword !== $confirmation) {
            return redirect()->back()->with('error', 'Konfirmasi password baru tidak sama.');
        }
        if (password_verify($newPassword, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Password baru harus berbeda dari password saat ini.');
        }

        $userModel->update($userId, ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)]);
        session()->regenerate();

        return redirect()->to(base_url('account'))->with('success', 'Password Anda berhasil diperbarui.');
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
