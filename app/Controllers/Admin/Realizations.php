<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\RealizationService;
use App\Models\FiscalYearModel;
use App\Models\BudgetTypeModel;

class Realizations extends BaseController
{
    protected RealizationService $realService;
    protected FiscalYearModel $fyModel;
    protected BudgetTypeModel $btModel;

    public function __construct()
    {
        $this->realService = new RealizationService();
        $this->fyModel = new FiscalYearModel();
        $this->btModel = new BudgetTypeModel();
    }

    public function index()
    {
        $allYears = $this->fyModel->orderBy('year', 'DESC')->findAll();
        $yearParam = $this->request->getGet('year');
        $selectedYear = $this->fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $fyId = $selectedYear ? $selectedYear['id'] : 0;
        $grid = $this->realService->getMonthlyGrid($fyId);

        $targetModel = new \App\Models\AbsorptionTargetModel();
        $bdModel = new \App\Models\BudgetDetailModel();
        $budgetTypes = $this->btModel->findAll();
        $pagus = [];
        foreach ($budgetTypes as $bt) {
            $t = $targetModel->where(['fiscal_year_id' => $fyId, 'budget_type_id' => $bt['id']])->first();
            if ($t && (float)($t['pagu_ceiling'] ?? 0) > 0) {
                $pagus[$bt['id']] = (float)$t['pagu_ceiling'];
            } else {
                $sum = $bdModel
                    ->where('fiscal_year_id', $fyId)
                    ->where('budget_type_id', $bt['id'])
                    ->selectSum('final_ceiling')
                    ->first()['final_ceiling'] ?? 0;
                $pagus[$bt['id']] = (float)$sum;
            }
        }

        return view('admin/realizations/index', [
            'all_years'     => $allYears,
            'selected_year' => $selectedYear,
            'budget_types'  => $budgetTypes,
            'pagus'         => $pagus,
            'grid'          => $grid,
            'months'        => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        ]);
    }

    public function updateCell()
    {
        $fiscalYearId = (int)$this->request->getPost('fiscal_year_id');
        $budgetTypeId = (int)$this->request->getPost('budget_type_id');
        $month = (int)$this->request->getPost('month');
        $amount = (float)$this->request->getPost('amount');

        $fy = $this->fyModel->find($fiscalYearId);
        if (!$fy || $month < 1 || $month > 12) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Parameter tidak valid.',
            ]);
        }

        $ok = $this->realService->updateMonthlyCell(
            $fiscalYearId,
            $budgetTypeId,
            (int)$fy['year'],
            $month,
            max(0, $amount)
        );

        if ($ok) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Realisasi bulan berhasil disimpan.',
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status'  => 'error',
            'message' => 'Gagal memperbarui realisasi.',
        ]);
    }
}
