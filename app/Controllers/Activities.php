<?php

namespace App\Controllers;

use App\Models\FiscalYearModel;
use App\Models\ProgramModel;
use App\Models\ActivityModel;
use App\Models\BudgetDetailModel;
use App\Models\RealizationModel;
use App\Models\BudgetTypeModel;

class Activities extends BaseController
{
    protected FiscalYearModel $fyModel;
    protected ProgramModel $progModel;
    protected ActivityModel $actModel;
    protected BudgetDetailModel $bdModel;
    protected RealizationModel $realModel;
    protected BudgetTypeModel $btModel;

    public function __construct()
    {
        $this->fyModel = new FiscalYearModel();
        $this->progModel = new ProgramModel();
        $this->actModel = new ActivityModel();
        $this->bdModel = new BudgetDetailModel();
        $this->realModel = new RealizationModel();
        $this->btModel = new BudgetTypeModel();
    }

    public function index()
    {
        $allYears = $this->fyModel->orderBy('year', 'DESC')->findAll();
        $yearParam = $this->request->getGet('year');
        $selectedYear = $this->fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $fyId = $selectedYear ? $selectedYear['id'] : 0;

        $programs = $this->progModel->where('fiscal_year_id', $fyId)->findAll();
        $budgetTypes = $this->btModel->findAll();

        $selectedProgram = $this->request->getGet('program_id');
        $selectedBelanja = $this->request->getGet('budget_type_id');
        $keyword = trim((string)$this->request->getGet('q'));

        // Query Budget Details with relationships
        $builder = $this->bdModel
            ->select('budget_details.*, programs.name as program_name, activities.name as activity_name, activities.unit_name, budget_types.name as budget_type_name, budget_types.color as budget_type_color')
            ->join('programs', 'programs.id = budget_details.program_id', 'left')
            ->join('activities', 'activities.id = budget_details.activity_id', 'left')
            ->join('budget_types', 'budget_types.id = budget_details.budget_type_id', 'left')
            ->where('budget_details.fiscal_year_id', $fyId);

        if ($selectedProgram) {
            $builder->where('budget_details.program_id', (int)$selectedProgram);
        }
        if ($selectedBelanja) {
            $builder->where('budget_details.budget_type_id', (int)$selectedBelanja);
        }
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('budget_details.account_code', $keyword)
                ->orLike('budget_details.description', $keyword)
                ->orLike('activities.name', $keyword)
                ->orLike('activities.code', $keyword)
                ->orLike('programs.name', $keyword)
                ->groupEnd();
        }

        $items = $builder->orderBy('budget_details.account_code', 'ASC')->findAll();

        // Calculate Realization per detail
        foreach ($items as &$it) {
            $realSum = $this->realModel
                ->where('fiscal_year_id', $fyId)
                ->where('budget_detail_id', $it['id'])
                ->selectSum('amount')
                ->first()['amount'] ?? 0;

            $pagu = (float)$it['final_ceiling'];
            $it['realisasi'] = (float)$realSum;
            $it['sisa'] = max(0, $pagu - (float)$realSum);
            $it['serap_pct'] = $pagu > 0 ? round(($realSum / $pagu * 100), 2) : 0;
        }

        return view('activities/index', [
            'all_years'        => $allYears,
            'selected_year'    => $selectedYear,
            'programs'         => $programs,
            'budget_types'     => $budgetTypes,
            'items'            => $items,
            'selected_program' => $selectedProgram,
            'selected_belanja' => $selectedBelanja,
            'keyword'          => $keyword,
        ]);
    }

    public function detail(int $id)
    {
        $item = $this->bdModel
            ->select('budget_details.*, programs.name as program_name, activities.name as activity_name, activities.unit_name, budget_types.name as budget_type_name, budget_types.code as budget_type_code')
            ->join('programs', 'programs.id = budget_details.program_id', 'left')
            ->join('activities', 'activities.id = budget_details.activity_id', 'left')
            ->join('budget_types', 'budget_types.id = budget_details.budget_type_id', 'left')
            ->find($id);

        if (!$item) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }

        // Ambil histori bulanan
        $monthly = array_fill(1, 12, 0.0);
        $reals = $this->realModel
            ->where('budget_detail_id', $id)
            ->findAll();

        foreach ($reals as $r) {
            $m = (int)$r['month'];
            if ($m >= 1 && $m <= 12) {
                $monthly[$m] += (float)$r['amount'];
            }
        }

        $totalReal = array_sum($monthly);
        $pagu = (float)$item['final_ceiling'];

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'item'        => $item,
                'monthly'     => $monthly,
                'total_real'  => $totalReal,
                'pagu'        => $pagu,
                'sisa'        => max(0, $pagu - $totalReal),
                'serap_pct'   => $pagu > 0 ? round(($totalReal / $pagu * 100), 2) : 0,
            ],
        ]);
    }

    public function search()
    {
        $q = trim((string)$this->request->getGet('q'));
        if (strlen($q) < 2) {
            return $this->response->setJSON(['status' => 'success', 'results' => []]);
        }

        $yearParam = $this->request->getGet('year');
        $fy = $this->fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $fyId = $fy ? (int)$fy['id'] : 1;

        // 1. Search in budget_details
        $results = $this->bdModel
            ->select('budget_details.id, budget_details.account_code, budget_details.description, budget_details.final_ceiling, activities.name as activity_name, programs.name as program_name, \'budget\' as item_type')
            ->join('programs', 'programs.id = budget_details.program_id', 'left')
            ->join('activities', 'activities.id = budget_details.activity_id', 'left')
            ->where('budget_details.fiscal_year_id', $fyId)
            ->groupStart()
                ->like('budget_details.account_code', $q)
                ->orLike('budget_details.description', $q)
                ->orLike('activities.code', $q)
                ->orLike('activities.name', $q)
                ->orLike('programs.name', $q)
            ->groupEnd()
            ->limit(8)
            ->findAll();

        // 2. Search in activity_descriptions (Kamus Uraian Operasional 110 Giat)
        try {
            $descModel = new \App\Models\ActivityDescriptionModel();
            $descResults = $descModel
                ->select('id, item_no, activity_name, biro, division, ro_code, ceiling as final_ceiling, description, \'uraian\' as item_type')
                ->where('fiscal_year_id', $fyId)
                ->groupStart()
                    ->like('activity_name', $q)
                    ->orLike('description', $q)
                    ->orLike('biro', $q)
                    ->orLike('division', $q)
                    ->orLike('ro_code', $q)
                ->groupEnd()
                ->limit(6)
                ->findAll();

            $results = array_merge($results, $descResults);
        } catch (\Throwable $e) {
            // Ignore if table not found
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'results' => $results,
        ]);
    }
}
