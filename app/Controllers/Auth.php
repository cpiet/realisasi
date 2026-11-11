<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('is_logged_in')) {
            return redirect()->to(base_url());
        }

        return view('auth/login');
    }

    public function process()
    {
        $throttler = service('throttler');
        $throttleKey = 'login-' . sha1((string)$this->request->getIPAddress());
        if (!$throttler->check($throttleKey, 10, MINUTE)) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan login. Silakan tunggu satu menit.');
        }

        $loginType = trim((string)$this->request->getPost('login_type'));
        $pinInput = trim((string)($this->request->getPost('pin') ?? $this->request->getPost('token') ?? ''));

        $userModel = new UserModel();

        // 1. Login menggunakan PIN 6 Digit (atau Token)
        if ($loginType === 'pin' || $loginType === 'token' || ($pinInput !== '' && $loginType !== 'password')) {
            if ($pinInput === '') {
                return redirect()->back()->withInput()->with('error', 'PIN login 6 digit wajib dimasukkan.');
            }

            $user = $userModel->where('login_token', $pinInput)->first();

            if (!$user) {
                return redirect()->back()->withInput()->with('error', 'PIN login salah atau belum terdaftar.');
            }

            if ((int)$user['is_active'] !== 1) {
                return redirect()->back()->with('error', 'Akun pengguna dinonaktifkan.');
            }

            session()->regenerate();

            session()->set([
                'user_id'       => $user['id'],
                'username'      => $user['username'],
                'name'          => $user['name'],
                'role'          => $user['role'],
                'is_logged_in'  => true,
                'last_activity' => time(),
            ]);

            $userModel->update($user['id'], [
                'last_login_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to(base_url())->with('success', 'Login berhasil via PIN! Selamat datang, ' . $user['name']);
        }

        // 2. Login menggunakan Username & Password
        $username = trim((string)$this->request->getPost('username'));
        $password = (string)$this->request->getPost('password');

        if ($username === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Username dan password wajib diisi.');
        }

        $user = $userModel->where('username', $username)->first();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Username atau password salah.');
        }

        if ((int)$user['is_active'] !== 1) {
            return redirect()->back()->with('error', 'Akun Anda dinonaktifkan.');
        }

        // Regenerate session id to prevent fixation
        session()->regenerate();

        session()->set([
            'user_id'       => $user['id'],
            'username'      => $user['username'],
            'name'          => $user['name'],
            'role'          => $user['role'],
            'is_logged_in'  => true,
            'last_activity' => time(),
        ]);

        $userModel->update($user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url())->with('success', 'Selamat datang kembali, ' . $user['name']);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url())->with('success', 'Anda telah berhasil logout.');
    }
}
