<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->get('is_logged_in')) {
            if ($request->isAJAX()) {
                $response = service('response');
                return $response->setStatusCode(401)->setJSON([
                    'status'  => 'error',
                    'message' => 'Sesi login telah berakhir atau Anda belum login.',
                ]);
            }
            return redirect()->to(base_url('login'))->with('error', 'Silakan login terlebih dahulu untuk mengakses menu ini.');
        }

        $lastActivity = (int)$session->get('last_activity');
        if ($lastActivity > 0 && time() - $lastActivity > 1800) {
            $session->destroy();
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Sesi berakhir karena tidak aktif.']);
            }
            return redirect()->to(base_url('login'))->with('error', 'Sesi berakhir karena tidak aktif selama 30 menit.');
        }

        $user = (new UserModel())->find((int)$session->get('user_id'));
        if (!$user || (int)$user['is_active'] !== 1) {
            $session->destroy();
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Akun sudah tidak aktif.']);
            }
            return redirect()->to(base_url('login'))->with('error', 'Akun sudah tidak aktif atau tidak ditemukan.');
        }

        $session->set([
            'name' => $user['name'],
            'role' => $user['role'],
            'last_activity' => time(),
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
