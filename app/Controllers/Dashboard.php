<?php

namespace App\Controllers;

use App\Services\DashboardService;
use App\Models\FiscalYearModel;
use App\Models\SettingModel;

class Dashboard extends BaseController
{
    protected DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    public function index()
    {
        $yearParam = $this->request->getGet('year');
        $fyModel = new FiscalYearModel();
        $selectedYear = $fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $year = (int)$selectedYear['year'];

        $data = $this->dashboardService->getDashboardData($year);
        $data['app_name'] = SettingModel::getItem('app_name', 'SIMRAN');
        $data['institution'] = SettingModel::getItem('institution', 'Divisi Hubungan Masyarakat Polri');

        return view('dashboard/index', $data);
    }

    public function getData()
    {
        $yearParam = $this->request->getGet('year');
        $fyModel = new FiscalYearModel();
        $selectedYear = $fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $year = (int)$selectedYear['year'];

        $data = $this->dashboardService->getDashboardData($year);
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Endpoint untuk berpindah tahun anggaran secara global.
     * Menyimpan pilihan ke Session & Cookie, lalu mengarahkan kembali ke halaman asal.
     */
    public function switchYear(int $year)
    {
        $fyModel = new FiscalYearModel();
        $fy = $fyModel->setSelectedYear($year);

        $redirect = $this->request->getGet('redirect') ?: $this->request->getServer('HTTP_REFERER');

        if (!empty($redirect)) {
            // Perbarui parameter ?year= di URL tujuan jika ada
            $parts = parse_url($redirect);
            $query = [];
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
            }
            $query['year'] = $year;
            $newQuery = http_build_query($query);
            $targetUrl = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? 'localhost')
                . (isset($parts['port']) ? ':' . $parts['port'] : '')
                . ($parts['path'] ?? '/')
                . ($newQuery ? '?' . $newQuery : '');
            return redirect()->to($targetUrl);
        }

        return redirect()->to(base_url('?year=' . $year));
    }

    /**
     * Endpoint AJAX untuk mengganti tahun anggaran tanpa reload penuh
     */
    public function apiSetYear()
    {
        $year = (int)($this->request->getPost('year') ?: $this->request->getGet('year'));
        $fyModel = new FiscalYearModel();
        $fy = $fyModel->setSelectedYear($year);

        if (!$fy) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Tahun anggaran tidak ditemukan.',
            ])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'year'   => $fy['year'],
            'name'   => $fy['name'],
        ]);
    }
}

