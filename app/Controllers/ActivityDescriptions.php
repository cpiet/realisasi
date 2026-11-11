<?php

namespace App\Controllers;

use App\Models\FiscalYearModel;
use App\Models\ActivityDescriptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class ActivityDescriptions extends BaseController
{
    protected FiscalYearModel $fyModel;
    protected ActivityDescriptionModel $descModel;

    public function __construct()
    {
        $this->fyModel = new FiscalYearModel();
        $this->descModel = new ActivityDescriptionModel();
    }

    public function index()
    {
        $allYears = $this->fyModel->orderBy('year', 'DESC')->findAll();

        $yearParam = $this->request->getGet('year');
        $selectedYear = $this->fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $fyId = $selectedYear ? (int)$selectedYear['id'] : 0;

        $biro = trim((string)($this->request->getGet('biro') ?? 'all'));
        $keyword = trim((string)($this->request->getGet('q') ?? ''));

        $items = $this->descModel->getFiltered($fyId, $biro, $keyword);
        $summary = $this->descModel->getBiroSummary($fyId);

        $isFluid = true;

        return view('activities/uraian', [
            'isFluid' => $isFluid,
            'allYears' => $allYears,
            'selectedYear' => $selectedYear,
            'items' => $items,
            'summary' => $summary,
            'activeBiro' => $biro,
            'keyword' => $keyword,
        ]);
    }

    /**
     * AJAX endpoint untuk mengambil detail rincian kegiatan saat baris diklik
     */
    public function detail(int $id): ResponseInterface
    {
        $item = $this->descModel->find($id);
        if (!$item) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data uraian kegiatan tidak ditemukan.',
            ])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'id' => (int)$item['id'],
                'item_no' => (int)$item['item_no'],
                'activity_name' => $item['activity_name'],
                'category_group' => $item['category_group'],
                'biro' => $item['biro'],
                'division' => $item['division'],
                'rkakl_activity' => $item['rkakl_activity'],
                'ro_code' => $item['ro_code'],
                'ceiling' => (float)$item['ceiling'],
                'ceiling_formatted' => 'Rp ' . number_format((float)$item['ceiling'], 0, ',', '.'),
                'description' => $item['description'],
                'rkakl_source_page' => $item['rkakl_source_page'] ?: '—',
            ]
        ]);
    }
}
