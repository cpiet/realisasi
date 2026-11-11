<?php

namespace App\Services;

use App\Models\RealizationModel;
use App\Models\BudgetTypeModel;
use App\Models\FiscalYearModel;

class RealizationService
{
    protected RealizationModel $realModel;
    protected BudgetTypeModel $btModel;
    protected FiscalYearModel $fyModel;

    public function __construct()
    {
        $this->realModel = new RealizationModel();
        $this->btModel = new BudgetTypeModel();
        $this->fyModel = new FiscalYearModel();
    }

    public function getMonthlyGrid(int $fiscalYearId): array
    {
        $budgetTypes = $this->btModel->orderBy('code', 'ASC')->findAll();
        $grid = [];

        foreach ($budgetTypes as $bt) {
            $months = array_fill(1, 12, 0.0);
            $rows = $this->realModel
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('budget_type_id', $bt['id'])
                ->findAll();

            foreach ($rows as $r) {
                $m = (int)$r['month'];
                if ($m >= 1 && $m <= 12) {
                    $months[$m] += (float)$r['amount'];
                }
            }

            $grid[$bt['id']] = [
                'type'   => $bt,
                'months' => $months,
                'total'  => array_sum($months),
            ];
        }

        return $grid;
    }

    public function updateMonthlyCell(int $fiscalYearId, int $budgetTypeId, int $year, int $month, float $amount, string $notes = ''): bool
    {
        // Snapshot/Upsert single aggregate record for the month & budget_type
        $existing = $this->realModel
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('budget_type_id', $budgetTypeId)
            ->where('month', $month)
            ->where('budget_detail_id', null) // Aggregated type-level row
            ->first();

        if ($existing) {
            return (bool)$this->realModel->update($existing['id'], [
                'amount'      => $amount,
                'notes'       => $notes ?: 'Diperbarui melalui Input Cepat Realisasi',
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            return (bool)$this->realModel->insert([
                'fiscal_year_id'   => $fiscalYearId,
                'budget_detail_id' => null,
                'budget_type_id'   => $budgetTypeId,
                'year'             => $year,
                'month'            => $month,
                'amount'           => $amount,
                'source_type'      => 'manual',
                'notes'            => $notes ?: 'Input Cepat Realisasi',
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
