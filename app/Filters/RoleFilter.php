<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $allowedRoles = array_map('strtolower', array_map('trim', $arguments ?? []));
        $currentRole = strtolower((string)session()->get('role'));

        if ($allowedRoles !== [] && !in_array($currentRole, $allowedRoles, true)) {
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(403)->setJSON([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki hak akses untuk tindakan ini.',
                ]);
            }

            return redirect()->to(base_url())->with('error', 'Anda tidak memiliki hak akses ke menu tersebut.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No response mutation required.
    }
}
