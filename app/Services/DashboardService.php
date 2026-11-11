<?php

namespace App\Services;

use App\Models\FiscalYearModel;
use App\Models\BudgetTypeModel;
use App\Models\BudgetDetailModel;
use App\Models\RealizationModel;
use App\Models\AbsorptionTargetModel;
use App\Models\IkpaModel;

class DashboardService
{
    protected FiscalYearModel $fyModel;
    protected BudgetTypeModel $btModel;
    protected BudgetDetailModel $bdModel;
    protected RealizationModel $realModel;
    protected AbsorptionTargetModel $targetModel;
    protected IkpaModel $ikpaModel;

    public function __construct()
    {
        $this->fyModel = new FiscalYearModel();
        $this->btModel = new BudgetTypeModel();
        $this->bdModel = new BudgetDetailModel();
        $this->realModel = new RealizationModel();
        $this->targetModel = new AbsorptionTargetModel();
        $this->ikpaModel = new IkpaModel();
    }

    public function getDashboardData(?int $year = null): array
    {
        $allYears = $this->fyModel->orderBy('year', 'DESC')->findAll();
        $selectedYear = null;
        if ($year) {
            $selectedYear = $this->fyModel->where('year', $year)->first();
        }
        if (!$selectedYear) {
            $selectedYear = $this->fyModel->getActiveYear();
        }
        if (!$selectedYear && !empty($allYears)) {
            $selectedYear = $allYears[0];
        }

        $fyId = $selectedYear ? $selectedYear['id'] : 0;
        $budgetTypes = $this->btModel->orderBy('code', 'ASC')->findAll();

        // 1. Pagu per Jenis Belanja
        $paguPerType = [];
        $totalPagu = 0.0;
        foreach ($budgetTypes as $bt) {
            $tgtRow = $this->targetModel
                ->where('fiscal_year_id', $fyId)
                ->where('budget_type_id', $bt['id'])
                ->first();

            if ($tgtRow && (float)($tgtRow['pagu_ceiling'] ?? 0) > 0) {
                $ceiling = (float)$tgtRow['pagu_ceiling'];
            } else {
                $sumCeiling = $this->bdModel
                    ->where('fiscal_year_id', $fyId)
                    ->where('budget_type_id', $bt['id'])
                    ->selectSum('final_ceiling')
                    ->first()['final_ceiling'] ?? 0;
                $ceiling = (float)$sumCeiling;
            }

            $paguPerType[$bt['id']] = $ceiling;
            $totalPagu += $ceiling;
        }

        // 2. Target per Jenis Belanja
        $targetMap = [];
        $defaultTargets = [
            '51' => [20, 50, 75, 95],
            '52' => [15, 40, 70, 90],
            '53' => [10, 40, 70, 90],
        ];

        foreach ($budgetTypes as $bt) {
            $tgt = $this->targetModel
                ->where('fiscal_year_id', $fyId)
                ->where('budget_type_id', $bt['id'])
                ->first();

            if ($tgt) {
                $targetMap[$bt['id']] = [(float)$tgt['q1'], (float)$tgt['q2'], (float)$tgt['q3'], (float)$tgt['q4']];
            } else {
                $targetMap[$bt['id']] = $defaultTargets[$bt['code']] ?? [10, 40, 70, 90];
            }
        }

        // 3. Realisasi per Bulan (1..12) per jenis belanja
        $realMatrix = []; // [bt_id => [1..12 => amount]]
        $lastFilledMonth = 0;

        foreach ($budgetTypes as $bt) {
            $realMatrix[$bt['id']] = array_fill(1, 12, 0.0);

            $hasDetails = $this->realModel
                ->where('fiscal_year_id', $fyId)
                ->where('budget_type_id', $bt['id'])
                ->where('budget_detail_id IS NOT NULL')
                ->countAllResults() > 0;

            $builder = $this->realModel
                ->where('fiscal_year_id', $fyId)
                ->where('budget_type_id', $bt['id']);

            if ($hasDetails) {
                $builder->where('budget_detail_id IS NOT NULL');
            }

            $rows = $builder->findAll();

            foreach ($rows as $r) {
                $m = (int)$r['month'];
                $amt = (float)$r['amount'];
                if ($m >= 1 && $m <= 12) {
                    $realMatrix[$bt['id']][$m] += $amt;
                    if ($amt > 0 && $m > $lastFilledMonth) {
                        $lastFilledMonth = $m;
                    }
                }
            }
        }

        // 4. Perhitungan Realisasi & Target s.d Bulan Berjalan
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $posisiText = $lastFilledMonth > 0 ? ('s.d. ' . $months[$lastFilledMonth - 1]) : 'Belum ada input';

        $totalRealToDate = 0.0;
        $totalTargetRpToDate = 0.0;
        $belanjaSummary = [];

        foreach ($budgetTypes as $bt) {
            $btId = $bt['id'];
            $pagu = $paguPerType[$btId] ?? 0.0;
            $targets = $targetMap[$btId] ?? [0, 0, 0, 0];

            // Kumulatif realisasi 1..12
            $realSumAll = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $realSumAll += $realMatrix[$btId][$m];
            }

            // Target % pada bulan terakhir terisi
            $targetPctNow = $lastFilledMonth > 0 ? $this->calcTargetPctAtMonth($targets, $lastFilledMonth) : 0.0;
            $targetRpNow = $pagu * ($targetPctNow / 100);

            $serapPct = $pagu > 0 ? ($realSumAll / $pagu * 100) : 0.0;
            $sisaAnggaran = max(0, $pagu - $realSumAll);
            $deviasi = $realSumAll - $targetRpNow;

            // Status evaluasi
            $status = 'bad';
            $statusLabel = 'tertinggal';
            if ($serapPct >= $targetPctNow) {
                $status = 'ok';
                $statusLabel = 'sesuai target';
            } elseif ($serapPct >= ($targetPctNow * 0.85)) {
                $status = 'warn';
                $statusLabel = 'mendekati';
            }

            $totalRealToDate += $realSumAll;
            $totalTargetRpToDate += $targetRpNow;

            $belanjaSummary[] = [
                'type'          => $bt,
                'pagu'          => $pagu,
                'realisasi'     => $realSumAll,
                'sisa'          => $sisaAnggaran,
                'serap_pct'     => round($serapPct, 2),
                'target_pct'    => round($targetPctNow, 2),
                'target_rp'     => $targetRpNow,
                'deviasi'       => $deviasi,
                'status'        => $status,
                'status_label'  => $statusLabel,
                'monthly'       => $realMatrix[$btId],
                'targets_q'     => $targets,
            ];
        }

        $totalSerapPct = $totalPagu > 0 ? ($totalRealToDate / $totalPagu * 100) : 0.0;
        $totalTargetPct = $totalPagu > 0 ? ($totalTargetRpToDate / $totalPagu * 100) : 0.0;
        $totalSisa = max(0, $totalPagu - $totalRealToDate);
        $totalDeviasi = $totalRealToDate - $totalTargetRpToDate;

        // 5. Kumulatif Bulanan untuk Grafik Chart.js
        $chartMonths = [];
        $chartRealCumulative = [];
        $chartTargetCumulative = [];
        $chartMonthlyBreakdown = [];

        $cumRealAcc = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $monthRealTotal = 0.0;
            $monthTargetTotal = 0.0;

            foreach ($budgetTypes as $bt) {
                $btId = $bt['id'];
                $monthRealTotal += $realMatrix[$btId][$m];
                $pagu = $paguPerType[$btId] ?? 0.0;
                $targets = $targetMap[$btId] ?? [0, 0, 0, 0];
                $targetPctM = $this->calcTargetPctAtMonth($targets, $m);
                $monthTargetTotal += ($pagu * ($targetPctM / 100));
            }

            $cumRealAcc += $monthRealTotal;

            $chartMonths[] = $months[$m - 1];
            $chartTargetCumulative[] = round($monthTargetTotal, 2);

            if ($m <= $lastFilledMonth) {
                $chartRealCumulative[] = round($cumRealAcc, 2);
            } else {
                $chartRealCumulative[] = null; // belum berjalan
            }

            $chartMonthlyBreakdown[] = round($monthRealTotal, 2);
        }

        // 6. Rekap per Triwulan
        $quarterRecap = [];
        foreach ($budgetTypes as $bt) {
            $btId = $bt['id'];
            $pagu = $paguPerType[$btId] ?? 0.0;
            $targets = $targetMap[$btId] ?? [0, 0, 0, 0];

            $twTargets = [];
            for ($q = 0; $q < 4; $q++) {
                $twTargets[] = [
                    'pct' => $targets[$q],
                    'rp'  => $pagu * ($targets[$q] / 100),
                ];
            }

            // Realisasi kumulatif
            $realSum = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $realSum += $realMatrix[$btId][$m];
            }

            $quarterRecap[] = [
                'type'       => $bt,
                'pagu'       => $pagu,
                'q_targets'  => $twTargets,
                'realisasi'  => $realSum,
                'serap_pct'  => $pagu > 0 ? round(($realSum / $pagu * 100), 2) : 0,
            ];
        }

        // 7. IKPA Data
        // IKPA resmi terbit setelah bulan berakhir. Untuk tahun berjalan, tampilkan
        // periode maksimal bulan sebelumnya dan abaikan rekaman periode yang kosong.
        $selectedYearNumber = (int)($selectedYear['year'] ?? date('Y'));
        $ikpaCutoffMonth = $selectedYearNumber === (int)date('Y')
            ? max(1, (int)date('n') - 1)
            : 12;
        $ikpaRaw = $this->ikpaModel
            ->where('fiscal_year_id', $fyId)
            ->where('period_month <=', $ikpaCutoffMonth)
            ->groupStart()
                ->where('revisi IS NOT NULL', null, false)
                ->orWhere('deviasi IS NOT NULL', null, false)
                ->orWhere('penyerapan IS NOT NULL', null, false)
                ->orWhere('kontraktual IS NOT NULL', null, false)
                ->orWhere('tagihan IS NOT NULL', null, false)
                ->orWhere('uptup IS NOT NULL', null, false)
                ->orWhere('output IS NOT NULL', null, false)
            ->groupEnd()
            ->orderBy('period_month', 'DESC')
            ->first();
        $ikpaScore = IkpaModel::calculateScore($ikpaRaw);

        // 8. Data Rencana Penarikan Anggaran (RPA)
        $rpaData = null;
        try {
            $rpaRevModel = new \App\Models\RpaRevisionModel();
            $rpaItemModel = new \App\Models\RpaItemModel();

            $rpaRevision = $rpaRevModel
                ->where('fiscal_year_id', $fyId)
                ->where('status', 'active')
                ->orderBy('revision_no', 'DESC')
                ->first();

            if ($rpaRevision) {
                $programItems = $rpaItemModel
                    ->where('revision_id', $rpaRevision['id'])
                    ->like('activity_code', '060.01.', 'after')
                    ->findAll();

                $rpaMonthly = array_fill(1, 12, 0.0);
                $rpaCeiling = (float)($rpaRevision['source_total'] ?? 0);

                if (!empty($programItems)) {
                    $calcCeil = 0.0;
                    foreach ($programItems as $prog) {
                        $calcCeil += (float)$prog['ceiling'];
                        for ($m = 1; $m <= 12; $m++) {
                            $rpaMonthly[$m] += (float)$prog['month_' . $m];
                        }
                    }
                    if ($rpaCeiling <= 0) $rpaCeiling = $calcCeil;
                }

                // Kumulatif RPA bulanan
                $rpaCumulative = [];
                $rpaCumAcc = 0.0;
                for ($m = 1; $m <= 12; $m++) {
                    $rpaCumAcc += $rpaMonthly[$m];
                    $rpaCumulative[] = round($rpaCumAcc, 2);
                }

                // Target 4 Bulan Akhir (Sep - Des)
                $q4Total = $rpaMonthly[9] + $rpaMonthly[10] + $rpaMonthly[11] + $rpaMonthly[12];
                $q4Pct = $rpaCeiling > 0 ? round(($q4Total / $rpaCeiling) * 100, 1) : 0;

                // Top 5 Kegiatan Strategis dengan Target Terbesar di Q4
                $db = db_connect();
                $topQ4Items = $db->query("
                    SELECT activity_code, activity_name, ceiling,
                           (month_9 + month_10 + month_11 + month_12) as q4_total,
                           month_9, month_10, month_11, month_12
                    FROM rpa_items
                    WHERE revision_id = ?
                      AND activity_code NOT LIKE '060.01.%'
                      AND activity_code != ''
                      AND (month_9 + month_10 + month_11 + month_12) > 0
                    ORDER BY q4_total DESC
                    LIMIT 5
                ", [$rpaRevision['id']])->getResultArray();

                $rpaData = [
                    'revision'       => $rpaRevision,
                    'ceiling'        => $rpaCeiling,
                    'monthly'        => $rpaMonthly,
                    'cumulative'     => $rpaCumulative,
                    'q4_total'       => $q4Total,
                    'q4_pct'         => $q4Pct,
                    'sep'            => $rpaMonthly[9],
                    'okt'            => $rpaMonthly[10],
                    'nov'            => $rpaMonthly[11],
                    'des'            => $rpaMonthly[12],
                    'top_q4_items'   => $topQ4Items,
                ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard RPA loading failed gracefully: ' . $e->getMessage());
            $rpaData = null;
        }

        return [
            'all_years'          => $allYears,
            'selected_year'      => $selectedYear,
            'budget_types'       => $budgetTypes,
            'last_filled_month'  => $lastFilledMonth,
            'posisi_text'        => $posisiText,
            'kpi'                => [
                'pagu'             => $totalPagu,
                'realisasi'        => $totalRealToDate,
                'serap_pct'        => round($totalSerapPct, 2),
                'sisa'             => $totalSisa,
                'target_pct'       => round($totalTargetPct, 2),
                'target_rp'        => $totalTargetRpToDate,
                'deviasi'          => $totalDeviasi,
                'deviasi_status'   => $totalDeviasi >= 0 ? 'good' : 'alert',
                'deviasi_label'    => $totalDeviasi >= 0 ? 'di atas target' : 'di bawah target',
            ],
            'belanja_summary'    => $belanjaSummary,
            'quarter_recap'      => $quarterRecap,
            'ikpa_raw'           => $ikpaRaw,
            'ikpa_score'         => $ikpaScore,
            'rpa_data'           => $rpaData,
            'chart_data'         => [
                'labels'           => $chartMonths,
                'real_cumulative'  => $chartRealCumulative,
                'target_cumulative'=> $chartTargetCumulative,
                'rpa_cumulative'   => $rpaData ? $rpaData['cumulative'] : null,
                'monthly_total'    => $chartMonthlyBreakdown,
            ],
        ];
    }

    /**
     * Menghitung target % kumulatif pada akhir bulan m (1..12),
     * interpolasi linier antar pos triwulan (Q1: bln 3, Q2: bln 6, Q3: bln 9, Q4: bln 12).
     */
    public function calcTargetPctAtMonth(array $q, int $m): float
    {
        $cp = [0.0, (float)$q[0], (float)$q[1], (float)$q[2], (float)$q[3]];
        $mm = [0, 3, 6, 9, 12];

        if ($m <= 0) return 0.0;
        if ($m >= 12) return (float)$q[3];

        for ($i = 1; $i <= 4; $i++) {
            if ($m <= $mm[$i]) {
                $t = ($m - $mm[$i - 1]) / ($mm[$i] - $mm[$i - 1]);
                return $cp[$i - 1] + $t * ($cp[$i] - $cp[$i - 1]);
            }
        }

        return (float)$q[3];
    }
}
