<?php

namespace App\Filters;

use App\Models\FiscalYearModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter global untuk memastikan tahun anggaran aktif pengguna tersimpan
 * secara konsisten di Session dan Cookie pada setiap menu/halaman.
 */
class FiscalYearFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $yearParam = $request->getGet('year');
        $requestedYear = ($yearParam !== null && is_numeric($yearParam)) ? (int)$yearParam : null;

        $fyModel = new FiscalYearModel();
        // Memanggil determineSelectedYear akan otomatis memvalidasi
        // dan menyimpannya ke session & cookie
        $fyModel->determineSelectedYear($requestedYear);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah request
    }
}
